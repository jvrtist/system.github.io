-- Migration: Create client_messages table for internal messaging
-- Date: 2026-02-08

START TRANSACTION;

CREATE TABLE IF NOT EXISTS client_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    client_id INT NOT NULL,
    user_id INT NULL COMMENT 'Staff user ID if sent by staff',
    sent_by_client BOOLEAN DEFAULT FALSE COMMENT 'True if sent by client, False if sent by staff',
    message_subject VARCHAR(255) NULL,
    message_content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read_by_client BOOLEAN DEFAULT FALSE,
    is_read_by_staff BOOLEAN DEFAULT FALSE,
    replied_to_message_id INT NULL,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (replied_to_message_id) REFERENCES client_messages(message_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
