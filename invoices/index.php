<?php
/**
 * ISS Investigations - Financial Intelligence
 * Secure ledger for invoice tracking and payment reconciliation.
 */
require_once '../config.php';
require_login();

$page_title = "Financial Intelligence";
$conn = get_db_connection();

// --- 1. Data Harvesting for Filters ---
$clients_list = [];
$invoice_statuses = ['Draft', 'Sent', 'Paid', 'Partially Paid', 'Overdue', 'Void', 'Cancelled'];

if ($conn) {
    $clients_list = $conn->query("SELECT client_id, first_name, last_name FROM clients ORDER BY last_name ASC")->fetch_all(MYSQLI_ASSOC);
}

// --- 2. Filtering & Parameters ---
$f_client = (int)($_GET['client_id'] ?? 0);
$f_status = sanitize_input($_GET['status'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');

$sort = in_array($_GET['sort'] ?? '', ['i.invoice_number', 'client_name', 'i.issue_date', 'i.due_date', 'i.total_amount', 'i.status']) ? $_GET['sort'] : 'i.issue_date';
$order = (strtolower($_GET['order'] ?? '') === 'asc') ? 'ASC' : 'DESC';

// --- 3. Query Construction ---
$sql = "SELECT i.*, CONCAT(cl.first_name, ' ', cl.last_name) as client_name
        FROM invoices i
        JOIN clients cl ON i.client_id = cl.client_id
        WHERE 1=1";

$params = [];
$types = "";

if ($search) {
    $sql .= " AND (i.invoice_number LIKE ? OR cl.first_name LIKE ? OR cl.last_name LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term);
    $types .= "sss";
}
if ($f_client) { $sql .= " AND i.client_id = ?"; $params[] = $f_client; $types .= "i"; }
if ($f_status) { $sql .= " AND i.status = ?"; $params[] = $f_status; $types .= "s"; }

$sql .= " ORDER BY $sort $order";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- 4. Presentation Helpers ---
function invoice_status_pill($status) {
    $map = [
        'paid' => 'bg-green-500/10 text-green-400 border-green-500/20',
        'sent' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'overdue' => 'bg-red-500/10 text-red-500 border-red-500/20',
        'partially paid' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        'draft' => 'bg-slate-800 text-slate-400 border-slate-700'
    ];
    $style = $map[strtolower($status)] ?? 'bg-slate-900 text-slate-500 border-white/5';
    $safe_status = htmlspecialchars($status);
    return "<span class='px-2 py-0.5 rounded-full border text-[9px] font-black uppercase tracking-tighter $style'>$safe_status</span>";
}

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Invoice <span class="text-primary">Management</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">View, create, and manage all invoices</p>
        </div>
        <a href="add_invoice.php" class="w-full md:w-auto bg-primary hover:bg-orange-600 text-white text-sm font-black uppercase tracking-widest px-6 py-3 rounded-lg transition-all flex items-center justify-center shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30">
            <i class="fas fa-file-invoice-dollar mr-2"></i> Create Invoice
        </a>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <form action="index.php" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Ledger Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Invoice # or Client..." class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Client Filter</label>
                <select name="client_id" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Entities</option>
                    <?php foreach ($clients_list as $cl): ?>
                        <option value="<?= $cl['client_id'] ?>" <?= $f_client === (int)$cl['client_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['last_name']) ?>, <?= htmlspecialchars($cl['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Payment Status</label>
                <select name="status" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none">
                    <option value="">All Statuses</option>
                    <?php foreach ($invoice_statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-black uppercase tracking-[0.2em] py-3 rounded-lg transition-all border border-white/5">
                    Execute Query
                </button>
            </div>
        </div>
    </form>

    <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-2xl overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/[0.02] border-b border-white/5">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Invoice Identifier</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Client Entity</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Schedule</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500 text-right">Balance Metrics</th>
                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-500">Status/Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if ($invoices): ?>
                    <?php foreach ($invoices as $i): 
                        $balance = $i['total_amount'] - $i['amount_paid'];
                        $is_overdue = (strtolower($i['status']) !== 'paid' && $i['due_date'] < date('Y-m-d'));
                    ?>
                        <tr class="hover:bg-white/[0.01] transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-white group-hover:text-primary transition-colors"><?= htmlspecialchars($i['invoice_number']) ?></span>
                                    <span class="text-[9px] tech-mono text-slate-600 uppercase">Created: <?= date("Y.m.d", strtotime($i['issue_date'])) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-400 font-bold uppercase tracking-tight"><?= htmlspecialchars($i['client_name']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-black text-slate-500 uppercase">Due Date</span>
                                    <span class="text-xs tech-mono <?= $is_overdue ? 'text-red-500 animate-pulse' : 'text-slate-300' ?>">
                                        <?= $i['due_date'] ? date("Y.m.d", strtotime($i['due_date'])) : '---' ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-white">$<?= number_format($i['total_amount'], 2) ?></span>
                                    <span class="text-[9px] tech-mono <?= $balance > 0 ? 'text-orange-500' : 'text-slate-600' ?>">
                                        Pending: $<?= number_format($balance, 2) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center gap-2">
                                    <?= invoice_status_pill($i['status']) ?>
                                    <div class="flex items-center gap-3 mt-1">
                                        <a href="view_invoice.php?id=<?= $i['invoice_id'] ?>" class="text-slate-600 hover:text-primary transition-colors text-xs"><i class="fas fa-eye"></i></a>
                                        <a href="edit_invoice.php?id=<?= $i['invoice_id'] ?>" class="text-slate-600 hover:text-amber-500 transition-colors text-xs"><i class="fas fa-edit"></i></a>
                                        <a href="record_payment.php?invoice_id=<?= $i['invoice_id'] ?>" class="text-slate-600 hover:text-green-500 transition-colors text-xs"><i class="fas fa-cash-register"></i></a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No financial records matching current criteria.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
