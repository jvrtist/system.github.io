<?php
// client_portal/actions/send_message_action.php

// Correctly require the config file from two directories up.
require_once __DIR__ . '/../../config.php';

// The config file starts the session, so now we can check login status.
// We don't need to include client_auth.php, as this is an action file, not a page.
// We will perform the check manually.
if (!is_client_logged_in()) {
    // If we're here, the session is invalid. Stop execution.
    // We can't redirect because this might be an AJAX call in the future.
    // So we'll send an error response.
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token(); 

    // --- Retrieve form data ---
    $case_id = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
    $client_id_from_form = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $client_id_from_session = $_SESSION[CLIENT_ID_SESSION_VAR];

    $message_subject = isset($_POST['message_subject']) && !empty($_POST['message_subject']) ? sanitize_input($_POST['message_subject']) : null;
    $message_content = isset($_POST['message_content']) ? sanitize_input($_POST['message_content']) : '';
    
    $replied_to_message_id = isset($_POST['replied_to_message_id']) && !empty($_POST['replied_to_message_id']) ? (int)$_POST['replied_to_message_id'] : null;

    // --- Validation ---
    if ($case_id <= 0 || $client_id_from_form !== $client_id_from_session) {
        $_SESSION['client_error_message'] = "Authentication error. Cannot send message.";
        redirect('client_portal/dashboard.php');
    }
    if (empty(trim($message_content))) {
        $_SESSION['client_error_message'] = "Message content cannot be empty.";
        redirect('client_portal/view_case_details.php?id=' . $case_id);
    }
    
    $conn = get_db_connection();
    if ($conn) {
        // Security Check: Verify that the case actually belongs to this client
        $stmt_verify_case = $conn->prepare("SELECT client_id FROM cases WHERE case_id = ? AND client_id = ?");
        $stmt_verify_case->bind_param("ii", $case_id, $client_id_from_session);
        $stmt_verify_case->execute();
        if ($stmt_verify_case->get_result()->num_rows !== 1) {
            $_SESSION['client_error_message'] = "You do not have permission to access this case.";
            $stmt_verify_case->close();
            $conn->close();
            redirect('client_portal/dashboard.php');
        }
        $stmt_verify_case->close();

        // SQL statement is correct
        $sql = "INSERT INTO client_messages (case_id, client_id, sent_by_client, message_subject, message_content) 
                VALUES (?, ?, TRUE, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // --- FIXED: Corrected the bind_param type string ---
            $stmt->bind_param("isss",
                $case_id, 
                $client_id_from_session, 
                $message_subject,
                $message_content
            );

            if ($stmt->execute()) {
                $_SESSION['client_success_message'] = "Your message has been sent successfully.";
                
                // Update the parent case's updated_at timestamp
                $update_case_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                if($update_case_stmt) {
                    $update_case_stmt->bind_param("i", $case_id);
                    $update_case_stmt->execute();
                    $update_case_stmt->close();
                }
            } else {
                $_SESSION['client_error_message'] = "Error sending message: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['client_error_message'] = "Database statement preparation error: " . $conn->error;
        }
        $conn->close();
    } else {
        $_SESSION['client_error_message'] = "Database connection failed. Message not sent.";
    }

    redirect('client_portal/view_case_details.php?id=' . $case_id . '#messages-content');

} else {
    $_SESSION['client_error_message'] = "Invalid request method.";
    redirect('client_portal/dashboard.php');
}
?>