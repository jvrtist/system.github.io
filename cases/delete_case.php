<?php
/**
 * ISS Investigations - Case Archive Action
 * Admin action to archive (soft delete) existing cases with audit logging.
 */
require_once '../config.php';
require_login();

// Only admins should be able to archive cases
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = 'ACCESS DENIED: Administrative clearance required.';
    redirect('cases/');
}

$case_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token.";
    redirect('cases/');
}

if ($case_id <= 0) {
    $_SESSION['error_message'] = "Invalid case ID.";
    redirect('cases/');
}

$conn = get_db_connection();

if ($conn) {
    // Soft delete: Update status to 'Archived'
    $stmt = $conn->prepare("UPDATE cases SET status = 'Archived', updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $case_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['success_message'] = "Case has been successfully archived.";
                log_audit_action($_SESSION['user_id'], null, 'archive_case', 'case', $case_id, 'Case archived');
            } else {
                $_SESSION['warning_message'] = "Case not found or already archived.";
            }
        } else {
            $_SESSION['error_message'] = "Error archiving case: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error.";
    }
    $conn->close();
} else {
    $_SESSION['error_message'] = "Database connection failed.";
}

redirect('cases/');
?>
