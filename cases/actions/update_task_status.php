<?php
require_once '../../config.php';
require_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We expect JSON input for this endpoint
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
        exit;
    }

    $task_id = isset($data['task_id']) ? (int)$data['task_id'] : 0;
    $new_status = isset($data['status']) ? sanitize_input($data['status']) : '';
    $valid_statuses = ['Pending', 'In Progress', 'Completed', 'Deferred'];

    if ($task_id <= 0 || !in_array($new_status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid task ID or status.']);
        exit;
    }

    $conn = get_db_connection();
    
    // Security check: Ensure the user has permission to update this task.
    // An admin or the user assigned to the task can update it.
    $check_sql = "SELECT t.assigned_to_user_id, c.assigned_to_user_id as case_assigned_to FROM tasks t JOIN cases c ON t.case_id = c.case_id WHERE t.task_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $task_id);
    $stmt_check->execute();
    $task_data = $stmt_check->get_result()->fetch_assoc();
    
    $is_admin = user_has_role('admin');
    $is_assigned_user = ($_SESSION['user_id'] == $task_data['assigned_to_user_id'] || $_SESSION['user_id'] == $task_data['case_assigned_to']);

    if (!$is_admin && !$is_assigned_user) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit;
    }

    $sql = "UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE task_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_status, $task_id);
        if ($stmt->execute()) {
            // Also update the parent case's timestamp
            $update_case_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = (SELECT case_id FROM tasks WHERE task_id = ?)");
            $update_case_stmt->bind_param("i", $task_id);
            $update_case_stmt->execute();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Task status updated.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $conn->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
}
?>
