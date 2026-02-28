<?php
/**
 * ISS Investigations - Invoice Deletion Action
 * Handles safe deletion of invoices with CSRF protection and audit logging.
 */
require_once '../config.php';
require_login();

// Only admins can delete invoices
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "ACCESS DENIED: Administrative clearance required.";
    redirect('invoices/');
}

$invoice_id_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
    $_SESSION['error_message'] = "Invalid security token. Deletion aborted.";
    redirect('invoices/');
}

if ($invoice_id_to_delete <= 0) {
    $_SESSION['error_message'] = "Invalid invoice ID specified.";
    redirect('invoices/');
}

$conn = get_db_connection();
if ($conn) {
    // Fetch invoice details before deletion for audit log
    $stmt_fetch = $conn->prepare("SELECT invoice_number FROM invoices WHERE invoice_id = ?");
    $stmt_fetch->bind_param("i", $invoice_id_to_delete);
    $stmt_fetch->execute();
    $invoice_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();
    
    if (!$invoice_data) {
        $_SESSION['error_message'] = "Invoice not found.";
        redirect('invoices/');
    }
    
    // The ON DELETE CASCADE constraint will automatically delete related records
    $stmt = $conn->prepare("DELETE FROM invoices WHERE invoice_id = ?");
    $stmt->bind_param("i", $invoice_id_to_delete);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            log_audit_action($_SESSION['user_id'], null, 'delete_invoice', 'invoice', $invoice_id_to_delete, "Deleted: " . $invoice_data['invoice_number']);
            $_SESSION['success_message'] = "Invoice has been permanently deleted.";
        } else {
            $_SESSION['error_message'] = "Invoice not found or already deleted.";
        }
    } else {
        $_SESSION['error_message'] = "Error deleting invoice: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Database connection failed.";
}

redirect('invoices/');
?>