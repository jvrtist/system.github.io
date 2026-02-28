<?php
/**
 * ISS Investigations - Task Intelligence
 * Tactical oversight of operational objectives and assignments.
 */
require_once '../config.php';
require_login();

$page_title = "Task Intelligence";
$conn = get_db_connection();

// --- 1. Data Harvesting for Filters ---
$cases_list = [];
$users_list = [];
$task_statuses = ['Pending', 'In Progress', 'Completed', 'Deferred'];
$task_priorities = ['Low', 'Medium', 'High'];

if ($conn) {
    $cases_list = $conn->query("SELECT case_id, case_number, title FROM cases ORDER BY case_number ASC")->fetch_all(MYSQLI_ASSOC);
    $users_list = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
}

// --- 2. Filtering & Parameters ---
$filter_case_id = (int)($_GET['case_id'] ?? 0);
$filter_status = sanitize_input($_GET['status'] ?? '');
$filter_priority = sanitize_input($_GET['priority'] ?? '');
$filter_assigned_to = sanitize_input($_GET['assigned_to'] ?? ''); 
$filter_due_start = sanitize_input($_GET['due_start'] ?? '');
$filter_due_end = sanitize_input($_GET['due_end'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');

$sort = in_array($_GET['sort'] ?? '', ['t.description', 'c.case_number', 'assigned_user_name', 't.due_date', 't.status', 't.priority']) ? $_GET['sort'] : 't.due_date';
$order = (strtolower($_GET['order'] ?? '') === 'desc') ? 'DESC' : 'ASC';

// --- 3. Query Construction ---
$sql = "SELECT t.*, c.case_number, c.title as case_title, 
               u_assigned.full_name as assigned_user_name, 
               u_created.full_name as created_by_user_name
        FROM tasks t
        LEFT JOIN cases c ON t.case_id = c.case_id
        LEFT JOIN users u_assigned ON t.assigned_to_user_id = u_assigned.user_id
        LEFT JOIN users u_created ON t.created_by_user_id = u_created.user_id
        WHERE 1=1";

$params = [];
$types = "";

if ($search) {
    $sql .= " AND (t.description LIKE ? OR c.case_number LIKE ? OR c.title LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term);
    $types .= "sss";
}
if ($filter_case_id) { $sql .= " AND t.case_id = ?"; $params[] = $filter_case_id; $types .= "i"; }
if ($filter_status) {
    if ($filter_status === 'pending_or_progress') { $sql .= " AND t.status IN ('Pending', 'In Progress')"; }
    else { $sql .= " AND t.status = ?"; $params[] = $filter_status; $types .= "s"; }
}
if ($filter_priority) { $sql .= " AND t.priority = ?"; $params[] = $filter_priority; $types .= "s"; }
if ($filter_assigned_to) {
    if ($filter_assigned_to === 'me') { $sql .= " AND t.assigned_to_user_id = ?"; $params[] = $_SESSION['user_id']; $types .= "i"; }
    elseif ($filter_assigned_to === 'unassigned') { $sql .= " AND t.assigned_to_user_id IS NULL"; }
    else { $sql .= " AND t.assigned_to_user_id = ?"; $params[] = (int)$filter_assigned_to; $types .= "i"; }
}
if ($filter_due_start) { $sql .= " AND t.due_date >= ?"; $params[] = $filter_due_start; $types .= "s"; }
if ($filter_due_end) { $sql .= " AND t.due_date <= ?"; $params[] = $filter_due_end; $types .= "s"; }

$sql .= " ORDER BY $sort $order, t.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- 4. Presentation Helpers ---
function get_task_status_badge($status) {
    $map = [
        'pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
        'in progress' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'completed' => 'bg-green-500/10 text-green-400 border-green-500/20',
        'deferred' => 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    ];
    $style = $map[strtolower($status)] ?? 'bg-slate-800 text-slate-400 border-slate-700';
    $safe_status = htmlspecialchars($status);
    return "<span class='px-2 py-0.5 rounded-full border text-[9px] font-black uppercase tracking-tighter $style'>$safe_status</span>";
}

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Task <span class="text-primary">Intelligence</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Operational Objective Tracking</p>
        </div>
        <a href="add_task.php" class="bg-primary hover:bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl transition-all flex items-center shadow-lg shadow-primary/20">
            <i class="fas fa-plus-circle mr-2"></i> Initialize New Task
        </a>
    </div>

    <form action="index.php" method="GET" class="bg-slate-900 border border-white/5 p-4 rounded-2xl shadow-2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="lg:col-span-2">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Keywords or Case #..." class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>
            <div>
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Assigned Agent</label>
                <select name="assigned_to" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Personnel</option>
                    <option value="me" <?= $filter_assigned_to === 'me' ? 'selected' : '' ?>>Assignee: Me</option>
                    <option value="unassigned" <?= $filter_assigned_to === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                    <?php foreach ($users_list as $u): ?>
                        <option value="<?= $u['user_id'] ?>" <?= (int)$filter_assigned_to === $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Status</label>
                <select name="status" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none">
                    <option value="">All Statuses</option>
                    <option value="pending_or_progress" <?= $filter_status === 'pending_or_progress' ? 'selected' : '' ?>>Active Objectives</option>
                    <?php foreach ($task_statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-lg transition-all">
                    Apply Filter
                </button>
            </div>
        </div>
    </form>

    <?php if ($tasks): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($tasks as $t): 
                $is_overdue = ($t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'Completed');
            ?>
                <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-lg hover:shadow-primary/20 hover:border-primary/30 transition-all duration-300 flex flex-col <?= ($t['status'] === 'Completed') ? 'opacity-60' : '' ?>">
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <?= get_task_status_badge($t['status']) ?>
                            <?php if ($is_overdue): ?>
                                <span class="text-[9px] font-bold text-red-500 uppercase animate-pulse">Overdue</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-2 line-clamp-2">
                            <?= htmlspecialchars($t['description']) ?>
                        </h3>
                        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                            <i class="fas fa-folder-open"></i>
                            <a href="../cases/view_case.php?id=<?= $t['case_id'] ?>" class="hover:text-primary transition-colors">
                                <?= htmlspecialchars($t['case_number']) ?>
                            </a>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Due: <?= $t['due_date'] ? date("M j, Y", strtotime($t['due_date'])) : 'No Date' ?></span>
                        </div>
                    </div>
                    <div class="bg-slate-950/50 border-t border-white/5 px-6 py-3 flex justify-between items-center text-xs">
                        <div class="flex items-center gap-2 text-slate-500">
                            <i class="fas fa-user"></i>
                            <span><?= htmlspecialchars($t['assigned_user_name'] ?? 'Unassigned') ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <?php if ($t['status'] !== 'Completed'): ?>
                                <a href="update_task_status.php?id=<?= $t['task_id'] ?>&status=Completed&token=<?= htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]) ?>" 
                                   onclick="return confirm('Mark as complete?')"
                                   class="text-slate-600 hover:text-green-500 transition-colors" title="Complete"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                            <a href="edit_task.php?id=<?= $t['task_id'] ?>" class="text-slate-600 hover:text-amber-500 transition-colors" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if (user_has_role('admin')): ?>
                                <a href="delete_task.php?id=<?= $t['task_id'] ?>&token=<?= htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]) ?>" 
                                   onclick="return confirm('Delete task?')"
                                   class="text-slate-600 hover:text-red-500 transition-colors" title="Delete"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20 bg-slate-900 border border-white/5 rounded-2xl">
            <i class="fas fa-clipboard-check text-5xl text-slate-700 mb-4"></i>
            <h3 class="text-lg font-bold text-white">No Tasks Found</h3>
            <p class="text-sm text-slate-500 mt-2">No tasks match your current filter criteria.</p>
            <a href="index.php" class="mt-6 inline-block bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-widest px-6 py-3 rounded-lg transition-all">
                Clear Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
