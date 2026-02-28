<?php
// reports/generate_case_status_trends_report.php
require_once '../config.php';
require_login();

$page_title = "Case Status Trends";

$statuses = ['New', 'Open', 'In Progress', 'Pending Client Input', 'On Hold', 'Resolved', 'Closed', 'Archived'];

$filter_date_start = isset($_GET['filter_date_start']) ? sanitize_input($_GET['filter_date_start']) : '';
$filter_date_end = isset($_GET['filter_date_end']) ? sanitize_input($_GET['filter_date_end']) : '';

$report_rows = [];
$report_generated = false;

$conn = get_db_connection();

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['generate_report'])) {
    $report_generated = true;

    $sql = "
        SELECT DATE_FORMAT(date_opened, '%Y-%m') AS period,
               status,
               COUNT(*) AS total
        FROM cases
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if (!empty($filter_date_start)) {
        $sql .= " AND date_opened >= ?";
        $params[] = $filter_date_start;
        $types .= "s";
    }
    if (!empty($filter_date_end)) {
        $sql .= " AND date_opened <= ?";
        $params[] = $filter_date_end;
        $types .= "s";
    }

    $sql .= " GROUP BY period, status ORDER BY period DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $report_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $_SESSION['report_error_message'] = "Error preparing report statement: " . $conn->error;
    }
}

if ($conn) $conn->close();

$trend_data = [];
foreach ($report_rows as $row) {
    $period = $row['period'] ?: 'Unknown';
    if (!isset($trend_data[$period])) {
        $trend_data[$period] = array_fill_keys($statuses, 0);
    }
    if (isset($trend_data[$period][$row['status']])) {
        $trend_data[$period][$row['status']] = (int)$row['total'];
    }
}

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
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Case <span class="text-primary">Status Trends</span></h1>
            <p class="text-sm text-slate-500 tech-mono">Monthly case volume by status for strategic visibility.</p>
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

    <form action="generate_case_status_trends_report.php" method="GET" class="bg-slate-900/50 p-6 rounded-2xl border border-white/5 no-print">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Filters</h3>
            <span class="text-[10px] tech-mono text-slate-600 uppercase">Case Module</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <label for="filter_date_start" class="text-[10px] font-black uppercase text-slate-500 ml-1">Date From</label>
                <input type="date" name="filter_date_start" id="filter_date_start" value="<?php echo htmlspecialchars($filter_date_start); ?>"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label for="filter_date_end" class="text-[10px] font-black uppercase text-slate-500 ml-1">Date Until</label>
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
                <h2 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Case Status Trends</h2>
                <p class="text-sm">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                 <hr class="my-2">
            </div>

            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02] no-print">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Results</h3>
                <div class="flex items-center gap-4">
                    <span class="text-[10px] tech-mono text-slate-600 uppercase"><?php echo count($trend_data); ?> Periods</span>
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

            <?php if (!empty($trend_data)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/5">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Period</th>
                                <?php foreach ($statuses as $status): ?>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400"><?php echo htmlspecialchars($status); ?></th>
                                <?php endforeach; ?>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($trend_data as $period => $row): ?>
                                <?php $row_total = array_sum($row); ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4 text-xs font-bold text-primary"><?php echo htmlspecialchars($period); ?></td>
                                    <?php foreach ($statuses as $status): ?>
                                        <td class="px-6 py-4 text-xs text-slate-400"><?php echo (int)$row[$status]; ?></td>
                                    <?php endforeach; ?>
                                    <td class="px-6 py-4 text-xs text-white font-black"><?php echo (int)$row_total; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="py-16 text-center">
                    <i class="fas fa-database text-slate-800 text-4xl mb-4"></i>
                    <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No case status trends found for the selected criteria.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
