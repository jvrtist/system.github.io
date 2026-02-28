-- Migration: Enhance task management system
-- Created: 2026-02-23
-- Description: Adds enhanced task management features

ALTER TABLE tasks
ADD COLUMN IF NOT EXISTS estimated_hours DECIMAL(5,2) DEFAULT NULL AFTER priority,
ADD COLUMN IF NOT EXISTS progress_percentage TINYINT DEFAULT 0 AFTER estimated_hours,
ADD COLUMN IF NOT EXISTS actual_hours DECIMAL(5,2) DEFAULT NULL AFTER progress_percentage,
ADD COLUMN IF NOT EXISTS task_category VARCHAR(50) DEFAULT 'General' AFTER actual_hours,
ADD COLUMN IF NOT EXISTS parent_task_id INT DEFAULT NULL AFTER task_category,
ADD COLUMN IF NOT EXISTS is_recurring BOOLEAN DEFAULT FALSE AFTER parent_task_id,
ADD COLUMN IF NOT EXISTS recurrence_pattern ENUM('daily', 'weekly', 'monthly', 'quarterly') DEFAULT NULL AFTER is_recurring,
ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL AFTER recurrence_pattern,
ADD COLUMN IF NOT EXISTS notes TEXT AFTER completed_at,

ADD CONSTRAINT fk_parent_task FOREIGN KEY (parent_task_id) REFERENCES tasks(task_id) ON DELETE SET NULL;

-- Create task templates table
CREATE TABLE IF NOT EXISTS task_templates (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default task templates
INSERT IGNORE INTO task_templates (name, description, category, estimated_hours, priority) VALUES
('Initial Client Consultation', 'Conduct initial consultation with client to gather case details and requirements', 'Client Management', 2.00, 'High'),
('Evidence Collection', 'Gather and document all relevant evidence for the case', 'Investigation', 8.00, 'High'),
('Witness Interviews', 'Conduct interviews with witnesses and document statements', 'Investigation', 4.00, 'Medium'),
('Background Research', 'Perform background checks and research on involved parties', 'Research', 6.00, 'Medium'),
('Report Writing', 'Compile investigation findings into comprehensive report', 'Documentation', 12.00, 'High'),
('Legal Document Review', 'Review and analyze legal documents related to the case', 'Legal', 8.00, 'Medium'),
('Site Inspection', 'Conduct on-site inspections and gather physical evidence', 'Field Work', 6.00, 'Medium'),
('Follow-up Communication', 'Communicate findings and recommendations to client', 'Client Management', 2.00, 'Low');
