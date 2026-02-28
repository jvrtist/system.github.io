<?php
require_once '../config.php';
require_login();

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($invoice_id <= 0) {
    echo "Invalid invoice ID.";
    exit;
}

$conn = get_db_connection();
$stmt = $conn->prepare("SELECT i.*, cl.first_name, cl.last_name, cl.company_name, cl.email, cl.phone, cl.address, cs.case_number as related_case_number FROM invoices i JOIN clients cl ON i.client_id = cl.client_id LEFT JOIN cases cs ON i.case_id = cs.case_id WHERE i.invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    echo "Invoice not found.";
    exit;
}

// Fetch items
$stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id ASC");
$stmt_items->bind_param("i", $invoice_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

// Fetch payment history for this invoice
$stmt_payments = $conn->prepare("SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC");
$stmt_payments->bind_param("i", $invoice_id);
$stmt_payments->execute();
$payments = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_payments->close();

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?> - Print</title>
<style>
    /* Reset and Base Styles */
    * { margin:0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #000; background: #fff; font-size: 10pt; line-height: 1; }
    
    /* Print Specifics */
    @media print {
        @page { size: A4; margin: 10mm; }
        body { margin: 0; }
    }

    .container {
        width: 100%;
        max-width: 210mm; /* A4 width */
        margin: 0 auto;
        padding: 0;
    }

    /* Header */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8mm;
        padding-bottom: 5mm;
        border-bottom: 2px solid #000;
    }
    .header-left img { max-width: 25mm; height: auto; margin-bottom: 2mm; }
    .header-left h2 { font-size: 14pt; font-weight: bold; margin: 0; }
    .header-left p { font-size: 8pt; margin: 1mm 0; line-height: 1.2; }
    
    .header-right { text-align: right; }
    .header-right h1 { font-size: 18pt; font-weight: bold; text-transform: uppercase; margin: 0; }
    .header-right p { font-size: 11pt; font-weight: bold; margin-top: 1mm; }

    /* Info Sections */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
    .info-table td { vertical-align: top; padding: 0; }
    .info-col-left { width: 50%; padding-right: 10mm; }
    .info-col-right { width: 50%; padding-left: 10mm; }

    .section-title { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3mm; }
    .info-text { font-size: 10pt; margin-bottom: 1mm; }
    .info-text.bold { font-weight: bold; }

    .details-table { width: 100%; border-collapse: collapse; }
    .details-table td { padding: 2mm 0; }
    .details-label { font-weight: bold; width: 40%; }
    .details-value { text-align: right; }

    /* Items Table */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 8mm; }
    .items-table th, .items-table td { border: 1px solid #ddd; padding: 3mm 4mm; text-align: left; font-size: 9pt; }
    .items-table th { background-color: #f0f0f0 !important; font-weight: bold; -webkit-print-color-adjust: exact; }
    .items-table .text-right { text-align: right; }
    .items-table .bold { font-weight: bold; }

    /* Totals */
    .totals-container { float: right; width: 45%; margin-left: 10mm; }
    .totals-row { display: flex; justify-content: space-between; padding: 2mm 4mm; border-bottom: 1px solid #ddd; font-size: 9pt; }
    .totals-row.total { border-bottom: 2px solid #000; font-weight: bold; font-size: 11pt; margin-top: 1mm; }
    .totals-row.balance { background-color: #f0f0f0 !important; font-weight: bold; -webkit-print-color-adjust: exact; }

    /* Payment History */
    .payment-history { clear: both; margin-top: 0; padding-top: 10mm; }
    .payment-history h3 { font-size: 9pt; font-weight: bold; text-transform: uppercase; margin-bottom: 3mm; border-bottom: 1px solid #000; padding-bottom: 1mm; }
    
    /* Footer */
    .footer { clear: both; margin-top: 15mm; padding-top: 5mm; border-top: 1px solid #ddd; font-size: 8pt; }
    .footer p { margin-bottom: 2mm; }

    /* Utilities */
    .clearfix::after { content: ""; display: table; clear: both; }
</style>
</head>
<body>
    <div class="print-area bg-white text-black p-0">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="../images/logo.png" alt="Logo">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>
                    95 Houtkop Road<br>
                    Duncanville, Vereeniging, 1900<br>
                    billing@iss-investigations.co.za
                </p>
            </div>
            <div class="header-right">
                <h1>INVOICE</h1>
                <p>#<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
            </div>
        </div>

        <!-- Info Section -->
        <table class="info-table">
            <tr>
                <td class="info-col-left">
                    <div class="section-title">BILL TO</div>
                    <div class="info-text bold"><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></div>
                    <?php if ($invoice['company_name']): ?>
                        <div class="info-text"><?php echo htmlspecialchars($invoice['company_name']); ?></div>
                    <?php endif; ?>
                    <?php if ($invoice['address']): ?>
                        <div class="info-text"><?php echo nl2br(htmlspecialchars($invoice['address'])); ?></div>
                    <?php endif; ?>
                    <div class="info-text"><?php echo htmlspecialchars($invoice['email']); ?></div>
                    <div class="info-text"><?php echo htmlspecialchars($invoice['phone']); ?></div>
                </td>
                <td class="info-col-right">
                    <table class="details-table">
                        <tr>
                            <td class="details-label">Invoice Date:</td>
                            <td class="details-value"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                        </tr>
                        <tr>
                            <td class="details-label">Due Date:</td>
                            <td class="details-value"><?php echo date('d M Y', strtotime($invoice['due_date'])); ?></td>
                        </tr>
                        <?php if ($invoice['related_case_number']): ?>
                        <tr>
                            <td class="details-label">Case Ref:</td>
                            <td class="details-value"><?php echo htmlspecialchars($invoice['related_case_number']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right" style="width: 12%">Qty</th>
                    <th class="text-right" style="width: 15%">Unit Price</th>
                    <th class="text-right" style="width: 15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?php echo nl2br(htmlspecialchars($it['description'])); ?></td>
                        <td class="text-right"><?php echo number_format($it['quantity'], 2); ?></td>
                        <td class="text-right">R<?php echo number_format($it['unit_price'], 2); ?></td>
                        <td class="text-right bold">R<?php echo number_format($it['quantity'] * $it['unit_price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="clearfix">
            <div class="totals-container">
                <div class="totals-row">
                    <span>Subtotal:</span>
                    <span>R<?php echo number_format($invoice['subtotal_amount'], 2); ?></span>
                </div>
                <div class="totals-row">
                    <span>Tax (<?php echo number_format($invoice['tax_rate_percentage'], 2); ?>%):</span>
                    <span>R<?php echo number_format($invoice['tax_amount'], 2); ?></span>
                </div>
                <div class="totals-row total">
                    <span>Total:</span>
                    <span>R<?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
                <div class="totals-row">
                    <span>Amount Paid:</span>
                    <span>R<?php echo number_format($invoice['amount_paid'], 2); ?></span>
                </div>
                <div class="totals-row balance">
                    <span>Balance Due:</span>
                    <span>R<?php echo number_format($invoice['total_amount'] - $invoice['amount_paid'], 2); ?></span>
                </div>
            </div>
        </div>

       

        <!-- Footer -->
        <div class="footer">
            <p><strong>Terms:</strong> Payment due by <?php echo date('d M Y', strtotime($invoice['due_date'])); ?></p>
            <p>Thank you for your business!</p>
        </div>
    </div>
</div>

<div style="page-break-before: always" > </div>
 <!-- Payment History -->
        <?php if (!empty($payments)): ?>
        <div class="payment-history">
            <h3>Payment History</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 25%">Date</th>
                        <th style="width: 30%">Method</th>
                        <th class="text-right" style="width: 25%">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method'] ?: 'N/A'); ?></td>
                            <td class="text-right bold">R<?php echo number_format($payment['amount_paid'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>
