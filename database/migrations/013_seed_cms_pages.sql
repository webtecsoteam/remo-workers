-- Optional starter legal pages (draft — publish from admin when ready)
INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'Privacy Policy', 'privacy-policy', '<p>We respect your privacy. This policy explains how Remoworkers collects, uses, and protects your data.</p>', 'legal', 'content', 10, 1, 'draft', 'Privacy Policy | Remoworkers', 'Learn how Remoworkers handles your personal data and privacy.', 'privacy policy, data protection, Remoworkers'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'privacy-policy');

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'Terms of Service', 'terms-of-service', '<p>These terms govern your use of the Remoworkers platform.</p>', 'legal', 'content', 20, 1, 'draft', 'Terms of Service | Remoworkers', 'Read the Remoworkers terms of service for clients and freelancers.', 'terms of service, user agreement, Remoworkers'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'terms-of-service');

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'About Us', 'about-us', '<p>Remoworkers connects businesses with independent talent across 180+ countries.</p>', 'company', 'content', 10, 1, 'draft', 'About Remoworkers', 'Learn about Remoworkers — the global freelance marketplace.', 'about Remoworkers, freelance marketplace'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'about-us');
