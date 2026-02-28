<?php
require_once '../config.php';
require_login();

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($invoice_id <= 0) {
    echo "Invalid invoice ID.";
    exit;
}

// Prepare a print HTML using the existing print_invoice.php logic by buffering
ob_start();
include 'print_invoice.php';
$html = ob_get_clean();

// If Dompdf is available via Composer autoload
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    try {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();
        // Stream PDF to browser
        $filename = 'invoice_' . $invoice_id . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 1]);
        exit;
    } catch (Exception $e) {
        error_log('Dompdf error: ' . $e->getMessage());
        // Fallthrough to render HTML fallback
    }
}

// Fallback: Serve the print HTML so user can save as PDF from browser
echo $html;
exit;
