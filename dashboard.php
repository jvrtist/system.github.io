<?php
/**
 * ISS Investigations - Command Dashboard
 * Central intelligence hub for investigators and administrators.
 */
require_once 'config.php';
require_login();
require_once 'includes/notifications.php';

// Process automated notifications
process_automated_notifications();

$page_title = "Command Dashboard";
$conn = get_db_connection();
$user_id = $_SESSION['user_id'];

$stats = [
    'active_cases' => 0,
    'my_tasks_pending' => 0,
    'invoices_balance_due' => 0.00,
    'total_clients' => 0,
    'closed_cases' => 0,
    'total_revenue' => 0.00,
    'avg_resolution_days' => 0,
    'cases_this_month' => 0,
    'posts_this_month' => 0,
];
$recent_cases = [];
$overdue_tasks = [];
$case_status_counts = ['Active' => 0, 'Pending' => 0, 'Closed' => 0, 'Resolved' => 0, 'Archived' => 0];
$recent_posts = [];

if ($conn) {
    // 1. Operational Metrics
    $result_cases = $conn->query("SELECT COUNT(*) as count FROM cases WHERE status NOT IN ('Closed', 'Resolved', 'Archived')");
    if ($result_cases) $stats['active_cases'] = $result_cases->fetch_assoc()['count'];

    $stmt_tasks = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_to_user_id = ? AND status = 'Pending'");
    $stmt_tasks->bind_param("i", $user_id);
    $stmt_tasks->execute();
    $stats['my_tasks_pending'] = $stmt_tasks->get_result()->fetch_assoc()['count'];

    $result_inv = $conn->query("SELECT SUM(total_amount - amount_paid) as balance FROM invoices WHERE status IN ('Sent', 'Partially Paid', 'Overdue')");
    $stats['invoices_balance_due'] = $result_inv->fetch_assoc()['balance'] ?? 0.00;

    $result_clients = $conn->query("SELECT COUNT(*) as count FROM clients");
    $stats['total_clients'] = $result_clients->fetch_assoc()['count'];

    $result_closed = $conn->query("SELECT COUNT(*) as count FROM cases WHERE status IN ('Closed', 'Resolved', 'Archived')");
    $stats['closed_cases'] = $result_closed->fetch_assoc()['count'];

    $result_revenue = $conn->query("SELECT SUM(amount_paid) as revenue FROM invoices");
    $stats['total_revenue'] = $result_revenue->fetch_assoc()['revenue'] ?? 0.00;

    // Performance Metrics
    $result_resolution = $conn->query("SELECT AVG(DATEDIFF(updated_at, created_at)) as avg_days FROM cases WHERE status IN ('Closed', 'Resolved') AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)");
    $stats['avg_resolution_days'] = round($result_resolution->fetch_assoc()['avg_days'] ?? 0, 1);

    $result_monthly_cases = $conn->query("SELECT COUNT(*) as count FROM cases WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['cases_this_month'] = $result_monthly_cases->fetch_assoc()['count'];

    $result_monthly_posts = $conn->query("SELECT COUNT(*) as count FROM posts WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status = 'Published'");
    $stats['posts_this_month'] = $result_monthly_posts->fetch_assoc()['count'];

    // Case status counts
    $stmt_status = $conn->query("SELECT status, COUNT(*) as count FROM cases GROUP BY status");
    while ($row = $stmt_status->fetch_assoc()) {
        if (isset($case_status_counts[$row['status']])) {
            $case_status_counts[$row['status']] = $row['count'];
        }
    }

    // 2. Intelligence Feed: Recent Cases
    $stmt_recent = $conn->prepare("SELECT case_id, case_number, title, status, updated_at FROM cases ORDER BY updated_at DESC LIMIT 6");
    $stmt_recent->execute();
    $recent_cases = $stmt_recent->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. High Priority: Overdue Tasks
    $stmt_overdue = $conn->prepare("SELECT t.task_id, t.description, t.due_date, c.case_id, c.case_number 
                                   FROM tasks t JOIN cases c ON t.case_id = c.case_id 
                                   WHERE t.assigned_to_user_id = ? AND t.status != 'Completed' AND t.due_date < CURDATE() 
                                   ORDER BY t.due_date ASC LIMIT 5");
    $stmt_overdue->bind_param("i", $user_id);
    // 4. Recent Blog Posts
    $stmt_posts = $conn->prepare("SELECT post_id, title, view_count, created_at FROM posts WHERE status = 'Published' AND (publish_at IS NULL OR publish_at <= NOW()) ORDER BY created_at DESC LIMIT 5");
    $stmt_posts->execute();
    $recent_posts = $stmt_posts->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_posts->close();

    $conn->close();
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Operational <span class="text-primary">Overview</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">
                Agent: <span class="text-slate-300"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span> &bull; Status: <span class="text-green-500">Active</span>
            </p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden md:block">
                <p class="text-[10px] font-black uppercase text-slate-600 tracking-[0.2em]">System Date</p>
                <p class="text-sm font-bold text-white tech-mono"><?= date('Y-m-d H:i'); ?></p>
            </div>
            <!-- Quick Search -->
            <div class="relative hidden md:block">
                <input type="text" id="quickSearch" placeholder="Search cases, clients..." 
                       class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 w-64">
                <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>
            <a href="<?= BASE_URL; ?>cases/add_case.php" class="bg-primary hover:bg-orange-600 text-white text-xs font-black uppercase tracking-widest py-3 px-6 rounded-xl shadow-lg shadow-primary/20 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i> New Case
            </a>
        </div>
    </header>

    <!-- Welcome Message -->
    <div class="bg-gradient-to-r from-primary/10 to-blue-500/10 border border-primary/20 rounded-2xl p-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-20">
            <i class="fas fa-shield-alt text-6xl text-primary"></i>
        </div>
        <div class="relative z-10">
            <h2 class="text-2xl font-black text-white mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p class="text-slate-300 mb-4">
                <?php
                $hour = (int)date('H');
                if ($hour < 12) {
                    echo "Good morning. Ready to tackle today's intelligence operations?";
                } elseif ($hour < 17) {
                    echo "Good afternoon. Your operational dashboard is updated with the latest metrics.";
                } else {
                    echo "Good evening. Review your case progress and prepare for tomorrow's objectives.";
                }
                ?>
            </p>
            <div class="flex items-center gap-4 text-sm">
                <span class="flex items-center gap-2 text-slate-400">
                    <i class="fas fa-calendar text-primary"></i>
                    <?php echo date('l, F j, Y'); ?>
                </span>
                <span class="flex items-center gap-2 text-slate-400">
                    <i class="fas fa-clock text-primary"></i>
                    <?php echo date('H:i'); ?> UTC+2
                </span>
                <span class="flex items-center gap-2 text-green-400">
                    <i class="fas fa-circle text-xs animate-pulse"></i>
                    System Status: Operational
                </span>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
        <!-- Active Cases -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-primary/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-folder-tree fa-4x text-primary"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Active Intel</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none"><?= $stats['active_cases']; ?></p>
                <span class="text-[10px] font-bold text-primary uppercase">Cases</span>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-primary w-3/4 rounded-full"></div>
            </div>
        </div>

        <!-- Pending Tasks -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-blue-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-list-check fa-4x text-blue-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">My Tasks</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none"><?= $stats['my_tasks_pending']; ?></p>
                <span class="text-[10px] font-bold text-blue-500 uppercase">Pending</span>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-blue-500 w-1/2 rounded-full"></div>
            </div>
        </div>

        <!-- Average Resolution Time -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-green-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-clock fa-4x text-green-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Avg Resolution</p>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black text-white leading-none"><?= $stats['avg_resolution_days']; ?></p>
                <span class="text-[10px] font-bold text-green-500 uppercase">Days</span>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 w-4/5 rounded-full"></div>
            </div>
        </div>

        <!-- Cases This Month -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-purple-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-calendar-plus fa-4x text-purple-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">This Month</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none"><?= $stats['cases_this_month']; ?></p>
                <span class="text-[10px] font-bold text-purple-500 uppercase">New Cases</span>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 w-full rounded-full"></div>
            </div>
        </div>

        <!-- Posts This Month -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-yellow-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-blog fa-4x text-yellow-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Content Published</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none"><?= $stats['posts_this_month']; ?></p>
                <span class="text-[10px] font-bold text-yellow-500 uppercase">Posts</span>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-yellow-500 w-2/3 rounded-full"></div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-yellow-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-dollar-sign fa-4x text-yellow-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Total Revenue</p>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black text-white leading-none">R<?= number_format($stats['total_revenue'], 0); ?></p>
                <span class="text-[10px] font-bold text-yellow-500 uppercase">Earned</span>
            </div>
            <div class="mt-4 h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-yellow-500 w-4/5 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Recent Blog Posts -->
    <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-blog text-primary"></i>
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-300">Recent Intelligence Posts</h2>
            </div>
            <a href="admin/posts/" class="text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">Manage Posts</a>
        </div>
        <div class="space-y-4">
            <?php if (!empty($recent_posts)): ?>
                <?php foreach ($recent_posts as $post): ?>
                    <a href="admin/posts/view_post.php?id=<?= $post['post_id']; ?>" class="flex items-center gap-4 p-4 rounded-xl hover:bg-white/[0.02] transition-colors group">
                        <div class="hidden sm:flex flex-col items-center justify-center w-10 h-10 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-primary/30 group-hover:text-primary transition-all">
                            <i class="fas fa-file-alt text-sm"></i>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] tech-mono text-slate-500">#<?= $post['post_id']; ?></span>
                                <span class="text-[10px] text-slate-500 flex items-center gap-1">
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($post['view_count']); ?>
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-white truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($post['title']); ?></h3>
                            <p class="text-[10px] text-slate-600 mt-0.5">Published: <?= date('d M Y @ H:i', strtotime($post['created_at'])); ?></p>
                        </div>
                        <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-edit text-slate-700 text-3xl mb-3"></i>
                    <p class="text-[10px] tech-mono text-slate-600 uppercase">No published posts yet.</p>
                    <a href="admin/posts/add_post.php" class="text-primary hover:text-orange-400 text-xs font-bold mt-2 inline-block">Create your first post</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Alerts & Actions -->
        <div class="space-y-8">
            <!-- Critical Alerts -->
            <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                <div class="bg-red-500/10 px-6 py-4 border-b border-red-500/20 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                        <h2 class="text-xs font-black uppercase tracking-widest text-red-500">Critical Alerts</h2>
                    </div>
                    <span class="text-[10px] tech-mono bg-red-500/20 text-red-400 px-2 py-0.5 rounded border border-red-500/30"><?= count($overdue_tasks); ?></span>
                </div>
                <div class="p-2">
                    <ul class="space-y-1">
                        <?php if (!empty($overdue_tasks)): ?>
                            <?php foreach($overdue_tasks as $task): ?>
                                <li>
                                    <a href="<?= BASE_URL; ?>cases/view_case.php?id=<?= $task['case_id']; ?>" class="block p-3 rounded-lg hover:bg-white/[0.03] transition-colors group">
                                        <div class="flex justify-between items-start mb-1">
                                            <span class="text-[9px] tech-mono text-slate-500 uppercase">Case #<?= htmlspecialchars($task['case_number']); ?></span>
                                            <span class="text-[9px] font-bold text-red-500 uppercase">Overdue</span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-300 group-hover:text-white transition-colors line-clamp-1"><?= htmlspecialchars($task['description']); ?></p>
                                        <p class="text-[9px] text-slate-600 mt-1">Due: <?= date("d M Y", strtotime($task['due_date'])); ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="py-8 text-center">
                                <i class="fas fa-check-circle text-green-500/50 text-3xl mb-3"></i>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-600">All Systems Nominal</p>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="space-y-3">
                <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 ml-2">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="<?= BASE_URL; ?>clients/add_client.php" class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-800 hover:bg-slate-700 border border-white/5 transition-all group">
                        <i class="fas fa-user-plus text-slate-400 group-hover:text-white mb-2 text-lg"></i>
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-500 group-hover:text-white">Add Client</span>
                    </a>
                    <a href="<?= BASE_URL; ?>invoices/add_invoice.php" class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-800 hover:bg-slate-700 border border-white/5 transition-all group">
                        <i class="fas fa-file-invoice-dollar text-slate-400 group-hover:text-white mb-2 text-lg"></i>
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-500 group-hover:text-white">New Invoice</span>
                    </a>
                </div>
            </section>
        </div>

        <!-- Right Column: Live Feed -->
        <div class="lg:col-span-2">
            <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl h-full">
                <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-satellite-dish text-primary animate-pulse"></i>
                        <h2 class="text-xs font-black uppercase tracking-widest text-slate-300">Live Case Intelligence</h2>
                    </div>
                    <a href="<?= BASE_URL; ?>cases/" class="text-[9px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">View All</a>
                </div>
                <div class="divide-y divide-white/5">
                    <?php if (!empty($recent_cases)): ?>
                        <?php foreach ($recent_cases as $case): 
                            $statusColor = 'text-slate-500';
                            $statusBg = 'bg-slate-500/10 border-slate-500/20';
                            
                            if($case['status'] == 'Active') {
                                $statusColor = 'text-green-400';
                                $statusBg = 'bg-green-500/10 border-green-500/20';
                            } elseif($case['status'] == 'Pending') {
                                $statusColor = 'text-amber-400';
                                $statusBg = 'bg-amber-500/10 border-amber-500/20';
                            }
                        ?>
                            <a href="<?= BASE_URL; ?>cases/view_case.php?id=<?= $case['case_id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors group">
                                <div class="hidden sm:flex flex-col items-center justify-center w-12 h-12 rounded-lg bg-slate-800 border border-white/5 text-slate-500 group-hover:border-primary/30 group-hover:text-primary transition-all">
                                    <i class="fas fa-folder-open text-lg"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] tech-mono text-slate-500">#<?= htmlspecialchars($case['case_number']); ?></span>
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border <?= $statusBg ?> <?= $statusColor ?>"><?= htmlspecialchars($case['status']) ?></span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($case['title']); ?></h3>
                                    <p class="text-[10px] text-slate-600 mt-0.5">Updated: <?= date('d M Y @ H:i', strtotime($case['updated_at'])); ?></p>
                                </div>
                                <i class="fas fa-chevron-right text-slate-700 group-hover:text-white transition-colors text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-20 text-center">
                            <i class="fas fa-wind text-slate-700 text-4xl mb-4"></i>
                            <p class="text-xs tech-mono text-slate-600 uppercase">No recent activity detected.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('caseStatusChart').getContext('2d');
    const data = {
        labels: ['Active', 'Pending', 'Closed', 'Resolved', 'Archived'],
        datasets: [{
            data: [<?php echo $case_status_counts['Active']; ?>, <?php echo $case_status_counts['Pending']; ?>, <?php echo $case_status_counts['Closed']; ?>, <?php echo $case_status_counts['Resolved']; ?>, <?php echo $case_status_counts['Archived']; ?>],
            backgroundColor: [
                '#f78f18',
                '#3b82f6',
                '#10b981',
                '#8b5cf6',
                '#f59e0b'
            ],
            borderWidth: 0
        }]
    };
    const config = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#94a3b8',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    };
    new Chart(ctx, config);

    // Quick Search Functionality
    const quickSearch = document.getElementById('quickSearch');
    if (quickSearch) {
        quickSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    // Search cases first, then clients if no results
                    window.location.href = `<?= BASE_URL; ?>cases/?search=${encodeURIComponent(query)}`;
                }
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
