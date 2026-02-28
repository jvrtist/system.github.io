<?php
// clients/delete_client.php
require_once '../config.php'; // Adjust path as needed
require_login();

$page_title = "Delete Client"; // Not strictly necessary as this page mostly processes and redirects

// Get client ID and CSRF token from URL
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// 1. Validate CSRF token
if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. Client deletion aborted.";
    redirect('clients/');
}

// 2. Validate Client ID
if ($client_id <= 0) {
    $_SESSION['error_message'] = "Invalid client ID specified for deletion.";
    redirect('clients/');
}

$conn = get_db_connection();
$client_name_for_message = "Client (ID: $client_id)"; // Fallback name

if ($conn) {
    // First, fetch the client's name for a more user-friendly message (optional but good practice)
    $stmt_fetch = $conn->prepare("SELECT first_name, last_name FROM clients WHERE client_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $client_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $client_data = $result_fetch->fetch_assoc();
            $client_name_for_message = htmlspecialchars($client_data['first_name'] . ' ' . $client_data['last_name']);
        } else {
            $_SESSION['error_message'] = "Client not found for deletion (ID: $client_id).";
            $stmt_fetch->close();
            $conn->close();
            redirect('clients/');
        }
        $stmt_fetch->close();
    } else {
        // Not critical if name fetch fails, can still proceed with deletion using ID in message
        error_log("Delete Client: Failed to prepare statement to fetch client name: " . $conn->error);
    }

    // 3. Proceed with deletion
    // The ON DELETE CASCADE in the database schema for 'cases' (and subsequently other related tables)
    // will handle deletion of associated records.
    $stmt_delete = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $client_id);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $_SESSION['success_message'] = "Client '" . $client_name_for_message . "' and all associated data have been successfully deleted.";
                // Optional: Log this action
                // log_audit_action($_SESSION['user_id'], 'delete_client', 'client', $client_id, 'Client deleted: ' . $client_name_for_message);
            } else {
                // This case might occur if the client was deleted by another process between fetch and delete,
                // or if the ID was valid but somehow didn't match any rows for deletion.
                $_SESSION['warning_message'] = "Client '" . $client_name_for_message . "' was not found or already deleted.";
            }
        } else {
            $_SESSION['error_message'] = "Error deleting client '" . $client_name_for_message . "': " . $stmt_delete->error;
            // Detailed error for logging
            error_log("Error deleting client ID $client_id: " . $stmt_delete->error);
        }
        $stmt_delete->close();
    } else {
        $_SESSION['error_message'] = "Database statement preparation error for deletion: " . $conn->error;
        error_log("Delete Client: Failed to prepare delete statement: " . $conn->error);
    }
    $conn->close();
} else {
    $_SESSION['error_message'] = "Database connection failed. Client deletion aborted.";
}

redirect('clients/'); // Redirect back to the client list page

?>
