<?php
/**
 * ISS Investigations - Content Modification
 * Interface for editing existing blog posts with publishing controls.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$page_title = "Modify Content Post";
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
$stmt = $conn->prepare("SELECT * FROM posts WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    $_SESSION['error_message'] = "Content post not found.";
    redirect('admin/posts/');
}

// Populate form with existing data
$title = $post['title'] ?? '';
$content = $post['content'] ?? ''; // Content is already sanitized when stored
$status = $post['status'] ?? 'Draft';
$keywords = $post['keywords'] ?? '';
$meta_description = $post['meta_description'] ?? '';
$tags = $post['tags'] ?? '';
$category = $post['category'] ?? '';
$excerpt = $post['excerpt'] ?? '';
$seo_title = $post['seo_title'] ?? '';
$featured_image = $post['featured_image'] ?? '';
$publish_at = $post['publish_at'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $title = sanitize_input($_POST['title']);
    $content = sanitize_content($_POST['content']); // Allow special characters in content
    $status = in_array($_POST['status'], ['Draft', 'Published']) ? $_POST['status'] : 'Draft';
    $keywords = sanitize_input($_POST['keywords'] ?? '');
    $meta_description = sanitize_input($_POST['meta_description'] ?? '');
    $tags = sanitize_input($_POST['tags'] ?? '');
    $category = sanitize_input($_POST['category'] ?? '');
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $seo_title = sanitize_input($_POST['seo_title'] ?? '');
    $publish_at = sanitize_input($_POST['publish_at'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Handle featured image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['featured_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors['featured_image'] = "Invalid file type. Only JPEG, PNG, WebP, and GIF images are allowed.";
        }
        // Validate file size
        elseif ($file['size'] > $max_size) {
            $errors['featured_image'] = "File size exceeds 5MB limit.";
        }
        else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../../uploads/post_media/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_filename = 'post_' . uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $featured_image = 'uploads/post_media/' . $unique_filename;
            } else {
                $errors['featured_image'] = "Failed to upload file. Please try again.";
            }
        }
    } else {
        // Keep existing image if no new file uploaded
        $featured_image = $post['featured_image'] ?? '';
    }

    // Auto-generate meta description if not provided
    if (empty($meta_description) && !empty($content)) {
        $meta_description = substr(strip_tags($content), 0, 160) . '...';
    }

    // Auto-generate SEO title if not provided
    if (empty($seo_title)) {
        $seo_title = $title;
    }

    // Auto-generate excerpt if not provided
    if (empty($excerpt) && !empty($content)) {
        $excerpt = substr(strip_tags($content), 0, 150) . '...';
    }

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
        $stmt_update = $conn->prepare("UPDATE posts SET title = ?, content = ?, status = ?, slug = ?, keywords = ?, meta_description = ?, tags = ?, category = ?, excerpt = ?, seo_title = ?, featured_image = ?, publish_at = ?, updated_at = CURRENT_TIMESTAMP WHERE post_id = ?");
        $publish_at_value = !empty($publish_at) ? date('Y-m-d H:i:s', strtotime($publish_at)) : null;
        $stmt_update->bind_param("sssssssssssssi", $title, $content, $status, $slug, $keywords, $meta_description, $tags, $category, $excerpt, $seo_title, $featured_image, $publish_at_value, $post_id);
        
        if ($stmt_update->execute()) {
            log_audit_action($_SESSION['user_id'], null, 'update_post', 'post', $post_id, "Updated: $title");
            $_SESSION['success_message'] = "Content post updated successfully!";
            redirect('admin/posts/view_post.php?slug=' . urlencode($slug));
        } else {
            $errors['db'] = "Failed to update post: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

include_once '../../includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <div class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Modify <span class="text-primary"><?php echo htmlspecialchars(substr($post['title'], 0, 30)); ?></span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Update post content and settings</p>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200">Please correct the errors below:</p>
                <ul class="text-xs text-red-300 mt-2 list-disc list-inside">
                    <?php foreach ($errors as $field => $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form action="edit_post.php?id=<?php echo $post_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrf_input(); ?>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Post Content</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="title" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required 
                           class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['title']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                           value="<?php echo htmlspecialchars($title); ?>">
                    <?php if (isset($errors['title'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['title']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="content" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Content <span class="text-red-500">*</span></label>
                    <textarea name="content" id="content" rows="12" required 
                              class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['content']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($content); ?></textarea>
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
                        <select name="status" id="status" 
                                class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="Draft" <?php if($post['status'] == 'Draft') echo 'selected'; ?>>Draft (Not Visible)</option>
                            <option value="Published" <?php if($post['status'] == 'Published') echo 'selected'; ?>>Published (Visible to Clients)</option>
                        </select>
                    </div>
                    <div>
                        <label for="publish_at" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Schedule Publish <span class="text-slate-500">(Optional)</span></label>
                        <input type="datetime-local" name="publish_at" id="publish_at" value="<?php echo !empty($publish_at) ? date('Y-m-d\TH:i', strtotime($publish_at)) : ''; ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['publish_at']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                               placeholder="Leave blank to publish immediately">
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
                        <input type="text" name="seo_title" id="seo_title" maxlength="60" value="<?php echo htmlspecialchars($seo_title); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['seo_title']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                               placeholder="Custom SEO title (leave blank to use post title)">
                        <p class="text-[10px] text-slate-500 mt-1"><?php echo strlen($seo_title); ?>/60 characters</p>
                        <?php if (isset($errors['seo_title'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['seo_title']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="category" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Category</label>
                        <select name="category" id="category" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">Select Category</option>
                            <option value="Investigations" <?php if($category == 'Investigations') echo 'selected'; ?>>Investigations</option>
                            <option value="Security" <?php if($category == 'Security') echo 'selected'; ?>>Security</option>
                            <option value="Legal Updates" <?php if($category == 'Legal Updates') echo 'selected'; ?>>Legal Updates</option>
                            <option value="Technology" <?php if($category == 'Technology') echo 'selected'; ?>>Technology</option>
                            <option value="Industry News" <?php if($category == 'Industry News') echo 'selected'; ?>>Industry News</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="keywords" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Keywords <span class="text-slate-500">(Optional)</span></label>
                    <input type="text" name="keywords" id="keywords" value="<?php echo htmlspecialchars($keywords); ?>"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                           placeholder="private investigation, surveillance, security, corporate intelligence">
                    <p class="text-[10px] text-slate-500 mt-1">Comma-separated keywords for SEO optimization</p>
                </div>

                <div>
                    <label for="tags" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Tags <span class="text-slate-500">(Optional)</span></label>
                    <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($tags); ?>"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                           placeholder="surveillance, fraud, investigation, security">
                    <p class="text-[10px] text-slate-500 mt-1">Comma-separated tags for categorization and filtering</p>
                </div>

                <div>
                    <label for="meta_description" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Meta Description</label>
                    <textarea name="meta_description" id="meta_description" rows="3" maxlength="160"
                              class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['meta_description']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($meta_description); ?></textarea>
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
                    <input type="file" name="featured_image" id="featured_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                           class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-orange-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all cursor-pointer">
                    <p class="text-[10px] text-slate-500 mt-2">Supported formats: JPEG, PNG, WebP, GIF (Max 5MB). Leave empty to keep existing image.</p>
                </div>
                <?php if (!empty($featured_image)): ?>
                    <div class="mt-4">
                        <p class="text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Current Image</p>
                        <img src="<?php echo htmlspecialchars($featured_image); ?>" alt="Current featured image" class="w-48 h-auto rounded-lg border border-slate-700 object-cover">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">05. Content Preview</h2>
            </div>
            <div class="p-6">
                <label for="excerpt" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Excerpt/Summary <span class="text-slate-500">(Optional)</span></label>
                <textarea name="excerpt" id="excerpt" rows="4"
                          class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($excerpt); ?></textarea>
                <p class="text-[10px] text-slate-500 mt-1">Short summary for post previews (leave blank for auto-generation from content)</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="index.php" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-save mr-2"></i>Update Post
            </button>
        </div>
    </form>
</div>

<?php include_once '../../includes/footer.php'; ?>
