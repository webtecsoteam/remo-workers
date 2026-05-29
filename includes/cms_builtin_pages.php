<?php
/**
 * Built-in CMS pages used when no published row exists in cms_pages.
 * Admin can override any slug by publishing a page with the same slug.
 *
 * @return array<string, array<string, mixed>>
 */
function cmsBuiltinPageCatalog(): array
{
    $p = static function (
        string $name,
        string $slug,
        string $html,
        ?string $footerSection = null,
        string $seoTitle = '',
        string $seoDescription = '',
        string $linkType = 'content',
        ?string $linkTarget = null,
        bool $showInFooter = true
    ): array {
        return [
            'id' => 0,
            'name' => $name,
            'slug' => $slug,
            'description' => $html,
            'footer_section' => $footerSection,
            'link_type' => $linkType,
            'link_target' => $linkTarget,
            'sort_order' => 0,
            'show_in_footer' => $showInFooter,
            'status' => 'published',
            'seo_title' => $seoTitle !== '' ? $seoTitle : ($name . ' | Remoworkers'),
            'seo_description' => $seoDescription,
            'seo_keywords' => '',
            'created_at' => null,
            'updated_at' => null,
        ];
    };

    return [
        'success-stories' => $p(
            'Success Stories',
            'success-stories',
            '<p>Teams around the world use Remoworkers to move faster, control spend, and work with vetted independent talent. Here are a few of their stories.</p>
<h2>Finova Technologies</h2>
<p>“We launched our entire product redesign in six weeks using Remoworkers. The quality of designers is genuinely world-class. NPS jumped from 32 to 71 after the redesign shipped.”</p>
<ul><li><strong>6 weeks</strong> — full redesign delivered</li><li><strong>+39 pts</strong> — NPS improvement</li><li><strong>3</strong> — freelancers hired on one program</li></ul>
<h2>Driftwave Inc.</h2>
<p>“Our engineer was immediately productive — no hand-holding. He delivered a complex API integration in three days. That would have taken our in-house team three weeks.”</p>
<ul><li><strong>3 days</strong> — API integration shipped</li><li><strong>5×</strong> — faster than in-house estimate</li></ul>
<h2>NexaFlow</h2>
<p>“Milestone Payments gave our CFO confidence to move forward. We now run eight ongoing contracts and reduced external spend by 34% versus agency retainers.”</p>
<ul><li><strong>8</strong> — active contracts</li><li><strong>34%</strong> — savings vs. agency costs</li></ul>',
            'resources',
            'Success Stories | Remoworkers',
            'Client success stories from Remoworkers — faster delivery, lower cost, trusted talent.'
        ),
        'how-to-hire' => $p(
            'How to Hire',
            'how-to-hire',
            '<p>Post a job for free, review proposals from vetted freelancers, and pay safely with milestone escrow. Most clients receive their first qualified proposals within hours.</p>
<ol><li><strong>Post your job</strong> — describe skills, timeline, and budget.</li><li><strong>Review proposals</strong> — compare portfolios, ratings, and work history.</li><li><strong>Hire with protection</strong> — fund milestones in escrow and release payment when work is approved.</li></ol>
<p><a href="' . htmlspecialchars(baseUrl('client'), ENT_QUOTES, 'UTF-8') . '">Go to client dashboard →</a></p>',
            'clients',
            'How to Hire on Remoworkers',
            'Step-by-step guide to hiring freelancers on Remoworkers.'
        ),
        'talent-marketplace' => $p(
            'Talent Marketplace',
            'talent-marketplace',
            '<p>Browse millions of skilled professionals across design, development, marketing, AI, and more. Filter by rate, location, availability, and verified reviews.</p>
<p>Every profile includes work history, skill assessments, and client feedback so you can hire with confidence.</p>',
            'clients'
        ),
        'project-catalog' => $p(
            'Project Catalog',
            'project-catalog',
            '<p>Choose pre-scoped project packages with fixed pricing and clear deliverables — from logo design to full-stack apps. See timelines, inclusions, and freelancer ratings before you buy.</p>',
            'clients'
        ),
        'talent-scout' => $p(
            'Talent Scout',
            'talent-scout',
            '<p>Our team sources and shortlists talent for you based on your brief. Ideal for roles where you want curated candidates without reviewing hundreds of proposals yourself.</p>',
            'clients'
        ),
        'enterprise' => $p(
            'Enterprise Solutions',
            'enterprise',
            '<p>Managed talent programs for larger teams: dedicated account support, consolidated billing, compliance options, and custom onboarding for your organization.</p>
<p><a href="' . htmlspecialchars(baseUrl('page/contact-us'), ENT_QUOTES, 'UTF-8') . '">Contact sales →</a></p>',
            'clients'
        ),
        'trust-safety' => $p(
            'Trust & Safety',
            'trust-safety',
            '<p>Remoworkers Payment Protection holds funds in escrow until you approve work. Dispute resolution, identity verification, and secure payments are built into every contract.</p>
<ul><li>Escrow milestone payments</li><li>24/7 support for payment issues</li><li>Verified profiles and reviews</li></ul>',
            'resources'
        ),
        'pricing' => $p(
            'Pricing',
            'pricing',
            '<p>Posting jobs is free for clients. You pay only when you hire — plus a small platform fee on payments. Freelancers can browse jobs for free; Connects are used when submitting proposals.</p>
<p>Enterprise and payroll options are available for teams at scale.</p>',
            'clients'
        ),
        'how-to-find-work' => $p(
            'How to Find Work',
            'how-to-find-work',
            '<p>Create a strong profile, take skill assessments, and browse jobs that match your expertise. Use Connects to apply to roles you care about.</p>
<p><a href="' . htmlspecialchars(baseUrl('remoworkers-dashboard#find-work'), ENT_QUOTES, 'UTF-8') . '">Browse open jobs →</a></p>',
            'talent'
        ),
        'sell-services' => $p(
            'Sell Services',
            'sell-services',
            '<p>Package your expertise into fixed-price services clients can buy instantly. Set deliverables, timelines, and tiers — then grow repeat business from the Project Catalog.</p>',
            'talent'
        ),
        'community-forum' => $p(
            'Community Forum',
            'community-forum',
            '<p>Connect with freelancers and clients worldwide — share tips, ask questions, and learn from peers building on Remoworkers.</p>
<p>Community access is included with your Remoworkers account.</p>',
            'talent'
        ),
        'certifications' => $p(
            'Certifications',
            'certifications',
            '<p>Earn verified skill badges through proctored assessments. Certified freelancers receive more invitations and stand out in search results.</p>',
            'talent'
        ),
        'help-center' => $p(
            'Help Center',
            'help-center',
            '<p>Find answers about accounts, payments, contracts, and disputes. Our support team is available 24/7 for urgent payment and trust &amp; safety issues.</p>
<p><a href="' . htmlspecialchars(baseUrl('page/contact-us'), ENT_QUOTES, 'UTF-8') . '">Contact support →</a></p>',
            'resources'
        ),
        'templates' => $p(
            'Templates',
            'templates',
            '<p>Download free contract, NDA, and statement-of-work templates to use with your Remoworkers hires. Always review with your legal advisor before signing.</p>',
            'resources'
        ),
        'community' => $p(
            'Community',
            'community',
            '<p>Join thousands of professionals learning, hiring, and growing on Remoworkers — events, guides, and forums for clients and freelancers.</p>',
            'resources'
        ),
        'about-us' => $p(
            'About Us',
            'about-us',
            '<p>Remoworkers connects businesses with independent talent across 180+ countries. Founded to make hiring flexible, fair, and fast for everyone.</p>',
            'company',
            '',
            '',
            'content',
            null,
            false
        ),
        'careers' => $p(
            'Careers',
            'careers',
            '<p>We are hiring across product, engineering, design, and operations. Help build the future of work at Remoworkers.</p>
<p>Send your CV to careers@remoworkers.com</p>',
            'company'
        ),
        'press-room' => $p(
            'Press Room',
            'press-room',
            '<p>Media kit, press releases, and brand assets for journalists and partners. For press inquiries, contact press@remoworkers.com</p>',
            'company'
        ),
        'investor-relations' => $p(
            'Investor Relations',
            'investor-relations',
            '<p>Financial reports, governance, and investor inquiries. Contact ir@remoworkers.com</p>',
            'company'
        ),
        'partners' => $p(
            'Partners',
            'partners',
            '<p>Partner with Remoworkers to offer talent solutions to your customers. Earn commissions and co-market with our team.</p>',
            'company'
        ),
        'affiliates' => $p(
            'Affiliates',
            'affiliates',
            '<p>Promote Remoworkers and earn rewards when new clients and freelancers join through your link.</p>',
            'company'
        ),
        'contact-us' => $p(
            'Contact Us',
            'contact-us',
            '<p>Questions about hiring, payments, or your account? Reach our team at support@remoworkers.com — we typically respond within one business day.</p>',
            'company'
        ),
        'privacy-policy' => $p(
            'Privacy Policy',
            'privacy-policy',
            '<p>We respect your privacy. This policy explains how Remoworkers collects, uses, stores, and protects your personal data.</p>
<p>We never sell your personal information to third parties.</p>',
            'legal',
            'Privacy Policy | Remoworkers',
            'Learn how Remoworkers handles your personal data and privacy.'
        ),
        'terms-of-service' => $p(
            'Terms of Service',
            'terms-of-service',
            '<p>These terms govern your use of the Remoworkers platform for clients, freelancers, and visitors.</p>',
            'legal',
            'Terms of Service | Remoworkers',
            'Read the Remoworkers terms of service.'
        ),
        'cookie-settings' => $p(
            'Cookie Settings',
            'cookie-settings',
            '<p>We use essential cookies to run the site and optional analytics cookies to improve your experience. You can manage preferences in your browser at any time.</p>',
            'legal'
        ),
        'accessibility' => $p(
            'Accessibility',
            'accessibility',
            '<p>Remoworkers is committed to WCAG 2.1 AA accessibility. If you encounter barriers, contact accessibility@remoworkers.com</p>',
            'legal'
        ),
        'social-x' => $p(
            'Follow us on X',
            'social-x',
            '<p>Follow <strong>@Remoworkers</strong> for product updates, hiring tips, and community highlights.</p>',
            null,
            '',
            '',
            'external',
            socialProfileUrl('x')
        ),
        'social-linkedin' => $p(
            'LinkedIn',
            'social-linkedin',
            '<p>Connect with Remoworkers on LinkedIn for company news and talent insights.</p>',
            null,
            '',
            '',
            'external',
            socialProfileUrl('linkedin')
        ),
        'social-facebook' => $p(
            'Facebook',
            'social-facebook',
            '<p>Join the Remoworkers community on Facebook.</p>',
            null,
            '',
            '',
            'external',
            socialProfileUrl('facebook')
        ),
        'social-youtube' => $p(
            'YouTube',
            'social-youtube',
            '<p>Watch tutorials, customer stories, and platform tips on our YouTube channel.</p>',
            null,
            '',
            '',
            'external',
            socialProfileUrl('youtube')
        ),
        'social-instagram' => $p(
            'Instagram',
            'social-instagram',
            '<p>Behind-the-scenes and talent spotlights on Instagram.</p>',
            null,
            '',
            '',
            'external',
            socialProfileUrl('instagram')
        ),
    ];
}
