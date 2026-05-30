-- User referral codes for invite / share program
ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_code VARCHAR(16) NULL;

-- Unique index (multiple NULLs allowed in MySQL)
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_referral_code ON users (referral_code);
