<?php
// tasks/delete_task.php
require_once '../config.php'; // Adjust path to root config.php
require_login();

// Example: Restrict direct deletion from URL to admins,
// or allow users to delete their own tasks if needed (add more logic for that).
if (!user_has_role('admin')) {
    // If non-admins should be able to delete tasks they created or are assigned to,
    // you'd need more complex permission checks here.
    // For now, only admins can delete via this direct script.
    // Task status updates (like marking complete) might be more common for non-admins.
    // $_SESSION['error_message'] = "You do not have permission to delete tasks directly. Please use the edit screen or contact an administrator.";
    // redirect('tasks/');
    // For simplicity, we'll allow any logged-in user to delete for now, but in production, this needs refinement.
}

$page_title = "Delete Task"; // Not strictly necessary

// Get task ID and CSRF token from URL
$task_id_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// 1. Validate CSRF token
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. Task deletion aborted.";
    redirect('tasks/');
}

// 2. Validate Task ID
if ($task_id_to_delete <= 0) {
    $_SESSION['error_message'] = "Invalid task ID specified for deletion.";
    redirect('tasks/');
}

$conn = get_db_connection();
$task_description_for_message = "Task (ID: $task_id_to_delete)"; // Fallback
$original_case_id_for_update = null;

if ($conn) {
    // Optional: Fetch task description and case_id for a better message and to update parent case
    $stmt_fetch = $conn->prepare("SELECT description, case_id, created_by_user_id, assigned_to_user_id FROM tasks WHERE task_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $task_id_to_delete);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $task_details = $result_fetch->fetch_assoc();
            $task_description_for_message = htmlspecialchars(substr($task_details['description'], 0, 50)) . (strlen($task_details['description']) > 50 ? '...' : '');
            $original_case_id_for_update = $task_details['case_id'];
            $current_user_id = $_SESSION['user_id'];
            $is_task_owner = ((int)$task_details['created_by_user_id'] === (int)$current_user_id);
            $is_task_assignee = (!empty($task_details['assigned_to_user_id']) && (int)$task_details['assigned_to_user_id'] === (int)$current_user_id);
            if (!user_has_role('admin') && !$is_task_owner && !$is_task_assignee) {
                $_SESSION['error_message'] = "You do not have permission to delete this task.";
                $stmt_fetch->close();
                $conn->close();
                redirect('tasks/');
            }
        } else {
            // Task not found, but proceed with delete attempt
            $_SESSION['warning_message'] = "Task (ID: $task_id_to_delete) was not found before attempting deletion. It may have already been deleted.";
        }
        $stmt_fetch->close();
    } else {
        error_log("Delete Task: Failed to prepare statement to fetch task details: " . $conn->error);
    }

    // 3. Proceed with deletion
    $stmt_delete = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $task_id_to_delete);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $_SESSION['success_message'] = "Task '" . $task_description_for_message . "' has been successfully deleted.";
                
                // Update the parent case's updated_at timestamp if case_id was found
                if ($original_case_id_for_update) {
                    $update_case_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                    if($update_case_stmt) {
                        $update_case_stmt->bind_param("i", $original_case_id_for_update);
                        $update_case_stmt->execute();
                        $update_case_stmt->close();
                    } else {
                        error_log("Failed to prepare statement to update case updated_at after task deletion: " . $conn->error);
                    }
                }
                // Optional: Log this action
                // log_audit_action($_SESSION['user_id'], 'delete_task', 'task', $task_id_to_delete, 'Task deleted: ' . $task_description_for_message);
            } else {
                if (!isset($_SESSION['warning_message'])) { // If no prior warning about task not found
                     $_SESSION['warning_message'] = "Task '" . $task_description_for_message . "' was not found or already deleted. No changes made.";
                }
            }
        } else {
            $_SESSION['error_message'] = "Error deleting task '" . $task_description_for_message . "': " . $stmt_delete->error;
            error_log("Error deleting task ID $task_id_to_delete: " . $stmt_delete->error);
        }
        $stmt_delete->close();
    } else {
        $_SESSION['error_message'] = "Database statement preparation error for task deletion: " . $conn->error;
        error_log("Delete Task: Failed to prepare delete statement: " . $conn->error);
    }
    $conn->close();
} else {
    $_SESSION['error_message'] = "Database connection failed. Task deletion aborted.";
}

redirect('tasks/'); // Redirect back to the task list page

?>
