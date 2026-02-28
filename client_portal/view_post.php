<?php
// client_portal/view_post.php
require_once '../config.php';
require_once 'client_auth.php';

$post_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($post_slug) && $post_id <= 0) {
    redirect('posts.php');
}

$conn = get_db_connection();
if ($conn) {
    // Build query based on what parameter we have
    if (!empty($post_slug)) {
        $stmt = $conn->prepare("SELECT p.*, u.full_name FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.slug = ? AND p.status = 'Published' AND (p.publish_at IS NULL OR p.publish_at <= NOW())");
        $stmt->bind_param("s", $post_slug);
    } else {
        $stmt = $conn->prepare("SELECT p.*, u.full_name FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.post_id = ? AND p.status = 'Published' AND (p.publish_at IS NULL OR p.publish_at <= NOW())");
        $stmt->bind_param("i", $post_id);
    }
    
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
        $_SESSION['client_error_message'] = "Post not found.";
        redirect('posts.php');
    }

    // Increment view count
    $stmt_view = $conn->prepare("UPDATE posts SET view_count = view_count + 1 WHERE post_id = ?");
    $stmt_view->bind_param("i", $post['post_id']);
    $stmt_view->execute();
    $stmt_view->close();

    // Fetch comments
    $stmt_comments = $conn->prepare("SELECT pc.*, c.first_name, c.last_name FROM post_comments pc JOIN clients c ON pc.client_id = c.client_id WHERE pc.post_id = ? ORDER BY pc.created_at ASC");
    $stmt_comments->bind_param("i", $post['post_id']);
    $stmt_comments->execute();
    $comments = $stmt_comments->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_comments->close();

    $conn->close();
} else {
    $_SESSION['client_error_message'] = "Database connection failed.";
    redirect('posts.php');
}
$page_title = $post['title'];
include_once 'client_header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-8 no-print animate-fade-in-up" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-2 md:space-x-4">
            <li class="inline-flex items-center">
                <a href="dashboard.php" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-primary transition-colors">
                    <i class="fas fa-home mr-2 text-primary"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-slate-400 mx-2"></i>
                    <a href="posts.php" class="text-sm font-semibold text-slate-500 hover:text-primary transition-colors">News</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-slate-400 mx-2"></i>
                    <span class="text-sm font-black text-secondary truncate max-w-xs"><?php echo htmlspecialchars(substr($post['title'], 0, 50)) . (strlen($post['title']) > 50 ? '...' : ''); ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['client_success_message'])): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 mb-6 no-print animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-green-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <div class="text-green-800 font-semibold"><?php echo htmlspecialchars($_SESSION['client_success_message']); ?></div>
            </div>
            <?php unset($_SESSION['client_success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['client_error_message'])): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 mb-6 no-print animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <div class="text-red-800 font-semibold"><?php echo htmlspecialchars($_SESSION['client_error_message']); ?></div>
            </div>
            <?php unset($_SESSION['client_error_message']); ?>
        </div>
    <?php endif; ?>

    <article class="card-premium overflow-hidden mb-8 animate-fade-in-up" style="animation-delay: 0.1s">
        <div class="p-8 md:p-12">
            <header class="mb-10 border-b border-slate-200 pb-8">
                <h1 class="text-4xl md:text-5xl font-black text-secondary leading-tight mb-6"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="flex items-center gap-6 text-sm text-slate-500">
                    <div class="flex items-center gap-3 bg-slate-100 px-4 py-2 rounded-full">
                        <i class="far fa-user-circle text-primary text-lg"></i>
                        <span class="font-bold"><?php echo htmlspecialchars($post['full_name']); ?></span>
                    </div>
                    <div class="flex items-center gap-3 bg-slate-100 px-4 py-2 rounded-full">
                        <i class="far fa-calendar-alt text-primary text-lg"></i>
                        <span class="font-bold"><?php echo date("F j, Y", strtotime($post['created_at'])); ?></span>
                    </div>
                </div>
            </header>

            <?php if (!empty($post['featured_image'])): ?>
                <div class="mb-10">
                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto rounded-3xl shadow-elevated object-cover">
                </div>
            <?php endif; ?>

            <div class="prose prose-xl prose-slate max-w-none text-slate-700 leading-relaxed">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
        </div>
    </article>

    <!-- Social Sharing -->
    <div class="card-premium p-8 mb-8 animate-fade-in-up" style="animation-delay: 0.2s">
        <h3 class="text-lg font-black text-secondary uppercase tracking-widest mb-6 flex items-center gap-3">
            <i class="fas fa-share-alt text-primary text-xl"></i> Share This Article
        </h3>
        <div class="flex flex-wrap gap-4">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
               target="_blank" 
               class="inline-flex items-center gap-3 bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-2xl text-sm font-black transition-all duration-300 transform hover:scale-105 shadow-glow">
                <i class="fab fa-facebook-f text-lg"></i> Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($post['title']); ?>" 
               target="_blank" 
               class="inline-flex items-center gap-3 bg-sky-500 hover:bg-sky-600 text-white px-6 py-4 rounded-2xl text-sm font-black transition-all duration-300 transform hover:scale-105 shadow-glow">
                <i class="fab fa-twitter text-lg"></i> Twitter
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
               target="_blank" 
               class="inline-flex items-center gap-3 bg-blue-700 hover:bg-blue-800 text-white px-6 py-4 rounded-2xl text-sm font-black transition-all duration-300 transform hover:scale-105 shadow-glow">
                <i class="fab fa-linkedin-in text-lg"></i> LinkedIn
            </a>
            <a href="mailto:?subject=<?php echo urlencode($post['title']); ?>&body=<?php echo urlencode('Check out this article: ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
               class="inline-flex items-center gap-3 bg-slate-600 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl text-sm font-black transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-envelope text-lg"></i> Email
            </a>
        </div>
    </div>

    <section id="comments" class="card-premium p-8 md:p-12 animate-fade-in-up" style="animation-delay: 0.3s">
        <h2 class="text-2xl font-black text-secondary mb-8 flex items-center gap-3">
            <i class="far fa-comments text-primary text-2xl"></i>
            Discussion (<?php echo count($comments); ?>)
        </h2>
        
        <div class="space-y-8 mb-10">
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="flex gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-primary flex items-center justify-center text-white font-black shadow-card text-lg">
                                <?php echo htmlspecialchars(strtoupper(substr($comment['first_name'] ?? '', 0, 1))); ?>
                            </div>
                        </div>
                        <div class="flex-1 bg-gradient-to-br from-slate-50 to-white p-6 rounded-3xl border border-slate-200 shadow-card">
                            <div class="flex justify-between items-start mb-3">
                                <span class="font-black text-secondary text-lg"><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></span>
                                <span class="text-sm text-slate-400 bg-slate-100 px-3 py-1 rounded-full font-semibold"><?php echo date("M j, g:i a", strtotime($comment['created_at'])); ?></span>
                            </div>
                            <p class="text-slate-600 whitespace-pre-wrap leading-relaxed text-lg"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-12 bg-gradient-to-br from-slate-50 to-slate-100 rounded-3xl border border-dashed border-slate-300">
                    <div class="p-6 rounded-2xl bg-white/50 inline-block">
                        <i class="far fa-comments text-4xl text-slate-300 mb-4"></i>
                        <p class="text-slate-500 font-semibold text-lg mb-1">No comments yet</p>
                        <p class="text-slate-400">Be the first to share your thoughts on this article!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <form action="actions/add_comment_action.php" method="POST" class="bg-gradient-to-br from-white to-slate-50 p-8 rounded-3xl border border-slate-200 shadow-card">
            <h3 class="text-xl font-black text-secondary uppercase tracking-widest mb-6 flex items-center gap-3">
                <i class="fas fa-comment-dots text-primary text-xl"></i> Leave a Comment
            </h3>
            <?php echo csrf_input(); ?>
            <input type="hidden" name="post_slug" value="<?php echo htmlspecialchars($post['slug']); ?>">
            <div class="mb-6">
                <textarea name="comment" rows="5" required class="w-full bg-white border border-slate-200 rounded-2xl px-6 py-4 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 resize-none text-lg placeholder-slate-400" placeholder="Share your thoughts on this article..."></textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary text-lg font-black py-4 px-8 rounded-2xl flex items-center gap-3">
                    <i class="fas fa-paper-plane"></i> Post Comment
                </button>
            </div>
        </form>
    </section>
</div>

<?php include_once 'client_footer.php'; ?>
