<?php
// admin/search.php - Global Search Functionality
require_once '../config.php';
require_login();

$page_title = "Search Results";
$search_query = trim($_GET['q'] ?? '');
$search_type = $_GET['type'] ?? 'all';
$results = [];
$stats = ['cases' => 0, 'clients' => 0, 'posts' => 0, 'invoices' => 0, 'tasks' => 0];

if (!empty($search_query) && strlen($search_query) >= 2) {
    $conn = get_db_connection();
    $search_term = '%' . $conn->real_escape_string($search_query) . '%';

    // Search Cases
    if ($search_type === 'all' || $search_type === 'cases') {
        $stmt = $conn->prepare("
            SELECT case_id, case_number, title, status, created_at, client_id,
                   (SELECT company_name FROM clients WHERE client_id = cases.client_id) as client_name
            FROM cases
            WHERE case_number LIKE ? OR title LIKE ? OR description LIKE ?
            ORDER BY updated_at DESC LIMIT 10
        ");
        $stmt->bind_param("sss", $search_term, $search_term, $search_term);
        $stmt->execute();
        $results['cases'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stats['cases'] = count($results['cases']);
    }

    // Search Clients
    if ($search_type === 'all' || $search_type === 'clients') {
        $stmt = $conn->prepare("
            SELECT client_id, company_name, email, phone, created_at
            FROM clients
            WHERE company_name LIKE ? OR email LIKE ? OR phone LIKE ?
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->bind_param("sss", $search_term, $search_term, $search_term);
        $stmt->execute();
        $results['clients'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stats['clients'] = count($results['clients']);
    }

    // Search Posts
    if ($search_type === 'all' || $search_type === 'posts') {
        $stmt = $conn->prepare("
            SELECT post_id, title, excerpt, status, created_at, view_count
            FROM posts
            WHERE title LIKE ? OR content LIKE ? OR excerpt LIKE ? OR keywords LIKE ?
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
        $stmt->execute();
        $results['posts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stats['posts'] = count($results['posts']);
    }

    // Search Invoices
    if ($search_type === 'all' || $search_type === 'invoices') {
        $stmt = $conn->prepare("
            SELECT i.invoice_id, i.invoice_number, i.total_amount, i.status, i.created_at,
                   c.company_name
            FROM invoices i
            LEFT JOIN clients c ON i.client_id = c.client_id
            WHERE i.invoice_number LIKE ? OR i.description LIKE ?
            ORDER BY i.created_at DESC LIMIT 10
        ");
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $results['invoices'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stats['invoices'] = count($results['invoices']);
    }

    // Search Tasks
    if ($search_type === 'all' || $search_type === 'tasks') {
        $stmt = $conn->prepare("
            SELECT t.task_id, t.description, t.status, t.due_date, t.created_at,
                   c.case_id, c.case_number, c.title as case_title,
                   u.full_name as assigned_to_name
            FROM tasks t
            LEFT JOIN cases c ON t.case_id = c.case_id
            LEFT JOIN users u ON t.assigned_to_user_id = u.user_id
            WHERE t.description LIKE ?
            ORDER BY t.created_at DESC LIMIT 10
        ");
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $results['tasks'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stats['tasks'] = count($results['tasks']);
    }

    $conn->close();
}

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Search Header -->
    <div class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-search text-primary"></i>
                <div>
                    <h1 class="text-2xl font-black text-white">Search Results</h1>
                    <p class="text-slate-400 text-sm">Search query: "<span class="text-white"><?php echo htmlspecialchars($search_query); ?></span>"</p>
                </div>
            </div>

            <!-- Search Form -->
            <form method="GET" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Search cases, clients, posts, invoices, tasks..."
                           class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50">
                </div>
                <select name="type" class="bg-slate-800 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50">
                    <option value="all" <?php echo $search_type === 'all' ? 'selected' : ''; ?>>All Content</option>
                    <option value="cases" <?php echo $search_type === 'cases' ? 'selected' : ''; ?>>Cases</option>
                    <option value="clients" <?php echo $search_type === 'clients' ? 'selected' : ''; ?>>Clients</option>
                    <option value="posts" <?php echo $search_type === 'posts' ? 'selected' : ''; ?>>Posts</option>
                    <option value="invoices" <?php echo $search_type === 'invoices' ? 'selected' : ''; ?>>Invoices</option>
                    <option value="tasks" <?php echo $search_type === 'tasks' ? 'selected' : ''; ?>>Tasks</option>
                </select>
                <button type="submit" class="bg-primary hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
            </form>

            <!-- Results Summary -->
            <?php if (!empty($search_query)): ?>
                <div class="mt-6 flex items-center gap-6 text-sm">
                    <span class="text-slate-400">Results found:</span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-folder-tree text-primary"></i>
                        <span class="text-white"><?php echo $stats['cases']; ?> Cases</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-users text-purple-500"></i>
                        <span class="text-white"><?php echo $stats['clients']; ?> Clients</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-blog text-yellow-500"></i>
                        <span class="text-white"><?php echo $stats['posts']; ?> Posts</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-file-invoice-dollar text-green-500"></i>
                        <span class="text-white"><?php echo $stats['invoices']; ?> Invoices</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <i class="fas fa-tasks text-blue-500"></i>
                        <span class="text-white"><?php echo $stats['tasks']; ?> Tasks</span>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($search_query)): ?>
        <!-- No Search Yet -->
        <div class="bg-slate-900 rounded-2xl border border-white/5 p-12 text-center">
            <i class="fas fa-search text-slate-600 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-slate-300 mb-2">Ready to Search</h3>
            <p class="text-slate-500">Enter a search term above to find cases, clients, posts, invoices, and tasks.</p>
        </div>
    <?php elseif (empty(array_filter($results))): ?>
        <!-- No Results -->
        <div class="bg-slate-900 rounded-2xl border border-white/5 p-12 text-center">
            <i class="fas fa-search text-slate-600 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-slate-300 mb-2">No Results Found</h3>
            <p class="text-slate-500">Try adjusting your search terms or search in a different category.</p>
        </div>
    <?php else: ?>
        <!-- Results Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Cases Results -->
            <?php if (!empty($results['cases'])): ?>
                <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-folder-tree text-primary"></i>
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Cases (<?php echo $stats['cases']; ?>)</h2>
                        </div>
                        <a href="cases/?search=<?php echo urlencode($search_query); ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">View All</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        <?php foreach ($results['cases'] as $case): ?>
                            <a href="cases/view_case.php?id=<?php echo $case['case_id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors group">
                                <div class="hidden sm:flex flex-col items-center justify-center w-10 h-10 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-primary/30 group-hover:text-primary transition-all">
                                    <i class="fas fa-folder-open text-sm"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] tech-mono text-slate-500">#<?php echo htmlspecialchars($case['case_number']); ?></span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded border
                                            <?php
                                            if ($case['status'] == 'Active') echo 'bg-green-500/10 border-green-500/20 text-green-400';
                                            elseif ($case['status'] == 'Pending') echo 'bg-amber-500/10 border-amber-500/20 text-amber-400';
                                            else echo 'bg-slate-500/10 border-slate-500/20 text-slate-400';
                                            ?>">
                                            <?php echo htmlspecialchars($case['status']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-primary transition-colors"><?php echo htmlspecialchars($case['title']); ?></h3>
                                    <p class="text-[10px] text-slate-600 mt-0.5">
                                        Client: <?php echo htmlspecialchars($case['client_name'] ?? 'N/A'); ?> •
                                        Created: <?php echo date('M j, Y', strtotime($case['created_at'])); ?>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Clients Results -->
            <?php if (!empty($results['clients'])): ?>
                <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-users text-purple-500"></i>
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Clients (<?php echo $stats['clients']; ?>)</h2>
                        </div>
                        <a href="clients/?search=<?php echo urlencode($search_query); ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">View All</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        <?php foreach ($results['clients'] as $client): ?>
                            <a href="clients/view_client.php?id=<?php echo $client['client_id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors group">
                                <div class="hidden sm:flex flex-col items-center justify-center w-10 h-10 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-purple-500/30 group-hover:text-purple-500 transition-all">
                                    <i class="fas fa-user text-sm"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-purple-500 transition-colors"><?php echo htmlspecialchars($client['company_name']); ?></h3>
                                    <p class="text-[10px] text-slate-600 mt-0.5">
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Posts Results -->
            <?php if (!empty($results['posts'])): ?>
                <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-blog text-yellow-500"></i>
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Posts (<?php echo $stats['posts']; ?>)</h2>
                        </div>
                        <a href="posts/?search=<?php echo urlencode($search_query); ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">View All</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        <?php foreach ($results['posts'] as $post): ?>
                            <a href="posts/view_post.php?id=<?php echo $post['post_id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors group">
                                <div class="hidden sm:flex flex-col items-center justify-center w-10 h-10 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-yellow-500/30 group-hover:text-yellow-500 transition-all">
                                    <i class="fas fa-file-alt text-sm"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] tech-mono text-slate-500">#<?php echo $post['post_id']; ?></span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded border
                                            <?php echo $post['status'] == 'Published' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-slate-500/10 border-slate-500/20 text-slate-400'; ?>">
                                            <?php echo htmlspecialchars($post['status']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-yellow-500 transition-colors"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p class="text-[10px] text-slate-600 mt-0.5">
                                        Views: <?php echo number_format($post['view_count']); ?> •
                                        Created: <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Invoices Results -->
            <?php if (!empty($results['invoices'])): ?>
                <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-file-invoice-dollar text-green-500"></i>
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Invoices (<?php echo $stats['invoices']; ?>)</h2>
                        </div>
                        <a href="../invoices/?search=<?php echo urlencode($search_query); ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">View All</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        <?php foreach ($results['invoices'] as $invoice): ?>
                            <a href="../invoices/view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors group">
                                <div class="hidden sm:flex flex-col items-center justify-center w-10 h-10 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-green-500/30 group-hover:text-green-500 transition-all">
                                    <i class="fas fa-receipt text-sm"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] tech-mono text-slate-500">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded border
                                            <?php
                                            if ($invoice['status'] == 'Paid') echo 'bg-green-500/10 border-green-500/20 text-green-400';
                                            elseif ($invoice['status'] == 'Sent') echo 'bg-blue-500/10 border-blue-500/20 text-blue-400';
                                            else echo 'bg-slate-500/10 border-slate-500/20 text-slate-400';
                                            ?>">
                                            <?php echo htmlspecialchars($invoice['status']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-green-500 transition-colors">R<?php echo number_format($invoice['total_amount'], 2); ?></h3>
                                    <p class="text-[10px] text-slate-600 mt-0.5">
                                        Client: <?php echo htmlspecialchars($invoice['company_name'] ?? 'N/A'); ?> •
                                        Created: <?php echo date('M j, Y', strtotime($invoice['created_at'])); ?>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Tasks Results -->
            <?php if (!empty($results['tasks'])): ?>
                <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-tasks text-blue-500"></i>
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Tasks (<?php echo $stats['tasks']; ?>)</h2>
                        </div>
                        <a href="../tasks/?search=<?php echo urlencode($search_query); ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">View All</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        <?php foreach ($results['tasks'] as $task): ?>
                            <a href="../cases/view_case.php?id=<?php echo $task['case_id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors group">
                                <div class="hidden sm:flex flex-col items-center justify-center w-10 h-10 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-blue-500/30 group-hover:text-blue-500 transition-all">
                                    <i class="fas fa-list-check text-sm"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded border
                                            <?php echo $task['status'] == 'Completed' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-slate-500/10 border-slate-500/20 text-slate-400'; ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-blue-500 transition-colors"><?php echo htmlspecialchars($task['description']); ?></h3>
                                    <p class="text-[10px] text-slate-600 mt-0.5">
                                        Case: <?php echo htmlspecialchars($task['case_number']); ?> •
                                        Due: <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
