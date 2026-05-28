-- Google Analytics platform settings (also seeded via ensurePlatformSettingsTable in config.php)
INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'google_analytics_enabled', '0', 'Enable Google Analytics tracking on the public site (1 = on, 0 = off).'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'google_analytics_enabled');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'google_analytics_id', '', 'Google Analytics 4 Measurement ID (e.g. G-XXXXXXXXXX).'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'google_analytics_id');
