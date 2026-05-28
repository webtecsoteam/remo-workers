-- Blog post category (e.g. Hiring Guide, AI & Tech, Freelancing)
ALTER TABLE blogs ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL AFTER name;
