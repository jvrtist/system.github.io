<?php
/**
 * ISS Investigations - Admin View Client
 * Administrative interface for viewing detailed client information and account status.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = 'ACCESS DENIED: Administrative clearance required.';
    redirect('dashboard.php');
}

$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($client_id <= 0) {
    $_SESSION['error_message'] = "Invalid client ID specified.";
    redirect('admin/clients/');
}

$conn = get_db_connection();

// Fetch client details
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as added_by_name
    FROM clients c
    LEFT JOIN users u ON c.added_by_user_id = u.user_id
    WHERE c.client_id = ?
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$client) {
    $_SESSION['error_message'] = "Client not found.";
    redirect('admin/clients/');
}

// Fetch associated cases
$stmt_cases = $conn->prepare("
    SELECT case_id, case_number, title, status, priority, created_at, updated_at
    FROM cases
    WHERE client_id = ?
    ORDER BY created_at DESC
");
$stmt_cases->bind_param("i", $client_id);
$stmt_cases->execute();
$cases = $stmt_cases->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cases->close();

// Fetch associated invoices
$stmt_invoices = $conn->prepare("
    SELECT invoice_id, invoice_number, total_amount, amount_paid, status, invoice_date, due_date
    FROM invoices
    WHERE client_id = ?
    ORDER BY invoice_date DESC
    LIMIT 5
");
$stmt_invoices->bind_param("i", $client_id);
$stmt_invoices->execute();
$invoices = $stmt_invoices->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_invoices->close();

$conn->close();

$page_title = "Client: " . $client['first_name'] . " " . $client['last_name'];
include_once '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
            Client <span class="text-primary">Profile</span>
        </h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">ID: <?php echo $client['client_id']; ?> • Account oversight</p>
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

    <!-- Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <a href="index.php" class="text-sm text-slate-400 hover:text-primary transition-colors font-semibold flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Back to Client List
        </a>
        <div class="flex gap-3">
            <a href="edit_client.php?id=<?php echo $client_id; ?>" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold px-4 py-2 rounded-lg transition-colors text-sm">
                <i class="fas fa-edit mr-2"></i>Edit Client
            </a>
            <a href="../cases/add_case.php?client_id=<?php echo $client_id; ?>" class="bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider px-4 py-2 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-plus mr-2"></i>New Case
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Client Information Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Profile Card -->
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 shadow-2xl">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 rounded-full bg-slate-800 border-4 border-primary/20 flex items-center justify-center text-3xl font-black text-primary mx-auto mb-4">
                        <?php echo htmlspecialchars(strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1))); ?>
                    </div>
                    <h2 class="text-xl font-bold text-white"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h2>
                    <p class="text-slate-400 text-sm"><?php echo htmlspecialchars($client['email']); ?></p>
                </div>

                <!-- Status Badge -->
                <div class="text-center mb-6">
                    <span class="px-3 py-1 text-sm font-bold rounded-full <?php
                        switch ($client['client_account_status']) {
                            case 'Active': echo 'bg-green-500/20 text-green-300 border border-green-500/30'; break;
                            case 'Pending Activation': echo 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30'; break;
                            case 'Disabled': echo 'bg-red-500/20 text-red-300 border border-red-500/30'; break;
                            default: echo 'bg-slate-500/20 text-slate-300 border border-slate-500/30';
                        }
                    ?>">
                        <?php echo htmlspecialchars($client['client_account_status']); ?>
                    </span>
                </div>
            </div>

            <!-- Contact Details -->
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Contact Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] uppercase text-slate-600 tech-mono block">Email Address</label>
                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="text-sm text-primary hover:underline">
                            <?php echo htmlspecialchars($client['email']); ?>
                        </a>
                    </div>
                    <div>
                        <label class="text-[9px] uppercase text-slate-600 tech-mono block">Phone Number</label>
                        <span class="text-sm text-slate-200"><?php echo htmlspecialchars($client['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div>
                        <label class="text-[9px] uppercase text-slate-600 tech-mono block">Company</label>
                        <span class="text-sm text-slate-200"><?php echo htmlspecialchars($client['company_name'] ?: 'Individual client'); ?></span>
                    </div>
                    <div>
                        <label class="text-[9px] uppercase text-slate-600 tech-mono block">Date of Birth</label>
                        <span class="text-sm text-slate-200"><?php echo $client['date_of_birth'] ? date('M j, Y', strtotime($client['date_of_birth'])) : 'Not provided'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <?php if ($client['address']): ?>
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Address</h3>
                <p class="text-sm text-slate-200 whitespace-pre-wrap"><?php echo htmlspecialchars($client['address']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Account Details -->
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 shadow-2xl">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Account Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Client ID:</span>
                        <span class="text-slate-200 font-mono"><?php echo $client['client_id']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Created:</span>
                        <span class="text-slate-200"><?php echo date('M j, Y', strtotime($client['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Added by:</span>
                        <span class="text-slate-200"><?php echo htmlspecialchars($client['added_by_name'] ?: 'System'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Last Updated:</span>
                        <span class="text-slate-200"><?php echo date('M j, Y', strtotime($client['updated_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Associated Cases -->
            <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-2xl">
                <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Associated Cases</h3>
                    <span class="text-xs tech-mono text-slate-600">Total: <?php echo count($cases); ?></span>
                </div>
                <div class="p-6">
                    <?php if (!empty($cases)): ?>
                        <div class="space-y-4">
                            <?php foreach ($cases as $case): ?>
                                <div class="flex items-center justify-between p-4 bg-slate-800/50 rounded-lg hover:bg-slate-800/70 transition-colors">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($case['title']); ?></p>
                                        <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                                            <span class="font-mono">#<?php echo htmlspecialchars($case['case_number']); ?></span>
                                            <span>Priority: <?php echo htmlspecialchars($case['priority']); ?></span>
                                            <span>Created: <?php echo date('M j, Y', strtotime($case['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 ml-4">
                                        <span class="px-2 py-1 text-xs font-bold rounded-full border <?php
                                            switch (strtolower($case['status'])) {
                                                case 'new': echo 'bg-sky-500/10 text-sky-400 border-sky-500/20'; break;
                                                case 'open': echo 'bg-green-500/10 text-green-400 border-green-500/20'; break;
                                                case 'in progress': echo 'bg-primary/10 text-primary border-primary/20'; break;
                                                case 'resolved': echo 'bg-teal-500/10 text-teal-400 border-teal-500/20'; break;
                                                case 'closed': echo 'bg-slate-500/10 text-slate-400 border-slate-500/20'; break;
                                                default: echo 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($case['status']); ?>
                                        </span>
                                        <a href="../cases/view_case.php?id=<?php echo $case['case_id']; ?>" class="text-slate-400 hover:text-primary transition-colors">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-folder-open text-3xl text-slate-600 mb-3"></i>
                            <p class="text-slate-400">No cases have been opened for this client.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-2xl">
                <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Recent Invoices</h3>
                    <a href="../invoices/index.php?client_id=<?php echo $client_id; ?>" class="text-xs text-primary hover:underline">View All</a>
                </div>
                <div class="p-6">
                    <?php if (!empty($invoices)): ?>
                        <div class="space-y-4">
                            <?php foreach ($invoices as $invoice): ?>
                                <div class="flex items-center justify-between p-4 bg-slate-800/50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                                        <p class="text-xs text-slate-400">Date: <?php echo date('M j, Y', strtotime($invoice['invoice_date'])); ?> • Due: <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-white">R<?php echo number_format($invoice['total_amount'], 2); ?></p>
                                        <span class="px-2 py-1 text-xs font-bold rounded-full <?php
                                            switch ($invoice['status']) {
                                                case 'Paid': echo 'bg-green-500/20 text-green-300 border border-green-500/30'; break;
                                                case 'Partially Paid': echo 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30'; break;
                                                case 'Sent': echo 'bg-blue-500/20 text-blue-300 border border-blue-500/30'; break;
                                                default: echo 'bg-slate-500/20 text-slate-300 border border-slate-500/20';
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($invoice['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-file-invoice text-3xl text-slate-600 mb-3"></i>
                            <p class="text-slate-400">No invoices have been created for this client.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
