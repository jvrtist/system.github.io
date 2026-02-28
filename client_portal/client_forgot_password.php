<?php
// client_forgot_password.php
require_once '../config.php'; // Adjust path to root config.php

// Define client session variables if not already defined (for consistency, though not strictly needed here)
if (!defined('CLIENT_SESSION_VAR')) define('CLIENT_SESSION_VAR', 'client_logged_in');

// If client is already logged in, they don't need to be here
if (isset($_SESSION[CLIENT_SESSION_VAR]) && $_SESSION[CLIENT_SESSION_VAR] === true) {
    redirect('client_portal/dashboard.php');
}

$page_title = "Forgot Client Password";
$email_input = '';
$errors = [];
$success_message = ''; // To display confirmation or simulated email content

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
            $stmt_check = $conn->prepare("SELECT client_id, first_name, client_account_status FROM clients WHERE email = ? LIMIT 1");
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
                            $reset_token = null; // Ensure it's null if generation fails
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
                                    // In a real application, you would send an email here.
                                    $reset_link = BASE_URL . "client_reset_password.php?token=" . $reset_token;
                                    
                                    $email_subject = "Password Reset Request - " . SITE_NAME;
                                    $email_body = "Hello " . htmlspecialchars($client_data['first_name']) . ",\n\n";
                                    $email_body .= "You requested a password reset for your account on " . SITE_NAME . ".\n";
                                    $email_body .= "Please click the link below to set a new password. This link is valid for 1 hour:\n";
                                    $email_body .= $reset_link . "\n\n";
                                    $email_body .= "If you did not request this, please ignore this email.\n\n";
                                    $email_body .= "Regards,\n" . SITE_NAME . " Team";

                                    // For demonstration, we'll display this on screen.
                                    // In production, REMOVE THIS and implement actual email sending.
                                    $success_message = "If an account with that email exists and is active, a password reset link has been sent (or would be sent).<br><br>";
                                    $success_message .= "<strong>--- For Demonstration Only: Email Content ---</strong><br>";
                                    $success_message .= "<strong>To:</strong> " . htmlspecialchars($email_input) . "<br>";
                                    $success_message .= "<strong>Subject:</strong> " . htmlspecialchars($email_subject) . "<br>";
                                    $success_message .= "<pre style='white-space: pre-wrap; word-wrap: break-word; background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars($email_body) . "</pre>";
                                    $success_message .= "<strong>--- End Demonstration ---</strong>";

                                    // Clear the email input field on success to prevent resubmission of same email easily
                                    $email_input = ''; 

                                } else {
                                    $errors['form'] = "Error updating reset token: " . $stmt_update_token->error;
                                    error_log("Forgot Password - DB Update Error: " . $stmt_update_token->error);
                                }
                                $stmt_update_token->close();
                            } else {
                                $errors['form'] = "Database statement preparation error for token update.";
                            }
                        } // end if $reset_token
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
    } // end if empty($errors) initial validation
    
    if (!empty($errors) && empty($errors['form']) && empty($success_message)) {
        $_SESSION['client_error_message_fp'] = "Please correct the errors below.";
    } elseif (!empty($errors['form']) && empty($success_message)) {
        $_SESSION['client_error_message_fp'] = $errors['form'];
    }
}

// Check for messages passed via session (e.g., from this page itself after processing)
if (isset($_SESSION['client_error_message_fp'])) {
    $error_message_from_session = $_SESSION['client_error_message_fp'];
    unset($_SESSION['client_error_message_fp']);
    if (empty($errors['form']) && empty($success_message)) { // Avoid double display
        $errors['form'] = $error_message_from_session;
    }
}


include_once '../includes/simple_header.php'; // Use a simpler header if available, or main one
// Or, for self-contained page:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ' - ' . (defined('SITE_NAME') ? SITE_NAME : 'ISS Investigations'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind = {
            config: {
                theme: {
                    extend: {
                        colors: {
                            primary: '#ea580c', // Orange-600
                            primaryHover: '#dc2626', // Red-600 for hover
                            secondary: '#0f172a', // Slate-900
                            accent: '#1e293b', // Slate-800
                            'gradient-primary': 'linear-gradient(135deg, #ea580c 0%, #dc2626 100%)',
                            'gradient-secondary': 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
                            'gradient-accent': 'linear-gradient(135deg, #f59e0b 0%, #ea580c 100%)'
                        },
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            mono: ['JetBrains Mono', 'monospace']
                        },
                        boxShadow: {
                            'glow': '0 0 20px rgba(234, 88, 12, 0.3)',
                            'glow-lg': '0 0 30px rgba(234, 88, 12, 0.4)',
                            'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                            'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                            'elevated': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
                        },
                        animation: {
                            'fade-in-up': 'fadeInUp 0.6s ease-out',
                            'float': 'float 3s ease-in-out infinite',
                            'bounce-subtle': 'bounceSubtle 2s infinite',
                            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                            'scale-in': 'scaleIn 0.3s ease-out'
                        },
                        keyframes: {
                            fadeInUp: {
                                '0%': { opacity: '0', transform: 'translateY(30px)' },
                                '100%': { opacity: '1', transform: 'translateY(0)' }
                            },
                            float: {
                                '0%, 100%': { transform: 'translateY(0px)' },
                                '50%': { transform: 'translateY(-10px)' }
                            },
                            bounceSubtle: {
                                '0%, 100%': { transform: 'translateY(0)' },
                                '50%': { transform: 'translateY(-2px)' }
                            },
                            scaleIn: {
                                '0%': { transform: 'scale(0.9)', opacity: '0' },
                                '100%': { transform: 'scale(1)', opacity: '1' }
                            }
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
        }
        
        /* Premium Button Styles */
        .btn-primary {
            @apply bg-gradient-to-r from-primary to-red-600 hover:from-red-600 hover:to-primary text-white font-black py-3 px-6 rounded-full shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300;
        }
        
        /* Premium Card Styles */
        .card-premium {
            @apply bg-white rounded-3xl shadow-card hover:shadow-card-hover transition-all duration-500 border border-slate-100;
        }
        
        /* Text Shadow for better readability */
        .text-shadow { text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .text-shadow-lg { text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8">
        <!-- Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary/10 rounded-full blur-3xl animate-float"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-primary/5 rounded-full blur-3xl animate-pulse-slow"></div>
        </div>

        <div class="relative z-10 w-full max-w-md animate-fade-in-up">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-primary rounded-3xl shadow-glow mb-6">
                    <i class="fas fa-shield-alt text-3xl text-white"></i>
                </div>
                <h1 class="text-4xl font-black text-white mb-3 text-shadow-lg">Forgot Password</h1>
                <p class="text-slate-300 text-lg">Enter your email to receive a reset link</p>
            </div>

            <!-- Form Card -->
            <div class="card-premium p-8 bg-white/95 backdrop-blur-sm shadow-elevated">
                <?php if (!empty($success_message)): ?>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6 mb-6 animate-scale-in">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-600 mt-0.5 flex-shrink-0 text-lg"></i>
                            <div class="text-green-800 font-semibold text-sm leading-relaxed">
                                <?php echo $success_message; // Already HTML formatted for demo ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors['form']) && empty($success_message)): ?>
                    <div class="bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-2xl p-6 mb-6 animate-scale-in">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-red-600 mt-0.5 flex-shrink-0 text-lg"></i>
                            <div class="text-red-800 font-semibold text-sm">
                                <?php echo htmlspecialchars($errors['form']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($success_message)): // Only show form if no success message ?>
                <form action="client_forgot_password.php" method="POST" class="space-y-6">
                    <?php echo csrf_input(); ?>
                    <div>
                        <label for="email" class="block text-sm font-black text-secondary mb-3 uppercase tracking-widest">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-primary text-lg"></i>
                            </div>
                            <input type="email" name="email" id="email"
                                   class="w-full pl-12 pr-6 py-4 bg-slate-50 border <?php echo isset($errors['email']) ? 'border-red-300 focus:border-red-500' : 'border-slate-200 focus:border-primary'; ?> rounded-2xl text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 placeholder-slate-400 text-lg"
                                   value="<?php echo htmlspecialchars($email_input); ?>" 
                                   required 
                                   placeholder="your.email@example.com">
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-600 text-sm mt-2 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($errors['email']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full text-lg font-black py-4 px-8 rounded-2xl flex items-center justify-center gap-3">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Link
                    </button>
                </form>
                <?php endif; ?>

                <div class="mt-8 pt-6 border-t border-slate-200">
                    <div class="text-center">
                        <p class="text-slate-600 text-sm">
                            Remember your password? 
                            <a href="client_login.php" class="font-bold text-primary hover:text-red-600 transition-colors">
                                Login here
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-slate-400 text-sm">
                    &copy; <?php echo date("Y"); ?> <?php echo (defined('SITE_NAME') ? SITE_NAME : 'ISS Investigations'); ?>. 
                    <span class="text-primary font-semibold">Client Portal</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
