<?php
// iss/view_post.php (Public Post View)
require_once 'config.php';
$post_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($post_slug) && $post_id <= 0) {
    header('Location: blog.php');
    exit;
}

$conn = get_db_connection();
if (!$conn) {
    header('Location: blog.php');
    exit;
}

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

// Increment view count
$stmt_view = $conn->prepare("UPDATE posts SET view_count = view_count + 1 WHERE post_id = ?");
$stmt_view->bind_param("i", $post['post_id']);
$stmt_view->execute();
$stmt_view->close();

$conn->close();
if (!$post) {
header('Location: index.php');
exit;
}
$page_title = $post['title'] . " | ISS Intelligence";
include_once 'includes/public_header.php';
?>
<header class="relative bg-slate-900 pt-32 pb-20 overflow-hidden">
<div class="container mx-auto px-4 relative z-10">
<div class="max-w-4xl mx-auto">
<a href="index.php" class="inline-flex items-center text-primary text-xs font-black uppercase tracking-widest mb-8 hover:text-white transition-colors">
<i class="fas fa-chevron-left mr-2"></i> Return to Archives
</a>
<div class="flex items-center gap-3 mb-6">
<span class="px-3 py-1 bg-primary text-white text-[10px] font-black uppercase tracking-tighter rounded">Public Insight</span>
<span class="text-slate-500 text-xs font-mono uppercase tracking-widest">Case ID: #ISS-<?= str_pad((string)(int)$post['post_id'], 4, '0', STR_PAD_LEFT) ?></span>
</div>
<h1 class="text-4xl md:text-6xl font-black text-white leading-tight mb-8">
<?= htmlspecialchars($post['title']); ?>
</h1>
<div class="flex items-center gap-6 border-t border-white/10 pt-8">
<div class="flex items-center gap-3">
<div class="w-10 h-10 bg-slate-800 rounded-full flex items-center justify-center border border-primary/30">
<i class="fas fa-user-tie text-primary text-sm"></i>
</div>
<div class="text-left">
<p class="text-white text-sm font-bold leading-none"><?= htmlspecialchars($post['full_name']); ?></p>
<p class="text-slate-500 text-[10px] uppercase tracking-widest mt-1">Lead Investigator</p>
</div>
</div>
<div class="text-left border-l border-white/10 pl-6">
<p class="text-white text-sm font-bold leading-none"><?= date("M d, Y", strtotime($post['created_at'])); ?></p>
<p class="text-slate-500 text-[10px] uppercase tracking-widest mt-1">Date Released</p>
</div>
</div>
</div>
</div>
<div class="absolute inset-0 opacity-20 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
</header>
<?php if (!empty($post['featured_image'])): ?>
<div class="bg-slate-100 py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <img src="<?= htmlspecialchars($post['featured_image']); ?>" alt="<?= htmlspecialchars($post['title']); ?>" class="w-full h-auto rounded-2xl shadow-2xl object-cover">
        </div>
    </div>
</div>
<?php endif; ?>
<main class="bg-white py-20 relative">
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
<div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-16">
<aside class="hidden lg:block lg:col-span-1">
<div class="sticky top-32 space-y-8">
<div>
<h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4">Share Intel</h4>
<div class="flex flex-col gap-3">
<a href="#" class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-secondary hover:bg-primary hover:text-white transition-all">
<i class="fab fa-linkedin-in text-sm"></i>
</a>
<a href="#" class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-secondary hover:bg-primary hover:text-white transition-all">
<i class="fab fa-twitter text-sm"></i>
</a>
<a href="#" class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-secondary hover:bg-primary hover:text-white transition-all">
<i class="fas fa-link text-sm"></i>
</a>
</div>
</div>
<div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
<h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-secondary mb-3">Newsletter</h4>
<p class="text-xs text-slate-500 leading-relaxed mb-4">Stay informed on corporate risk management and private security trends.</p>
<a href="contact.php" class="text-primary font-black text-[10px] uppercase tracking-widest hover:text-secondary transition-colors italic">Join Briefing &rarr;</a>
</div>
</div>
</aside>
<article class="lg:col-span-3">
<div class="prose prose-slate prose-lg max-w-none prose-p:text-slate-600 prose-p:leading-relaxed prose-p:mb-8 prose-headings:text-secondary prose-headings:font-black prose-headings:tracking-tight prose-strong:text-secondary prose-strong:font-bold prose-img:rounded-3xl prose-img:shadow-2xl">
<?= nl2br(htmlspecialchars($post['content'])); ?>
</div>
<footer class="mt-16 pt-12 border-t border-slate-100">
<div class="bg-slate-900 rounded-[2rem] p-8 md:p-12 text-white flex flex-col md:flex-row items-center justify-between gap-8 shadow-2xl">
<div class="text-center md:text-left">
<h3 class="text-2xl font-black mb-2">Requirement for <span class="text-primary">Action?</span></h3>
<p class="text-slate-400 text-sm">Contact ISS Investigations for a confidential assessment of your specific requirements.</p>
</div>
<a href="contact.php" class="px-8 py-4 bg-primary hover:bg-orange-600 text-white font-black rounded-xl transition-all transform hover:scale-105 shadow-lg whitespace-nowrap">
Request Consultation
</a>
</div>
</footer>
</article>
</div>
</div>
</main>
<?php include_once 'includes/public_footer.php'; ?>
