-- Referral program settings (stored in platform_settings, editable from admin)
INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'referral_enabled', '1', 'Enable the referral program for clients and freelancers (1 = on, 0 = off).'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'referral_enabled');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'referral_reward_threshold', '10', 'Number of fully qualified referrals required before each wallet reward.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'referral_reward_threshold');

INSERT INTO platform_settings (setting_key, setting_value, description)
SELECT 'referral_reward_amount', '1.00', 'USD amount credited to referrer wallet per milestone.'
WHERE NOT EXISTS (SELECT 1 FROM platform_settings WHERE setting_key = 'referral_reward_amount');
