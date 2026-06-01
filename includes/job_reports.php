<?php

const JOB_REPORT_TYPES = [
    'suspicious'    => 'Suspicious activity',
    'fraud'         => 'Fraud / scam attempt',
    'spam'          => 'Spam or duplicate posting',
    'inappropriate' => 'Inappropriate content',
    'misleading'    => 'Misleading job details',
    'scam'          => 'Payment scam',
    'other'         => 'Other',
];

function isValidJobReportType(string $type): bool
{
    return array_key_exists($type, JOB_REPORT_TYPES);
}

function jobReportTypeLabel(string $type): string
{
    return JOB_REPORT_TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

function jobReportTypesForSelect(): array
{
    $options = [];
    foreach (JOB_REPORT_TYPES as $value => $label) {
        $options[] = ['value' => $value, 'label' => $label];
    }
    return $options;
}

function ensureJobReportedTable(PDO $db): void
{
    $db->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
