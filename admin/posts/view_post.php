<?php
/**
 * ISS Investigations - Content Post Viewer
 * Read-only view of blog posts with client comments and administrative metadata.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$post_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($post_slug) && $post_id <= 0) redirect('admin/posts/');

$conn = get_db_connection();

// Build query based on what parameter we have
if (!empty($post_slug)) {
    $stmt = $conn->prepare("SELECT p.*, u.full_name FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.slug = ?");
    $stmt->bind_param("s", $post_slug);
} else {
    $stmt = $conn->prepare("SELECT p.*, u.full_name FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.post_id = ?");
    $stmt->bind_param("i", $post_id);
}

$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    $_SESSION['error_message'] = "Content post not found.";
    redirect('admin/posts/');
}

// Fetch comments for the post
$stmt_comments = $conn->prepare("SELECT pc.*, c.first_name, c.last_name FROM post_comments pc JOIN clients c ON pc.client_id = c.client_id WHERE pc.post_id = ? ORDER BY pc.created_at DESC");
$stmt_comments->bind_param("i", $post_id);
$stmt_comments->execute();
$comments = $stmt_comments->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_comments->close();

$page_title = $post['title'];
include_once '../../includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="border-l-4 border-primary pl-6">
            <a href="index.php" class="text-primary hover:text-orange-400 text-sm mb-3 inline-block font-semibold flex items-center"><i class="fas fa-arrow-left mr-2"></i>Back to Posts</a>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter"><?php echo htmlspecialchars($post['title']); ?></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">By <?php echo htmlspecialchars($post['full_name']); ?> on <?php echo date("F j, Y", strtotime($post['created_at'])); ?></p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="edit_post.php?id=<?php echo $post_id; ?>" class="px-6 py-2.5 bg-blue-600/20 border border-blue-600/50 hover:bg-blue-600/30 text-blue-300 rounded-lg transition-colors font-semibold text-sm text-center">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="../../view_post.php?slug=<?php echo urlencode($post['slug']); ?>" target="_blank" class="px-6 py-2.5 bg-green-600/20 border border-green-600/50 hover:bg-green-600/30 text-green-300 rounded-lg transition-colors font-semibold text-sm text-center">
                <i class="fas fa-external-link-alt mr-2"></i>Preview Public
            </a>
            <a href="delete_post.php?id=<?php echo $post_id; ?>&token=<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>" class="px-6 py-2.5 bg-red-600/20 border border-red-600/50 hover:bg-red-600/30 text-red-300 rounded-lg transition-colors font-semibold text-sm text-center" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');"><i class="fas fa-trash-alt mr-2"></i>Delete</a>
        </div>
    </div>

    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Publication Status</h2>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-eye text-slate-500 text-xs"></i>
                    <span class="text-xs text-slate-400"><?php echo number_format($post['view_count'] ?? 0); ?> views</span>
                </div>
                <span class="px-2.5 py-1 text-xs font-bold rounded-full <?php echo $post['status'] === 'Published' ? 'bg-green-500/30 text-green-300 border border-green-500/50' : 'bg-yellow-500/30 text-yellow-300 border border-yellow-500/50'; ?>">
                    <?php echo htmlspecialchars($post['status']); ?>
                </span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Created</label>
                    <p class="text-sm text-slate-100 mt-1"><?php echo date("M j, Y \a\t g:i A", strtotime($post['created_at'])); ?></p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Last Updated</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php echo $post['updated_at'] ? date("M j, Y \a\t g:i A", strtotime($post['updated_at'])) : 'Never'; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Publish Schedule</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['publish_at'])): ?>
                            <span class="text-primary"><?php echo date("M j, Y \a\t g:i A", strtotime($post['publish_at'])); ?></span>
                            <?php if (strtotime($post['publish_at']) > time()): ?>
                                <span class="text-yellow-400">(Scheduled)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-slate-500">Immediate</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Visibility</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php
                        $is_published = $post['status'] === 'Published';
                        $is_scheduled = !empty($post['publish_at']) && strtotime($post['publish_at']) <= time();
                        $is_future = !empty($post['publish_at']) && strtotime($post['publish_at']) > time();

                        if ($is_published && ($is_scheduled || empty($post['publish_at']))): ?>
                            <span class="text-green-400"><i class="fas fa-globe mr-1"></i>Public</span>
                        <?php elseif ($is_future): ?>
                            <span class="text-blue-400"><i class="fas fa-clock mr-1"></i>Scheduled</span>
                        <?php else: ?>
                            <span class="text-slate-500"><i class="fas fa-lock mr-1"></i>Draft</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Metadata Information -->
    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
            <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">SEO & Metadata</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Category</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['category'])): ?>
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-300 text-xs rounded-full"><?php echo htmlspecialchars($post['category']); ?></span>
                        <?php else: ?>
                            <span class="text-slate-500">Not set</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Keywords</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['keywords'])): ?>
                            <?php echo htmlspecialchars($post['keywords']); ?>
                        <?php else: ?>
                            <span class="text-slate-500">Not set</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">SEO Title</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['seo_title'])): ?>
                            <?php echo htmlspecialchars($post['seo_title']); ?>
                            <span class="text-[10px] text-slate-500">(<?php echo strlen($post['seo_title']); ?>/60 chars)</span>
                        <?php else: ?>
                            <span class="text-slate-500">Not set (uses post title)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Tags</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['tags'])): ?>
                            <?php 
                            $tags = array_map('trim', explode(',', $post['tags']));
                            foreach ($tags as $tag): 
                            ?>
                                <span class="inline-block px-2 py-1 bg-orange-500/20 text-orange-300 text-xs rounded-full mr-1 mb-1"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-slate-500">No tags</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Meta Description</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['meta_description'])): ?>
                            <?php echo htmlspecialchars($post['meta_description']); ?>
                            <span class="text-[10px] text-slate-500">(<?php echo strlen($post['meta_description']); ?>/160 chars)</span>
                        <?php else: ?>
                            <span class="text-slate-500">Not set (auto-generated from content)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-400 tracking-widest">Excerpt</label>
                    <p class="text-sm text-slate-100 mt-1">
                        <?php if (!empty($post['excerpt'])): ?>
                            <?php echo htmlspecialchars($post['excerpt']); ?>
                        <?php else: ?>
                            <span class="text-slate-500">Not set (auto-generated from content)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics & Engagement -->
    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
            <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Analytics & Engagement</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-eye text-blue-400 text-xl"></i>
                    </div>
                    <div class="text-2xl font-bold text-slate-100"><?php echo number_format($post['view_count'] ?? 0); ?></div>
                    <div class="text-xs text-slate-400 uppercase tracking-widest">Total Views</div>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-comments text-green-400 text-xl"></i>
                    </div>
                    <div class="text-2xl font-bold text-slate-100"><?php echo count($comments); ?></div>
                    <div class="text-xs text-slate-400 uppercase tracking-widest">Comments</div>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-chart-line text-purple-400 text-xl"></i>
                    </div>
                    <div class="text-2xl font-bold text-slate-100">
                        <?php
                        $days_since_creation = max(1, (time() - strtotime($post['created_at'])) / (60 * 60 * 24));
                        $avg_views_per_day = ($post['view_count'] ?? 0) / $days_since_creation;
                        echo number_format($avg_views_per_day, 1);
                        ?>
                    </div>
                    <div class="text-xs text-slate-400 uppercase tracking-widest">Avg Views/Day</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($post['featured_image'])): ?>
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Featured Image</h2>
            </div>
            <div class="p-6">
                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="w-full h-auto rounded-xl shadow-lg object-cover">
            </div>
        </div>
    <?php endif; ?>

    <article class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl p-8">
        <div class="prose prose-invert max-w-none text-slate-100 leading-relaxed">
            <?php echo $post['content']; ?>
        </div>
    </article>

    <section id="comments" class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="border-l-4 border-primary pl-6">
                <h2 class="text-2xl font-black text-white uppercase tracking-tighter">Reader <span class="text-primary">Comments</span></h2>
                <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Total: <?php echo count($comments); ?> comments</p>
            </div>
            <?php if (!empty($comments)): ?>
                <a href="#" onclick="printComments()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-white rounded-lg transition-colors font-semibold text-sm">
                    <i class="fas fa-print mr-2"></i>Print Comments
                </a>
            <?php endif; ?>
        </div>

        <div class="space-y-4">
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="bg-slate-900 border border-white/5 rounded-xl p-6 hover:border-white/10 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-primary font-bold text-sm">
                                    <?php echo htmlspecialchars(strtoupper(substr($comment['first_name'], 0, 1))); ?>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-100"><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo date("M j, Y \a\t g:i A", strtotime($comment['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="deleteComment(<?php echo $comment['comment_id']; ?>)" class="text-red-400 hover:text-red-300 transition-colors" title="Delete Comment">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-slate-300 whitespace-pre-wrap leading-relaxed pl-11"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-slate-900 border border-white/5 rounded-xl p-8 text-center">
                    <i class="fas fa-comments text-4xl text-slate-700 mb-3 block"></i>
                    <p class="text-slate-400 font-medium">No comments yet on this post.</p>
                    <p class="text-slate-500 text-sm mt-1">Comments will appear here once readers engage with the content.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
        // For now, just show an alert. In a real implementation, you'd make an AJAX call to delete the comment
        alert('Comment deletion functionality would be implemented here with AJAX call to delete comment ID: ' + commentId);
    }
}

function printComments() {
    const printContent = document.getElementById('comments').innerHTML;
    const originalContent = document.body.innerHTML;

    document.body.innerHTML = `
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #000; border-bottom: 2px solid #ff8800; padding-bottom: 10px;">${document.querySelector('h1').textContent} - Comments</h1>
            ${printContent}
            <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
                Printed from ISS Investigations Admin Panel - ${new Date().toLocaleString()}
            </div>
        </div>
    `;

    window.print();
    document.body.innerHTML = originalContent;
}
</script>

<?php include_once '../../includes/footer.php'; ?>