<?php
/**
 * ISS Investigations Database Status Checker
 * Check current database structure and migration status
 */

// Include configuration
require_once __DIR__ . '/config.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

echo "ISS Investigations Database Status Checker\n";
echo "==========================================\n\n";

$conn = get_db_connection();

try {
    // Check migrations log
    echo "Migration Status:\n";
    echo "=================\n";

    $result = $conn->query("SHOW TABLES LIKE 'migrations_log'");
    if ($result->num_rows === 0) {
        echo "No migrations_log table found. Creating it...\n";
        $conn->query("CREATE TABLE migrations_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_file VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration_file (migration_file)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Created migrations_log table.\n\n";
    } else {
        $applied = [];
        $result = $conn->query("SELECT migration_file, executed_at FROM migrations_log ORDER BY executed_at");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $applied[] = $row;
            }
        }

        echo "Applied Migrations (" . count($applied) . "):\n";
        foreach ($applied as $migration) {
            echo "  ✓ " . $migration['migration_file'] . " (" . $migration['executed_at'] . ")\n";
        }
        echo "\n";
    }

    // Check key tables
    echo "Key Tables Status:\n";
    echo "==================\n";

    $tables_to_check = [
        'users' => 'User management',
        'cases' => 'Case management',
        'clients' => 'Client database',
        'tasks' => 'Task management',
        'posts' => 'Blog/Content management',
        'invoices' => 'Billing system',
        'notifications' => 'Notification system',
        'task_templates' => 'Task templates',
        'post_comments' => 'Blog comments'
    ];

    foreach ($tables_to_check as $table => $description) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $status = $result->num_rows > 0 ? "✓ EXISTS" : "✗ MISSING";
        echo sprintf("%-20s %-15s %s\n", $table, "($status)", $description);
    }

    echo "\n";

    // Check key columns
    echo "Key Columns Check:\n";
    echo "==================\n";

    $columns_to_check = [
        'users' => ['user_id', 'full_name', 'email', 'role', 'totp_secret', 'notification_preferences'],
        'posts' => ['post_id', 'title', 'content', 'status', 'publish_at', 'view_count', 'category', 'featured_image'],
        'tasks' => ['task_id', 'title', 'assigned_to_user_id', 'estimated_hours', 'progress_percentage', 'task_category'],
        'notifications' => ['notification_id', 'user_id', 'title', 'message', 'type', 'is_read']
    ];

    foreach ($columns_to_check as $table => $columns) {
        echo "Table: $table\n";
        foreach ($columns as $column) {
            $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            $status = $result->num_rows > 0 ? "✓" : "✗";
            echo "  $status $column\n";
        }
        echo "\n";
    }

    // List available migration files
    echo "Available Migration Files:\n";
    echo "==========================\n";

    $migrations_dir = __DIR__ . '/migrations/';
    $files = glob($migrations_dir . '*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        echo "  • $filename\n";
    }

    echo "\nDatabase check completed.\n";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
