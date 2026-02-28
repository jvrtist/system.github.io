<?php
require_once '../../config.php';
require_once '../client_auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
    $client_id = $_SESSION[CLIENT_ID_SESSION_VAR];

    if ($invoice_id <= 0) {
        $_SESSION['client_error_message'] = "Invalid invoice.";
        redirect('client_portal/invoices.php');
    }

    $conn = get_db_connection();

    // 1. Fetch Invoice and Case Details
    $stmt = $conn->prepare("
        SELECT i.invoice_id, i.total_amount, i.amount_paid, i.status, i.case_id, c.retainer_amount 
        FROM invoices i
        JOIN cases c ON i.case_id = c.case_id
        WHERE i.invoice_id = ? AND i.client_id = ?
    ");
    $stmt->bind_param("ii", $invoice_id, $client_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$invoice) {
        $_SESSION['client_error_message'] = "Invoice not found or access denied.";
        redirect('client_portal/invoices.php');
    }

    if ($invoice['status'] === 'Paid') {
        $_SESSION['client_error_message'] = "Invoice is already paid.";
        redirect('client_portal/view_invoice.php?id=' . $invoice_id);
    }

    $case_id = $invoice['case_id'];
    $retainer_limit = $invoice['retainer_amount'];
    $invoice_balance = $invoice['total_amount'] - $invoice['amount_paid'];

    // 2. Calculate Used Retainer (Sum of all payments made with method 'Retainer' for this case)
    // We need to look at all invoices for this case and sum their 'Retainer' payments.
    $stmt_used = $conn->prepare("
        SELECT SUM(ip.amount_paid) as used_amount 
        FROM invoice_payments ip
        JOIN invoices inv ON ip.invoice_id = inv.invoice_id
        WHERE inv.case_id = ? AND ip.payment_method = 'Retainer'
    ");
    $stmt_used->bind_param("i", $case_id);
    $stmt_used->execute();
    $used_result = $stmt_used->get_result()->fetch_assoc();
    $retainer_used = $used_result['used_amount'] ?? 0.00;
    $stmt_used->close();

    $retainer_available = $retainer_limit - $retainer_used;

    if ($retainer_available <= 0) {
        $_SESSION['client_error_message'] = "No retainer funds available.";
        redirect('client_portal/view_invoice.php?id=' . $invoice_id);
    }

    // 3. Determine Payment Amount
    $payment_amount = min($invoice_balance, $retainer_available);

    if ($payment_amount <= 0) {
        $_SESSION['client_error_message'] = "Cannot process payment amount of zero.";
        redirect('client_portal/view_invoice.php?id=' . $invoice_id);
    }

    // 4. Record Payment
    $conn->begin_transaction();
    try {
        // Insert Payment Record
        $stmt_pay = $conn->prepare("INSERT INTO invoice_payments (invoice_id, payment_date, amount_paid, payment_method, notes) VALUES (?, CURRENT_DATE, ?, 'Retainer', 'Paid via Client Portal using Retainer Funds')");
        $stmt_pay->bind_param("id", $invoice_id, $payment_amount);
        $stmt_pay->execute();
        $stmt_pay->close();

        // Update Invoice Status
        $new_amount_paid = $invoice['amount_paid'] + $payment_amount;
        $new_status = ($new_amount_paid >= $invoice['total_amount'] - 0.01) ? 'Paid' : 'Partially Paid';
        
        $stmt_update = $conn->prepare("UPDATE invoices SET amount_paid = ?, status = ?, payment_date = CURRENT_DATE, updated_at = CURRENT_TIMESTAMP WHERE invoice_id = ?");
        $stmt_update->bind_param("dsi", $new_amount_paid, $new_status, $invoice_id);
        $stmt_update->execute();
        $stmt_update->close();

        $conn->commit();
        $_SESSION['client_success_message'] = "Payment of R" . number_format($payment_amount, 2) . " applied from retainer.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['client_error_message'] = "Transaction failed: " . $e->getMessage();
    }
    $conn->close();

    redirect('client_portal/view_invoice.php?id=' . $invoice_id);

} else {
    redirect('client_portal/invoices.php');
}
?>
