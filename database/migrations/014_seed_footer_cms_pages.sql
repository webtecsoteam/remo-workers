-- Publish default footer/static pages (editable in Admin → Pages)
-- Skips slugs that already exist.

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'Success Stories', 'success-stories',
'<p>Teams around the world use Remoworkers to move faster, control spend, and work with vetted independent talent.</p>',
'resources', 'content', 30, 1, 'published', 'Success Stories | Remoworkers', 'Client success stories from Remoworkers.', 'success stories, case studies'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'success-stories');

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'How to Hire', 'how-to-hire',
'<p>Post a job for free, review proposals, and pay safely with milestone escrow.</p>',
'clients', 'content', 10, 1, 'published', 'How to Hire | Remoworkers', 'Guide to hiring freelancers on Remoworkers.', 'how to hire, clients'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'how-to-hire');

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'Talent Marketplace', 'talent-marketplace',
'<p>Browse skilled professionals across design, development, marketing, and more.</p>',
'clients', 'content', 20, 1, 'published', 'Talent Marketplace | Remoworkers', 'Browse and hire freelancers.', 'talent marketplace'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'talent-marketplace');

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'Trust & Safety', 'trust-safety',
'<p>Payment Protection, escrow, and dispute resolution for every contract.</p>',
'resources', 'content', 60, 1, 'published', 'Trust & Safety | Remoworkers', 'How Remoworkers protects clients and freelancers.', 'trust, safety, escrow'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'trust-safety');

INSERT INTO cms_pages (name, slug, description, footer_section, link_type, sort_order, show_in_footer, status, seo_title, seo_description, seo_keywords)
SELECT 'Help Center', 'help-center',
'<p>Answers about accounts, payments, contracts, and support.</p>',
'resources', 'content', 10, 1, 'published', 'Help Center | Remoworkers', 'Get help using Remoworkers.', 'help center, support'
WHERE NOT EXISTS (SELECT 1 FROM cms_pages WHERE slug = 'help-center');

UPDATE cms_pages SET status = 'published' WHERE slug IN ('privacy-policy', 'terms-of-service', 'about-us') AND status = 'draft';
