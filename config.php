<?php
/**
 * Configuration file for ISS Investigations - Client Case Management System
 */

// --- Database Configuration ---
// IMPORTANT: Replace with your actual database credentials!
define('DB_SERVER', 'localhost');       // Your database server (e.g., 'localhost' or IP address)
define('DB_USERNAME', 'root');  // Your database username
define('DB_PASSWORD', ''); // Your database password
define('DB_NAME', 'iss');         // Your database name (as created in the SQL schema)

// --- Site Configuration ---
define('SITE_NAME', 'ISS Portal');
// IMPORTANT: Update this to your project's full base URL. Include the trailing slash.
define('BASE_URL', 'http://localhost/system/');
define('DEFAULT_TIMEZONE', 'Africa/Johannesburg'); // Set your default timezone (e.g., 'UTC', 'America/New_York')
define('APP_VERSION', '2.0.0'); // Optional: Application version

// --- Session Configuration ---
define('SESSION_NAME', 'iss_cms_secure_session'); // A unique name for your session
define('SESSION_LIFETIME', 0); // 0 = Session lasts until the browser is closed. Set in seconds for a specific lifetime (e.g., 1800 for 30 minutes).
define('SESSION_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'); // Set to true if you are using HTTPS
define('SESSION_HTTP_ONLY', true); // Helps prevent XSS attacks by restricting cookie access from JavaScript
define('SESSION_REGENERATE_TIME', 1800); // Regenerate session ID every 30 minutes (in seconds)

// --- Error Reporting ---
// For development: Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
// For production: Log errors to a file and don't display them to users
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/logs/php_error.log'); // Ensure 'logs' directory exists and is writable by the web server

// --- Sentry Error Logging (Optional) ---
// To enable Sentry error tracking:
// 1. Sign up at https://sentry.io
// 2. Create a PHP project and get your DSN
// 3. Install: composer require sentry/sdk
// 4. Uncomment and set SENTRY_DSN below with your DSN
define('SENTRY_ENABLED', false); // Set to true to enable Sentry
define('SENTRY_DSN', ''); // Your Sentry DSN: https://xxxx@xxxx.ingest.sentry.io/xxxx
define('SENTRY_ENVIRONMENT', 'production'); // 'development' or 'production'
define('SENTRY_TRACE_SAMPLE_RATE', 0.1); // 10% of transactions (0.0 to 1.0)

// --- File Upload Configuration (Example) ---
define('UPLOAD_DIR_BASE', __DIR__ . '/uploads/'); // Base directory for uploads. Ensure this directory exists and is writable.
define('MAX_FILE_SIZE_BYTES', 10 * 1024 * 1024); // 10 MB maximum file size
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'text/plain' => 'txt'
]);

// --- Security Constants ---
define('CSRF_TOKEN_NAME', 'csrf_token'); // Name for CSRF token in forms and session

// --- HTTP Security Headers Configuration ---
define('ENABLE_SECURITY_HEADERS', false); // Set to false to disable security headers (not recommended)

// --- Core Functions & Helpers ---

// Include common utility functions
require_once __DIR__ . '/includes/functions.php';

/**
 * Sends HTTP security headers to prevent common web vulnerabilities.
 * Should be called before any content is sent to the browser.
 * 
 * Headers sent:
 * - Strict-Transport-Security (HSTS): Forces HTTPS connections
 * - Content-Security-Policy (CSP): Prevents inline scripts and XSS
 * - X-Content-Type-Options: Prevents MIME type sniffing
 * - X-Frame-Options: Prevents clickjacking
 * - X-XSS-Protection: Legacy XSS protection (modern browsers use CSP)
 * - Referrer-Policy: Controls referrer information
 */
function send_security_headers() {
    if (!ENABLE_SECURITY_HEADERS) {
        return;
    }

    // Only send HSTS if using HTTPS (prevents mixed-content warnings)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload', false);
    }

    // Content Security Policy - strict but allows required resources
    // Adjust 'script-src', 'style-src', etc. if you need inline or external scripts
    $csp = "default-src 'self'; "
          . "script-src 'self' https://cdnjs.cloudflare.com; "
          . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
          . "font-src 'self' https://fonts.gstatic.com; "
          . "img-src 'self' data: https:; "
          . "connect-src 'self'; "
          . "frame-ancestors 'none'; "
          . "base-uri 'self'; "
          . "form-action 'self'";
    header('Content-Security-Policy: ' . $csp, true);

    // Prevent MIME type sniffing (e.g., treating .txt as .js)
    header('X-Content-Type-Options: nosniff', true);

    // Prevent clickjacking by disallowing framing
    header('X-Frame-Options: DENY', true);

    // Legacy XSS protection header (modern browsers use CSP)
    header('X-XSS-Protection: 1; mode=block', true);

    // Control referrer information sent to external sites
    header('Referrer-Policy: strict-origin-when-cross-origin', true);

    // Permissions Policy (formerly Feature Policy) - restrict powerful features
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()', true);
}

/**
 * Establishes a database connection using MySQLi.
 * @return mysqli|false Database connection object on success, or false on failure.
 * In case of failure, it logs the error and terminates script execution.
 */
function get_db_connection() {
    static $conn = null; // Static variable to hold the connection

    if ($conn === null) { // Connect only if not already connected
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            error_log("Database Connection Failed: " . $conn->connect_errno . " - " . $conn->connect_error);
            // For a production environment, you might want a more user-friendly error page
            die("Critical Error: Unable to connect to the database. Please contact support. Error Code: DBERR01");
        }

        if (!$conn->set_charset("utf8mb4")) {
            error_log("Error loading character set utf8mb4: " . $conn->error);
            // Continue, but be aware of potential encoding issues.
        }
    }
    return $conn;
}

/**
 * Starts or resumes a secure session with appropriate settings.
 */
function start_secure_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/', // Available across the entire domain
            'domain' => $_SERVER['SERVER_NAME'], // Or your specific domain
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTP_ONLY,
            'samesite' => 'Lax' // Mitigates CSRF attacks. Can be 'Strict' or 'None' (if 'Secure' is true).
        ]);
        session_start();
    }

    // Periodically regenerate session ID to prevent session fixation
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_REGENERATE_TIME)) {
        session_regenerate_id(true); // true = delete old session file
        $_SESSION['last_activity'] = time(); // Update last activity time after regenerating
    }
    if (!isset($_SESSION['last_activity'])) { // Set initial activity time
        $_SESSION['last_activity'] = time();
    }

    // Set initial CSRF token if not already set for this session
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        try {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Handle error if random_bytes fails (highly unlikely)
            error_log("Failed to generate CSRF token: " . $e->getMessage());
            die("Critical security error. Please try again later. Error Code: CSRFGEN01");
        }
    }
}

/**
 * Redirects to a specified URL relative to the BASE_URL.
 * @param string $url The path to redirect to (e.g., 'login.php', 'admin/dashboard.php').
 */
function redirect($url) {
    if (headers_sent()) {
        // If headers are already sent, use JavaScript redirection as a fallback
        echo "<script>window.location.href='" . BASE_URL . ltrim($url, '/') . "';</script>";
    } else {
        header("Location: " . BASE_URL . ltrim($url, '/'));
    }
    exit; // Ensure no further code is executed after redirection
}

/**
 * Checks if a user is currently logged in by verifying session variables.
 * @return bool True if the user is logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Enforces login for a page. If the user is not logged in,
 * stores the intended URL and redirects to the login page.
 */
function require_login() {
    if (!is_logged_in()) {
        // Store the current request URI to redirect back after login
        // Ensure it's a relative path within the application to prevent open redirect vulnerabilities
        $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base_path = parse_url(BASE_URL, PHP_URL_PATH);
        if (strpos($current_path, $base_path) === 0) {
            $_SESSION['redirect_url'] = substr($current_path, strlen($base_path));
        } else {
            $_SESSION['redirect_url'] = 'dashboard.php'; // Default safe redirect
        }

        $_SESSION['error_message'] = "You must be logged in to access this page.";
        redirect('login.php');
    }
}

/**
 * Checks if the logged-in user has a specific role.
 * @param string $role The role to check against (e.g., 'admin', 'investigator').
 * @return bool True if the user is logged in and has the specified role, false otherwise.
 */
function user_has_role($role_to_check) {
    if (is_logged_in() && isset($_SESSION['user_role'])) {
        if (is_array($_SESSION['user_role'])) { // If user can have multiple roles
            return in_array($role_to_check, $_SESSION['user_role']);
        }
        return $_SESSION['user_role'] === $role_to_check;
    }
    return false;
}

/**
 * Generates a CSRF token input field for forms.
 * @return string HTML input field for CSRF token.
 */
function csrf_input() {
    if (isset($_SESSION[CSRF_TOKEN_NAME])) {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]) . '">';
    }
    return ''; // Should not happen if session is managed correctly
}

/**
 * Verifies the CSRF token submitted with a form.
 * Call this at the beginning of POST request processing.
 * @return bool True if token is valid, false otherwise (and terminates script).
 */
function verify_csrf_token() {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME]) ||
        !hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME])) {
        // Token mismatch or missing token - potential CSRF attack
        error_log("CSRF token validation failed. SESSION: " . ($_SESSION[CSRF_TOKEN_NAME] ?? 'Not Set') . " POST: " . ($_POST[CSRF_TOKEN_NAME] ?? 'Not Set'));
        $_SESSION['error_message'] = "Security token validation failed. Please try submitting the form again.";
        // Optionally, destroy session or take other security measures
        // For now, redirect to a safe page or the previous page if possible
        // To prevent resubmission issues, it's often better to redirect to the form page with an error.
        // If HTTP_REFERER is available and from the same host, redirect back.
        if (isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) == $_SERVER['HTTP_HOST']) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            redirect('dashboard.php'); // Or a generic error page
        }
        exit;
    }
    return true;
}


// --- Initialization ---
// Set default timezone for all date/time functions
date_default_timezone_set(DEFAULT_TIMEZONE);

// Send HTTP security headers to prevent common vulnerabilities
send_security_headers();

// Start secure session handling for all pages that include this config file.
// This should be one of the first things done.
start_secure_session();
// Add this function at the end of iss/config.php

/**
 * Captures an error, exception, or message to Sentry (if enabled).
 * Useful for tracking non-fatal errors and important events.
 * 
 * @param string $message The error message or event description
 * @param string $level 'error', 'warning', 'info', 'debug'
 * @param array $context Additional context data
 */
/*
function capture_sentry_message($message, $level = 'error', $context = []) {
    if (SENTRY_ENABLED && function_exists('\Sentry\captureMessage')) {
        try {
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($message, $level, $context) {
                foreach ($context as $key => $value) {
                    $scope->setContext($key, [$key => $value]);
                }
                \Sentry\captureMessage($message, $level);
            });
        } catch (Exception $e) {
            error_log('Sentry capture failed: ' . $e->getMessage());
        }
    }
}

/**
 * Captures an exception to Sentry (if enabled).
 * 
 * @param Exception|Throwable $exception The exception to capture
 */
/*
function capture_sentry_exception($exception) {
    if (SENTRY_ENABLED && function_exists('\Sentry\captureException')) {
        try {
            \Sentry\captureException($exception);
        } catch (Exception $e) {
            error_log('Sentry exception capture failed: ' . $e->getMessage());
        }
    }
}
*/

/**
 * Logs an audit action to the database.
 * 
 * @param int|null $user_id The ID of the staff member performing the action.
 * @param int|null $client_id The ID of the client performing the action (for client portal).
 * @param string $action_type A short description of the action (e.g., 'login', 'create_case').
 * @param string $target_type The type of entity being affected (e.g., 'case', 'client', 'user').
 * @param int|null $target_id The ID of the entity being affected.
 * @param string|null $details More detailed information about the action.
 */
function log_audit_action($user_id, $client_id, $action_type, $target_type, $target_id, $details = null) {
    $conn = get_db_connection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $sql = "INSERT INTO audit_log (user_id, client_id, action_type, target_type, target_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iisssis", 
            $user_id, 
            $client_id, 
            $action_type, 
            $target_type, 
            $target_id, 
            $details, 
            $ip_address
        );
        
        if (!$stmt->execute()) {
            // Log the error to the system's error log instead of showing it to the user
            error_log("Failed to log audit action: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare audit log statement: " . $conn->error);
    }
    // Do not close the static connection here
}
/**
 * Fetches custom field definitions for a specific module.
 * @param string $applies_to The module name ('clients' or 'cases').
 * @return array An array of custom field definitions.
 */
function get_custom_fields_for($applies_to) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM custom_fields WHERE applies_to = ? ORDER BY field_label");
    $stmt->bind_param("s", $applies_to);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

/**
 * Fetches saved values for custom fields for a specific record.
 * @param int $target_id The ID of the record (e.g., a client_id).
 * @return array An associative array of [field_id => value].
 */
function get_custom_field_values($target_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT field_id, field_value FROM custom_field_values WHERE target_id = ?");
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_column($result, 'field_value', 'field_id');
}

// Add this to the end of your iss/config.php file

// --- Client Portal Session Definitions ---
define('CLIENT_SESSION_VAR', 'client_logged_in');
define('CLIENT_ID_SESSION_VAR', 'client_id_sess');
define('CLIENT_NAME_SESSION_VAR', 'client_name_sess');

/**
 * Checks if a client is currently logged in.
 * @return bool True if the client is logged in, false otherwise.
 */
function is_client_logged_in() {
    return isset($_SESSION[CLIENT_SESSION_VAR]) && $_SESSION[CLIENT_SESSION_VAR] === true;
}
?>
