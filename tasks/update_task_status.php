<?php
// tasks/update_task_status.php
require_once '../config.php'; // Adjust path to root config.php
require_login();

$task_id_to_update = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$new_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// 1. Validate CSRF token
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. Task status update aborted.";
    redirect('tasks/');
}

// 2. Validate Task ID
if ($task_id_to_update <= 0) {
    $_SESSION['error_message'] = "Invalid task ID specified for status update.";
    redirect('tasks/');
}

// 3. Validate the new status (ensure it's one of the allowed statuses)
$allowed_task_statuses = ['Pending', 'In Progress', 'Completed', 'Deferred']; // From tasks table schema
if (empty($new_status) || !in_array($new_status, $allowed_task_statuses)) {
    $_SESSION['error_message'] = "Invalid status provided for update ('" . htmlspecialchars($new_status) . "').";
    redirect('tasks/');
}

$conn = get_db_connection();
$task_description_for_message = "Task (ID: $task_id_to_update)"; // Fallback

if ($conn) {
    // Optional: Fetch task description for a better message
    $stmt_fetch = $conn->prepare("SELECT description, case_id, created_by_user_id, assigned_to_user_id FROM tasks WHERE task_id = ?");
    $original_case_id = null;
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $task_id_to_update);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $task_details = $result_fetch->fetch_assoc();
            $task_description_for_message = htmlspecialchars(substr($task_details['description'], 0, 50)) . (strlen($task_details['description']) > 50 ? '...' : '');
            $original_case_id = $task_details['case_id'];
            $current_user_id = $_SESSION['user_id'];
            $is_task_owner = ((int)$task_details['created_by_user_id'] === (int)$current_user_id);
            $is_task_assignee = (!empty($task_details['assigned_to_user_id']) && (int)$task_details['assigned_to_user_id'] === (int)$current_user_id);
            if (!user_has_role('admin') && !$is_task_owner && !$is_task_assignee) {
                $_SESSION['error_message'] = "You do not have permission to update this task.";
                $stmt_fetch->close();
                $conn->close();
                redirect('tasks/');
            }
        } else {
            $_SESSION['error_message'] = "Task not found for status update (ID: $task_id_to_update).";
            $stmt_fetch->close();
            $conn->close();
            redirect('tasks/');
        }
        $stmt_fetch->close();
    }


    // 4. Proceed with status update
    $sql = "UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE task_id = ?";
    $stmt_update = $conn->prepare($sql);

    if ($stmt_update) {
        $stmt_update->bind_param("si", $new_status, $task_id_to_update);
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $_SESSION['success_message'] = "Status for task '" . $task_description_for_message . "' updated to '" . htmlspecialchars($new_status) . "'.";
                
                // Update the parent case's updated_at timestamp if case_id was found
                if ($original_case_id) {
                    $update_case_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                    if($update_case_stmt) {
                        $update_case_stmt->bind_param("i", $original_case_id);
                        $update_case_stmt->execute();
                        $update_case_stmt->close();
                    } else {
                        error_log("Failed to prepare statement to update case updated_at after task status update: " . $conn->error);
                    }
                }

            } else {
                // This could happen if the status was already the new status, or task not found (though checked above)
                $_SESSION['warning_message'] = "Task status for '" . $task_description_for_message . "' was already '" . htmlspecialchars($new_status) . "' or task not found. No changes made.";
            }
        } else {
            $_SESSION['error_message'] = "Error updating task status for '" . $task_description_for_message . "': " . $stmt_update->error;
            error_log("Update Task Status Error: " . $stmt_update->error . " SQL: " . $sql);
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message'] = "Database statement preparation error for task status update: " . $conn->error;
    }
    $conn->close();
} else {
    $_SESSION['error_message'] = "Database connection failed. Task status update aborted.";
}

// Redirect back to the task list page (or potentially a referring page if tracked)
$redirect_url = 'tasks/';
if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tasks/index.php') !== false) {
    // If referred from tasks list, try to preserve filters by redirecting back to referer
    // This requires filters to be GET parameters on tasks/index.php
    // For simplicity now, just redirect to base tasks list.
    // A more robust solution would parse HTTP_REFERER and append query string.
    // header("Location: " . $_SERVER['HTTP_REFERER']);
    // exit;
}

redirect($redirect_url);
?>
