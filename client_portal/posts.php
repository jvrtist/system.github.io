<?php
// client_portal/posts.php
require_once '../config.php';
require_once 'client_auth.php';

$page_title = "News & Updates";
$conn = get_db_connection();

$sql = "SELECT p.post_id, p.title, p.content, p.slug, p.created_at, u.full_name, p.featured_image
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'Published'
        AND (p.publish_at IS NULL OR p.publish_at <= NOW())
        ORDER BY p.created_at DESC";
$posts = [];
if ($conn) {
    $result = $conn->query($sql);
    if ($result) {
        $posts = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $_SESSION['client_error_message'] = "Unable to load posts.";
    }
    $conn->close();
} else {
    $_SESSION['client_error_message'] = "Database connection failed.";
}

include_once 'client_header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <!-- Header Section -->
    <header class="border-l-4 border-primary pl-6 animate-fade-in-up">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-secondary mb-2">News & Updates</h1>
                <p class="text-slate-600 text-lg">Stay informed with the latest announcements and articles.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="btn-primary inline-flex items-center gap-2 text-sm font-semibold">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <?php if (isset($_SESSION['client_success_message'])): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 mb-6 animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-green-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <div class="text-green-800 font-semibold"><?php echo htmlspecialchars($_SESSION['client_success_message']); ?></div>
            </div>
            <?php unset($_SESSION['client_success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['client_error_message'])): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 mb-6 animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <div class="text-red-800 font-semibold"><?php echo htmlspecialchars($_SESSION['client_error_message']); ?></div>
            </div>
            <?php unset($_SESSION['client_error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Posts Feed -->
    <div class="space-y-8">
        <?php if (!empty($posts)): ?>
            <?php foreach($posts as $index => $post): ?>
                <article class="card-premium overflow-hidden hover:shadow-card-hover transition-all duration-500 animate-fade-in-up" style="animation-delay: <?= $index * 0.1 ?>s">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="h-64 bg-gradient-to-br from-slate-200 to-slate-300 relative overflow-hidden">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                    <?php endif; ?>
                    <div class="p-8">
                        <div class="flex items-center gap-4 text-sm text-slate-500 mb-6">
                            <div class="flex items-center gap-2 bg-slate-100 px-3 py-1 rounded-full">
                                <i class="far fa-calendar-alt text-primary"></i>
                                <span class="font-semibold"><?php echo date("F j, Y", strtotime($post['created_at'])); ?></span>
                            </div>
                            <div class="flex items-center gap-2 bg-slate-100 px-3 py-1 rounded-full">
                                <i class="far fa-user text-primary"></i>
                                <span class="font-semibold"><?php echo htmlspecialchars($post['full_name']); ?></span>
                            </div>
                        </div>
                        
                        <h2 class="text-3xl font-black text-secondary mb-4 hover:text-primary transition-colors leading-tight">
                            <a href="view_post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h2>
                        
                        <div class="text-slate-600 mb-8 leading-relaxed text-lg">
                            <?php 
                                $excerpt = strip_tags($post['content']);
                                echo htmlspecialchars(substr($excerpt, 0, 400)) . (strlen($excerpt) > 400 ? '...' : ''); 
                            ?>
                        </div>
                        
                        <a href="view_post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                           class="btn-primary inline-flex items-center gap-3 text-lg font-black py-4 px-8 rounded-2xl group">
                            Read Full Article 
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card-premium p-16 text-center animate-scale-in">
                <div class="w-24 h-24 bg-gradient-to-br from-slate-100 to-slate-200 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <i class="far fa-newspaper text-4xl text-slate-400"></i>
                </div>
                <h3 class="text-2xl font-black text-secondary mb-3">No updates yet</h3>
                <p class="text-slate-600 text-lg">Check back later for the latest news and announcements.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'client_footer.php'; ?>
