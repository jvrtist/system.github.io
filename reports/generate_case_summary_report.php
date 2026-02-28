<?php
// reports/generate_case_summary_report.php
require_once '../config.php'; // Adjust path to root config.php
require_login();
// Optionally, restrict access to certain roles if needed
// if (!user_has_role('admin') && !user_has_role('investigator')) { ... }

$page_title = "Case Summary Report";

$conn = get_db_connection();

// Fetch data for filter dropdowns
$clients_list = [];
$users_list = []; // For 'Assigned To' or 'Created By' filter
$statuses = ['New', 'Open', 'In Progress', 'Pending Client Input', 'On Hold', 'Resolved', 'Closed', 'Archived'];
$priorities = ['Low', 'Medium', 'High', 'Urgent'];

if ($conn) {
    $client_result = $conn->query("SELECT client_id, first_name, last_name, company_name FROM clients ORDER BY last_name, first_name");
    if ($client_result) $clients_list = $client_result->fetch_all(MYSQLI_ASSOC);

    $user_result = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name");
    if ($user_result) $users_list = $user_result->fetch_all(MYSQLI_ASSOC);
}

// --- Filter Handling ---
$filter_client_id = isset($_GET['filter_client_id']) ? (int)$_GET['filter_client_id'] : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_input($_GET['filter_status']) : '';
$filter_priority = isset($_GET['filter_priority']) ? sanitize_input($_GET['filter_priority']) : '';
$filter_assigned_to_user_id = isset($_GET['filter_assigned_to_user_id']) ? (int)$_GET['filter_assigned_to_user_id'] : '';
$filter_date_opened_start = isset($_GET['filter_date_opened_start']) ? sanitize_input($_GET['filter_date_opened_start']) : '';
$filter_date_opened_end = isset($_GET['filter_date_opened_end']) ? sanitize_input($_GET['filter_date_opened_end']) : '';

$report_data = [];
$report_generated = false; // Flag to check if form submitted and report should be shown

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['generate_report'])) { // Check for a specific trigger
    $report_generated = true;

    $sql = "SELECT 
                cs.case_id, cs.case_number, cs.title, cs.status, cs.priority, cs.date_opened, cs.date_closed,
                CONCAT(cl.first_name, ' ', cl.last_name) as client_name, 
                cl.company_name as client_company,
                u_assigned.full_name as assigned_user_name,
                u_created.full_name as created_by_user_name,
                cs.updated_at
            FROM cases cs
            JOIN clients cl ON cs.client_id = cl.client_id
            LEFT JOIN users u_assigned ON cs.assigned_to_user_id = u_assigned.user_id
            LEFT JOIN users u_created ON cs.created_by_user_id = u_created.user_id
            WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty($filter_client_id)) {
        $sql .= " AND cs.client_id = ?";
        array_push($params, $filter_client_id);
        $types .= "i";
    }
    if (!empty($filter_status)) {
        $sql .= " AND cs.status = ?";
        array_push($params, $filter_status);
        $types .= "s";
    }
    if (!empty($filter_priority)) {
        $sql .= " AND cs.priority = ?";
        array_push($params, $filter_priority);
        $types .= "s";
    }
    if (!empty($filter_assigned_to_user_id)) {
        $sql .= " AND cs.assigned_to_user_id = ?";
        array_push($params, $filter_assigned_to_user_id);
        $types .= "i";
    }
    if (!empty($filter_date_opened_start)) {
        $sql .= " AND cs.date_opened >= ?";
        array_push($params, $filter_date_opened_start);
        $types .= "s";
    }
    if (!empty($filter_date_opened_end)) {
        $sql .= " AND cs.date_opened <= ?";
        array_push($params, $filter_date_opened_end);
        $types .= "s";
    }

    $sql .= " ORDER BY cs.date_opened DESC, cs.case_id DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $_SESSION['report_error_message'] = "Error preparing report statement: " . $conn->error;
    }
} // end if generate_report

if ($conn) $conn->close();

// Helper functions for styling (can be shared or defined locally)
function get_report_status_badge_class($status) { /* ... similar to cases/index.php ... */ 
    $base_class = "px-2 py-0.5 inline-flex text-xs leading-4 font-semibold rounded-full border";
    switch (strtolower($status)) {
        case 'new': return "$base_class bg-sky-100 text-sky-800 border-sky-400";
        case 'open': return "$base_class bg-green-100 text-green-800 border-green-400";
        case 'in progress': return "$base_class bg-yellow-100 text-yellow-800 border-yellow-400";
        case 'pending client input': return "$base_class bg-orange-100 text-orange-800 border-orange-400";
        case 'on hold': return "$base_class bg-purple-100 text-purple-800 border-purple-400";
        case 'resolved': return "$base_class bg-teal-100 text-teal-800 border-teal-400";
        case 'closed': return "$base_class bg-slate-200 text-slate-700 border-slate-500";
        case 'archived': return "$base_class bg-slate-300 text-slate-800 border-slate-600";
        default: return "$base_class bg-slate-100 text-slate-800 border-slate-400";
    }
}
function get_report_priority_icon_class($priority) { /* ... similar to cases/index.php ... */
    $color_class = '';
    switch (strtolower($priority)) {
        case 'low': $color_class = 'text-sky-500'; break;
        case 'medium': $color_class = 'text-yellow-500'; break;
        case 'high': $color_class = 'text-orange-500'; break;
        case 'urgent': $color_class = 'text-red-500'; break;
        default: $color_class = 'text-slate-500';
    }
     switch (strtolower($priority)) {
        case 'low': return "fas fa-arrow-down $color_class";
        case 'medium': return "fas fa-minus $color_class";
        case 'high': return "fas fa-arrow-up $color_class";
        case 'urgent': return "fas fa-exclamation-triangle $color_class";
        default: return "fas fa-question-circle $color_class";
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
    .print-header { display: none; } /* Hidden by default, shown only on print */
</style>

<div class="space-y-10">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Case <span class="text-primary">Summary</span></h1>
            <p class="text-sm text-slate-500 tech-mono">Generate case intelligence by client, status, priority, and date range.</p>
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

    <form action="generate_case_summary_report.php" method="GET" class="bg-slate-900/50 p-6 rounded-2xl border border-white/5 no-print">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Filters</h3>
            <span class="text-[10px] tech-mono text-slate-600 uppercase">Case Module</span>
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
                <label for="filter_status" class="text-[10px] font-black uppercase text-slate-500 ml-1">Case Status</label>
                <select name="filter_status" id="filter_status" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $status_opt): ?>
                        <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php echo ($filter_status === $status_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords($status_opt)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_priority" class="text-[10px] font-black uppercase text-slate-500 ml-1">Case Priority</label>
                <select name="filter_priority" id="filter_priority" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">All Priorities</option>
                     <?php foreach ($priorities as $priority_opt): ?>
                        <option value="<?php echo htmlspecialchars($priority_opt); ?>" <?php echo ($filter_priority === $priority_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords($priority_opt)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_assigned_to_user_id" class="text-[10px] font-black uppercase text-slate-500 ml-1">Assigned To</label>
                <select name="filter_assigned_to_user_id" id="filter_assigned_to_user_id" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">Any User</option>
                    <?php foreach ($users_list as $user_opt): ?>
                        <option value="<?php echo htmlspecialchars((string)$user_opt['user_id']); ?>" <?php echo ($filter_assigned_to_user_id == $user_opt['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user_opt['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_date_opened_start" class="text-[10px] font-black uppercase text-slate-500 ml-1">Date Opened From</label>
                <input type="date" name="filter_date_opened_start" id="filter_date_opened_start" value="<?php echo htmlspecialchars($filter_date_opened_start); ?>"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label for="filter_date_opened_end" class="text-[10px] font-black uppercase text-slate-500 ml-1">Date Opened Until</label>
                <input type="date" name="filter_date_opened_end" id="filter_date_opened_end" value="<?php echo htmlspecialchars($filter_date_opened_end); ?>"
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
                <h2 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Case Summary Report</h2>
                <p class="text-sm">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                <p class="text-sm">Filters Applied: 
                    <?php
                    $applied_filters = [];
                    if (!empty($filter_client_id)) $applied_filters[] = "Client ID: " . htmlspecialchars($filter_client_id);
                    if (!empty($filter_status)) $applied_filters[] = "Status: " . htmlspecialchars($filter_status);
                    if (!empty($filter_priority)) $applied_filters[] = "Priority: " . htmlspecialchars($filter_priority);
                    if (!empty($filter_assigned_to_user_id)) $applied_filters[] = "Assigned ID: " . htmlspecialchars($filter_assigned_to_user_id);
                    if (!empty($filter_date_opened_start)) $applied_filters[] = "Opened From: " . htmlspecialchars($filter_date_opened_start);
                    if (!empty($filter_date_opened_end)) $applied_filters[] = "Opened Until: " . htmlspecialchars($filter_date_opened_end);
                    echo !empty($applied_filters) ? implode('; ', $applied_filters) : 'None';
                    ?>
                </p>
                 <hr class="my-2">
            </div>

            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02] no-print">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Results</h3>
                <div class="flex items-center gap-4">
                    <span class="text-[10px] tech-mono text-slate-600 uppercase"><?php echo count($report_data); ?> Cases</span>
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
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Case #</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Title</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Client</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Priority</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Assigned To</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Date Opened</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Last Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($report_data as $case_item): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4 text-xs font-bold text-primary"><?php echo htmlspecialchars($case_item['case_number']); ?></td>
                                    <td class="px-6 py-4 text-xs text-white max-w-xs truncate" title="<?php echo htmlspecialchars($case_item['title']); ?>"><?php echo htmlspecialchars($case_item['title']); ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400" title="<?php echo htmlspecialchars($case_item['client_company'] ?: ''); ?>"><?php echo htmlspecialchars($case_item['client_name']); ?></td>
                                    <td class="px-6 py-4 text-[10px]"><span class="<?php echo get_report_status_badge_class($case_item['status']); ?> text-slate-800"><?php echo htmlspecialchars(ucwords($case_item['status'])); ?></span></td>
                                    <td class="px-6 py-4 text-xs"><i class="<?php echo get_report_priority_icon_class($case_item['priority']); ?> mr-1"></i></td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo htmlspecialchars($case_item['assigned_user_name'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-[10px] tech-mono text-slate-500 uppercase"><?php echo date("Y-m-d", strtotime($case_item['date_opened'])); ?></td>
                                    <td class="px-6 py-4 text-[10px] tech-mono text-slate-500 uppercase"><?php echo date("Y-m-d H:i", strtotime($case_item['updated_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="py-16 text-center">
                    <i class="fas fa-database text-slate-800 text-4xl mb-4"></i>
                    <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No cases found matching the selected criteria.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php
include_once '../includes/footer.php';
?>
