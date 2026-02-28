<?php
/**
 * ISS Investigations - Invoice Display Interface
 * Read-only view of invoices with payment history and professional print layout.
 */
require_once '../config.php';
require_login();

$page_title = "Invoice Details";
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoice_id <= 0) {
    $_SESSION['error_message'] = "Invalid invoice ID specified.";
    redirect('invoices/');
}

$conn = get_db_connection();
$invoice = null;
$invoice_items = [];
$payments = [];

if ($conn) {
    // Fetch main invoice data
    $stmt_invoice = $conn->prepare("
        SELECT i.*, 
               cl.first_name, cl.last_name, cl.company_name, cl.email, cl.phone, cl.address,
               cs.case_number as related_case_number
        FROM invoices i
        JOIN clients cl ON i.client_id = cl.client_id
        LEFT JOIN cases cs ON i.case_id = cs.case_id
        WHERE i.invoice_id = ?
    ");
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $invoice = $stmt_invoice->get_result()->fetch_assoc();
    $stmt_invoice->close();

    if (!$invoice) {
        $_SESSION['error_message'] = "Invoice not found (ID: $invoice_id).";
        redirect('invoices/');
    }

    // Fetch invoice items
    $stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id ASC");
    $stmt_items->bind_param("i", $invoice_id);
    $stmt_items->execute();
    $invoice_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    // Fetch payment history
    $stmt_payments = $conn->prepare("SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC");
    $stmt_payments->bind_param("i", $invoice_id);
    $stmt_payments->execute();
    $payments = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_payments->close();
    
    $conn->close();
}

$balance_due = $invoice['total_amount'] - $invoice['amount_paid'];

function get_invoice_status_badge_class($status) {
    $base = "px-2.5 py-1 inline-flex text-xs leading-4 font-bold uppercase tracking-wider rounded-full border";
    $colors = [
        'draft' => 'bg-slate-700 text-slate-300 border-slate-500',
        'sent' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
        'paid' => 'bg-green-500/20 text-green-300 border-green-500/30',
        'partially paid' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
        'overdue' => 'bg-red-500/20 text-red-300 border-red-500/30',
        'void' => 'bg-gray-700 text-gray-400 border-gray-600 opacity-70',
        'cancelled' => 'bg-gray-700 text-gray-400 border-gray-600 opacity-70',
    ];
    return $base . ' ' . ($colors[strtolower($status)] ?? 'bg-slate-700 text-slate-300 border-slate-500');
}

include_once '../includes/header.php';
?>
<style>
    /* Standardized Print Layout */
    @media print {
        @page { size: A4; margin: 5mm; }
        body { margin: 0; background: #fff; }
        .no-print { display: none !important; }
        .print-area { visibility: visible; position: absolute; left: 0; top: 0; width: 100%; height: 100%; }
        body * { visibility: hidden; }
    }
    /* Reset and Base Styles */
    .print-area * { margin: 0; padding: 0; box-sizing: border-box; }
    .print-area { font-family: Arial, sans-serif; color: #000; background: #fff; font-size: 10pt; line-height: 1.4; }
    .print-container { width: 100%; max-width: 210mm; margin: 0 auto; padding: 10mm; }
    .print-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8mm; padding-bottom: 5mm; border-bottom: 2px solid #000; }
    .print-header-left img { max-width: 25mm; height: auto; margin-bottom: 2mm; }
    .print-header-left h2 { font-size: 14pt; font-weight: bold; margin: 0; }
    .print-header-left p { font-size: 8pt; margin: 1mm 0; line-height: 1.2; }
    .print-header-right { text-align: right; }
    .print-header-right h1 { font-size: 18pt; font-weight: bold; text-transform: uppercase; margin: 0; }
    .print-header-right p { font-size: 11pt; font-weight: bold; margin-top: 1mm; }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
    .info-table td { vertical-align: top; padding: 0; }
    .info-col-left { width: 50%; padding-right: 10mm; }
    .info-col-right { width: 50%; padding-left: 10mm; }
    .section-title { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3mm; }
    .info-text { font-size: 10pt; margin-bottom: 1mm; }
    .info-text.bold { font-weight: bold; }
    .details-table { width: 100%; border-collapse: collapse; }
    .details-table td { padding: 2mm 0; }
    .details-label { font-weight: bold; width: 40%; }
    .details-value { text-align: right; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
    .items-table th, .items-table td { border: 1px solid #ddd; padding: 3mm 4mm; text-align: left; font-size: 9pt; }
    .items-table th { background-color: #f0f0f0 !important; font-weight: bold; -webkit-print-color-adjust: exact; }
    .items-table .text-right { text-align: right; }
    .items-table .bold { font-weight: bold; }
    .totals-container { float: right; width: 45%; margin-left: 10mm; }
    .totals-row { display: flex; justify-content: space-between; padding: 2mm 4mm; border-bottom: 1px solid #ddd; font-size: 9pt; }
    .totals-row.total { border-bottom: 2px solid #000; font-weight: bold; font-size: 11pt; margin-top: 1mm; }
    .totals-row.balance { background-color: #f0f0f0 !important; font-weight: bold; -webkit-print-color-adjust: exact; }
    .payment-history { clear: both; margin-top: 12mm; padding-top: 5mm; }
    .payment-history h3 { font-size: 9pt; font-weight: bold; text-transform: uppercase; margin-bottom: 3mm; border-bottom: 1px solid #000; padding-bottom: 1mm; }
    .footer { clear: both; margin-top: 15mm; padding-top: 5mm; border-top: 1px solid #ddd; font-size: 8pt; }
    .footer p { margin-bottom: 2mm; }
    .clearfix::after { content: ""; display: table; clear: both; }
</style>

<div class="max-w-6xl mx-auto space-y-8">
    <!-- Header -->
    <header class="border-l-4 border-primary pl-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Invoice <span class="text-primary">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></span></h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="<?php echo get_invoice_status_badge_class($invoice['status']); ?>"><?php echo htmlspecialchars($invoice['status']); ?></span>
                    <span class="text-slate-500 text-xs">To: <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="index.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-colors text-sm font-semibold">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
                <a href="edit_invoice.php?id=<?php echo $invoice_id; ?>" class="px-4 py-2 bg-blue-600/20 hover:bg-blue-600/40 text-blue-300 rounded-lg transition-colors text-sm font-semibold">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="print_invoice.php?id=<?php echo $invoice_id; ?>" class="px-4 py-2 bg-primary hover:bg-orange-600 text-white rounded-lg transition-colors text-sm font-bold shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="fas fa-print mr-1"></i> Print
                </a>
                <?php if ($balance_due > 0): ?>
                <a href="record_payment.php?invoice_id=<?php echo $invoice_id; ?>" class="px-4 py-2 bg-primary hover:bg-orange-600 text-white rounded-lg transition-colors text-sm font-bold shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> Record Payment
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Invoice Preview -->
    <div class="bg-slate-800 border border-white/5 rounded-2xl shadow-2xl p-4">
        <div class="bg-white text-black rounded-lg shadow-inner p-8">
            <div class="print-container">
                <div class="print-header">
                    <div class="header-left">
                        <img src="../images/logo.png" alt="Logo">
                        <h2><?php echo SITE_NAME; ?></h2>
                        <p>95 Houtkop Road<br>Duncanville, Vereeniging, 1900<br>billing@iss-investigations.co.za</p>
                    </div>
                    <div class="header-right">
                        <h1>INVOICE</h1>
                        <p>#<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    </div>
                </div>
                <table class="info-table">
                    <tr>
                        <td class="info-col-left">
                            <div class="section-title">BILL TO</div>
                            <div class="info-text bold"><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></div>
                            <?php if ($invoice['company_name']): ?><div class="info-text"><?php echo htmlspecialchars($invoice['company_name']); ?></div><?php endif; ?>
                            <?php if ($invoice['address']): ?><div class="info-text"><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></div><?php endif; ?>
                            <div class="info-text"><?php echo htmlspecialchars($invoice['email']); ?></div>
                            <div class="info-text"><?php echo htmlspecialchars($invoice['phone']); ?></div>
                        </td>
                        <td class="info-col-right">
                            <table class="details-table">
                                <tr><td class="details-label">Invoice Date:</td><td class="details-value"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td></tr>
                                <tr><td class="details-label">Due Date:</td><td class="details-value"><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td></tr>
                                <?php if ($invoice['related_case_number']): ?><tr><td class="details-label">Case Ref:</td><td class="details-value"><?php echo htmlspecialchars($invoice['related_case_number']); ?></td></tr><?php endif; ?>
                            </table>
                        </td>
                    </tr>
                </table>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right" style="width: 12%">Qty</th>
                            <th class="text-right" style="width: 15%">Unit Price</th>
                            <th class="text-right" style="width: 15%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice_items as $item): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                                <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                                <td class="text-right">R<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right bold">R<?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="clearfix">
                    <div class="totals-container">
                        <div class="totals-row"><span>Subtotal:</span><span>R<?php echo number_format($invoice['subtotal_amount'], 2); ?></span></div>
                        <div class="totals-row"><span>Tax (<?php echo number_format($invoice['tax_rate_percentage'], 2); ?>%):</span><span>R<?php echo number_format($invoice['tax_amount'], 2); ?></span></div>
                        <div class="totals-row total"><span>Total:</span><span>R<?php echo number_format($invoice['total_amount'], 2); ?></span></div>
                        <div class="totals-row"><span>Amount Paid:</span><span>R<?php echo number_format($invoice['amount_paid'], 2); ?></span></div>
                        <div class="totals-row balance"><span>Balance Due:</span><span>R<?php echo number_format($balance_due, 2); ?></span></div>
                    </div>
                </div>
                <?php if (!empty($payments)): ?>
                <div class="payment-history">
                    <h3>Payment History</h3>
                    <table class="items-table">
                        <thead><tr><th style="width: 25%">Date</th><th style="width: 30%">Method</th><th class="text-right" style="width: 25%">Amount</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method'] ?: 'N/A'); ?></td>
                                    <td class="text-right bold">R<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <div class="footer">
                    <p><strong>Terms:</strong> Payment due by <?php echo date('d M Y', strtotime($invoice['due_date'])); ?></p>
                    <p>Thank you for your business!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
