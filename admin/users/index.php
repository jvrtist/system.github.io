<?php
/**
 * ISS Investigations - User Management Console
 * Secure administration of system user accounts, roles, and permissions.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$page_title = "User Management";

$conn = get_db_connection();
$users = [];

// --- Filtering Logic ---
$sql = "SELECT user_id, username, email, full_name, role, created_at FROM users WHERE 1=1";

$params = [];
$types = "";

if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if (!empty($_GET['role'])) {
    $sql .= " AND role = ?";
    $params[] = $_GET['role'];
    $types .= "s";
}

$sql .= " ORDER BY username ASC";

$result = $conn->prepare($sql);
if (!empty($params)) {
    $result->bind_param($types, ...$params);
}
$result->execute();
$users = $result->get_result()->fetch_all(MYSQLI_ASSOC);
$result->close();

if (!$users) {
    $_SESSION['error_message'] = "Error fetching users: " . $conn->error;
}
$conn->close();

include_once '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">User <span class="text-primary">Management</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Administer system user accounts and permissions</p>
        </div>
        <a href="add_user.php" class="w-full md:w-auto bg-primary hover:bg-orange-600 text-white text-sm font-black uppercase tracking-widest px-6 py-3 rounded-lg transition-all flex items-center justify-center shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30">
            <i class="fas fa-user-plus mr-2"></i> Add New User
        </a>
    </header>

    <!-- Search Form -->
    <form method="GET" class="bg-slate-900 border border-white/5 p-6 rounded-2xl shadow-2xl">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Search Users</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Username, name, or email" class="w-full bg-slate-950 border border-white/10 rounded-lg pl-12 pr-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Role Filter</label>
                <select name="role" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Roles</option>
                    <option value="admin" <?= (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="investigator" <?= (isset($_GET['role']) && $_GET['role'] == 'investigator') ? 'selected' : '' ?>>Investigator</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-primary hover:bg-orange-600 text-white text-[10px] font-black uppercase tracking-[0.2em] py-3 rounded-lg transition-all shadow-lg shadow-primary/20">
                    Search
                </button>
                <a href="index.php" class="bg-slate-700 hover:bg-slate-600 text-white text-[10px] font-black uppercase tracking-[0.2em] px-4 py-3 rounded-lg transition-all">Clear</a>
            </div>
        </div>
    </form>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-2xl overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/[0.02] border-b border-white/5">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Username</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Full Name</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Email</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Role</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Date Created</th>
                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-white/[0.01] transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-primary group-hover:text-orange-400 transition-colors"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="text-[9px] tech-mono text-slate-600 uppercase">ID: <?php echo (int)$user['user_id']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-200"><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td class="px-6 py-4">
                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-xs text-slate-300 hover:text-primary transition-colors"><?php echo htmlspecialchars($user['email']); ?></a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-black uppercase px-2.5 py-1 rounded-full <?php echo $user['role'] === 'admin' ? 'bg-red-500/20 text-red-300 border border-red-500/50' : 'bg-blue-500/20 text-blue-300 border border-blue-500/50'; ?> border">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400 tech-mono"><?php echo date("Y.m.d", strtotime($user['created_at'])); ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="edit_user.php?id=<?php echo (int)$user['user_id']; ?>" class="text-slate-600 hover:text-primary transition-colors text-xs" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['user_id'] != $_SESSION['user_id'] && $user['user_id'] != 1): ?>
                                        <a href="delete_user.php?id=<?php echo (int)$user['user_id']; ?>&token=<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>" class="text-slate-600 hover:text-red-400 transition-colors text-xs" title="Delete User" onclick="return confirm('Delete user \'<?php echo htmlspecialchars(addslashes($user['username'])); ?>\'? This cannot be undone.');"><i class="fas fa-trash-alt"></i></a>
                                    <?php else: ?>
                                        <span class="text-slate-700 cursor-not-allowed text-xs" title="Cannot delete self or primary admin"><i class="fas fa-trash-alt"></i></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No users found in system.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include_once '../../includes/footer.php'; // Adjust path for includes
?>
