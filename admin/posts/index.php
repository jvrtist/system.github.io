<?php
/**
 * ISS Investigations - Content Management Dashboard
 * Administrative interface for managing all blog posts with search and filtering capabilities.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$page_title = "Manage Content Posts";
$conn = get_db_connection();

$sql = "SELECT p.post_id, p.title, p.status, p.created_at, p.category, p.tags, p.keywords, p.slug, u.full_name 
        FROM posts p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($_GET['tags'])) {
    $tags_search = '%' . $_GET['tags'] . '%';
    $sql .= " AND p.tags LIKE ?";
    $params[] = $tags_search;
    $types .= "s";
}

if (!empty($_GET['category'])) {
    $sql .= " AND p.category = ?";
    $params[] = $_GET['category'];
    $types .= "s";
}

$sql .= " ORDER BY p.created_at DESC";

$posts = [];
if ($conn) {
    $result = $conn->prepare($sql);
    if ($result) {
        if (!empty($params)) {
            $result->bind_param($types, ...$params);
        }
        $result->execute();
        $posts = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        $result->close();
    } else {
        $_SESSION['error_message'] = "Error preparing query: " . $conn->error;
    }
} else {
    $_SESSION['error_message'] = "Database connection failed.";
}

$conn->close();

include_once '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <div class="border-l-4 border-primary pl-6 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Manage <span class="text-primary">Content Posts</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Create and maintain blog posts for client portal</p>
        </div>
        <a href="add_post.php" class="mt-4 md:mt-0 bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-6 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 flex items-center justify-center">
            <i class="fas fa-plus-circle mr-2"></i> Create Post
        </a>
    </div>

    <!-- Search Form -->
    <form method="GET" class="bg-slate-900 border border-white/5 p-6 rounded-2xl shadow-2xl">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Search Posts</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Post title or content" class="w-full bg-slate-950 border border-white/10 rounded-lg pl-12 pr-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Tags Filter</label>
                <input type="text" name="tags" value="<?= htmlspecialchars($_GET['tags'] ?? '') ?>" placeholder="Filter by tags" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Category Filter</label>
                <select name="category" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Categories</option>
                    <option value="Investigations" <?= (isset($_GET['category']) && $_GET['category'] == 'Investigations') ? 'selected' : '' ?>>Investigations</option>
                    <option value="Security" <?= (isset($_GET['category']) && $_GET['category'] == 'Security') ? 'selected' : '' ?>>Security</option>
                    <option value="Legal Updates" <?= (isset($_GET['category']) && $_GET['category'] == 'Legal Updates') ? 'selected' : '' ?>>Legal Updates</option>
                    <option value="Technology" <?= (isset($_GET['category']) && $_GET['category'] == 'Technology') ? 'selected' : '' ?>>Technology</option>
                    <option value="Industry News" <?= (isset($_GET['category']) && $_GET['category'] == 'Industry News') ? 'selected' : '' ?>>Industry News</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Status Filter</label>
                <select name="status" class="w-full bg-slate-950 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-primary outline-none appearance-none">
                    <option value="">All Status</option>
                    <option value="Published" <?= (isset($_GET['status']) && $_GET['status'] == 'Published') ? 'selected' : '' ?>>Published</option>
                    <option value="Draft" <?= (isset($_GET['status']) && $_GET['status'] == 'Draft') ? 'selected' : '' ?>>Draft</option>
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

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-700">
                <thead class="bg-white/[0.03] border-b border-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-slate-400 tracking-widest">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-slate-400 tracking-widest">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-slate-400 tracking-widest">Tags</th>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-slate-400 tracking-widest">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-slate-400 tracking-widest">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-slate-400 tracking-widest">Created</th>
                        <th class="px-6 py-3 text-center text-xs font-black uppercase text-slate-400 tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-sm font-semibold text-slate-100"><?php echo htmlspecialchars($post['title']); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-300">
                                    <?php if (!empty($post['category'])): ?>
                                        <span class="px-2 py-1 bg-blue-500/20 text-blue-300 text-xs rounded-full"><?php echo htmlspecialchars($post['category']); ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-300">
                                    <?php if (!empty($post['tags'])): ?>
                                        <?php 
                                        $tags = array_map('trim', explode(',', $post['tags']));
                                        $display_tags = array_slice($tags, 0, 2);
                                        echo htmlspecialchars(implode(', ', $display_tags));
                                        if (count($tags) > 2) echo '...';
                                        ?>
                                    <?php else: ?>
                                        <span class="text-slate-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full <?php echo $post['status'] === 'Published' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'; ?>">
                                        <?php echo htmlspecialchars($post['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-300"><?php echo htmlspecialchars($post['full_name']); ?></td>
                                <td class="px-6 py-4 text-sm text-slate-300"><?php echo date("M j, Y", strtotime($post['created_at'])); ?></td>
                                <td class="px-6 py-4 text-center text-sm">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="view_post.php?slug=<?php echo urlencode($post['slug']); ?>" class="text-primary hover:text-orange-400 transition-colors" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="edit_post.php?id=<?php echo (int)$post['post_id']; ?>" class="text-blue-400 hover:text-blue-300 transition-colors" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_post.php?id=<?php echo (int)$post['post_id']; ?>&token=<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>" class="text-red-400 hover:text-red-300 transition-colors" title="Delete" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <i class="fas fa-file-alt text-4xl mb-3 opacity-50"></i>
                                <p class="text-sm font-medium">No posts found. <a href="add_post.php" class="text-primary hover:text-orange-400">Create one now</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
