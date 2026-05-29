-- Align connects_history with payments/users (utf8mb4_unicode_ci).
-- Fixes: Illegal mix of collations when joining description columns.
ALTER TABLE connects_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
