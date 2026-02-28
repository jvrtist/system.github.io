<?php
// reports/generate_task_status_report.php
require_once '../config.php'; // Adjust path to root config.php
require_login();

$page_title = "Task Status Report";

$conn = get_db_connection();

// Fetch data for filter dropdowns
$cases_list = [];
$users_list = []; // For 'Assigned To' filter
$task_statuses = ['Pending', 'In Progress', 'Completed', 'Deferred'];
$task_priorities = ['Low', 'Medium', 'High'];

if ($conn) {
    $case_result = $conn->query("SELECT case_id, case_number, title FROM cases WHERE status NOT IN ('Closed', 'Archived', 'Resolved') ORDER BY case_number ASC");
    if ($case_result) $cases_list = $case_result->fetch_all(MYSQLI_ASSOC);

    $user_result = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name");
    if ($user_result) $users_list = $user_result->fetch_all(MYSQLI_ASSOC);
}

// --- Filter Handling ---
$filter_case_id = isset($_GET['filter_case_id']) ? (int)$_GET['filter_case_id'] : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_input($_GET['filter_status']) : '';
$filter_priority = isset($_GET['filter_priority']) ? sanitize_input($_GET['filter_priority']) : '';
$filter_assigned_to_user_id = isset($_GET['filter_assigned_to_user_id']) ? sanitize_input($_GET['filter_assigned_to_user_id']) : ''; // can be 'me' or user_id
$filter_due_date_start = isset($_GET['filter_due_date_start']) ? sanitize_input($_GET['filter_due_date_start']) : '';
$filter_due_date_end = isset($_GET['filter_due_date_end']) ? sanitize_input($_GET['filter_due_date_end']) : '';

$report_data = [];
$report_generated = false;

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['generate_report'])) {
    $report_generated = true;

    $sql = "SELECT 
                t.task_id, t.description, t.status, t.priority, t.due_date,
                c.case_number, c.title as case_title,
                u_assigned.full_name as assigned_user_name,
                u_created.full_name as created_by_user_name,
                t.created_at
            FROM tasks t
            LEFT JOIN cases c ON t.case_id = c.case_id
            LEFT JOIN users u_assigned ON t.assigned_to_user_id = u_assigned.user_id
            LEFT JOIN users u_created ON t.created_by_user_id = u_created.user_id
            WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty($filter_case_id)) {
        $sql .= " AND t.case_id = ?";
        array_push($params, $filter_case_id);
        $types .= "i";
    }
    if (!empty($filter_status)) {
        $sql .= " AND t.status = ?";
        array_push($params, $filter_status);
        $types .= "s";
    }
    if (!empty($filter_priority)) {
        $sql .= " AND t.priority = ?";
        array_push($params, $filter_priority);
        $types .= "s";
    }
    if (!empty($filter_assigned_to_user_id)) {
        if ($filter_assigned_to_user_id === 'me' && isset($_SESSION['user_id'])) {
            $sql .= " AND t.assigned_to_user_id = ?";
            array_push($params, $_SESSION['user_id']);
            $types .= "i";
        } elseif ($filter_assigned_to_user_id === 'unassigned') {
            $sql .= " AND t.assigned_to_user_id IS NULL";
        } elseif (is_numeric($filter_assigned_to_user_id)) {
            $sql .= " AND t.assigned_to_user_id = ?";
            array_push($params, (int)$filter_assigned_to_user_id);
            $types .= "i";
        }
    }
    if (!empty($filter_due_date_start)) {
        $sql .= " AND t.due_date >= ?";
        array_push($params, $filter_due_date_start);
        $types .= "s";
    }
    if (!empty($filter_due_date_end)) {
        $sql .= " AND t.due_date <= ?";
        array_push($params, $filter_due_date_end);
        $types .= "s";
    }

    $sql .= " ORDER BY t.due_date ASC, t.priority DESC";

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
}

if ($conn) $conn->close();

// Helper functions for styling (can be shared or defined locally)
function get_report_task_status_badge_class($status) {
    $base_class = "px-2 py-0.5 inline-flex text-xs leading-4 font-semibold rounded-full border";
    switch (strtolower($status)) {
        case 'pending': return "$base_class bg-yellow-100 text-yellow-800 border-yellow-400";
        case 'in progress': return "$base_class bg-blue-100 text-blue-800 border-blue-400";
        case 'completed': return "$base_class bg-green-100 text-green-800 border-green-400";
        case 'deferred': return "$base_class bg-purple-100 text-purple-800 border-purple-400";
        default: return "$base_class bg-slate-100 text-slate-800 border-slate-400";
    }
}
function get_report_task_priority_icon_class($priority) {
    $color_class = '';
    switch (strtolower($priority)) {
        case 'low': $color_class = 'text-sky-500'; break;
        case 'medium': $color_class = 'text-yellow-500'; break;
        case 'high': $color_class = 'text-orange-500'; break;
        default: $color_class = 'text-slate-500';
    }
     switch (strtolower($priority)) {
        case 'low': return "fas fa-arrow-down $color_class";
        case 'medium': return "fas fa-minus $color_class";
        case 'high': return "fas fa-arrow-up $color_class";
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
    .print-header { display: none; }
</style>

<div class="space-y-10">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Task <span class="text-primary">Status</span></h1>
            <p class="text-sm text-slate-500 tech-mono">Generate operational task intelligence by case, status, and due date.</p>
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

    <form action="generate_task_status_report.php" method="GET" class="bg-slate-900/50 p-6 rounded-2xl border border-white/5 no-print">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Filters</h3>
            <span class="text-[10px] tech-mono text-slate-600 uppercase">Task Module</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <div class="space-y-1">
                <label for="filter_case_id" class="text-[10px] font-black uppercase text-slate-500 ml-1">Case</label>
                <select name="filter_case_id" id="filter_case_id" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">All Cases</option>
                    <?php foreach ($cases_list as $case_opt): ?>
                        <option value="<?php echo htmlspecialchars((string)$case_opt['case_id']); ?>" <?php echo ($filter_case_id == $case_opt['case_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($case_opt['case_number'] . ' - ' . substr($case_opt['title'], 0, 30) . (strlen($case_opt['title']) > 30 ? '...' : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_assigned_to_user_id" class="text-[10px] font-black uppercase text-slate-500 ml-1">Assigned To</label>
                <select name="filter_assigned_to_user_id" id="filter_assigned_to_user_id" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">Anyone</option>
                    <option value="me" <?php echo ($filter_assigned_to_user_id === 'me') ? 'selected' : ''; ?>>Me (<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>)</option>
                    <option value="unassigned" <?php echo ($filter_assigned_to_user_id === 'unassigned') ? 'selected' : ''; ?>>Unassigned</option>
                    <?php foreach ($users_list as $user_opt): ?>
                        <option value="<?php echo htmlspecialchars((string)$user_opt['user_id']); ?>" <?php echo ($filter_assigned_to_user_id == $user_opt['user_id'] && $filter_assigned_to_user_id !== 'me') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user_opt['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_status" class="text-[10px] font-black uppercase text-slate-500 ml-1">Task Status</label>
                <select name="filter_status" id="filter_status" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">All Statuses</option>
                    <?php foreach ($task_statuses as $status_opt): ?>
                        <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php echo ($filter_status === $status_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords($status_opt)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_priority" class="text-[10px] font-black uppercase text-slate-500 ml-1">Task Priority</label>
                <select name="filter_priority" id="filter_priority" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
                    <option value="">All Priorities</option>
                     <?php foreach ($task_priorities as $priority_opt): ?>
                        <option value="<?php echo htmlspecialchars($priority_opt); ?>" <?php echo ($filter_priority === $priority_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords($priority_opt)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-1">
                <label for="filter_due_date_start" class="text-[10px] font-black uppercase text-slate-500 ml-1">Due Date From</label>
                <input type="date" name="filter_due_date_start" id="filter_due_date_start" value="<?php echo htmlspecialchars($filter_due_date_start); ?>"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label for="filter_due_date_end" class="text-[10px] font-black uppercase text-slate-500 ml-1">Due Date Until</label>
                <input type="date" name="filter_due_date_end" id="filter_due_date_end" value="<?php echo htmlspecialchars($filter_due_date_end); ?>"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="md:col-start-4 self-end">
                <button type="submit" name="generate_report" value="1" class="w-full bg-primary hover:bg-orange-600 text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg shadow-primary/10">
                    <i class="fas fa-cogs mr-2"></i> Generate Report
                </button>
            </div>
        </div>
    </form>

    <?php if ($report_generated): ?>
        <section class="print-area bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
            <div class="print-header">
                <h2 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Task Status Report</h2>
                <p class="text-sm">Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
                 <hr class="my-2">
            </div>

            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02] no-print">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Report Results</h3>
                <div class="flex items-center gap-4">
                    <span class="text-[10px] tech-mono text-slate-600 uppercase"><?php echo count($report_data); ?> Tasks</span>
                    <button onclick="window.print();" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-[10px] font-black uppercase tracking-widest py-2.5 px-4 rounded-xl transition-all">
                        <i class="fas fa-print mr-2"></i> Print Report
                    </button>
                </div>
            </div>

            <?php if (!empty($report_data)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/5">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Task Description</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Related Case</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Assigned To</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Priority</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Due Date</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($report_data as $task_item): ?>
                                <tr class="<?php echo ($task_item['status'] === 'Completed') ? 'opacity-60' : ''; ?> <?php echo (!empty($task_item['due_date']) && $task_item['due_date'] < date('Y-m-d') && $task_item['status'] !== 'Completed') ? 'bg-red-900/30' : ''; ?> hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-4 text-xs text-white break-words"><?php echo htmlspecialchars($task_item['description']); ?></td>
                                    <td class="px-6 py-4 text-xs font-bold text-primary" title="<?php echo htmlspecialchars($task_item['case_title']); ?>"><?php echo htmlspecialchars($task_item['case_number']); ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo htmlspecialchars($task_item['assigned_user_name'] ?: 'Unassigned'); ?></td>
                                    <td class="px-6 py-4 text-[10px]"><span class="<?php echo get_report_task_status_badge_class($task_item['status']); ?> text-slate-800"><?php echo htmlspecialchars(ucwords($task_item['status'])); ?></span></td>
                                    <td class="px-6 py-4 text-xs"><i class="<?php echo get_report_task_priority_icon_class($task_item['priority']); ?> mr-1"></i> <span class="sr-only"><?php echo htmlspecialchars(ucwords($task_item['priority'])); ?></span></td>
                                    <td class="px-6 py-4 text-[10px] tech-mono uppercase <?php echo (!empty($task_item['due_date']) && $task_item['due_date'] < date('Y-m-d') && $task_item['status'] !== 'Completed') ? 'text-red-400 font-black' : 'text-slate-500'; ?>">
                                        <?php echo $task_item['due_date'] ? date("Y-m-d", strtotime($task_item['due_date'])) : 'N/A'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-[10px] tech-mono text-slate-500 uppercase"><?php echo date("Y-m-d", strtotime($task_item['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="py-16 text-center">
                    <i class="fas fa-database text-slate-800 text-4xl mb-4"></i>
                    <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No tasks found matching the selected criteria.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php
include_once '../includes/footer.php';
?>
