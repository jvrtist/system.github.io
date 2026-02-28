<?php
/**
 * ISS Investigations - Admin Clients Index
 * Administrative interface for managing client accounts and portal access.
 */
require_once '../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = 'ACCESS DENIED: Administrative clearance required.';
    redirect('dashboard.php');
}

$page_title = "Client Management";

// Handle search and filtering
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "client_account_status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$conn = get_db_connection();
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients $where_clause");
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_clients = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Get clients with pagination
$sql = "SELECT client_id, first_name, last_name, email, phone, company_name, client_account_status, created_at
        FROM clients $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_pages = ceil($total_clients / $per_page);

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Client <span class="text-primary">Management</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Portal accounts and client oversight</p>
        </div>
        <a href="add_client.php" class="w-full md:w-auto bg-primary hover:bg-orange-600 text-white text-sm font-black uppercase tracking-widest px-6 py-3 rounded-lg transition-all flex items-center justify-center shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30">
            <i class="fas fa-user-plus mr-2"></i> Add New Client
        </a>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form action="index.php" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Client Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Name, email, or company..."
                       class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Status Filter</label>
                <select name="status" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Statuses</option>
                    <option value="Pending Activation" <?php echo $status_filter === 'Pending Activation' ? 'selected' : ''; ?>>Pending Activation</option>
                    <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Disabled" <?php echo $status_filter === 'Disabled' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            <div class="md:col-span-2 flex items-end">
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
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Client Entity</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Contact Details</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Account Status</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Registration</th>
                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-500">Operations</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (!empty($clients)): ?>
                    <?php foreach ($clients as $client): ?>
                        <tr class="hover:bg-white/[0.01] transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-800 border-2 border-primary/20 flex items-center justify-center">
                                        <span class="text-xs font-bold text-primary"><?php echo htmlspecialchars(strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1))); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-black text-white group-hover:text-primary transition-colors"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></span>
                                        <span class="text-[9px] tech-mono text-slate-600 uppercase">ID: <?php echo $client['client_id']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs text-slate-400 font-bold"><?php echo htmlspecialchars($client['email']); ?></span>
                                    <?php if ($client['phone']): ?>
                                        <span class="text-[9px] tech-mono text-slate-600"><?php echo htmlspecialchars($client['phone']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full border text-[9px] font-black uppercase tracking-tighter <?php
                                    switch ($client['client_account_status']) {
                                        case 'Active': echo 'bg-green-500/10 text-green-400 border-green-500/20'; break;
                                        case 'Pending Activation': echo 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'; break;
                                        case 'Disabled': echo 'bg-red-500/10 text-red-400 border-red-500/20'; break;
                                        default: echo 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($client['client_account_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[9px] tech-mono text-slate-300"><?php echo date('Y.m.d', strtotime($client['created_at'])); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="../clients/view_client.php?id=<?php echo $client['client_id']; ?>"
                                       class="text-slate-600 hover:text-primary transition-colors text-xs"><i class="fas fa-eye"></i></a>
                                    <a href="edit_client.php?id=<?php echo $client['client_id']; ?>"
                                       class="text-slate-600 hover:text-amber-500 transition-colors text-xs"><i class="fas fa-edit"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-widest">No client records matching current criteria.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-between">
            <div class="text-sm text-slate-400">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                       class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-colors text-sm">
                        <i class="fas fa-chevron-left mr-1"></i>Previous
                    </a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>"
                       class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-colors text-sm">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="px-2 py-2 text-slate-500">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                       class="px-3 py-2 rounded-lg transition-colors text-sm <?php echo $i === $page ? 'bg-primary text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="px-2 py-2 text-slate-500">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"
                       class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-colors text-sm"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                       class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition-colors text-sm">
                        Next<i class="fas fa-chevron-right ml-1"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
