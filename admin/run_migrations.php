<?php
// admin/run_migrations.php
require_once '../config.php';
require_login();

if (!user_has_role('admin')) {
    $_SESSION['admin_error_message'] = "Unauthorized access.";
    redirect('dashboard.php');
}

/**
 * Ensures the migrations_log table exists for tracking executed migrations
 */
function ensureMigrationsTable($conn) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS migrations_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_file VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_migration_file (migration_file)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($create_table_sql)) {
        throw new Exception("Failed to create migrations_log table: " . $conn->error);
    }
}

/**
 * Gets the list of already executed migrations
 */
function getAppliedMigrations($conn) {
    $applied = [];
    $result = $conn->query("SELECT migration_file FROM migrations_log ORDER BY migration_file");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $applied[] = $row['migration_file'];
        }
        $result->free();
    }
    
    return $applied;
}

/**
 * Records a migration as executed
 */
function recordMigration($conn, $filename) {
    $stmt = $conn->prepare("INSERT INTO migrations_log (migration_file) VALUES (?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare migration log statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $filename);
    $success = $stmt->execute();
    $stmt->close();
    
    if (!$success) {
        throw new Exception("Failed to record migration: " . $conn->error);
    }
}

/**
 * Executes a single migration file within a transaction
 */
function runMigration($conn, $file) {
    $filename = basename($file);
    $sql_content = file_get_contents($file);
    
    if ($sql_content === false) {
        throw new Exception("Failed to read migration file: $filename");
    }
    
    // Remove BOM if present
    if (substr($sql_content, 0, 3) === "\xEF\xBB\xBF") {
        $sql_content = substr($sql_content, 3);
    }
    
    // Split into individual statements
    // This regex splits by semicolon but ignores semicolons inside quotes
    $statements = preg_split('/;\s*[\r\n]+/', $sql_content);
    
    $statements_executed = 0;
    $statements_skipped = 0;
    $has_error = false;
    $error_message = '';
    
    // Note: We don't use transactions for DDL statements because they cause implicit commits in MySQL
    // Instead, we execute each statement individually and handle errors gracefully
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Skip comment lines
        if (preg_match('/^\s*(--|#)/', $statement)) {
            continue;
        }
        
        // Try to execute
        $result = $conn->query($statement);
        if ($result === false) {
            // Check for specific ignorable errors
            // 1060: Duplicate column name
            // 1061: Duplicate key name
            // 1050: Table already exists
            // 1091: Can't DROP 'x'; check that column/key exists
            // 1062: Duplicate entry for key
            // 1146: Table doesn't exist (for DROP TABLE)
            $ignorable_errors = [1060, 1061, 1050, 1091, 1062, 1146];
            
            $error_code = $conn->errno;
            $error_message = $conn->error;
            
            // Check if this is an ignorable error
            if (in_array($error_code, $ignorable_errors)) {
                // Ignorable error - skip this statement
                $statements_skipped++;
                continue;
            }
            
            // Non-ignorable error - record it and continue
            $has_error = true;
            $error_message = "SQL Error ({$error_code}): {$error_message} in statement: " . substr($statement, 0, 100) . "...";
            break;
        }
        $statements_executed++;
    }
    
    // Only record the migration as executed if it was successful
    if (!$has_error) {
        recordMigration($conn, $filename);
    }
    
    // Return result
    if ($has_error) {
        return ['success' => false, 'error' => $error_message];
    }
    
    return ['success' => true, 'statements' => $statements_executed, 'skipped' => $statements_skipped];
}

// Main execution
$migrations_dir = __DIR__ . '/../migrations/';

// Check if migrations directory exists
if (!is_dir($migrations_dir)) {
    $_SESSION['admin_error_message'] = "Migrations directory not found: $migrations_dir";
    redirect('admin/settings.php');
}

$files = glob($migrations_dir . '*.sql');

if (empty($files)) {
    $_SESSION['admin_success_message'] = "No migration files found in $migrations_dir";
    redirect('admin/settings.php');
}

// Sort files alphabetically to ensure consistent execution order
sort($files);

$conn = get_db_connection();

try {
    // Ensure migrations_log table exists
    ensureMigrationsTable($conn);
    
    // Get list of already applied migrations
    $applied_migrations = getAppliedMigrations($conn);
    
    $results = [];
    $errors = [];
    $skipped = [];
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Skip if already applied
        if (in_array($filename, $applied_migrations)) {
            $skipped[] = "$filename (already applied)";
            continue;
        }
        
        // Run the migration
        $result = runMigration($conn, $file);
        
        if ($result['success']) {
            $skipped_count = $result['skipped'] ?? 0;
            $msg = "$filename: Executed {$result['statements']} statements successfully.";
            if ($skipped_count > 0) {
                $msg .= " ($skipped_count skipped)";
            }
            $results[] = $msg;
        } else {
            $errors[] = "<strong>$filename</strong>: {$result['error']}";
        }
    }
    
    // Build response message
    $message_parts = [];
    
    if (!empty($results)) {
        $message_parts[] = "<strong>Successfully executed " . count($results) . " migration(s):</strong><br>" . implode('<br>', $results);
    }
    
    if (!empty($skipped)) {
        $message_parts[] = "<strong>Skipped " . count($skipped) . " already-applied migration(s):</strong><br>" . implode('<br>', $skipped);
    }
    
    if (empty($errors)) {
        $_SESSION['admin_success_message'] = "Migration process completed successfully.<br><br>" . implode('<br><br>', $message_parts);
    } else {
        $_SESSION['admin_error_message'] = "Migration completed with errors:<br>" . implode('<br>', $errors);
        if (!empty($message_parts)) {
            $_SESSION['admin_error_message'] .= "<br><br>" . implode('<br><br>', $message_parts);
        }
    }
    
} catch (Exception $e) {
    $_SESSION['admin_error_message'] = "Migration process failed: " . $e->getMessage();
}

redirect('admin/settings.php');
?>