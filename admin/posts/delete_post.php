<?php
/**
 * ISS Investigations - Content Deletion Action
 * Handles safe deletion of blog posts with CSRF protection and audit logging.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$post_id_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. Deletion aborted.";
    redirect('admin/posts/');
}

if ($post_id_to_delete <= 0) {
    $_SESSION['error_message'] = "Invalid post ID specified.";
    redirect('admin/posts/');
}

$conn = get_db_connection();
if ($conn) {
    // Fetch post details before deletion for audit log
    $stmt_fetch = $conn->prepare("SELECT title FROM posts WHERE post_id = ?");
    $stmt_fetch->bind_param("i", $post_id_to_delete);
    $stmt_fetch->execute();
    $post_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();
    
    if (!$post_data) {
        $_SESSION['error_message'] = "Content post not found.";
        redirect('admin/posts/');
    }
    
    // ON DELETE CASCADE will handle deleting comments
    $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id_to_delete);

    if ($stmt->execute()) {
        log_audit_action($_SESSION['user_id'], null, 'delete_post', 'post', $post_id_to_delete, "Deleted: " . $post_data['title']);
        $_SESSION['success_message'] = "Content post has been permanently deleted.";
    } else {
        $_SESSION['error_message'] = "Error deleting post: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Database connection failed.";
}

redirect('admin/posts/');
?>