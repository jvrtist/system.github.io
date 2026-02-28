<?php
// reports/generate_client_activity_report.php
require_once '../config.php';
require_login();

if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "You do not have permission to access this report.";
    redirect('dashboard.php');
}

$page_title = "Client Activity Report";

$conn = get_db_connection();
$clients_list = [];

if ($conn) {
    $client_result = $conn->query("SELECT client_id, first_name, last_name, company_name FROM clients ORDER BY last_name, first_name");
    if ($client_result) $clients_list = $client_result->fetch_all(MYSQLI_ASSOC);
}

$filter_client_id = isset($_GET['filter_client_id']) ? (int)$_GET['filter_client_id'] : '';
$filter_date_start = isset($_GET['filter_date_start']) ? sanitize_input($_GET['filter_date_start']) : '';
$filter_date_end = isset($_GET['filter_date_end']) ? sanitize_input($_GET['filter_date_end']) : '';

$report_data = [];
$report_generated = false;

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['generate_report'])) {
    $report_generated = true;

    $params = [];
    $types = "";

    $start_dt = $filter_date_start ? $filter_date_start . " 00:00:00" : '';
    $end_dt = $filter_date_end ? $filter_date_end . " 23:59:59" : '';

    $case_date_sql = "";
    $message_date_sql = "";
    $invoice_date_sql = "";

    if ($start_dt) {
        $case_date_sql .= " AND updated_at >= ?";
        $message_date_sql .= " AND sent_at >= ?";
        $invoice_date_sql .= " AND invoice_date >= ?";
        array_push($params, $start_dt, $start_dt, $filter_date_start);
        $types .= "sss";
    }
    if ($end_dt) {
        $case_date_sql .= " AND updated_at <= ?";
        $message_date_sql .= " AND sent_at <= ?";
        $invoice_date_sql .= " AND invoice_date <= ?";
        array_push($params, $end_dt, $end_dt, $filter_date_end);
        $types .= "sss";
    }

    $sql = "
        SELECT 
            cl.client_id,
            cl.first_name,
            cl.last_name,
            cl.company_name,
            COALESCE(cs.total_cases, 0) AS total_cases,
            COALESCE(cs.open_cases, 0) AS open_cases,
            COALESCE(cm.total_messages, 0) AS total_messages,
            COALESCE(inv.total_invoices, 0) AS total_invoices,
            COALESCE(inv.balance_due, 0) AS balance_due,
            GREATEST(
                COALESCE(cs.last_case_update, '1970-01-01'),
                COALESCE(cm.last_message, '1970-01-01'),
                COALESCE(inv.last_invoice_update, '1970-01-01')
            ) AS last_activity
        FROM clients cl
        LEFT JOIN (
            SELECT client_id,
                   COUNT(*) AS total_cases,
                   SUM(CASE WHEN status NOT IN ('Closed', 'Resolved', 'Archived') THEN 1 ELSE 0 END) AS open_cases,
                   MAX(updated_at) AS last_case_update
            FROM cases
            WHERE 1=1 {$case_date_sql}
            GROUP BY client_id
        ) cs ON cl.client_id = cs.client_id
        LEFT JOIN (
            SELECT client_id,
                   COUNT(*) AS total_messages,
                   MAX(sent_at) AS last_message
            FROM client_messages
            WHERE 1=1 {$message_date_sql}
            GROUP BY client_id
        ) cm ON cl.client_id = cm.client_id
        LEFT JOIN (
            SELECT client_id,
                   COUNT(*) AS total_invoices,
                   SUM(total_amount - amount_paid) AS balance_due,
                   MAX(updated_at) AS last_invoice_update
            FROM invoices
            WHERE 1=1 {$invoice_date_sql}
            GROUP BY client_id
        ) inv ON cl.client_id = inv.client_id
        WHERE 1=1
    ";

    if (!empty($filter_client_id)) {
        $sql .= " AND cl.client_id = ?";
        $params[] = $filter_client_id;
        $types .= "i";
    }

    $sql .= " ORDER BY last_activity DESC, cl.last_name ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $_SESSION['report_error_message'] = "Error preparing report statement: " . $conn->error;
    }
}

if ($conn) $conn->close();

include_once '../includes/header.php';
?>

<style>
    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        table { font-size: 10pt; }
        th, td { padding: 4px 8px; }
        .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
    }
    .print-header { display: none; }
</style>

<div class="space-y-10">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Client <span class="text-primary">Activity</span></h1>
            <p class="text-sm text-slate-500 tech-mono">Analyze client case volume, messages, and billing activity.</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-[10px] font-black uppercase tracking-widest text-primary hover:text-white transition-colors">
                <i class="fas fa-chevron-left mr-2"></i> Back to Reports
            </a>
            <div class="text-right hidden md:block">
                <p class="text-[10px] font-black uppercase text-slate-600 tracking-[0.2em]">Current Date</p>
                <p class="text-sm font-bold text-white"><?php echo date('l, d F Y'); ?></p>
            </div>
        </div>
    </header>

    <form action="generate_client_activity_report.php" method="GET" class="bg-slate-900/50 p-6 rounded-2xl border border-white/5 no-print">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Filters</h3>
            <span class="text-[10px] tech-mono text-slate-600 uppercase">Client Module</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <label for="filter_client_id" class="text-[10px] font-black uppercase text-slate-500 ml-1">Client</label>
                <select name="filter_client_id" id="filter_client_id" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">All Clients</option>
                    <?php foreach ($clients_list as $client_opt): ?>
                        <option value="<?php echo htmlspecialchars((string)$client_opt['client_id']); ?>" <?php echo ($filter_client_id == $client_opt['client_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client_opt['last_name'] . ', ' . $client_opt['first_name'] . ($client_opt['company_name'] ? ' (' . $client_opt['company_name'] . ')' : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_date_start" class="text-[10px] font-black uppercase text-slate-500 ml-1">Activity From</label>
                <input type="date" name="filter_date_start" id="filter_date_start" value="<?php echo htmlspecialchars($filter_date_start); ?>"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label for="filter_date_end" class="text-[10px] font-black uppercase text-slate-500 ml-1">Activity Until</label>
                <input type="date" name="filter_date_end" id="filter_date_end" value="<?php echo htmlspecialchars($filter_date_end); ?>"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="lg:col-start-4 self-end">
                <button type="submit" name="generate_report" value="1" class="w-full bg-primary hover:bg-orange-600 text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg shadow-primary/10">
                    <i class="fas fa-cogs mr-2"></i> Generate Report
                </button>
            </div>
        </div>
    </form>

    <?php if ($report_generated): ?>
        <section class="print-area bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
            <div class="print-header">
                <h2 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Client Activity Report</h2>
                <p class="text-sm">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                 <hr class="my-2">
            </div>

            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02] no-print">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Results</h3>
                <div class="flex items-center gap-4">
                    <span class="text-[10px] tech-mono text-slate-600 uppercase"><?php echo count($report_data); ?> Clients</span>
                    <button onclick="window.print();" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-black uppercase tracking-widest py-2.5 px-4 rounded-xl transition-all">
                        <i class="fas fa-print mr-2"></i> Print Report
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['report_error_message'])): ?>
                <div class="bg-red-500/20 border border-red-700 text-red-300 px-4 py-3 rounded-lg relative m-6 text-sm" role="alert">
                    <?php echo htmlspecialchars($_SESSION['report_error_message']); unset($_SESSION['report_error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($report_data)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/5">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Client</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Cases</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Open Cases</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Messages</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Invoices</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Balance Due</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Last Activity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($report_data as $row): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4 text-xs text-white">
                                        <?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?>
                                        <?php if (!empty($row['company_name'])): ?>
                                            <span class="text-[10px] tech-mono text-slate-500 uppercase ml-2"><?php echo htmlspecialchars($row['company_name']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo (int)$row['total_cases']; ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo (int)$row['open_cases']; ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo (int)$row['total_messages']; ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo (int)$row['total_invoices']; ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-300 font-bold">R<?php echo number_format((float)$row['balance_due'], 2); ?></td>
                                    <td class="px-6 py-4 text-[10px] tech-mono text-slate-500 uppercase">
                                        <?php echo $row['last_activity'] ? date("Y-m-d H:i", strtotime($row['last_activity'])) : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="py-16 text-center">
                    <i class="fas fa-database text-slate-800 text-4xl mb-4"></i>
                    <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No client activity found for the selected criteria.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
