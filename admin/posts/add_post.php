<?php
/**
 * ISS Investigations - Content Creation
 * Administrative interface for creating new blog posts for client portal distribution.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$page_title = "Create Content Post";
$errors = [];
$title = '';
$content = '';
$status = 'Draft';
$keywords = '';
$meta_description = '';
$tags = '';
$category = '';
$excerpt = '';
$seo_title = '';
$featured_image = '';
$publish_at = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_post'])) {
    // Debug: Log that POST request was received
    error_log("POST request received in add_post.php");
    $errors['debug'] = "Form submitted successfully - processing request...";

    try {
        verify_csrf_token();
        error_log("CSRF token verification passed");
    } catch (Exception $e) {
        error_log("CSRF token verification failed: " . $e->getMessage());
        $errors['csrf'] = "Security token verification failed. Please refresh the page and try again.";
    }

    if (empty($errors['csrf'])) {
        // Debug: Log that we're proceeding with processing
        error_log("Proceeding with form processing");

        // Sanitize and validate input
        $title = sanitize_input($_POST['title'] ?? '');
        $content = sanitize_content($_POST['content'] ?? '');
        $status = in_array($_POST['status'] ?? 'Draft', ['Draft', 'Published']) ? $_POST['status'] ?? 'Draft' : 'Draft';
        $keywords = sanitize_input($_POST['keywords'] ?? '');
        $meta_description = sanitize_input($_POST['meta_description'] ?? '');
        $tags = sanitize_input($_POST['tags'] ?? '');
        $category = sanitize_input($_POST['category'] ?? '');
        $excerpt = sanitize_input($_POST['excerpt'] ?? '');
        $seo_title = sanitize_input($_POST['seo_title'] ?? '');
        $publish_at = sanitize_input($_POST['publish_at'] ?? '');

        // Handle file upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['featured_image'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowed_types)) {
                $errors['featured_image'] = "Invalid file type. Only JPEG, PNG, WebP, and GIF images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $errors['featured_image'] = "File size exceeds 5MB limit.";
            } else {
                $upload_dir = '../../uploads/post_media/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $unique_filename = 'post_' . uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $unique_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $featured_image = 'uploads/post_media/' . $unique_filename;
                } else {
                    $errors['featured_image'] = "Failed to upload file. Please try again.";
                }
            }
        }

        // Generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        if (empty($slug)) {
            $slug = 'post-' . time();
        }

        // Auto-generate missing fields
        if (empty($meta_description) && !empty($content)) {
            $meta_description = substr(strip_tags($content), 0, 160) . '...';
        }

        if (empty($seo_title)) {
            $seo_title = $title;
        }

        if (empty($excerpt) && !empty($content)) {
            $excerpt = substr(strip_tags($content), 0, 150) . '...';
        }

        // Validation
        if (empty($title)) $errors['title'] = "Title is required.";
        if (empty($content)) $errors['content'] = "Content is required.";
        if (!empty($meta_description) && strlen($meta_description) > 160) {
            $errors['meta_description'] = "Meta description must be 160 characters or less.";
        }
        if (!empty($seo_title) && strlen($seo_title) > 60) {
            $errors['seo_title'] = "SEO title must be 60 characters or less.";
        }
        if (!empty($publish_at) && strtotime($publish_at) === false) {
            $errors['publish_at'] = "Invalid publish date format.";
        }

        if (empty($errors)) {
            $conn = get_db_connection();
            if (!$conn) {
                $errors['db'] = "Database connection failed";
            } else {
                $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, status, slug, keywords, meta_description, tags, category, excerpt, seo_title, featured_image, publish_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $errors['db'] = "Failed to prepare database statement";
                } else {
                    $publish_at_value = !empty($publish_at) ? date('Y-m-d H:i:s', strtotime($publish_at)) : null;
                    $stmt->bind_param("issssssssssss", $_SESSION['user_id'], $title, $content, $status, $slug, $keywords, $meta_description, $tags, $category, $excerpt, $seo_title, $featured_image, $publish_at_value);

                    if ($stmt->execute()) {
                        $new_post_id = $stmt->insert_id;
                        log_audit_action($_SESSION['user_id'], null, 'create_post', 'post', $new_post_id, "New Post: $title");
                        $_SESSION['success_message'] = "Content post created successfully!";
                        redirect('admin/posts/view_post.php?slug=' . urlencode($slug));
                    } else {
                        if ($conn->errno === 1062) {
                            $errors['slug'] = "A post with a similar title already exists. Please choose a different title.";
                        } else {
                            $errors['db'] = "Failed to create post: " . $stmt->error;
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }
}

include_once '../../includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <div class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Create <span class="text-primary">Content Post</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">New blog post for client portal</p>
    </div>

    <form action="add_post.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrf_input(); ?>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Post Content</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="title" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($title); ?>"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['title'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['title']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="content" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Content <span class="text-red-500">*</span></label>
                    <textarea name="content" rows="12" required
                              class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($content); ?></textarea>
                    <?php if (isset($errors['content'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['content']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Publishing Settings</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Publish Status</label>
                        <select name="status" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="Draft" <?php echo $status === 'Draft' ? 'selected' : ''; ?>>Draft (Not Visible)</option>
                            <option value="Published" <?php echo $status === 'Published' ? 'selected' : ''; ?>>Published (Visible to Clients)</option>
                        </select>
                    </div>
                    <div>
                        <label for="publish_at" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Schedule Publish <span class="text-slate-500">(Optional)</span></label>
                        <input type="datetime-local" name="publish_at" value="<?php echo htmlspecialchars($publish_at); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <p class="text-[10px] text-slate-500 mt-1">Schedule when this post should be published</p>
                        <?php if (isset($errors['publish_at'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['publish_at']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">03. SEO & Metadata</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="seo_title" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">SEO Title</label>
                        <input type="text" name="seo_title" maxlength="60" value="<?php echo htmlspecialchars($seo_title); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <p class="text-[10px] text-slate-500 mt-1"><?php echo strlen($seo_title); ?>/60 characters</p>
                        <?php if (isset($errors['seo_title'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['seo_title']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="category" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Category</label>
                        <select name="category" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">Select Category</option>
                            <option value="Investigations" <?php echo $category === 'Investigations' ? 'selected' : ''; ?>>Investigations</option>
                            <option value="Security" <?php echo $category === 'Security' ? 'selected' : ''; ?>>Security</option>
                            <option value="Legal Updates" <?php echo $category === 'Legal Updates' ? 'selected' : ''; ?>>Legal Updates</option>
                            <option value="Technology" <?php echo $category === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                            <option value="Industry News" <?php echo $category === 'Industry News' ? 'selected' : ''; ?>>Industry News</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="keywords" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Keywords <span class="text-slate-500">(Optional)</span></label>
                    <input type="text" name="keywords" value="<?php echo htmlspecialchars($keywords); ?>"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <p class="text-[10px] text-slate-500 mt-1">Comma-separated keywords for SEO optimization</p>
                </div>

                <div>
                    <label for="tags" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Tags <span class="text-slate-500">(Optional)</span></label>
                    <input type="text" name="tags" value="<?php echo htmlspecialchars($tags); ?>"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <p class="text-[10px] text-slate-500 mt-1">Comma-separated tags for categorization and filtering</p>
                </div>

                <div>
                    <label for="meta_description" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Meta Description</label>
                    <textarea name="meta_description" rows="3" maxlength="160"
                              class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($meta_description); ?></textarea>
                    <p class="text-[10px] text-slate-500 mt-1"><?php echo strlen($meta_description); ?>/160 characters (leave blank for auto-generation)</p>
                    <?php if (isset($errors['meta_description'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['meta_description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">04. Featured Media</h2>
            </div>
            <div class="p-6">
                <label for="featured_image" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Featured Image <span class="text-slate-500">(Optional)</span></label>
                <div class="relative">
                    <input type="file" name="featured_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-orange-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all cursor-pointer">
                    <p class="text-[10px] text-slate-500 mt-2">Supported formats: JPEG, PNG, WebP, GIF (Max 5MB)</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">05. Content Preview</h2>
            </div>
            <div class="p-6">
                <label for="excerpt" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Excerpt/Summary <span class="text-slate-500">(Optional)</span></label>
                <textarea name="excerpt" rows="4"
                          class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($excerpt); ?></textarea>
                <p class="text-[10px] text-slate-500 mt-1">Short summary for post previews (leave blank for auto-generation from content)</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="index.php" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" name="submit_post" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-plus-circle mr-2"></i>Create Post
            </button>
        </div>
    </form>
</div>

<?php include_once '../../includes/footer.php'; ?>
