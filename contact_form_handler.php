<?php
// A simple contact form handler. 
// For a real-world application, you should add more robust validation, 
// spam protection (like reCAPTCHA), and use a library like PHPMailer to send emails.
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"]);

    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.php?status=error");
        exit;
    }

    // --- In a real application, you would send an email here ---
    // $to = "your-email@yourdomain.com";
    // $subject = "New Contact Form Submission from $name";
    // $body = "Name: $name\n";
    // $body .= "Email: $email\n\n";
    // $body .= "Message:\n$message\n";
    // $headers = "From: $name <$email>";
    // mail($to, $subject, $body, $headers);
    // ---------------------------------------------------------

    // For now, we'll just redirect to a success page (or back with a success message)
    header("Location: contact.php?status=success");
    exit;
} else {
    header("Location: contact.php");
    exit;
}
?>
