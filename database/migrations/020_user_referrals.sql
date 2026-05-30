-- Referral signups: one row per referred user (prevents duplicate referral links)
CREATE TABLE IF NOT EXISTS user_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_user_id INT NOT NULL,
    referral_code_used VARCHAR(16) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_referred_user (referred_user_id),
    KEY idx_referrer (referrer_id),
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paid referral reward milestones (prevents duplicate $1 payouts)
CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    milestone INT NOT NULL COMMENT '1 = first 10 qualified, 2 = next 10, etc.',
    amount DECIMAL(12, 2) NOT NULL DEFAULT 1.00,
    qualified_count INT NOT NULL COMMENT 'Qualified referrals when reward was paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_referrer_milestone (referrer_id, milestone),
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
