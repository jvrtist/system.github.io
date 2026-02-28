<?php
// client_reset_password.php
require_once 'config.php'; // In root directory

// Define client session variables if not already defined
if (!defined('CLIENT_SESSION_VAR')) define('CLIENT_SESSION_VAR', 'client_logged_in');

// If client is already logged in, they probably don't need to be here,
// but a valid token might still be used. For simplicity, we'll allow it.
// if (isset($_SESSION[CLIENT_SESSION_VAR]) && $_SESSION[CLIENT_SESSION_VAR] === true) {
//    redirect('client_portal/dashboard.php');
// }

$page_title = "Reset Client Password";
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
$errors = [];
$success_message = '';
$show_form = false; // Flag to control if password reset form is displayed
$client_id_for_reset = null;

if (empty($token)) {
    $errors['form'] = "No reset token provided. Please use the link sent to your email.";
} else {
    $conn = get_db_connection();
    if ($conn) {
        // Validate the token
        $stmt_validate = $conn->prepare("SELECT client_id, first_name, password_reset_token_expires_at, client_account_status FROM clients WHERE password_reset_token = ? LIMIT 1");
        if ($stmt_validate) {
            $stmt_validate->bind_param("s", $token);
            $stmt_validate->execute();
            $result_validate = $stmt_validate->get_result();

            if ($result_validate->num_rows === 1) {
                $client_data = $result_validate->fetch_assoc();
                $client_id_for_reset = $client_data['client_id'];
                $token_expires_at = new DateTime($client_data['password_reset_token_expires_at']);
                $now = new DateTime();

                if ($client_data['client_account_status'] !== 'Password Reset') {
                    $errors['form'] = "This password reset token is no longer valid or has already been used. Please request a new one if needed.";
                } elseif ($now > $token_expires_at) {
                    $errors['form'] = "This password reset token has expired. Please request a new one.";
                    // Optionally, clear the expired token from DB here
                    $stmt_clear_expired = $conn->prepare("UPDATE clients SET password_reset_token = NULL, password_reset_token_expires_at = NULL WHERE client_id = ?");
                    if($stmt_clear_expired) {
                        $stmt_clear_expired->bind_param("i", $client_id_for_reset);
                        $stmt_clear_expired->execute();
                        $stmt_clear_expired->close();
                    }
                } else {
                    // Token is valid and not expired, and status is correct
                    $show_form = true;
                }
            } else {
                $errors['form'] = "Invalid password reset token. Please check the link or request a new one.";
            }
            $stmt_validate->close();
        } else {
            $errors['form'] = "Database error validating token. Please try again later.";
            error_log("Reset Password - Token Validation Prepare Error: " . $conn->error);
        }
        // $conn->close(); // Connection will be needed again if form is submitted
    } else {
        $errors['form'] = "Database connection failed. Please try again later.";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) { // Process form only if token was initially valid
    verify_csrf_token();

    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    // Token from POST to ensure it matches the one in URL (or hidden field)
    $token_from_post = isset($_POST['token']) ? sanitize_input($_POST['token']) : '';

    if ($token_from_post !== $token) {
        $errors['form'] = "Token mismatch. Password reset failed.";
        $show_form = false; // Don't show form again if tokens don't match
    } else {
        if (empty($new_password)) {
            $errors['new_password'] = "New password is required.";
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = "New password must be at least 8 characters long.";
        }
        if ($new_password !== $confirm_new_password) {
            $errors['confirm_new_password'] = "New passwords do not match.";
        }

        if (empty($errors)) {
            $conn_update = get_db_connection(); // Ensure connection is available
            if ($conn_update) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                // Update password, clear token fields, and set status to Active
                $stmt_update_pass = $conn_update->prepare("UPDATE clients SET password_hash = ?, password_reset_token = NULL, password_reset_token_expires_at = NULL, client_account_status = 'Active', updated_at = CURRENT_TIMESTAMP WHERE client_id = ? AND password_reset_token = ?");
                if ($stmt_update_pass) {
                    $stmt_update_pass->bind_param("sis", $new_password_hash, $client_id_for_reset, $token);
                    if ($stmt_update_pass->execute()) {
                        if ($stmt_update_pass->affected_rows > 0) {
                            $_SESSION['client_success_message'] = "Your password has been successfully reset. You can now log in with your new password.";
                            // Invalidate the token by hiding the form
                            $show_form = false;
                            $success_message = $_SESSION['client_success_message']; // For display on this page before redirect
                            // Redirect to login page after a short delay or immediately
                            // header("Refresh: 5; url=client_login.php"); // Example redirect after 5 seconds
                            redirect('client_login.php'); // Immediate redirect
                        } else {
                             $errors['form'] = "Failed to update password. The reset link might have been used or expired. Please try requesting a new reset link.";
                             $show_form = false; // Token likely invalid now or client_id mismatch
                        }
                    } else {
                        $errors['form'] = "Error updating password: " . $stmt_update_pass->error;
                        error_log("Reset Password - DB Update Error: " . $stmt_update_pass->error);
                    }
                    $stmt_update_pass->close();
                } else {
                    $errors['form'] = "Database statement preparation error for password update.";
                }
                // $conn_update->close(); // Let footer/config handle static connection
            } else {
                 $errors['form'] = "Database connection failed for password update.";
            }
        } // end if empty($errors) for password fields
    } // end if token_from_post matches
} // end if POST and show_form

if ($conn && !$show_form && empty($success_message)) { // Close connection if it was opened and form is not shown
    $conn->close();
}

// Use a simpler header or self-contained HTML
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
                <h1 class="text-2xl sm:text-3xl font-bold text-blue-400">Reset Your Password</h1>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-700/40 border border-green-600 text-green-200 px-4 py-3 rounded-lg relative mb-6 text-sm" role="alert">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                    <p class="mt-2"><a href="client_login.php" class="font-bold hover:underline">Click here to login.</a></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['form'])): ?>
                <div class="bg-red-700/40 border border-red-600 text-red-200 px-4 py-3 rounded-lg relative mb-6 text-sm" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline ml-1"><?php echo htmlspecialchars($errors['form']); ?></span>
                     <p class="mt-2"><a href="client_forgot_password.php" class="font-bold hover:underline">Request a new reset link.</a></p>
                </div>
            <?php endif; ?>

            <?php if ($show_form && empty($success_message)): ?>
                <p class="text-slate-300 text-sm mb-4">Please enter your new password below.</p>
                <form action="client_reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="space-y-5">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>"> 
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-slate-300 mb-1">New Password <span class="text-red-400">*</span></label>
                        <input type="password" name="new_password" id="new_password" required
                               class="w-full px-3 py-2.5 bg-slate-700 border <?php echo isset($errors['new_password']) ? 'border-red-500' : 'border-slate-600'; ?> rounded-lg text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors duration-300 placeholder-slate-400">
                        <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters long.</p>
                        <?php if (isset($errors['new_password'])): ?><p class="text-red-400 text-xs mt-1"><?php echo htmlspecialchars($errors['new_password']); ?></p><?php endif; ?>
                    </div>

                    <div>
                        <label for="confirm_new_password" class="block text-sm font-medium text-slate-300 mb-1">Confirm New Password <span class="text-red-400">*</span></label>
                        <input type="password" name="confirm_new_password" id="confirm_new_password" required
                               class="w-full px-3 py-2.5 bg-slate-700 border <?php echo isset($errors['confirm_new_password']) ? 'border-red-500' : 'border-slate-600'; ?> rounded-lg text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors duration-300 placeholder-slate-400">
                        <?php if (isset($errors['confirm_new_password'])): ?><p class="text-red-400 text-xs mt-1"><?php echo htmlspecialchars($errors['confirm_new_password']); ?></p><?php endif; ?>
                    </div>
                    
                    <div>
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 ease-in-out transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            <i class="fas fa-save mr-2"></i> Set New Password
                        </button>
                    </div>
                </form>
            <?php elseif (!$show_form && empty($success_message) && empty($errors['form'])): ?>
                 <div class="bg-yellow-700/40 border border-yellow-600 text-yellow-200 px-4 py-3 rounded-lg relative mb-6 text-sm" role="alert">
                    <p>Loading token information or an unexpected issue occurred.</p>
                </div>
            <?php endif; ?>

            <div class="mt-6 text-center text-sm">
                <p class="text-slate-400">Remembered your password? <a href="client_login.php" class="font-medium text-sky-400 hover:text-sky-300 hover:underline">Login here</a>.</p>
            </div>
             <p class="text-center text-xs text-slate-500 mt-8">
                &copy; <?php echo date("Y"); ?> <?php echo (defined('SITE_NAME') ? SITE_NAME : 'ISS Investigations'); ?>. Client Portal.
            </p>
        </div>
    </div>
</body>
</html>

