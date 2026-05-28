-- Allow longer SEO values in platform settings
ALTER TABLE platform_settings MODIFY COLUMN setting_value TEXT NOT NULL;

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_site_title', 'Remoworkers – Where Great Work Gets Done', 'Default site title (fallback for pages without custom SEO).'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_site_title');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_site_description', 'Access vetted freelancers across every skill. Post jobs free and hire with payment protection.', 'Default meta description for the public site.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_site_description');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_site_keywords', 'freelance, remote work, hire freelancers, jobs, Remoworkers', 'Default meta keywords (comma-separated).'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_site_keywords');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_home_title', 'Remoworkers – Where Great Work Gets Done', 'Homepage &lt;title&gt; tag.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_home_title');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_home_description', 'Access 5 million+ vetted professionals. Post your job free — get proposals in hours.', 'Homepage meta description.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_home_description');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_home_keywords', 'hire freelancers, remote talent, freelance marketplace, post a job', 'Homepage meta keywords.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_home_keywords');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'seo_og_image', '', 'Optional Open Graph image URL for social sharing.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'seo_og_image');
