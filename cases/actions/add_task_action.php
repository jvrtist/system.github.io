<?php
require_once '../../config.php';
require_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $case_id = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
    $description = sanitize_input($_POST['task_description']);
    $priority = sanitize_input($_POST['task_priority']);
    $due_date = !empty($_POST['task_due_date']) ? sanitize_input($_POST['task_due_date']) : null;
    $assigned_to = !empty($_POST['task_assigned_to']) ? (int)$_POST['task_assigned_to'] : null;

    if ($case_id <= 0 || empty(trim($description))) {
        $_SESSION['error_message'] = "Invalid data provided for the task.";
        redirect('cases/view_case.php?id=' . $case_id);
    }

    $conn = get_db_connection();
    $sql = "INSERT INTO tasks (case_id, description, priority, due_date, assigned_to_user_id, status) 
            VALUES (?, ?, ?, ?, ?, 'Pending')";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssi", $case_id, $description, $priority, $due_date, $assigned_to);
        
        if ($stmt->execute()) {
            $update_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
            $update_stmt->bind_param("i", $case_id);
            $update_stmt->execute();
            
            $_SESSION['success_message'] = "Task added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add task: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error: Could not prepare statement.";
    }
    $conn->close();

    redirect('cases/view_case.php?id=' . $case_id);
} else {
    redirect('cases/');
}
?>
