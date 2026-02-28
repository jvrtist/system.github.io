<?php
/**
 * ISS Investigations - Admin Clients Index
 * Administrative interface for managing client accounts and portal access.
 */
require_once '../../config.php';
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

include_once '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
            Client <span class="text-primary">Management</span>
        </h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Portal accounts and client oversight</p>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Search and Filters -->
    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
            <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Search & Filter</h2>
        </div>
        <div class="p-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by name, email, or company..."
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                </div>
                <div class="md:w-48">
                    <select name="status" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <option value="">All Statuses</option>
                        <option value="Pending Activation" <?php echo $status_filter === 'Pending Activation' ? 'selected' : ''; ?>>Pending Activation</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Disabled" <?php echo $status_filter === 'Disabled' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider px-6 py-2.5 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['search' => '', 'status' => ''])); ?>"
                       class="bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-white font-semibold px-4 py-2.5 rounded-lg transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="text-sm text-slate-400">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_clients); ?> of <?php echo $total_clients; ?> clients
        </div>
        <div class="flex gap-3">
            <a href="add_client.php" class="bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider px-6 py-2.5 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-user-plus mr-2"></i>Add New Client
            </a>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-slate-900 divide-y divide-slate-800">
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <tr class="hover:bg-slate-800/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-slate-800 border-2 border-primary/20 flex items-center justify-center text-sm font-bold text-primary">
                                            <?php echo htmlspecialchars(strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1))); ?>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-bold text-white">
                                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                            </div>
                                            <div class="text-xs text-slate-400">
                                                ID: <?php echo $client['client_id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-200"><?php echo htmlspecialchars($client['email']); ?></div>
                                    <?php if ($client['phone']): ?>
                                        <div class="text-xs text-slate-500"><?php echo htmlspecialchars($client['phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">
                                    <?php echo htmlspecialchars($client['company_name'] ?: 'Individual'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-bold rounded-full <?php
                                        switch ($client['client_account_status']) {
                                            case 'Active': echo 'bg-green-500/20 text-green-300 border border-green-500/30'; break;
                                            case 'Pending Activation': echo 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30'; break;
                                            case 'Disabled': echo 'bg-red-500/20 text-red-300 border border-red-500/30'; break;
                                            default: echo 'bg-slate-500/20 text-slate-300 border border-slate-500/30';
                                        }
                                    ?>">
                                        <?php echo htmlspecialchars($client['client_account_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                                    <?php echo date('M j, Y', strtotime($client['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="../clients/view_client.php?id=<?php echo $client['client_id']; ?>"
                                           class="text-blue-400 hover:text-blue-300 transition-colors" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_client.php?id=<?php echo $client['client_id']; ?>"
                                           class="text-slate-400 hover:text-white transition-colors" title="Edit Client">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="fas fa-users text-4xl text-slate-700"></i>
                                    <p class="text-slate-400 font-medium">No clients found</p>
                                    <p class="text-xs text-slate-500">
                                        <?php if (!empty($search) || !empty($status_filter)): ?>
                                            Try adjusting your search criteria
                                        <?php else: ?>
                                            <a href="add_client.php" class="text-primary hover:underline">Add your first client</a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

<?php include_once '../../includes/footer.php'; ?>
