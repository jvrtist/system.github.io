-- ISS Investigations - Reviews System Migration
-- Date: 2026-02-23
-- Adds review system with admin approval workflow

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Client reviews with admin approval workflow';
