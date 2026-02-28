<?php
/**
 * ISS Investigations - Case Management Intelligence
 * Comprehensive filtering and sorting for investigative records.
 */
require_once '../config.php';
require_login();

$page_title = "Case Intelligence";
$conn = get_db_connection();

// --- 1. Data Harvesting for Filters ---
$clients_list = [];
$users_list = [];
$statuses = ['New', 'Open', 'In Progress', 'Pending Client Input', 'On Hold', 'Resolved', 'Closed', 'Archived'];
$priorities = ['Low', 'Medium', 'High', 'Urgent'];

if ($conn) {
    $clients_list = $conn->query("SELECT client_id, first_name, last_name FROM clients ORDER BY last_name ASC")->fetch_all(MYSQLI_ASSOC);
    $users_list = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('admin', 'investigator') ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);
}

// --- 2. Filtering & Parameters ---
$search = sanitize_input($_GET['search'] ?? '');
$f_client = (int)($_GET['client_id'] ?? 0);
$f_status = sanitize_input($_GET['status'] ?? 'active'); // Default to active
$f_priority = sanitize_input($_GET['priority'] ?? '');
$f_assigned = sanitize_input($_GET['assigned_to'] ?? '');

$sort = in_array($_GET['sort'] ?? '', ['cs.case_number', 'cs.title', 'client_name', 'cs.status', 'cs.priority', 'cs.created_at']) ? $_GET['sort'] : 'cs.updated_at';
$order = (strtolower($_GET['order'] ?? '') === 'asc') ? 'ASC' : 'DESC';

// --- 3. Query Construction ---
$sql = "SELECT cs.*, CONCAT(cl.first_name, ' ', cl.last_name) as client_name, u.full_name as investigator 
        FROM cases cs 
        JOIN clients cl ON cs.client_id = cl.client_id 
        LEFT JOIN users u ON cs.assigned_to_user_id = u.user_id 
        WHERE 1=1";

$params = [];
$types = "";

if ($search) {
    $sql .= " AND (cs.case_number LIKE ? OR cs.title LIKE ? OR cl.last_name LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term);
    $types .= "sss";
}
if ($f_client) { $sql .= " AND cs.client_id = ?"; $params[] = $f_client; $types .= "i"; }
if ($f_status) { 
    if ($f_status === 'active') { $sql .= " AND cs.status NOT IN ('Closed', 'Resolved', 'Archived')"; }
    else { $sql .= " AND cs.status = ?"; $params[] = $f_status; $types .= "s"; }
}
if ($f_priority) { $sql .= " AND cs.priority = ?"; $params[] = $f_priority; $types .= "s"; }
if ($f_assigned) {
    $target_id = ($f_assigned === 'me') ? $_SESSION['user_id'] : (int)$f_assigned;
    $sql .= " AND cs.assigned_to_user_id = ?";
    $params[] = $target_id;
    $types .= "i";
}

$sql .= " ORDER BY $sort $order";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$cases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Case <span class="text-primary">Intelligence</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Operational Database Access</p>
        </div>
        <a href="add_case.php" class="bg-primary hover:bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl transition-all flex items-center shadow-lg shadow-primary/20">
            <i class="fas fa-plus-circle mr-2"></i> Initialize New Case
        </a>
    </div>

    <form action="index.php" method="GET" class="bg-slate-900 border border-white/5 p-4 rounded-2xl shadow-2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <div class="lg:col-span-2">
                <label for="search" class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Identifier</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Case #, Title, Client Name..." class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>
            <div>
                <label for="assigned_to" class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Agent</label>
                <select id="assigned_to" name="assigned_to" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All</option>
                    <option value="me" <?= $f_assigned === 'me' ? 'selected' : '' ?>>Assigned to Me</option>
                    <?php foreach ($users_list as $u): ?>
                        <option value="<?= $u['user_id'] ?>" <?= (int)$f_assigned === $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Status</label>
                <select id="status" name="status" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="active" <?= $f_status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="">All</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $f_status === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="priority" class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Priority</label>
                <select id="priority" name="priority" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All</option>
                    <?php foreach ($priorities as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $f_priority === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-black uppercase tracking-[0.2em] py-3 rounded-lg transition-all">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>
    </form>

    <?php if ($cases): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($cases as $c): ?>
                <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-lg hover:shadow-primary/20 hover:border-primary/30 transition-all duration-300 flex flex-col">
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <?= get_status_badge($c['status']) ?>
                            <span class="text-[10px] font-mono text-slate-600">#<?= htmlspecialchars($c['case_number']) ?></span>
                        </div>
                        <a href="view_case.php?id=<?= $c['case_id'] ?>" class="block mb-2">
                            <h3 class="text-md font-black text-white hover:text-primary transition-colors line-clamp-2">
                                <?= htmlspecialchars($c['title']) ?>
                            </h3>
                        </a>
                        <p class="text-xs text-slate-400 line-clamp-1">
                            <span class="font-semibold">Client:</span> <?= htmlspecialchars($c['client_name']) ?>
                        </p>
                    </div>
                    <div class="bg-slate-950/50 border-t border-white/5 px-6 py-3 flex justify-between items-center text-xs">
                        <div class="flex items-center gap-2 text-slate-500">
                            <i class="fas fa-user-shield"></i>
                            <span><?= htmlspecialchars($c['investigator'] ?? 'Unassigned') ?></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="edit_case.php?id=<?= $c['case_id'] ?>" class="text-slate-600 hover:text-amber-500 transition-colors" title="Edit Case"><i class="fas fa-edit"></i></a>
                            <?php if (user_has_role('admin')): ?>
                                <a href="delete_case.php?id=<?= $c['case_id'] ?>&token=<?= htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]) ?>" onclick="return confirm('Archive permanently?')" class="text-slate-600 hover:text-red-500 transition-colors" title="Delete Case"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20 bg-slate-900 border border-white/5 rounded-2xl">
            <i class="fas fa-folder-open text-5xl text-slate-700 mb-4"></i>
            <h3 class="text-lg font-bold text-white">No Matching Records</h3>
            <p class="text-sm text-slate-500 mt-2">No cases were found that match your current filter criteria.</p>
            <a href="index.php" class="mt-6 inline-block bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-widest px-6 py-3 rounded-lg transition-all">
                Clear Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
