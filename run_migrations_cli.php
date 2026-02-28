<?php
/**
 * CLI Migration Runner for ISS Investigations
 * Run all pending database migrations from command line
 */

// Include configuration
require_once __DIR__ . '/config.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
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
 * Executes a single migration file
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
    $statements = preg_split('/;\s*[\r\n]+/', $sql_content);

    $statements_executed = 0;
    $statements_skipped = 0;
    $has_error = false;
    $error_message = '';

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
            $ignorable_errors = [1060, 1061, 1050, 1091, 1062, 1146];

            $error_code = $conn->errno;
            $error_message = $conn->error;

            // Check if this is an ignorable error
            if (in_array($error_code, $ignorable_errors)) {
                $statements_skipped++;
                continue;
            }

            // Non-ignorable error
            $has_error = true;
            $error_message = "SQL Error ({$error_code}): {$error_message}";
            break;
        }
        $statements_executed++;
    }

    // Only record the migration as executed if it was successful
    if (!$has_error) {
        recordMigration($conn, $filename);
    }

    if ($has_error) {
        return ['success' => false, 'error' => $error_message];
    }

    return ['success' => true, 'statements' => $statements_executed, 'skipped' => $statements_skipped];
}

// Main execution
echo "ISS Investigations Database Migration Runner\n";
echo "==========================================\n\n";

$migrations_dir = __DIR__ . '/migrations/';

// Check if migrations directory exists
if (!is_dir($migrations_dir)) {
    echo "ERROR: Migrations directory not found: $migrations_dir\n";
    exit(1);
}

$files = glob($migrations_dir . '*.sql');

if (empty($files)) {
    echo "No migration files found in $migrations_dir\n";
    exit(0);
}

// Sort files alphabetically
sort($files);

echo "Found " . count($files) . " migration files\n\n";

$conn = get_db_connection();

try {
    // Ensure migrations_log table exists
    ensureMigrationsTable($conn);

    // Get list of already applied migrations
    $applied_migrations = getAppliedMigrations($conn);

    echo "Applied migrations: " . count($applied_migrations) . "\n\n";

    $results = [];
    $errors = [];
    $skipped = [];

    foreach ($files as $file) {
        $filename = basename($file);

        // Skip if already applied
        if (in_array($filename, $applied_migrations)) {
            $skipped[] = $filename;
            echo "SKIP: $filename (already applied)\n";
            continue;
        }

        echo "RUNNING: $filename\n";

        // Run the migration
        $result = runMigration($conn, $file);

        if ($result['success']) {
            $skipped_count = $result['skipped'] ?? 0;
            $msg = "SUCCESS: Executed {$result['statements']} statements";
            if ($skipped_count > 0) {
                $msg .= " ($skipped_count skipped)";
            }
            $results[] = $filename;
            echo "  → $msg\n";
        } else {
            $errors[] = $filename;
            echo "  → ERROR: {$result['error']}\n";
        }
    }

    echo "\n";
    echo "Migration Summary:\n";
    echo "==================\n";
    echo "Successfully executed: " . count($results) . "\n";
    echo "Skipped (already applied): " . count($skipped) . "\n";

    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "\n";
        echo "\nFailed migrations:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
        echo "\nPlease check the errors above and fix them before proceeding.\n";
        exit(1);
    } else {
        echo "\nAll migrations completed successfully! ✅\n";
        echo "Database is now up to date with the latest system requirements.\n";
    }

} catch (Exception $e) {
    echo "\nCRITICAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
