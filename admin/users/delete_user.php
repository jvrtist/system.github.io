<?php
/**
 * ISS Investigations - User Deletion Action
 * Handles safe deletion of user accounts with admin checks and audit logging.
 */
require_once '../../config.php';
require_login();

// Ensure only admins can delete users
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('dashboard.php');
}

$page_title = "Delete User"; // Not strictly necessary as this page mostly processes and redirects

// Get user ID and CSRF token from URL
$user_id_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// 1. Validate CSRF token
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. User deletion aborted.";
    redirect('admin/users/');
}

// 2. Validate User ID
if ($user_id_to_delete <= 0) {
    $_SESSION['error_message'] = "Invalid user ID specified for deletion.";
    redirect('admin/users/');
}

// 3. Prevent critical deletions
if ($user_id_to_delete == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own account.";
    redirect('admin/users/');
}
if ($user_id_to_delete == 1) { // Assuming user_id 1 is a primary/super admin
    $_SESSION['error_message'] = "The primary administrator account (User ID 1) cannot be deleted.";
    redirect('admin/users/');
}

$conn = get_db_connection();
$username_for_message = "User (ID: $user_id_to_delete)"; // Fallback identifier

if ($conn) {
    // Check if the user to be deleted is the last remaining admin
    $user_to_delete_role_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $user_to_delete_role_stmt->bind_param("i", $user_id_to_delete);
    $user_to_delete_role_stmt->execute();
    $user_to_delete_role_result = $user_to_delete_role_stmt->get_result();
    if ($user_to_delete_role_result->num_rows > 0) {
        $user_to_delete_details = $user_to_delete_role_result->fetch_assoc();
        if ($user_to_delete_details['role'] === 'admin') {
            $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
            $admin_count_stmt->execute();
            $admin_count_result = $admin_count_stmt->get_result()->fetch_assoc();
            $admin_count_stmt->close();
            if ($admin_count_result['admin_count'] <= 1) {
                $_SESSION['error_message'] = "Cannot delete the last administrator account. Assign another user as admin first.";
                $user_to_delete_role_stmt->close();
                $conn->close();
                redirect('admin/users/');
            }
        }
    }
    $user_to_delete_role_stmt->close();


    // Fetch the username for a more user-friendly message (optional but good practice)
    $stmt_fetch = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $user_id_to_delete);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $user_data = $result_fetch->fetch_assoc();
            $username_for_message = htmlspecialchars($user_data['username']);
        } else {
            // User not found, but proceed with delete attempt as it will gracefully fail (0 affected rows)
            $_SESSION['warning_message'] = "User (ID: $user_id_to_delete) was not found before attempting deletion. It may have already been deleted.";
        }
        $stmt_fetch->close();
    } else {
        error_log("Delete User: Failed to prepare statement to fetch username: " . $conn->error);
    }

    // 4. Proceed with deletion
    // Consider what happens to records created/assigned by this user.
    // For 'clients' and 'cases' tables, `added_by_user_id`, `assigned_to_user_id`, `created_by_user_id`
    // have `ON DELETE SET NULL`. This means if a user is deleted, these fields will become NULL.
    // This is generally a safe approach.
    
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $user_id_to_delete);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $_SESSION['success_message'] = "User '" . $username_for_message . "' has been successfully deleted.";
                // Optional: Log this action
                // log_audit_action($_SESSION['user_id'], 'delete_user', 'user', $user_id_to_delete, 'Admin deleted user: ' . $username_for_message);
            } else {
                // If warning was set because user not found, this confirms it.
                if (!isset($_SESSION['warning_message'])) {
                     $_SESSION['warning_message'] = "User '" . $username_for_message . "' was not found or already deleted. No changes made.";
                }
            }
        } else {
            $_SESSION['error_message'] = "Error deleting user '" . $username_for_message . "': " . $stmt_delete->error;
            error_log("Error deleting user ID $user_id_to_delete: " . $stmt_delete->error);
        }
        $stmt_delete->close();
    } else {
        $_SESSION['error_message'] = "Database statement preparation error for user deletion: " . $conn->error;
        error_log("Delete User: Failed to prepare delete statement: " . $conn->error);
    }
    $conn->close();
} else {
    $_SESSION['error_message'] = "Database connection failed. User deletion aborted.";
}

redirect('admin/users/'); // Redirect back to the user list page

?>
