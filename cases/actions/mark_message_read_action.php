<?php
// cases/actions/mark_message_read_action.php
require_once '../../config.php'; // Adjust path: up two levels to project root
require_login(); // Ensure staff is logged in

// Get message ID, case ID (for redirect), and CSRF token from URL
$message_id = isset($_GET['message_id']) ? (int)$_GET['message_id'] : 0;
$case_id_for_redirect = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// 1. Validate CSRF token
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. Action aborted.";
    redirect('cases/view_case.php?id=' . $case_id_for_redirect);
}

// 2. Validate Message ID and Case ID
if ($message_id <= 0) {
    $_SESSION['error_message'] = "Invalid message ID specified.";
    redirect('cases/view_case.php?id=' . $case_id_for_redirect);
}
if ($case_id_for_redirect <= 0) {
    // If case_id is not provided for redirect, go to general cases list
    $_SESSION['error_message'] = "Case context missing for message action.";
    redirect('cases/');
}

$conn = get_db_connection();

if ($conn) {
    // 3. Verify the message exists and belongs to the specified case (optional but good)
    // And ensure it was sent by the client if only client messages can be marked read this way.
    $stmt_check = $conn->prepare("SELECT message_id FROM client_messages WHERE message_id = ? AND case_id = ? AND sent_by_client = TRUE");
    if ($stmt_check) {
        $stmt_check->bind_param("ii", $message_id, $case_id_for_redirect);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows !== 1) {
            $_SESSION['error_message'] = "Message not found or not eligible to be marked as read.";
            $stmt_check->close();
            $conn->close();
            redirect('cases/view_case.php?id=' . $case_id_for_redirect . '#client-messages-content');
        }
        $stmt_check->close();
    } else {
        $_SESSION['error_message'] = "Error verifying message: " . $conn->error;
        $conn->close();
        redirect('cases/view_case.php?id=' . $case_id_for_redirect . '#client-messages-content');
    }


    // 4. Proceed with updating the sent_at timestamp (as a mark of read, since no read flag exists)
    $stmt = $conn->prepare("UPDATE client_messages SET is_read_by_staff = TRUE WHERE message_id = ? AND sent_by_client = TRUE");
    
    if ($stmt) {
        $stmt->bind_param("i", $message_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['success_message'] = "Message (ID: $message_id) marked as read.";
                // Optional: Update the parent case's updated_at timestamp
                $update_case_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                if($update_case_stmt) {
                    $update_case_stmt->bind_param("i", $case_id_for_redirect);
                    $update_case_stmt->execute();
                    $update_case_stmt->close();
                }
            } else {
                // This could happen if the message was already marked as read by another staff or process.
                $_SESSION['info_message'] = "Message (ID: $message_id) was already marked as read or no change was needed.";
            }
        } else {
            $_SESSION['error_message'] = "Error marking message as read: " . $stmt_update->error;
            error_log("Mark Message Read Error: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message'] = "Database statement preparation error for marking message read: " . $conn->error;
    }
    $conn->close();
} else {
    $_SESSION['error_message'] = "Database connection failed. Could not mark message as read.";
}

// Redirect back to the case view page, specifically to the client messages tab
redirect('cases/view_case.php?id=' . $case_id_for_redirect . '#client-messages-content');
?>
