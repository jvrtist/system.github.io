<?php
// client_portal/dashboard.php
require_once '../config.php';
require_once 'client_auth.php';

$page_title = "Dashboard";
$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
$private_key_verified = isset($_SESSION['private_key_verified']) && $_SESSION['private_key_verified'] === true;
$conn = get_db_connection();

// --- Initialize data arrays ---
$stats = ['open_cases' => 0, 'unread_messages' => 0, 'unpaid_balance' => 0.00, 'total_paid' => 0.00, 'closed_cases' => 0];
$timeline_items = [];
$active_cases = [];
$case_status_counts = ['Active' => 0, 'Pending' => 0, 'Closed' => 0, 'Resolved' => 0, 'Archived' => 0];
$recent_posts = [];

if ($conn) {
    // Only fetch sensitive data if private key is verified
    if ($private_key_verified) {
        // 1. Fetch Key Stats
        // Open Cases Count
        $stmt_open_cases = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE client_id = ? AND status NOT IN ('Closed', 'Resolved', 'Archived')");
        $stmt_open_cases->bind_param("i", $client_id);
        $stmt_open_cases->execute();
        $stats['open_cases'] = $stmt_open_cases->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt_open_cases->close();

        // Unread Messages Count
        $stmt_unread = $conn->prepare("SELECT COUNT(*) as count FROM client_messages WHERE client_id = ? AND sent_by_client = FALSE AND is_read_by_client = FALSE");
        $stmt_unread->bind_param("i", $client_id);
        $stmt_unread->execute();
        $stats['unread_messages'] = $stmt_unread->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt_unread->close();

        // Unpaid Invoices Balance
        $stmt_balance = $conn->prepare("SELECT SUM(total_amount - amount_paid) as balance FROM invoices WHERE client_id = ? AND status IN ('Sent', 'Partially Paid', 'Overdue')");
        $stmt_balance->bind_param("i", $client_id);
        $stmt_balance->execute();
        $stats['unpaid_balance'] = $stmt_balance->get_result()->fetch_assoc()['balance'] ?? 0.00;
        $stmt_balance->close();

        // Total Paid Amount
        $stmt_paid = $conn->prepare("SELECT SUM(amount_paid) as paid FROM invoices WHERE client_id = ?");
        $stmt_paid->bind_param("i", $client_id);
        $stmt_paid->execute();
        $stats['total_paid'] = $stmt_paid->get_result()->fetch_assoc()['paid'] ?? 0.00;
        $stmt_paid->close();

        // Closed Cases Count
        $stmt_closed = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE client_id = ? AND status IN ('Closed', 'Resolved', 'Archived')");
        $stmt_closed->bind_param("i", $client_id);
        $stmt_closed->execute();
        $stats['closed_cases'] = $stmt_closed->get_result()->fetch_assoc()['count'] ?? 0;
        $stmt_closed->close();

        // Case status counts for client
        $stmt_status = $conn->prepare("SELECT status, COUNT(*) as count FROM cases WHERE client_id = ? GROUP BY status");
        $stmt_status->bind_param("i", $client_id);
        $stmt_status->execute();
        $result_status = $stmt_status->get_result();
        while ($row = $result_status->fetch_assoc()) {
            if (isset($case_status_counts[$row['status']])) {
                $case_status_counts[$row['status']] = $row['count'];
            }
        }
        $stmt_status->close();

        // 2. Fetch Active Cases (non-closed)
        $stmt_cases = $conn->prepare("SELECT case_id, case_number, title, status FROM cases WHERE client_id = ? AND status NOT IN ('Closed', 'Resolved', 'Archived') ORDER BY updated_at DESC LIMIT 5");
        $stmt_cases->bind_param("i", $client_id);
        $stmt_cases->execute();
        $active_cases = $stmt_cases->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_cases->close();

        // 3. Fetch Data for Unified Timeline
        $stmt_timeline = $conn->prepare("
            (SELECT
                'note' as type,
                cn.note_text COLLATE utf8mb4_general_ci as content,
                cn.created_at as event_date,
                c.case_id,
                c.title COLLATE utf8mb4_general_ci as case_title,
                u.full_name COLLATE utf8mb4_general_ci as author
            FROM case_notes cn
            JOIN cases c ON cn.case_id = c.case_id
            LEFT JOIN users u ON cn.user_id = u.user_id
            WHERE c.client_id = ? AND cn.visibility = 'Client Visible')
            UNION ALL
            (SELECT
                'document' as type,
                d.description COLLATE utf8mb4_general_ci as content,
                d.uploaded_at as event_date,
                c.case_id,
                c.title COLLATE utf8mb4_general_ci as case_title,
                u.full_name COLLATE utf8mb4_general_ci as author
            FROM documents d
            JOIN cases c ON d.case_id = c.case_id
            LEFT JOIN users u ON d.uploaded_by_user_id = u.user_id
            WHERE c.client_id = ? AND d.visibility = 'Client Visible')
            UNION ALL
            (SELECT
                'message' as type,
            cm.message_subject COLLATE utf8mb4_general_ci as content,
            cm.sent_at as event_date,
            c.case_id,
            c.title COLLATE utf8mb4_general_ci as case_title,
            CASE WHEN cm.sent_by_client = TRUE THEN 'You' ELSE u.full_name END COLLATE utf8mb4_general_ci as author
        FROM client_messages cm
        JOIN cases c ON cm.case_id = c.case_id
        LEFT JOIN users u ON cm.user_id = u.user_id
        WHERE c.client_id = ?)
        ORDER BY event_date DESC
        LIMIT 7
    ");
    $stmt_timeline->bind_param("iii", $client_id, $client_id, $client_id);
    $stmt_timeline->execute();
    $timeline_items = $stmt_timeline->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_timeline->close();

    // 4. Recent Blog Posts
    $stmt_posts = $conn->prepare("SELECT post_id, title, excerpt, view_count, created_at FROM posts WHERE status = 'Published' AND (publish_at IS NULL OR publish_at <= NOW()) ORDER BY created_at DESC LIMIT 4");
    $stmt_posts->execute();
    $recent_posts = $stmt_posts->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_posts->close();

    $conn->close();
} else {
    // Private key not verified - only fetch public content (blog posts)
    // 4. Recent Blog Posts
    $stmt_posts = $conn->prepare("SELECT post_id, title, excerpt, view_count, created_at FROM posts WHERE status = 'Published' AND (publish_at IS NULL OR publish_at <= NOW()) ORDER BY created_at DESC LIMIT 4");
    $stmt_posts->execute();
    $recent_posts = $stmt_posts->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_posts->close();

    $conn->close();
} // Added the missing closing brace here

function get_timeline_item_details($type) {
    switch ($type) {
        case 'note':
            return ['icon' => 'fas fa-sticky-note', 'color' => 'bg-yellow-100 text-yellow-700'];
        case 'document':
            return ['icon' => 'fas fa-file-alt', 'color' => 'bg-green-100 text-green-700'];
        case 'message':
            return ['icon' => 'fas fa-comments', 'color' => 'bg-blue-100 text-blue-700'];
        default:
            return ['icon' => 'fas fa-question-circle', 'color' => 'bg-slate-100 text-slate-700'];
    }
}

include_once 'client_header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6 animate-fade-in-up">
        <h1 class="text-3xl font-black text-secondary mb-2">Welcome, <?php echo htmlspecialchars($_SESSION[CLIENT_NAME_SESSION_VAR]); ?>!</h1>
        <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-[0.2em] mt-1">Here's a summary of your account and recent activity.</p>
		
    </header>

    <!-- Welcome Message -->
    <div class="card-premium p-8 relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-primary/5 animate-fade-in-up" style="animation-delay: 0.1s">
        <div class="absolute top-0 right-0 p-6 opacity-10">
            <i class="fas fa-user-shield text-6xl text-primary animate-pulse-slow"></i>
        </div>
        <div class="absolute -bottom-16 -left-16 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
        <div class="relative z-10">
            <p class="text-slate-600 mb-6 leading-relaxed">
                <?php
                $hour = (int)date('H');
                if ($hour < 12) {
                    echo "Good morning. Here's your latest case activity and account overview.";
                } elseif ($hour < 17) {
                    echo "Good afternoon. Check your case progress and any new updates.";
                } else {
                    echo "Good evening. Review your investigation status before signing off.";
                }
                ?>
            </p>
            <div class="flex items-center gap-6 text-sm">
                <span class="flex items-center gap-2 text-slate-500">
                    <i class="fas fa-calendar text-primary"></i>
                    <?php echo date('l, F j, Y'); ?>
                </span>
                <span class="flex items-center gap-2 text-slate-500">
                    <i class="fas fa-clock text-primary"></i>
                    <?php echo date('H:i'); ?> UTC+2
                </span>
                <span class="flex items-center gap-2 text-green-600 font-semibold">
                    <i class="fas fa-circle text-xs animate-pulse-slow"></i>
                    Account Status: Active
                </span>
            </div>
        </div>
        </div>

    <?php if (isset($_SESSION['client_success_message'])): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-green-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <p class="text-green-800 font-semibold"><?php echo htmlspecialchars($_SESSION['client_success_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['client_success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['client_error_message'])): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <p class="text-red-800 font-semibold"><?php echo htmlspecialchars($_SESSION['client_error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['client_error_message']); ?>
    <?php endif; ?>

    <!-- Private Key Verification Notice -->
    <?php if (!isset($_SESSION['private_key_verified']) || $_SESSION['private_key_verified'] !== true): ?>
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-3xl p-6 mb-8 animate-fade-in-up">
            <div class="flex items-start gap-4">
                <div class="p-3 rounded-2xl bg-amber-100 flex-shrink-0">
                    <i class="fas fa-key text-amber-600 text-xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-black text-amber-900 mb-2">Private Key Verification Required</h3>
                    <p class="text-amber-800 text-sm mb-4 leading-relaxed">
                        To access case details, documents, and invoices, you need to verify your private key provided by your investigator.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="private_key_verification.php" class="inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-black py-3 px-6 rounded-xl text-sm transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-shield-alt"></i>
                            Verify Private Key
                        </a>
                        <p class="text-xs text-amber-700 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            Contact your investigator if you don't have your private key
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Stats Grid -->
    <?php if ($private_key_verified): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="card-premium p-6 flex items-center space-x-4 hover:shadow-card-hover transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.2s">
            <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-folder-open fa-xl"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-secondary"><?php echo $stats['open_cases']; ?></p>
                <p class="text-slate-500 text-xs uppercase font-bold tracking-widest">Open Cases</p>
            </div>
        </div>
		
		<div class="card-premium p-6 flex items-center space-x-4 hover:shadow-card-hover transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.6s">
            <div class="p-4 rounded-2xl bg-gradient-to-br from-purple-100 to-purple-200 text-purple-700 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-check-circle fa-xl"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-secondary"><?php echo $stats['closed_cases']; ?></p>
                <p class="text-slate-500 text-xs uppercase font-bold tracking-widest">Closed Cases</p>
            </div>
        </div>
		
        <div class="card-premium p-6 flex items-center space-x-4 hover:shadow-card-hover transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.3s">
            <div class="p-4 rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 text-orange-700 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-file-invoice-dollar fa-xl"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-secondary">R<?php echo number_format($stats['unpaid_balance'], 2); ?></p>
                <p class="text-slate-500 text-xs uppercase font-bold tracking-widest">Outstanding Balance</p>
            </div>
        </div>
        <div class="card-premium p-6 flex items-center space-x-4 hover:shadow-card-hover transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.4s">
            <div class="p-4 rounded-2xl bg-gradient-to-br from-teal-100 to-teal-200 text-teal-700 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-comments fa-xl"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-secondary"><?php echo $stats['unread_messages']; ?></p>
                <p class="text-slate-500 text-xs uppercase font-bold tracking-widest">Unread Messages</p>
            </div>
        </div>
        <div class="card-premium p-6 flex items-center space-x-4 hover:shadow-card-hover transition-all duration-300 animate-fade-in-up group" style="animation-delay: 0.5s">
            <div class="p-4 rounded-2xl bg-gradient-to-br from-green-100 to-green-200 text-green-700 group-hover:scale-110 transition-transform duration-300">
                <i class="fas fa-dollar-sign fa-xl"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-secondary">R<?php echo number_format($stats['total_paid']); ?></p>
                <p class="text-slate-500 text-xs uppercase font-bold tracking-widest">Total Paid</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-gradient-to-r from-slate-100 to-slate-200 border border-slate-300 rounded-3xl p-8 mb-8 animate-fade-in-up" style="animation-delay: 0.2s">
        <div class="text-center">
            <div class="p-6 rounded-2xl bg-slate-50 inline-block mb-6">
                <i class="fas fa-lock text-4xl text-slate-400"></i>
            </div>
            <h3 class="text-xl font-black text-slate-700 mb-4">Case Information Locked</h3>
            <p class="text-slate-600 mb-6 max-w-md mx-auto leading-relaxed">
                Verify your private key to access case statistics, financial information, and quick actions.
            </p>
            <a href="private_key_verification.php" class="inline-flex items-center justify-center gap-2 bg-slate-600 hover:bg-slate-700 text-white font-black py-3 px-6 rounded-xl text-sm transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-key"></i>
                Unlock Information
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Insights -->
    <section class="card-premium animate-fade-in-up" style="animation-delay: 0.9s">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="p-3 rounded-2xl bg-gradient-to-br from-yellow-100 to-orange-100">
                    <i class="fas fa-lightbulb text-2xl text-orange-600"></i>
                </div>
                <h2 class="text-xl font-black text-secondary">Latest Insights</h2>
            </div>
            <a href="../blog.php" class="text-sm font-bold text-primary hover:text-red-600 transition-colors flex items-center gap-2">
                View All <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (!empty($recent_posts)): ?>
                <?php foreach ($recent_posts as $post): ?>
                    <a href="../view_post.php?id=<?= $post['post_id']; ?>" class="group block p-6 rounded-3xl border border-slate-200 hover:border-primary/30 hover:shadow-card-hover transition-all duration-300 bg-gradient-to-br from-white to-slate-50/50 animate-scale-in">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full font-semibold">
                                <i class="fas fa-eye mr-1"></i><?= number_format($post['view_count']); ?> views
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                <?= date('M j', strtotime($post['created_at'])); ?>
                            </span>
                        </div>
                        <h3 class="font-black text-secondary group-hover:text-primary transition-colors line-clamp-2 mb-3 text-lg leading-tight">
                            <?= htmlspecialchars($post['title']); ?>
                        </h3>
                        <p class="text-slate-600 line-clamp-2 mb-4 leading-relaxed">
                            <?= htmlspecialchars($post['excerpt'] ? $post['excerpt'] : 'Read the latest investigation insights and professional intelligence...'); ?>
                        </p>
                        <div class="flex items-center text-primary font-bold text-sm group-hover:text-red-600 transition-colors">
                            Read More <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-2 text-center py-12 bg-gradient-to-br from-slate-50 to-slate-100 rounded-3xl border border-dashed border-slate-300">
                    <div class="p-6 rounded-2xl bg-white/50 inline-block">
                        <i class="fas fa-newspaper fa-3x text-slate-300 mb-4"></i>
                        <p class="text-slate-500 font-semibold">New insights will be published here soon.</p>
                        <p class="text-xs text-slate-400 mt-1">Check back for the latest investigation updates.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Timeline Section -->
        <div class="lg:col-span-2">
            <?php if ($private_key_verified): ?>
            <section class="card-premium h-full animate-fade-in-up" style="animation-delay: 1.0s">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100">
                            <i class="fas fa-history text-2xl text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-black text-secondary">Recent Activity</h2>
                    </div>
                    <span class="text-xs text-slate-500 bg-slate-100 px-3 py-2 rounded-full font-semibold">Last 7 Updates</span>
                </div>
                
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        <?php if (!empty($timeline_items)): ?>
                            <?php foreach ($timeline_items as $index => $item):
                                $details = get_timeline_item_details($item['type']);
                            ?>
                                <li>
                                    <div class="relative pb-10">
                                        <?php if ($index !== count($timeline_items) - 1): ?>
                                            <span class="absolute top-6 left-6 -ml-px h-full w-0.5 bg-gradient-to-b from-primary/20 to-primary/5" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="relative flex space-x-4">
                                            <div>
                                                <span class="<?php echo htmlspecialchars($details['color']); ?> h-12 w-12 rounded-2xl flex items-center justify-center ring-4 ring-white shadow-card animate-pulse-slow">
                                                    <i class="<?php echo htmlspecialchars($details['icon']); ?> text-lg"></i>
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-2">
                                                <div class="flex-1">
                                                    <p class="text-sm text-slate-600 font-medium">
                                                        <?php
                                                        $content = $item['content'] ? $item['content'] : '...';
                                                        switch($item['type']) {
                                                            case 'note': echo "New note on "; break;
                                                            case 'document': echo "Document uploaded to "; break;
                                                            case 'message': echo "Message sent regarding "; break;
                                                        }
                                                        ?>
                                                        <a href="view_case_details.php?id=<?php echo $item['case_id']; ?>" class="font-black text-primary hover:text-red-600 transition-colors"><?php echo htmlspecialchars($item['case_title']); ?></a>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-2 italic bg-slate-50 p-3 rounded-xl border border-slate-100 max-w-md">
                                                        <?php 
                                                        $truncated = substr($content, 0, 80);
                                                        echo '"' . htmlspecialchars($truncated);
                                                        if (strlen($content) > 80) {
                                                            echo '...';
                                                        }
                                                        echo '"';
                                                        ?>
                                                    </p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-xs text-slate-400 font-mono">
                                                    <time datetime="<?php echo htmlspecialchars($item['event_date']); ?>" class="bg-slate-100 px-2 py-1 rounded-full font-semibold"><?php echo date('M j, g:i a', strtotime($item['event_date'])); ?></time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <li class="text-center py-16 bg-gradient-to-br from-slate-50 to-slate-100 rounded-3xl border border-dashed border-slate-300">
                                <div class="p-6 rounded-2xl bg-white/50 inline-block">
                                    <i class="fas fa-stream fa-3x text-slate-300 mb-4"></i>
                                    <p class="text-sm font-semibold text-slate-500 mb-1">No recent activity to show.</p>
                                    <p class="text-xs text-slate-400">Updates will appear here as your cases progress.</p>
                                </div>
                             </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>
            <?php else: ?>
            <section class="card-premium h-full animate-fade-in-up bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200" style="animation-delay: 1.0s">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-slate-200">
                            <i class="fas fa-history text-2xl text-slate-500"></i>
                        </div>
                        <h2 class="text-xl font-black text-slate-600">Recent Activity</h2>
                    </div>
                    <span class="text-xs text-slate-400 bg-slate-200 px-3 py-2 rounded-full font-semibold">Locked</span>
                </div>
                
                <div class="text-center py-16">
                    <div class="p-6 rounded-2xl bg-white/50 inline-block">
                        <i class="fas fa-lock text-4xl text-slate-400 mb-4"></i>
                        <p class="text-sm font-semibold text-slate-500 mb-1">Activity Timeline Locked</p>
                        <p class="text-xs text-slate-400 max-w-sm">Verify your private key to view case updates, messages, and document activity.</p>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>

        <!-- Active Cases Sidebar -->
        <div class="lg:col-span-1">
            <?php if ($private_key_verified): ?>
            <section class="card-premium h-full animate-fade-in-up" style="animation-delay: 1.1s">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-gradient-to-br from-green-100 to-emerald-100">
                            <i class="fas fa-folder-open text-2xl text-green-600"></i>
                        </div>
                        <h2 class="text-xl font-black text-secondary">Active Cases</h2>
                    </div>
                    <a href="cases.php" class="text-xs font-bold text-primary hover:text-red-600 transition-colors">View All</a>
                </div>
                
                <ul class="space-y-4">
                    <?php if (!empty($active_cases)): ?>
                        <?php foreach($active_cases as $case): ?>
                            <li>
                                <a href="view_case_details.php?id=<?php echo $case['case_id']; ?>" class="group block bg-gradient-to-br from-white to-slate-50/50 p-6 rounded-3xl border border-slate-200 hover:border-primary/30 hover:shadow-card-hover transition-all duration-300 animate-scale-in">
                                    <div class="flex justify-between items-start mb-3">
                                        <span class="text-xs font-mono text-slate-400 bg-slate-100 px-3 py-1 rounded-full font-semibold">#<?php echo htmlspecialchars($case['case_number']); ?></span>
                                        <span class="px-3 py-1 inline-flex text-xs font-black uppercase tracking-wide rounded-full 
                                            <?php 
                                            $status_class = 'bg-blue-100 text-blue-800';
                                            if ($case['status'] === 'Active') $status_class = 'bg-green-100 text-green-800';
                                            elseif ($case['status'] === 'Pending') $status_class = 'bg-yellow-100 text-yellow-800';
                                            elseif ($case['status'] === 'Resolved') $status_class = 'bg-purple-100 text-purple-800';
                                            echo $status_class;
                                            ?>">
                                            <?php echo htmlspecialchars($case['status']); ?>
                                        </span>
                                    </div>
                                    <p class="font-black text-secondary group-hover:text-primary transition-colors line-clamp-2 text-lg leading-tight">
                                        <?php echo htmlspecialchars($case['title']); ?>
                                    </p>
                                    <div class="mt-4 flex items-center text-primary font-bold text-sm group-hover:text-red-600 transition-colors">
                                        View Details <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-center py-16 bg-gradient-to-br from-slate-50 to-slate-100 rounded-3xl border border-dashed border-slate-300">
                            <div class="p-6 rounded-2xl bg-white/50 inline-block">
                                <i class="fas fa-folder-open fa-3x text-slate-300 mb-4"></i>
                                <p class="text-sm font-semibold text-slate-500 mb-1">You have no active cases.</p>
                                <p class="text-xs text-slate-400">New cases will appear here when assigned.</p>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="mt-8 pt-8 border-t border-slate-200">
                    <h3 class="text-sm font-black text-secondary mb-4 flex items-center gap-2">
                        <i class="fas fa-question-circle text-primary"></i> Need Assistance?
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">Contact your investigator directly or use the messaging system within a case.</p>
                    <a href="mailto:support@iss-investigations.co.za" class="block w-full text-center bg-gradient-primary hover:from-red-600 hover:to-primary text-white font-black py-3 rounded-2xl text-sm transition-all duration-300 transform hover:scale-105 shadow-glow hover:shadow-glow-lg">
                        Contact Support
                    </a>
                </div>
            </section>
            <?php else: ?>
            <section class="card-premium h-full animate-fade-in-up bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200" style="animation-delay: 1.1s">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-slate-200">
                            <i class="fas fa-folder-open text-2xl text-slate-500"></i>
                        </div>
                        <h2 class="text-xl font-black text-slate-600">Active Cases</h2>
                    </div>
                    <span class="text-xs text-slate-400 bg-slate-200 px-3 py-2 rounded-full font-semibold">Locked</span>
                </div>
                
                <div class="text-center py-16">
                    <div class="p-6 rounded-2xl bg-white/50 inline-block">
                        <i class="fas fa-lock text-4xl text-slate-400 mb-4"></i>
                        <p class="text-sm font-semibold text-slate-500 mb-1">Case List Locked</p>
                        <p class="text-xs text-slate-400 max-w-sm">Verify your private key to view your active cases and access case details.</p>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-slate-300">
                    <h3 class="text-sm font-black text-slate-600 mb-4 flex items-center gap-2">
                        <i class="fas fa-question-circle text-slate-500"></i> Need Assistance?
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">Contact your investigator directly or use the messaging system within a case.</p>
                    <a href="mailto:support@iss-investigations.co.za" class="block w-full text-center bg-slate-400 text-slate-600 font-black py-3 rounded-2xl text-sm cursor-not-allowed">
                        Contact Support
                    </a>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('clientCaseChart').getContext('2d');
    const data = {
        labels: ['Active', 'Pending', 'Closed', 'Resolved', 'Archived'],
        datasets: [{
            data: [<?php echo $case_status_counts['Active']; ?>, <?php echo $case_status_counts['Pending']; ?>, <?php echo $case_status_counts['Closed']; ?>, <?php echo $case_status_counts['Resolved']; ?>, <?php echo $case_status_counts['Archived']; ?>],
            backgroundColor: [
                '#2563eb',
                '#f59e0b',
                '#10b981',
                '#8b5cf6',
                '#ef4444'
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
                        color: '#64748b',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    };
    new Chart(ctx, config);
});
</script>

<?php include_once 'client_footer.php'; ?>
<?php
}
?>