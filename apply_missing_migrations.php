<?php
/**
 * ISS Investigations Smart Migration Runner
 * Applies only missing database changes, handles partial migrations
 */

// Include configuration
require_once __DIR__ . '/config.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

echo "ISS Investigations Smart Migration Runner\n";
echo "=========================================\n\n";

$conn = get_db_connection();

try {
    // Ensure migrations_log table exists
    $conn->query("CREATE TABLE IF NOT EXISTS migrations_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_file VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_migration_file (migration_file)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Get applied migrations
    $applied = [];
    $result = $conn->query("SELECT migration_file FROM migrations_log");
    while ($row = $result->fetch_assoc()) {
        $applied[] = $row['migration_file'];
    }

    // Define migrations with their specific checks and fixes
    $migrations = [
        '2026_02_08_add_totp_2fa_support.sql' => [
            'check' => function($conn) {
                // Check if all TOTP columns exist
                $columns = ['totp_secret', 'totp_enabled', 'recovery_codes', 'recovery_codes_generated_at', 'two_fa_enabled_at'];
                $missing = [];
                foreach ($columns as $col) {
                    $result = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
                    if ($result->num_rows === 0) {
                        $missing[] = $col;
                    }
                }

                // Check audit_log two_fa_action column
                $result = $conn->query("SHOW COLUMNS FROM audit_log LIKE 'two_fa_action'");
                if ($result->num_rows === 0) {
                    $missing[] = 'audit_log.two_fa_action';
                }

                return $missing;
            },
            'apply' => function($conn) {
                $statements = [
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(32) NULL COMMENT 'Base32-encoded TOTP secret key'",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS totp_enabled BOOLEAN DEFAULT 0 COMMENT 'Whether TOTP 2FA is enabled for this user'",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS recovery_codes JSON NULL COMMENT 'JSON array of backup recovery codes (hashed)'",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS recovery_codes_generated_at TIMESTAMP NULL COMMENT 'When recovery codes were generated'",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS two_fa_enabled_at TIMESTAMP NULL COMMENT 'When 2FA was enabled'",
                    "ALTER TABLE audit_log ADD COLUMN IF NOT EXISTS two_fa_action VARCHAR(50) NULL COMMENT 'Type of 2FA action (enabled, disabled, recovery_used)'"
                ];

                foreach ($statements as $sql) {
                    $conn->query($sql);
                }
            }
        ],

        '2026_02_08_create_client_messages_table.sql' => [
            'check' => function($conn) {
                $result = $conn->query("SHOW TABLES LIKE 'client_messages'");
                return $result->num_rows === 0 ? ['client_messages table'] : [];
            },
            'apply' => function($conn) {
                $conn->query("CREATE TABLE IF NOT EXISTS client_messages (
                    message_id INT PRIMARY KEY AUTO_INCREMENT,
                    case_id INT NOT NULL,
                    client_id INT NOT NULL,
                    message TEXT NOT NULL,
                    sent_by_client BOOLEAN DEFAULT TRUE,
                    is_read_by_user BOOLEAN DEFAULT FALSE,
                    is_read_by_client BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
                    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
                    INDEX idx_case_client (case_id, client_id),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        ],

        '2026_02_08_create_note_templates_table.sql' => [
            'check' => function($conn) {
                $result = $conn->query("SHOW TABLES LIKE 'note_templates'");
                return $result->num_rows === 0 ? ['note_templates table'] : [];
            },
            'apply' => function($conn) {
                $conn->query("CREATE TABLE IF NOT EXISTS note_templates (
                    template_id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    category VARCHAR(100) DEFAULT 'General',
                    is_active BOOLEAN DEFAULT TRUE,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
                    INDEX idx_category (category),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        ],

        '2026_02_23_add_notifications_system.sql' => [
            'check' => function($conn) {
                $result = $conn->query("SHOW TABLES LIKE 'notifications'");
                if ($result->num_rows === 0) return ['notifications table'];

                // Check notification_preferences column in users table
                $result = $conn->query("SHOW COLUMNS FROM users LIKE 'notification_preferences'");
                return $result->num_rows === 0 ? ['users.notification_preferences'] : [];
            },
            'apply' => function($conn) {
                $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                    notification_id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
                    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                    related_entity_type ENUM('case', 'client', 'task', 'invoice', 'message', 'system') DEFAULT NULL,
                    related_entity_id INT DEFAULT NULL,
                    action_url VARCHAR(500) DEFAULT NULL,
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL,

                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                    INDEX idx_user_read (user_id, is_read),
                    INDEX idx_created (created_at),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // Check if notification_preferences column exists before adding
                $result = $conn->query("SHOW COLUMNS FROM users LIKE 'notification_preferences'");
                if ($result->num_rows === 0) {
                    $conn->query("ALTER TABLE users ADD COLUMN notification_preferences JSON DEFAULT ('{\"overdue_tasks\": true, \"new_messages\": true, \"case_updates\": true, \"system_alerts\": true}') AFTER totp_secret");
                }
            }
        ],

        '2026_02_23_enhance_task_management.sql' => [
            'check' => function($conn) {
                $columns = ['estimated_hours', 'progress_percentage', 'actual_hours', 'task_category', 'parent_task_id', 'is_recurring', 'recurrence_pattern', 'completed_at', 'notes'];
                $missing = [];
                foreach ($columns as $col) {
                    $result = $conn->query("SHOW COLUMNS FROM tasks LIKE '$col'");
                    if ($result->num_rows === 0) {
                        $missing[] = $col;
                    }
                }

                $result = $conn->query("SHOW TABLES LIKE 'task_templates'");
                if ($result->num_rows === 0) {
                    $missing[] = 'task_templates table';
                }

                return $missing;
            },
            'apply' => function($conn) {
                // Add columns to tasks table one by one
                $task_columns = [
                    ['estimated_hours', "ALTER TABLE tasks ADD COLUMN estimated_hours DECIMAL(5,2) DEFAULT NULL AFTER priority"],
                    ['progress_percentage', "ALTER TABLE tasks ADD COLUMN progress_percentage TINYINT DEFAULT 0 AFTER estimated_hours"],
                    ['actual_hours', "ALTER TABLE tasks ADD COLUMN actual_hours DECIMAL(5,2) DEFAULT NULL AFTER progress_percentage"],
                    ['task_category', "ALTER TABLE tasks ADD COLUMN task_category VARCHAR(50) DEFAULT 'General' AFTER actual_hours"],
                    ['parent_task_id', "ALTER TABLE tasks ADD COLUMN parent_task_id INT DEFAULT NULL AFTER task_category"],
                    ['is_recurring', "ALTER TABLE tasks ADD COLUMN is_recurring BOOLEAN DEFAULT FALSE AFTER parent_task_id"],
                    ['recurrence_pattern', "ALTER TABLE tasks ADD COLUMN recurrence_pattern ENUM('daily', 'weekly', 'monthly', 'quarterly') DEFAULT NULL AFTER is_recurring"],
                    ['completed_at', "ALTER TABLE tasks ADD COLUMN completed_at TIMESTAMP NULL AFTER recurrence_pattern"],
                    ['notes', "ALTER TABLE tasks ADD COLUMN notes TEXT AFTER completed_at"]
                ];

                foreach ($task_columns as [$col_name, $sql]) {
                    $result = $conn->query("SHOW COLUMNS FROM tasks LIKE '$col_name'");
                    if ($result->num_rows === 0) {
                        $conn->query($sql);
                    }
                }

                // Add foreign key constraint (only if parent_task_id column exists and constraint doesn't exist)
                $result = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'tasks' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_parent_task'");
                if ($result->num_rows === 0) {
                    $conn->query("ALTER TABLE tasks ADD CONSTRAINT fk_parent_task FOREIGN KEY (parent_task_id) REFERENCES tasks(task_id) ON DELETE SET NULL");
                }

                // Create task_templates table
                $conn->query("CREATE TABLE IF NOT EXISTS task_templates (
                    template_id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    category VARCHAR(50) DEFAULT 'General',
                    estimated_hours DECIMAL(5,2) DEFAULT NULL,
                    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
                    is_active BOOLEAN DEFAULT TRUE,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // Insert default templates (only if they don't exist)
                $templates = [
                    ['Initial Client Consultation', 'Conduct initial consultation with client to gather case details and requirements', 'Client Management', 2.00, 'High'],
                    ['Evidence Collection', 'Gather and document all relevant evidence for the case', 'Investigation', 8.00, 'High'],
                    ['Witness Interviews', 'Conduct interviews with witnesses and document statements', 'Investigation', 4.00, 'Medium'],
                    ['Background Research', 'Perform background checks and research on involved parties', 'Research', 6.00, 'Medium'],
                    ['Report Writing', 'Compile investigation findings into comprehensive report', 'Documentation', 12.00, 'High']
                ];

                foreach ($templates as $template) {
                    $stmt = $conn->prepare("INSERT IGNORE INTO task_templates (name, description, category, estimated_hours, priority) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssds", $template[0], $template[1], $template[2], $template[3], $template[4]);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        ],

        '2026_02_23_create_reviews_system.sql' => [
            'check' => function($conn) {
                $result = $conn->query("SHOW TABLES LIKE 'reviews'");
                return $result->num_rows === 0 ? ['reviews table'] : [];
            },
            'apply' => function($conn) {
                $conn->query("CREATE TABLE IF NOT EXISTS reviews (
                    review_id INT PRIMARY KEY AUTO_INCREMENT,
                    client_name VARCHAR(255) NOT NULL COMMENT 'Name of the reviewer (can be anonymous)',
                    client_email VARCHAR(255) DEFAULT NULL COMMENT 'Optional email for verification',
                    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5) COMMENT 'Rating from 1-5 stars',
                    review_title VARCHAR(255) DEFAULT NULL COMMENT 'Optional review title',
                    review_text TEXT NOT NULL COMMENT 'The review content',
                    service_type VARCHAR(100) DEFAULT NULL COMMENT 'Type of service reviewed (optional)',
                    case_type VARCHAR(100) DEFAULT NULL COMMENT 'Type of case (optional)',
                    is_approved BOOLEAN DEFAULT FALSE COMMENT 'Whether review is approved for public display',
                    approved_by INT DEFAULT NULL COMMENT 'User ID who approved the review',
                    approved_at TIMESTAMP NULL COMMENT 'When the review was approved',
                    rejected_by INT DEFAULT NULL COMMENT 'User ID who rejected the review',
                    rejected_at TIMESTAMP NULL COMMENT 'When the review was rejected',
                    rejection_reason TEXT DEFAULT NULL COMMENT 'Reason for rejection',
                    is_featured BOOLEAN DEFAULT FALSE COMMENT 'Whether to feature this review prominently',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
                    FOREIGN KEY (rejected_by) REFERENCES users(user_id) ON DELETE SET NULL,
                    INDEX idx_approved (is_approved),
                    INDEX idx_rating (rating),
                    INDEX idx_created (created_at),
                    INDEX idx_featured (is_featured)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Client reviews with admin approval workflow'");
            }
        ]
    ];

    // Get list of migration files
    $migrations_dir = __DIR__ . '/migrations/';
    $files = glob($migrations_dir . '*.sql');
    $filenames = array_map('basename', $files);

    echo "Checking " . count($migrations) . " key migrations...\n\n";

    $applied_count = 0;
    $skipped_count = 0;
    $fixed_count = 0;

    foreach ($migrations as $filename => $migration) {
        echo "Checking: $filename\n";

        if (!in_array($filename, $filenames)) {
            echo "  → File not found, skipping\n\n";
            continue;
        }

        if (in_array($filename, $applied)) {
            echo "  → Already applied\n";
            $applied_count++;
        } else {
            // Check what needs to be applied
            $missing = $migration['check']($conn);

            if (empty($missing)) {
                echo "  → Already applied (marked as applied)\n";
                $stmt = $conn->prepare("INSERT IGNORE INTO migrations_log (migration_file) VALUES (?)");
                $stmt->bind_param("s", $filename);
                $stmt->execute();
                $stmt->close();
                $applied_count++;
            } else {
                echo "  → Applying missing changes: " . implode(', ', $missing) . "\n";
                $migration['apply']($conn);
                $stmt = $conn->prepare("INSERT IGNORE INTO migrations_log (migration_file) VALUES (?)");
                $stmt->bind_param("s", $filename);
                $stmt->execute();
                $stmt->close();
                $fixed_count++;
                echo "  → Applied successfully\n";
            }
        }
        echo "\n";
    }

    echo "Migration Summary:\n";
    echo "==================\n";
    echo "Already applied: $applied_count\n";
    echo "Fixed/Applied: $fixed_count\n";
    echo "Total processed: " . ($applied_count + $fixed_count) . "\n\n";

    if ($fixed_count > 0) {
        echo "✅ Database successfully updated with missing changes!\n";
    } else {
        echo "✅ Database is already up to date!\n";
    }

} catch (Exception $e) {
    echo "\nCRITICAL ERROR: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
