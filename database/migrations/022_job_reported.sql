CREATE TABLE IF NOT EXISTS job_reported (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    reporter_id INT NOT NULL COMMENT 'User who submitted the report',
    reported_user_id INT NOT NULL COMMENT 'Job owner / user being reported',
    report_type ENUM('suspicious', 'fraud', 'spam', 'inappropriate', 'misleading', 'scam', 'other') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_job_reporter (job_id, reporter_id),
    KEY idx_job_reported_job (job_id),
    KEY idx_job_reported_reported_user (reported_user_id),
    KEY idx_job_reported_created (created_at),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
