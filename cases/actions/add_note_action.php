<?php
require_once '../../config.php';
require_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $case_id = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
    $note_text = sanitize_input($_POST['note_text']);
    $user_id = $_SESSION['user_id'];

    if ($case_id <= 0 || empty(trim($note_text))) {
        $_SESSION['error_message'] = "Invalid data provided for the note.";
        redirect('cases/view_case.php?id=' . $case_id);
    }

    $conn = get_db_connection();
    $sql = "INSERT INTO case_notes (case_id, user_id, note_text) VALUES (?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iis", $case_id, $user_id, $note_text);
        
        if ($stmt->execute()) {
            // Also update the case's updated_at timestamp
            $update_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
            $update_stmt->bind_param("i", $case_id);
            $update_stmt->execute();
            
            $_SESSION['success_message'] = "Note added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add note: " . $stmt->error;
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
