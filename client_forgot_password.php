<?php
// client_forgot_password.php
require_once 'config.php'; // In root directory

// Define client session variables if not already defined
if (!defined('CLIENT_SESSION_VAR')) define('CLIENT_SESSION_VAR', 'client_logged_in');

// If client is already logged in, redirect to dashboard
if (isset($_SESSION[CLIENT_SESSION_VAR]) && $_SESSION[CLIENT_SESSION_VAR] === true) {
   redirect('client_portal/dashboard.php');
}

$page_title = "Forgot Client Password";
$email_input = '';
$errors = [];
$success_message = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $email_input = sanitize_input($_POST['email']);

    if (empty($email_input)) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (empty($errors)) {
        $conn = get_db_connection();
        if ($conn) {
            // Check if an active client account exists with this email
            $stmt_check = $conn->prepare("SELECT client_id, first_name, client_account_status, password_hash FROM clients WHERE email = ? LIMIT 1");
            if ($stmt_check) {
                $stmt_check->bind_param("s", $email_input);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows === 1) {
                    $client_data = $result_check->fetch_assoc();

                    if ($client_data['client_account_status'] === 'Disabled') {
                        $errors['email'] = "This account is disabled. Please contact support.";
                    } elseif ($client_data['client_account_status'] === 'Pending Activation' && empty($client_data['password_hash'])) {
                         $errors['email'] = "This account has not been activated yet. Please check your email for an activation link or contact support.";
                    } else {
                        // Account exists and is not disabled/pending initial activation without password
                        // Generate a unique password reset token
                        try {
                            $reset_token = bin2hex(random_bytes(32)); // Generates a 64-character hex token
                        } catch (Exception $e) {
                            error_log("Failed to generate password reset token: " . $e->getMessage());
                            $errors['form'] = "Could not generate a reset token. Please try again later.";
                            $reset_token = null; 
                        }
                        
                        if ($reset_token) {
                            $token_expiry_duration = '+1 hour'; // Token valid for 1 hour
                            $expires_at = new DateTime();
                            $expires_at->modify($token_expiry_duration);
                            $expires_at_formatted = $expires_at->format('Y-m-d H:i:s');

                            $stmt_update_token = $conn->prepare("UPDATE clients SET password_reset_token = ?, password_reset_token_expires_at = ?, client_account_status = 'Password Reset' WHERE client_id = ?");
                            if ($stmt_update_token) {
                                $stmt_update_token->bind_param("ssi", $reset_token, $expires_at_formatted, $client_data['client_id']);
                                if ($stmt_update_token->execute()) {
                                    // ** SIMULATE EMAIL SENDING **
                                    $reset_link = BASE_URL . "client_reset_password.php?token=" . $reset_token;
                                    
                                    $email_subject = "Password Reset Request - " . SITE_NAME;
                                    $email_body = "Hello " . htmlspecialchars($client_data['first_name']) . ",\n\n";
                                    $email_body .= "You requested a password reset for your account on " . SITE_NAME . ".\n";
                                    $email_body .= "Please click the link below to set a new password. This link is valid for 1 hour:\n";
                                    $email_body .= $reset_link . "\n\n";
                                    $email_body .= "If you did not request this, please ignore this email.\n\n";
                                    $email_body .= "Regards,\n" . SITE_NAME . " Team";

                                    $success_message = "If an account with that email exists and is active, a password reset link has been sent (or would be sent).<br><br>";
                                    $success_message .= "<strong>--- For Demonstration Only: Email Content ---</strong><br>";
                                    $success_message .= "<strong>To:</strong> " . htmlspecialchars($email_input) . "<br>";
                                    $success_message .= "<strong>Subject:</strong> " . htmlspecialchars($email_subject) . "<br>";
                                    $success_message .= "<pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc; color: #333;'>" . htmlspecialchars($email_body) . "</pre>";
                                    $success_message .= "<strong>--- End Demonstration ---</strong>";

                                    $email_input = ''; 

                                } else {
                                    $errors['form'] = "Error updating reset token: " . $stmt_update_token->error;
                                    error_log("Forgot Password - DB Update Error: " . $stmt_update_token->error);
                                }
                                $stmt_update_token->close();
                            } else {
                                $errors['form'] = "Database statement preparation error for token update.";
                            }
                        } 
                    }
                } else {
                    // Email not found - show a generic message to prevent account enumeration
                    $success_message = "If an account with that email exists and is active, a password reset link has been sent (or would be sent).";
                }
                $stmt_check->close();
            } else {
                 $errors['form'] = "Database statement preparation error for email check.";
            }
            $conn->close();
        } else {
            $errors['form'] = "Database connection failed.";
        }
    } 
    
    if (!empty($errors) && empty($errors['form']) && empty($success_message)) {
        $_SESSION['client_error_message_fp'] = "Please correct the errors below.";
    } elseif (!empty($errors['form']) && empty($success_message)) {
        $_SESSION['client_error_message_fp'] = $errors['form'];
    }
}

if (isset($_SESSION['client_error_message_fp'])) {
    $error_message_from_session = $_SESSION['client_error_message_fp'];
    unset($_SESSION['client_error_message_fp']);
    if (empty($errors['form']) && empty($success_message)) { 
        $errors['form'] = $error_message_from_session;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ' - ' . (defined('SITE_NAME') ? SITE_NAME : 'ISS Investigations'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-image: linear-gradient(to bottom right, #1e3a8a, #3b82f6); }
        .form-container { min-height: 100vh; }
    </style>
</head>
<body class="text-slate-100">
    <div class="form-container flex flex-col items-center justify-center p-4 sm:p-6">
        <div class="bg-slate-800/80 backdrop-blur-md p-6 sm:p-8 md:p-10 rounded-xl shadow-2xl w-full max-w-md border border-slate-700">
            <div class="text-center mb-6">
                <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-16 w-16 mx-auto mb-4 object-contain" title="ISS Investigations">
                <h1 class="text-2xl sm:text-3xl font-bold text-blue-400">Forgot Password</h1>
                <p class="text-slate-300 mt-1 text-sm">Enter your email to receive a reset link.</p>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-700/40 border border-green-600 text-green-200 px-4 py-3 rounded-lg relative mb-6 text-sm" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['form']) && empty($success_message)): ?>
                <div class="bg-red-700/40 border border-red-600 text-red-200 px-4 py-3 rounded-lg relative mb-6 text-sm" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline ml-1"><?php echo htmlspecialchars($errors['form']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($success_message)): ?>
            <form action="client_forgot_password.php" method="POST" class="space-y-5">
                <?php echo csrf_input(); ?>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1">Your Email Address <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-slate-500"></i>
                        </div>
                        <input type="email" name="email" id="email"
                               class="w-full pl-10 pr-3 py-2.5 bg-slate-700 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-slate-600'; ?> rounded-lg text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors duration-300 placeholder-slate-400"
                               value="<?php echo htmlspecialchars($email_input); ?>" required placeholder="your.email@example.com">
                    </div>
                    <?php if (isset($errors['email'])): ?><p class="text-red-400 text-xs mt-1"><?php echo htmlspecialchars($errors['email']); ?></p><?php endif; ?>
                </div>
                
                <div>
                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <div class="mt-6 text-center text-sm">
                <p class="text-slate-400">Remember your password? <a href="client_login.php" class="font-medium text-sky-400 hover:text-sky-300 hover:underline">Login here</a>.</p>
            </div>
             <p class="text-center text-xs text-slate-500 mt-8">
                &copy; <?php echo date("Y"); ?> <?php echo (defined('SITE_NAME') ? SITE_NAME : 'ISS Investigations'); ?>. Client Portal.
            </p>
        </div>
    </div>
</body>
</html>
