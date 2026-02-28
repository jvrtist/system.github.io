-- Migration: Create note_templates table
-- Date: 2026-02-08

-- Create table for note templates
CREATE TABLE IF NOT EXISTS note_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default templates
INSERT INTO note_templates (title, content) VALUES 
('Surveillance Start', 'Surveillance operations commenced at [TIME]. Subject location: [LOCATION]. Team in position.'),
('Surveillance End', 'Surveillance operations concluded at [TIME]. No significant activity observed / Activity log attached.'),
('Client Update', 'Client contacted via [PHONE/EMAIL]. Update provided regarding [TOPIC]. Client requested [ACTION].'),
('Evidence Logged', 'New evidence item [ITEM ID] logged into secure storage. Chain of custody updated.'),
('Subject Contact', 'Subject made contact with [PERSON]. Interaction observed and recorded.');
