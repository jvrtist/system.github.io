<?php
require_once 'config.php'; 

// Unset only the client-specific session variables
unset($_SESSION[CLIENT_SESSION_VAR]);
unset($_SESSION[CLIENT_ID_SESSION_VAR]);
unset($_SESSION[CLIENT_NAME_SESSION_VAR]);
unset($_SESSION['private_key_verified']);

$_SESSION['client_success_message'] = "You have been successfully logged out.";
// Point to the new unified login page
redirect('login.php');
exit;