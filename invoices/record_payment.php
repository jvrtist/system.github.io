<?php
/**
 * ISS Investigations - Payment Recording Interface
 * Record payments against invoices with automatic status updates, audit logging, and retainer management.
 */
require_once '../config.php';
require_login();

$page_title = "Record Payment";
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

if ($invoice_id <= 0) {
    $_SESSION['error_message'] = "Invalid invoice ID specified.";
    redirect('invoices/');
}

$conn = get_db_connection();
$invoice_data = null;
$existing_payments = [];
$case_data = null;
$retainer_info = null;

try {
    // Fetch invoice data with case information
    $stmt_invoice = $conn->prepare("
        SELECT i.*, CONCAT(cl.first_name, ' ', cl.last_name) as client_name, cl.client_id,
               c.case_id, c.case_number, c.title as case_title, c.retainer_amount
        FROM invoices i
        JOIN clients cl ON i.client_id = cl.client_id
        LEFT JOIN cases c ON i.case_id = c.case_id
        WHERE i.invoice_id = ?
    ");
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $result = $stmt_invoice->get_result();

    if ($result->num_rows === 1) {
        $invoice_data = $result->fetch_assoc();

        // Fetch case data and retainer information if case exists
        if ($invoice_data['case_id']) {
            $case_data = [
                'case_id' => $invoice_data['case_id'],
                'case_number' => $invoice_data['case_number'],
                'case_title' => $invoice_data['case_title'],
                'retainer_amount' => $invoice_data['retainer_amount'] ?? 0.00
            ];

            // Calculate used retainer amount across all invoices for this case
            $stmt_retainer_used = $conn->prepare("
                SELECT SUM(ip.amount_paid) as used_amount
                FROM invoice_payments ip
                JOIN invoices inv ON ip.invoice_id = inv.invoice_id
                WHERE inv.case_id = ? AND ip.payment_method = 'Retainer'
            ");
            $stmt_retainer_used->bind_param("i", $invoice_data['case_id']);
            $stmt_retainer_used->execute();
            $retainer_result = $stmt_retainer_used->get_result()->fetch_assoc();
            $retainer_used = $retainer_result['used_amount'] ?? 0.00;
            $stmt_retainer_used->close();

            $retainer_info = [
                'total_retainer' => $case_data['retainer_amount'],
                'used_retainer' => $retainer_used,
                'available_retainer' => max(0, $case_data['retainer_amount'] - $retainer_used)
            ];
        }
    } else {
        $_SESSION['error_message'] = "Invoice not found.";
        redirect('invoices/');
    }
    $stmt_invoice->close();

    // Fetch existing payments
    $stmt_payments = $conn->prepare("SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC");
    $stmt_payments->bind_param("i", $invoice_id);
    $stmt_payments->execute();
    $existing_payments = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_payments->close();

} catch (Exception $e) {
    error_log("Error fetching invoice data: " . $e->getMessage());
    $_SESSION['error_message'] = "Error loading invoice data.";
    redirect('invoices/');
}

$balance_due = $invoice_data['total_amount'] - $invoice_data['amount_paid'];
$errors = [];
$form_payment_amount = number_format($balance_due, 2, '.', '');
$form_payment_date = date('Y-m-d');
$form_payment_method = '';
$form_notes = '';
$form_use_retainer = false;
$form_retainer_amount = '0.00';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    // Use hidden field for invoice_id if available, otherwise fallback to GET
    $posted_invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : $invoice_id;
    if ($posted_invoice_id !== $invoice_id) {
        $errors['general'] = "Invalid transaction context.";
    }

    $payment_amount = filter_var(trim($_POST['payment_amount'] ?? ''), FILTER_VALIDATE_FLOAT);
    $payment_date = sanitize_input($_POST['payment_date'] ?? '');
    $payment_method = sanitize_input($_POST['payment_method'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    $use_retainer = isset($_POST['use_retainer']) && $_POST['use_retainer'] === 'on';
    $retainer_amount = filter_var(trim($_POST['retainer_amount'] ?? '0'), FILTER_VALIDATE_FLOAT);

    $form_payment_amount = $_POST['payment_amount'] ?? $form_payment_amount;
    $form_payment_date = $payment_date;
    $form_payment_method = $payment_method;
    $form_notes = $notes;
    $form_use_retainer = $use_retainer;
    $form_retainer_amount = number_format($retainer_amount ?? 0, 2, '.', '');

    // Validation
    if ($payment_amount === false || $payment_amount <= 0) {
        $errors['payment_amount'] = "Payment amount must be a positive number.";
    }

    if (empty($payment_date)) {
        $errors['payment_date'] = "Payment date is required.";
    }

    if (empty($payment_method)) {
        $errors['payment_method'] = "Payment method is required.";
    }

    if ($use_retainer && $retainer_info) {
        if ($retainer_amount === false || $retainer_amount < 0) {
            $errors['retainer_amount'] = "Retainer amount must be a valid number.";
        }

        if ($retainer_amount > $retainer_info['available_retainer']) {
            $errors['retainer_amount'] = "Retainer amount cannot exceed available balance of R" . number_format($retainer_info['available_retainer'], 2) . ".";
        }

        if ($retainer_amount > $balance_due) {
            $errors['retainer_amount'] = "Retainer amount cannot exceed the balance due of R" . number_format($balance_due, 2) . ".";
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $total_payment_amount = $payment_amount;

            // Handle retainer portion if enabled
            if ($use_retainer && $retainer_info && $retainer_amount > 0) {
                // Record retainer payment
                $stmt_retainer = $conn->prepare("INSERT INTO invoice_payments (invoice_id, payment_date, amount_paid, payment_method, notes) VALUES (?, ?, ?, 'Retainer', ?)");
                $retainer_notes = "Retainer payment applied from case #" . $case_data['case_number'] . ". " . ($notes ? $notes . " " : "") . "(Retainer portion)";
                $stmt_retainer->bind_param("sdss", $invoice_id, $payment_date, $retainer_amount, $retainer_notes);
                $stmt_retainer->execute();
                $stmt_retainer->close();

                $total_payment_amount = $payment_amount - $retainer_amount;
            }

            // Record the regular payment if there's still an amount to record
            if ($total_payment_amount > 0) {
                $payment_notes = $notes;
                if ($use_retainer && $retainer_amount > 0) {
                    $payment_notes .= " (Regular payment portion - Retainer: R" . number_format($retainer_amount, 2) . ")";
                }

                $stmt_payment = $conn->prepare("INSERT INTO invoice_payments (invoice_id, payment_date, amount_paid, payment_method, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt_payment->bind_param("sdsss", $invoice_id, $payment_date, $total_payment_amount, $payment_method, $payment_notes);
                $stmt_payment->execute();
                $stmt_payment->close();
            }

            // Update invoice status
            $new_total_paid = $invoice_data['amount_paid'] + $payment_amount;
            $new_status = ($new_total_paid >= $invoice_data['total_amount'] - 0.01) ? 'Paid' : 'Partially Paid';

            $sql_update = "UPDATE invoices SET amount_paid = ?, status = ?, payment_date = ?, updated_at = NOW() WHERE invoice_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("dssi", $new_total_paid, $new_status, $payment_date, $invoice_id);
            $stmt_update->execute();
            $stmt_update->close();

            // Log the payment action
            log_audit_action($_SESSION['user_id'], $invoice_data['client_id'], 'record_payment', 'invoice', $invoice_id,
                "Payment of R" . number_format($payment_amount, 2) . " recorded. " .
                ($use_retainer && $retainer_amount > 0 ? "Retainer portion: R" . number_format($retainer_amount, 2) : "No retainer used"));

            $conn->commit();
            $_SESSION['success_message'] = "Payment of R" . number_format($payment_amount, 2) . " recorded successfully!" .
                ($use_retainer && $retainer_amount > 0 ? " (R" . number_format($retainer_amount, 2) . " from retainer)" : "");
            redirect('invoices/view_invoice.php?id=' . $invoice_id);

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error recording payment: " . $e->getMessage();
            error_log("Payment Error: " . $e->getMessage());
        }
    }
}
$conn->close();

include_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Record <span class="text-primary">Payment</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Invoice #<?php echo htmlspecialchars($invoice_data['invoice_number']); ?> - <?php echo htmlspecialchars($invoice_data['client_name']); ?></p>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Invoice Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Balance Due Card -->
        <div class="bg-primary/10 border border-primary/50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Outstanding Balance</p>
                    <p class="text-3xl font-black text-primary">R<?php echo number_format($balance_due, 2); ?></p>
                </div>
                <i class="fas fa-coins text-primary/60 text-2xl"></i>
            </div>
        </div>

        <!-- Total Amount Card -->
        <div class="bg-slate-900 border border-white/5 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Total Amount</p>
                    <p class="text-3xl font-black text-white">R<?php echo number_format($invoice_data['total_amount'], 2); ?></p>
                </div>
                <i class="fas fa-file-invoice-dollar text-slate-500 text-2xl"></i>
            </div>
        </div>

        <!-- Amount Paid Card -->
        <div class="bg-slate-900 border border-white/5 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm mb-1">Amount Paid</p>
                    <p class="text-3xl font-black text-white">R<?php echo number_format($invoice_data['amount_paid'], 2); ?></p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Retainer Information (if case exists) -->
    <?php if ($retainer_info): ?>
    <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fas fa-wallet text-primary"></i>
                Case Retainer Status
            </h3>
            <span class="text-xs tech-mono text-slate-500 uppercase tracking-widest bg-slate-800/50 px-3 py-1 rounded">
                Case #<?php echo htmlspecialchars($case_data['case_number']); ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <p class="text-slate-400 text-sm mb-1">Total Retainer</p>
                <p class="text-2xl font-bold text-white">R<?php echo number_format($retainer_info['total_retainer'], 2); ?></p>
            </div>
            <div class="text-center">
                <p class="text-slate-400 text-sm mb-1">Used Amount</p>
                <p class="text-2xl font-bold text-orange-400">R<?php echo number_format($retainer_info['used_retainer'], 2); ?></p>
            </div>
            <div class="text-center">
                <p class="text-slate-400 text-sm mb-1">Available Balance</p>
                <p class="text-2xl font-bold text-green-400">R<?php echo number_format($retainer_info['available_retainer'], 2); ?></p>
            </div>
        </div>

        <?php if ($retainer_info['available_retainer'] > 0): ?>
        <div class="mt-4 p-3 bg-green-500/10 border border-green-500/30 rounded-lg">
            <p class="text-sm text-green-300 flex items-center gap-2">
                <i class="fas fa-info-circle"></i>
                <span>R<?php echo number_format($retainer_info['available_retainer'], 2); ?> available from retainer for this payment</span>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Recent Payments History -->
    <?php if (!empty($existing_payments)): ?>
    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
            <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Recent Payment History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-slate-900 divide-y divide-slate-800">
                    <?php foreach (array_slice($existing_payments, 0, 5) as $payment): ?>
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">
                                <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-bold rounded-full <?php
                                    switch (strtolower($payment['payment_method'])) {
                                        case 'bank transfer': echo 'bg-blue-500/20 text-blue-300 border border-blue-500/30'; break;
                                        case 'credit card': echo 'bg-purple-500/20 text-purple-300 border border-purple-500/30'; break;
                                        case 'cash': echo 'bg-green-500/20 text-green-300 border border-green-500/30'; break;
                                        case 'retainer': echo 'bg-orange-500/20 text-orange-300 border border-orange-500/30'; break;
                                        default: echo 'bg-slate-500/20 text-slate-300 border border-slate-500/30';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-white">
                                R<?php echo number_format($payment['amount_paid'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400 truncate max-w-xs">
                                <?php echo htmlspecialchars($payment['notes'] ?: 'No notes'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if ($errors): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200">Please correct the errors below:</p>
                <ul class="text-xs text-red-300 mt-2 list-disc list-inside">
                    <?php foreach ($errors as $field => $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form action="record_payment.php?invoice_id=<?php echo $invoice_id; ?>" method="POST" class="space-y-6" id="paymentForm">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">

        <!-- Payment Details Section -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Payment Information</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="payment_amount" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Payment Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500 font-bold">R</span>
                            <input type="number" name="payment_amount" id="payment_amount" value="<?php echo htmlspecialchars($form_payment_amount); ?>" required step="0.01" min="0.01" max="<?php echo $balance_due; ?>" class="w-full pl-8 pr-4 py-2.5 bg-slate-800 border <?php echo isset($errors['payment_amount']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        </div>
                        <?php if (isset($errors['payment_amount'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['payment_amount']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="payment_date" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Payment Date <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($form_payment_date); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['payment_date']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['payment_date'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['payment_date']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="payment_method" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_method" id="payment_method" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['payment_method']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all appearance-none">
                        <option value="" disabled <?php echo empty($form_payment_method) ? 'selected' : ''; ?>>Select a method</option>
                        <option value="Bank Transfer" <?php echo ($form_payment_method == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="Credit Card" <?php echo ($form_payment_method == 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                        <option value="Cash" <?php echo ($form_payment_method == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                        <option value="Check" <?php echo ($form_payment_method == 'Check') ? 'selected' : ''; ?>>Check</option>
                        <option value="EFT" <?php echo ($form_payment_method == 'EFT') ? 'selected' : ''; ?>>EFT</option>
                        <option value="Other" <?php echo ($form_payment_method == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <?php if (isset($errors['payment_method'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['payment_method']); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($form_notes); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Retainer Management Section -->
        <?php if ($retainer_info && $retainer_info['available_retainer'] > 0): ?>
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5 flex items-center justify-between">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Retainer Management</h2>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="use_retainer" id="use_retainer" <?php echo $form_use_retainer ? 'checked' : ''; ?> class="w-4 h-4 text-primary bg-slate-800 border-slate-700 rounded focus:ring-primary focus:ring-2">
                    <label for="use_retainer" class="text-xs font-bold text-slate-400 uppercase tracking-widest cursor-pointer">Apply Retainer Funds</label>
                </div>
            </div>
            <div class="p-6">
                <div id="retainerSection" class="<?php echo $form_use_retainer ? '' : 'hidden'; ?> space-y-4">
                    <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-400 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <p class="text-sm font-bold text-blue-200 mb-2">Retainer Payment Options</p>
                                <ul class="text-xs text-blue-300 space-y-1">
                                    <li>• Available retainer balance: <strong>R<?php echo number_format($retainer_info['available_retainer'], 2); ?></strong></li>
                                    <li>• Maximum retainer portion: <strong>R<?php echo number_format(min($retainer_info['available_retainer'], $balance_due), 2); ?></strong></li>
                                    <li>• Remaining balance will be charged via selected payment method</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="retainer_amount" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Retainer Amount to Apply <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500 font-bold">R</span>
                            <input type="number" name="retainer_amount" id="retainer_amount" value="<?php echo htmlspecialchars($form_retainer_amount); ?>" step="0.01" min="0" max="<?php echo min($retainer_info['available_retainer'], $balance_due); ?>" class="w-full pl-8 pr-4 py-2.5 bg-slate-800 border <?php echo isset($errors['retainer_amount']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        </div>
                        <?php if (isset($errors['retainer_amount'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['retainer_amount']); ?></p>
                        <?php else: ?>
                            <p class="text-slate-500 text-xs mt-1.5">Leave at 0.00 to not apply retainer funds</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-slate-800/50 rounded-lg p-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-400">Total Payment Amount:</span>
                            <span class="font-bold text-white" id="totalPaymentDisplay">R<?php echo number_format($form_payment_amount, 2); ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-1">
                            <span class="text-slate-400">Retainer Portion:</span>
                            <span class="font-bold text-orange-400" id="retainerPortionDisplay">R<?php echo number_format($form_retainer_amount, 2); ?></span>
                        </div>
                        <div class="border-t border-slate-700 mt-2 pt-2 flex items-center justify-between text-sm">
                            <span class="text-slate-400">Amount to Charge:</span>
                            <span class="font-bold text-green-400" id="chargeAmountDisplay">R<?php echo number_format($form_payment_amount - $form_retainer_amount, 2); ?></span>
                        </div>
                    </div>
                </div>

                <div id="noRetainerSection" class="<?php echo $form_use_retainer ? 'hidden' : ''; ?>">
                    <div class="text-center py-8 text-slate-500">
                        <i class="fas fa-wallet text-3xl mb-3 text-slate-600"></i>
                        <p class="text-sm">Retainer option not selected</p>
                        <p class="text-xs">Check "Apply Retainer Funds" to use available retainer balance</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-check-circle mr-2"></i>Record Payment
            </button>
        </div>
    </form>
</div>

<script>
// Retainer management functionality
document.getElementById('use_retainer').addEventListener('change', function() {
    const retainerSection = document.getElementById('retainerSection');
    const noRetainerSection = document.getElementById('noRetainerSection');

    if (this.checked) {
        retainerSection.classList.remove('hidden');
        noRetainerSection.classList.add('hidden');
        updateCalculations();
    } else {
        retainerSection.classList.add('hidden');
        noRetainerSection.classList.remove('hidden');
        document.getElementById('retainer_amount').value = '0.00';
        updateCalculations();
    }
});

// Update calculations when amounts change
document.getElementById('payment_amount').addEventListener('input', updateCalculations);
document.getElementById('retainer_amount').addEventListener('input', updateCalculations);

function updateCalculations() {
    const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
    const retainerAmount = parseFloat(document.getElementById('retainer_amount').value) || 0;

    // Update displays
    document.getElementById('totalPaymentDisplay').textContent = 'R' + paymentAmount.toFixed(2);
    document.getElementById('retainerPortionDisplay').textContent = 'R' + retainerAmount.toFixed(2);
    document.getElementById('chargeAmountDisplay').textContent = 'R' + (paymentAmount - retainerAmount).toFixed(2);
}

// Initialize calculations on page load
updateCalculations();
</script>

<?php include_once '../includes/footer.php'; ?>
