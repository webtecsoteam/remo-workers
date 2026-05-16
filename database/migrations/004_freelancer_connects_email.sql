-- Freelancer connects and email verification
-- Note: ensureFreelancerSchema() in includes/config.php applies these safely on app boot.
ALTER TABLE users ADD COLUMN connects INT NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL;

-- Welcome connects for existing freelancers who have none (one-time backfill)
UPDATE users SET connects = 5 WHERE role = 'freelancer' AND connects = 0;
