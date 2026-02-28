<?php
/**
 * ISS Investigations - Add Post Comment Action
 * Client portal action to add comments to blog posts.
 */
require_once '../../config.php';
require_once '../client_auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $post_slug = isset($_POST['post_slug']) ? $_POST['post_slug'] : '';
    $client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
    $comment_text = sanitize_input($_POST['comment']);

    if (empty($post_slug) || empty(trim($comment_text))) {
        $_SESSION['client_error_message'] = "Invalid data provided.";
        redirect('client_portal/posts.php');
    }

    $conn = get_db_connection();

    // First get the post_id from the slug
    $stmt_get_id = $conn->prepare("SELECT post_id FROM posts WHERE slug = ? AND status = 'Published' AND (publish_at IS NULL OR publish_at <= NOW())");
    $stmt_get_id->bind_param("s", $post_slug);
    $stmt_get_id->execute();
    $post_result = $stmt_get_id->get_result();
    
    if ($post_result->num_rows === 0) {
        $_SESSION['client_error_message'] = "Post not found or not available for commenting.";
        $conn->close();
        redirect('client_portal/posts.php');
    }
    
    $post_data = $post_result->fetch_assoc();
    $post_id = $post_data['post_id'];
    $stmt_get_id->close();

    // Insert comment
    $stmt = $conn->prepare("INSERT INTO post_comments (post_id, client_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $client_id, $comment_text);

    if ($stmt->execute()) {
        $_SESSION['client_success_message'] = "Comment posted successfully!";
    } else {
        $_SESSION['client_error_message'] = "Failed to post comment.";
    }
    $stmt->close();
    $conn->close();

    redirect('client_portal/view_post.php?slug=' . urlencode($post_slug));
} else {
    redirect('client_portal/posts.php');
}
?>