<?php
// client_portal/download_document.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/client_sensitive_auth.php'; // Requires private key verification

$document_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];

if ($document_id <= 0) {
    die("Invalid document request.");
}

$conn = get_db_connection();
if ($conn) {
    // --- Security Query ---
    // Join documents with cases to ensure the document belongs to a case owned by the logged-in client.
    $sql = "SELECT d.file_name, d.stored_file_name, d.file_path, d.file_type
            FROM documents d
            JOIN cases c ON d.case_id = c.case_id
            WHERE d.document_id = ? AND c.client_id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $document_id, $client_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $document = $result->fetch_assoc();
            $stmt->close();
            $conn->close();

            // Construct the full file path on the server
            // UPLOAD_DIR_BASE is defined in config.php
            $file_path = UPLOAD_DIR_BASE . $document['file_path'];

            // --- Security and File Handling ---
            if (file_exists($file_path) && is_readable($file_path)) {
                // Set headers to trigger browser download
                header('Content-Description: File Transfer');
                // Use the stored MIME type, or a generic one as a fallback
                header('Content-Type: ' . ($document['file_type'] ?: 'application/octet-stream'));
                // Suggest the original, user-friendly filename for the download
                header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                
                // Clear output buffer before sending the file
                ob_clean();
                flush();
                
                // Read the file and stream it to the browser
                readfile($file_path);
                exit; // Stop script execution after file is sent
            } else {
                // File not found on the server
                error_log("File not found for document ID: $document_id at path: $file_path");
                die("Error: The requested file could not be found on the server. Please contact support.");
            }
        } else {
            // No document found for this ID and Client, or permission denied
            $stmt->close();
            $conn->close();
            die("Access Denied: You do not have permission to download this file.");
        }
    } else {
        $conn->close();
        die("Database query error. Please try again later.");
    }
} else {
    die("Database connection error.");
}
?>