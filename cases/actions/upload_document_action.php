<?php
require_once '../../config.php';
require_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $case_id = isset($_POST['case_id']) ? (int)$_POST['case_id'] : 0;
    $description = sanitize_input($_POST['document_description']);
    $visibility = isset($_POST['document_visibility']) ? sanitize_input($_POST['document_visibility']) : 'Client Visible';
    
    // Validate visibility value
    if (!in_array($visibility, ['Client Visible', 'Staff Only'])) {
        $visibility = 'Client Visible'; // Default to client visible if invalid value
    }
    $user_id = $_SESSION['user_id'];

    if ($case_id <= 0 || !isset($_FILES['document_file']) || $_FILES['document_file']['error'] != UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Invalid data or file upload error.";
        redirect('cases/view_case.php?id=' . $case_id);
    }

    $file = $_FILES['document_file'];
    $file_name = basename($file['name']);
    $file_size = $file['size'];
    $file_type = $file['type'];
    $file_tmp_name = $file['tmp_name'];

    // Validate file size and type
    if ($file_size > MAX_FILE_SIZE_BYTES) {
        $_SESSION['error_message'] = "File size exceeds the limit of " . (MAX_FILE_SIZE_BYTES / 1024 / 1024) . "MB.";
        redirect('cases/view_case.php?id=' . $case_id);
    }
    if (!array_key_exists($file_type, ALLOWED_MIME_TYPES)) {
        $_SESSION['error_message'] = "Invalid file type. Allowed types: " . implode(', ', array_values(ALLOWED_MIME_TYPES));
        redirect('cases/view_case.php?id=' . $case_id);
    }

    // Calculate SHA-256 Hash for Chain of Custody
    $file_hash = hash_file('sha256', $file_tmp_name);

    // Create a unique stored file name
    $file_extension = ALLOWED_MIME_TYPES[$file_type];
    $stored_file_name = uniqid('doc_' . $case_id . '_', true) . '.' . $file_extension;
    $upload_path = UPLOAD_DIR_BASE . $stored_file_name;

    if (move_uploaded_file($file_tmp_name, $upload_path)) {
        $conn = get_db_connection();
        $sql = "INSERT INTO documents (case_id, file_name, stored_file_name, file_path, file_hash, description, visibility, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $relative_path = $stored_file_name; // Store relative path
            $stmt->bind_param("issssssi", $case_id, $file_name, $stored_file_name, $relative_path, $file_hash, $description, $visibility, $user_id);
            
            if ($stmt->execute()) {
                $update_stmt = $conn->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                $update_stmt->bind_param("i", $case_id);
                $update_stmt->execute();
                
                $_SESSION['success_message'] = "Document uploaded successfully. Digital fingerprint secured. Visibility: " . $visibility . ".";
            } else {
                $_SESSION['error_message'] = "Failed to save document record: " . $stmt->error;
                unlink($upload_path); // Delete uploaded file if DB insert fails
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error: Could not prepare statement.";
            unlink($upload_path);
        }
        $conn->close();
    } else {
        $_SESSION['error_message'] = "Failed to move uploaded file.";
    }

    redirect('cases/view_case.php?id=' . $case_id);
} else {
    redirect('cases/');
}
?>
