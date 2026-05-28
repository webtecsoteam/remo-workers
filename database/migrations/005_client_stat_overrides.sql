-- Client statistics admin overrides (offset-based for continuous accumulation)
-- When admin sets "total spent" to $10,000 and real spent is $2,000, offset = $8,000.
-- Future payments keep adding to real spent, and display = real + offset.
ALTER TABLE users ADD COLUMN IF NOT EXISTS admin_spent_offset DECIMAL(12, 2) NOT NULL DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS admin_hires_offset INT NOT NULL DEFAULT 0;