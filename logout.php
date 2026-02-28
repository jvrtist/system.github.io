<?php
// logout.php
require_once 'config.php'; // Ensures session is started and config is available

// Log user out action (optional - if audit_log is used)
// if (isset($_SESSION['user_id'])) {
//     log_audit_action($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
// }

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Set a success message for the login page
// We need to start a new session (or the old one if not fully destroyed by above)
// to pass this message. config.php's start_secure_session will handle this.
// A new session will be created by start_secure_session() if one doesn't exist.
if (session_status() == PHP_SESSION_NONE) {
   start_secure_session(); // This might be redundant if config.php is included again by redirect, but good for clarity
}
$_SESSION['success_message'] = "You have been successfully logged out.";

// Redirect to login page
redirect('login.php');
exit;
?>
