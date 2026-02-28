<?php
// client_portal/client_sensitive_auth.php

// This file should be included on pages that require private key verification
// for accessing sensitive client information (cases, documents, etc.)

// First check basic client authentication
require_once __DIR__ . '/client_auth.php';

// Now check if private key has been verified
if (!isset($_SESSION['private_key_verified']) || $_SESSION['private_key_verified'] !== true) {
    // Store the current URL to redirect back after verification
    $_SESSION['private_key_redirect_url'] = $_SERVER['REQUEST_URI'];
    redirect('client_portal/private_key_verification.php');
}
