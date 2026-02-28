<?php
require_once '../../config.php';
require_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $case_id = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $staff_user_id = isset($_POST['staff_user_id']) ? (int)$_POST['staff_user_id'] : 0;
    $subject = sanitize_input($_POST['reply_message_subject']);
    $content = sanitize_input($_POST['reply_message_content']);

    if ($case_id <= 0 || $client_id <= 0 || $staff_user_id !== $_SESSION['user_id'] || empty(trim($content))) {
        $_SESSION['error_message'] = "Invalid data provided for the message.";
        redirect('cases/view_case.php?id=' . $case_id);
    }

    $conn = get_db_connection();
    $sql = "INSERT INTO client_messages (case_id, client_id, sent_by_client, message_subject, message_content) 
            VALUES (?, ?, FALSE, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiss", $case_id, $client_id, $subject, $content);
        
        if ($stmt->execute()) {
            $update_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
            $update_stmt->bind_param("i", $case_id);
            $update_stmt->execute();
            
            $_SESSION['success_message'] = "Message sent to client successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to send message: " . $stmt->error;
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
