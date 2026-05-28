-- CMS pages for footer links and static content (admin-managed)
CREATE TABLE IF NOT EXISTS cms_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    footer_section ENUM('clients', 'talent', 'resources', 'company', 'legal') NULL,
    link_type ENUM('content', 'modal', 'external') NOT NULL DEFAULT 'content',
    link_target VARCHAR(500) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    show_in_footer TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(500) NULL,
    seo_keywords VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cms_pages_slug (slug),
    KEY idx_cms_pages_footer (footer_section, show_in_footer, status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
