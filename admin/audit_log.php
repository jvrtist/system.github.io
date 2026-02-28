<?php
// admin/audit_log.php
require_once '../config.php';
require_login();

if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "Unauthorized access level. Admin credentials required.";
    header("Location: ../dashboard.php");
    exit;
}

$page_title = "System Audit Log";
$conn = get_db_connection();

// Fetch unique action types for filter dropdown
$action_types = $conn->query("SELECT DISTINCT action_type FROM audit_log ORDER BY action_type ASC")->fetch_all(MYSQLI_ASSOC);
$users_list = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

// --- Filtering Logic ---
$sql = "SELECT a.*, u.username as user_username, c.first_name, c.last_name 
        FROM audit_log a 
        LEFT JOIN users u ON a.user_id = u.user_id
        LEFT JOIN clients c ON a.client_id = c.client_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($_GET['user_id'])) {
    $sql .= " AND a.user_id = ?";
    $params[] = (int)$_GET['user_id'];
    $types .= "i";
}
if (!empty($_GET['action_type'])) {
    $sql .= " AND a.action_type = ?";
    $params[] = $_GET['action_type'];
    $types .= "s";
}
if (!empty($_GET['start_date'])) {
    $sql .= " AND a.created_at >= ?";
    $params[] = $_GET['start_date'] . ' 00:00:00';
    $types .= "s";
}
if (!empty($_GET['end_date'])) {
    $sql .= " AND a.created_at <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= "s";
}

$sql .= " ORDER BY a.created_at DESC LIMIT 250";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">System <span class="text-primary">Audit Log</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Forensic Record & Traceability</p>
        </div>
        <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-widest px-6 py-3 rounded-xl transition-all no-print">
            <i class="fas fa-print mr-2"></i> Export Log
        </button>
    </header>

    <form method="GET" class="bg-slate-900 border border-white/5 p-6 rounded-2xl shadow-2xl no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Personnel</label>
                <select name="user_id" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Personnel</option>
                    <?php foreach ($users_list as $user): ?>
                        <option value="<?= htmlspecialchars((string)$user['user_id']) ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] == $user['user_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Operation Type</label>
                <select name="action_type" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Operations</option>
                    <?php foreach ($action_types as $type): ?>
                        <option value="<?= htmlspecialchars((string)($type['action_type'] ?? '')) ?>" <?= (isset($_GET['action_type']) && $_GET['action_type'] == $type['action_type']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($type['action_type'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">From Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>

            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">To Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white text-[10px] font-black uppercase tracking-[0.2em] py-3 rounded-lg transition-all shadow-lg shadow-primary/20">
                    Apply Filter
                </button>
            </div>
        </div>
    </form>

    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/[0.02] border-b border-white/5">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Timestamp</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Actor</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Operation</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Target</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500 text-right">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($logs as $log): 
                    // Dynamic color coding for actions
                    $actionClass = 'bg-slate-800 text-slate-400 border-slate-700';
                    if (stripos($log['action_type'], 'delete') !== false) $actionClass = 'bg-red-500/10 text-red-500 border-red-500/20';
                    if (stripos($log['action_type'], 'create') !== false) $actionClass = 'bg-green-500/10 text-green-500 border-green-500/20';
                    if (stripos($log['action_type'], 'login') !== false) $actionClass = 'bg-blue-500/10 text-blue-500 border-blue-500/20';
                    if (stripos($log['action_type'], 'update') !== false) $actionClass = 'bg-amber-500/10 text-amber-500 border-amber-500/20';
                ?>
                    <tr class="hover:bg-white/[0.01] transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-white"><?= date("d M Y", strtotime($log['created_at'])); ?></span>
                                <span class="text-[10px] tech-mono text-slate-600"><?= date("H:i:s", strtotime($log['created_at'])); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full <?= $log['user_id'] ? 'bg-primary' : 'bg-blue-500' ?>"></div>
                                <span class="text-xs font-bold text-slate-300">
                                    <?php 
                                        if ($log['user_id']) echo htmlspecialchars((string)($log['user_username'] ?? ''));
                                        elseif ($log['client_id']) echo htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')));
                                        else echo 'SYSTEM';
                                    ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded border text-[9px] font-black uppercase tracking-tighter <?= $actionClass ?>">
                                <?= htmlspecialchars((string)($log['action_type'] ?? '')); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-400">
                                    <?= htmlspecialchars((string)($log['target_type'] ?? '')); ?> 
                                    <span class="text-slate-600 tech-mono">#<?= htmlspecialchars((string)($log['target_id'] ?? '')); ?></span>
                                </span>
                                <span class="text-[10px] text-slate-600 italic truncate max-w-xs" title="<?= htmlspecialchars((string)($log['details'] ?? '')); ?>">
                                    <?= htmlspecialchars((string)($log['details'] ?? '')); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-[10px] tech-mono text-slate-600"><?= htmlspecialchars((string)($log['ip_address'] ?? '')); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($logs)): ?>
            <div class="py-20 text-center">
                <i class="fas fa-database text-slate-800 text-5xl mb-4"></i>
                <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No entries recorded in current view</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
