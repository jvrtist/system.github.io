<?php
// client_portal/client_auth.php

// ALWAYS require the configuration file.
// It defines essential functions and handles session state internally.
require_once __DIR__ . '/../config.php';

// Now that we're sure config.php has loaded, we can safely call its functions.
if (!is_client_logged_in()) {
    $_SESSION['client_error_message'] = "Your session has expired. Please log in to access the portal.";
    // Point to the correct client login page
    redirect('client_login.php');
}

// Basic authentication is sufficient for dashboard access
// Private key verification will be required for sensitive content
