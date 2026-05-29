<?php
/**
 * Shared Olance/Remoworkers template shell (header + footer) for public site pages.
 */

function publicTemplatePath(): string
{
    static $logged = false;
    $configuredTemplatePath = trim((string) env('HOMEPAGE_TEMPLATE_PATH', ''));
    $debugEnabled = (bool) env('HOMEPAGE_TEMPLATE_DEBUG', false);
    $candidates = [BASE_PATH . '/home/template/index.php'];
    if ($configuredTemplatePath !== '') {
        $candidates[] = $configuredTemplatePath;
    }
    $candidates[] = dirname(BASE_PATH) . '/free/index.php';
    $candidates[] = BASE_PATH . '/free/index.php';

    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            if ($debugEnabled && !$logged) {
                error_log('[homepage-template] selected=' . $candidate . ' candidates=' . implode(' | ', $candidates));
                $logged = true;
            }
            return $candidate;
        }
    }

    if ($debugEnabled && !$logged) {
        error_log('[homepage-template] selected=NONE candidates=' . implode(' | ', $candidates));
        $logged = true;
    }

    return '';
}

function publicTemplateGetHtml(): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $templatePath = publicTemplatePath();
    $html = @file_get_contents($templatePath);
    if ($html === false) {
        $cache = '';
        return $cache;
    }

    $assetBase = baseUrl('assets/free-home') . '/';
    $replacements = [
        'href="assets/' => 'href="' . $assetBase,
        "href='assets/" => "href='" . $assetBase,
        'src="assets/' => 'src="' . $assetBase,
        "src='assets/" => "src='" . $assetBase,
        'srcset="assets/' => 'srcset="' . $assetBase,
        "srcset='assets/" => "srcset='" . $assetBase,
        'content="assets/' => 'content="' . $assetBase,
        "content='assets/" => "content='" . $assetBase,
        'url(assets/' => 'url(' . $assetBase,
        '/templates/basic/shape/' => '/templates/shape/',
        'https://script.viserlab.com/olance' => baseUrl(),
    ];
    $html = strtr($html, $replacements);
    $html = str_ireplace('Olance', 'RemoWorkers', $html);

    $brandLogoUrl = baseUrl('assets/logo.png');
    $brandFaviconUrl = baseUrl('favicon.png');
    $html = preg_replace('/(src|href)=("|\')[^"\']*logo_icon\/logo\.png("|\')/i', '$1=$2' . $brandLogoUrl . '$3', $html);
    $html = preg_replace('/(content)=("|\')[^"\']*logo_icon\/logo\.png("|\')/i', '$1=$2' . $brandLogoUrl . '$3', $html);
    $html = preg_replace('/(src|href)=("|\')[^"\']*logo_icon\/favicon\.png("|\')/i', '$1=$2' . $brandFaviconUrl . '$3', $html);
    $html = preg_replace('/(content)=("|\')[^"\']*logo_icon\/favicon\.png("|\')/i', '$1=$2' . $brandFaviconUrl . '$3', $html);

    $html = preg_replace(
        '/<div class="custom--dropdown">[\s\S]*?<li class="dropdown-list__item langSel"[\s\S]*?<\/ul>\s*<\/div>/i',
        '',
        $html
    );

    $html = preg_replace('/<script[^>]*src="https:\/\/accounts\.google\.com\/gsi\/client"[^>]*><\/script>/i', '', $html);
    $html = preg_replace('/<script[^>]*src="https:\/\/www\.googletagmanager\.com\/gtag\/js[^"]*"[^>]*><\/script>/i', '', $html);
    $html = preg_replace('/<link[^>]*id="googleidentityservice"[^>]*>/i', '', $html);
    $html = preg_replace('/<style[^>]*id="googleidentityservice_button_styles"[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script\b[^>]*>[\s\S]*?googleLoginResponse[\s\S]*?<\/script>/i', '', $html);
    $html = preg_replace('/<script\b[^>]*>[\s\S]*?gtag\([\s\S]*?<\/script>/i', '', $html);
    $html = preg_replace('/<script\b[^>]*src="https:\/\/static\.cloudflareinsights\.com\/beacon\.min\.js[^"]*"[^>]*><\/script>/i', '', $html);
    $html = preg_replace('/<script\b[^>]*>[\s\S]*?Tawk_API[\s\S]*?<\/script>/i', '', $html);
    $html = preg_replace('/<iframe[^>]*tawk[^>]*><\/iframe>/i', '', $html);
    $html = preg_replace('/<div[^>]*id="[^"]*tawk[^"]*"[^>]*>[\s\S]*?<\/div>/i', '', $html);
    $html = preg_replace('/<style[^>]*>[\s\S]*?tawk[\s\S]*?<\/style>/i', '', $html);

    $cache = $html;
    return $cache;
}

function publicTemplateAuthNavHtml(string $responsiveClasses = ''): string
{
    require_once __DIR__ . '/../../includes/classes/Auth.php';
    $user = Auth::user();
    $postJobUrl = htmlspecialchars(baseUrl('post-job'), ENT_QUOTES, 'UTF-8');
    if ($user) {
        $dash = $user['role'] === 'client' ? baseUrl('client') : baseUrl('remoworkers-dashboard');
        $responsive = $responsiveClasses !== '' ? ' ' . $responsiveClasses : '';
        return '<ul class="login-registration-list auth-nav-inline d-flex flex-wrap justify-content-between align-items-center' . $responsive . '">'
            . '<li class="login-registration-list__item js-auth-user"><a href="' . $postJobUrl . '" class="btn btn--base">Post Job</a></li>'
            . '<li class="login-registration-list__item js-auth-user"><span class="login-registration-list__link" style="cursor:default;opacity:.85">'
            . htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8')
            . '</span></li>'
            . '<li class="login-registration-list__item js-auth-user"><a href="' . htmlspecialchars($dash, ENT_QUOTES, 'UTF-8') . '" class="btn btn--base btn--sm">Dashboard</a></li>'
            . '<li class="login-registration-list__item js-auth-user"><a href="' . htmlspecialchars(baseUrl('logout'), ENT_QUOTES, 'UTF-8') . '" class="login-registration-list__link">Logout</a></li>'
            . '</ul>';
    }

    $responsive = $responsiveClasses !== '' ? ' ' . $responsiveClasses : '';
    return '<ul class="login-registration-list auth-nav-inline d-flex flex-wrap justify-content-between align-items-center' . $responsive . '">'
        . '<li class="login-registration-list__item"><a href="' . $postJobUrl . '" class="btn btn--base">Post Job</a></li>'
        . '<li class="login-registration-list__item"><a href="#" data-auth-modal="login" class="login-registration-list__link" role="button">Login</a></li>'
        . '<li class="login-registration-list__item"><a href="#" data-auth-modal="signup" class="login-registration-list__link" role="button">Register</a></li>'
        . '</ul>';
}

function publicTemplateApplyHeaderNav(string $header): string
{
    $blogUrl = htmlspecialchars(baseUrl('blog'), ENT_QUOTES, 'UTF-8');
    $findJobsUrl = htmlspecialchars(baseUrl('jobs'), ENT_QUOTES, 'UTF-8');
    $findTalentsUrl = htmlspecialchars(baseUrl('talents'), ENT_QUOTES, 'UTF-8');
    $homeUrl = htmlspecialchars(baseUrl(), ENT_QUOTES, 'UTF-8');

    $header = preg_replace('#href="[^"]*blogs[^"]*"#i', 'href="' . $blogUrl . '"', $header);
    $header = preg_replace('#href="freelance-jobs"#i', 'href="' . $findJobsUrl . '"', $header);
    $header = preg_replace('#href="talents"#i', 'href="' . $findTalentsUrl . '"', $header);
    $header = preg_replace(
        '#<li class="nav-item[^"]*">\s*<a class="nav-link" href="about">\s*About\s*</a>\s*</li>#i',
        '',
        $header
    );
    $header = preg_replace(
        '#<li class="nav-item[^"]*">\s*<a class="nav-link" href="contact">\s*Contact\s*</a>\s*</li>#i',
        '',
        $header
    );
    $header = preg_replace('#href="[^"]*buyer/job/post[^"]*"#i', 'href="' . htmlspecialchars(baseUrl('post-job'), ENT_QUOTES, 'UTF-8') . '"', $header);
    // The template contains two login-registration lists (mobile + desktop) and
    // we replace them separately to avoid duplicates.
    $authNavMobile = publicTemplateAuthNavHtml('d-flex d-xl-none');
    $authNavDesktop = publicTemplateAuthNavHtml('d-none d-xl-flex');

    $pattern = '#<ul class="login-registration-list[\s\S]*?</ul>#i';
    if (preg_match_all($pattern, $header, $m, PREG_OFFSET_CAPTURE) && !empty($m[0])) {
        $matches = $m[0];
        // Replace from the end so offsets don't shift.
        for ($idx = count($matches) - 1; $idx >= 0; $idx--) {
            $start = $matches[$idx][1];
            $full = $matches[$idx][0];
            $end = $start + strlen($full);

            // idx 0 is the first <ul>, idx 1 is the second <ul> in the template.
            // If there are more, we just alternate styles.
            $replacement = $idx === 0 ? $authNavMobile : $authNavDesktop;
            $header = substr($header, 0, $start) . $replacement . substr($header, $end);
        }
    }

    $header = preg_replace(
        '#<a class="navbar-brand logo" href="[^"]*">#i',
        '<a class="navbar-brand logo" href="' . $homeUrl . '">',
        $header,
        1
    );

    return $header;
}

function publicTemplateHeaderHtml(): string
{
    $html = publicTemplateGetHtml();
    if ($html === '' || !preg_match('#<div class="preloader"[\s\S]*?</header>#i', $html, $matches)) {
        return '';
    }

    return publicTemplateApplyHeaderNav($matches[0]);
}

function publicTemplateApplyFooterTransforms(string $footer): string
{
    require_once __DIR__ . '/../../includes/cms_pages.php';

    $homeUrl = htmlspecialchars(baseUrl(), ENT_QUOTES, 'UTF-8');
    $homeLogoUrl = htmlspecialchars(baseUrl('assets/white-logo.png'), ENT_QUOTES, 'UTF-8');
    $talentsUrl = htmlspecialchars(baseUrl('talents'), ENT_QUOTES, 'UTF-8');
    $jobsUrl = htmlspecialchars(baseUrl('jobs'), ENT_QUOTES, 'UTF-8');
    $postJobUrl = htmlspecialchars(baseUrl('post-job'), ENT_QUOTES, 'UTF-8');
    $brandColumn = '<div class="footer-item">'
        . '<a href="' . $homeUrl . '" class="d-inline-block mb-3"><img src="' . $homeLogoUrl . '" alt="Remoworkers" style="max-height:48px;width:auto"></a>'
        . '<p class="mb-4" style="color:rgba(255,255,255,.75);line-height:1.7">The world\'s work marketplace. Connecting businesses with independent talent across 180+ countries.</p>'
        . '<div class="social-list-wrapper">'
        . '<p class="title">Follow Us</p>'
        . '<ul class="social-list">'
        . '<li class="social-list__item"><a href="' . htmlspecialchars(socialProfileUrl('facebook'), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="social-list__link flex-center" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>'
        . '<li class="social-list__item"><a href="' . htmlspecialchars(socialProfileUrl('x'), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="social-list__link flex-center" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a></li>'
        . '<li class="social-list__item"><a href="' . htmlspecialchars(socialProfileUrl('linkedin'), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="social-list__link flex-center" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a></li>'
        . '<li class="social-list__item"><a href="' . htmlspecialchars(socialProfileUrl('youtube'), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="social-list__link flex-center" aria-label="YouTube"><i class="fab fa-youtube"></i></a></li>'
        . '<li class="social-list__item"><a href="' . htmlspecialchars(socialProfileUrl('instagram'), ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="social-list__link flex-center" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>'
        . '</ul></div></div>';

    $footer = preg_replace(
        '#<div class="footer-item">\s*<h5 class="footer-item__title">\s*Contact Us\s*</h5>[\s\S]*?<div class="social-list-wrapper">[\s\S]*?</ul>\s*</div>\s*</div>#i',
        $brandColumn,
        $footer,
        1
    );

    $footer = strtr($footer, [
        'href="policy/privacy-policy"' => 'href="' . htmlspecialchars(baseUrl('page/privacy-policy'), ENT_QUOTES, 'UTF-8') . '"',
        'href="policy/terms-of-service"' => 'href="' . htmlspecialchars(baseUrl('page/terms-of-service'), ENT_QUOTES, 'UTF-8') . '"',
        'href="cookie-policy"' => 'href="' . htmlspecialchars(baseUrl('page/cookie-settings'), ENT_QUOTES, 'UTF-8') . '"',
        'href="blogs"' => 'href="' . htmlspecialchars(baseUrl('blog'), ENT_QUOTES, 'UTF-8') . '"',
        'href="contact"' => 'href="' . htmlspecialchars(baseUrl('page/contact-us'), ENT_QUOTES, 'UTF-8') . '"',
        'href="buyer/job/post/job-details"' => 'href="' . $postJobUrl . '"',
        'href="freelance-jobs"' => 'href="' . $jobsUrl . '"',
    ]);

    $accessibilityFooterLink = '<li class="footer-menu__item"><a href="' . htmlspecialchars(baseUrl('page/accessibility'), ENT_QUOTES, 'UTF-8') . '" class="footer-menu__link">Accessibility</a></li>';
    $footer = preg_replace(
        '#(<h5 class="footer-item__title">\s*Terms\s*</h5>\s*<ul class="footer-menu">[\s\S]*?)(</ul>\s*</div>)#i',
        '$1' . $accessibilityFooterLink . '$2',
        $footer,
        1
    );

    $liveChatFooterLink = '<li class="footer-menu__item"><a href="#" class="footer-menu__link js-live-chat-trigger" data-live-chat-trigger="1">Live Chat</a></li>';
    $footer = preg_replace(
        '#(<h5 class="footer-item__title">\s*Important\s*Link\s*</h5>\s*<ul class="footer-menu">[\s\S]*?)(</ul>\s*</div>)#i',
        '$1' . $liveChatFooterLink . '$2',
        $footer,
        1
    );

    $footer = preg_replace(
        '#<div class="bottom-footer-text">[\s\S]*?</div>#i',
        '<div class="bottom-footer-text">Copyright ©' . date('Y') . ' <a href="' . $homeUrl . '">Remoworkers</a> All rights reserved.</div>',
        $footer,
        1
    );

    // Ensure footer "Find a Talent" link always points to the public listing page.
    $footer = preg_replace(
        ['~href="talents"~i', "~href='talents'~i"],
        ['href="' . $talentsUrl . '"', "href='" . $talentsUrl . "'"],
        $footer
    );

    // Remove "Login Now" from the Important Link list.
    $footer = preg_replace(
        '#<li class="footer-menu__item">\s*<a href="freelancer/login" class="footer-menu__link">\s*Login Now\s*</a>\s*</li>#i',
        '',
        $footer,
        1
    );

    $footer = preg_replace(
        '#<li class="footer-menu__item[^"]*">\s*<a href="about" class="footer-menu__link">\s*About\s*</a>\s*</li>#i',
        '',
        $footer,
        1
    );

    $footer = preg_replace(
        '~href="freelancer/register"~i',
        'href="#" data-auth-modal="signup"',
        $footer
    );
    $footer = preg_replace(
        '~<a href="[^"]*buyer/register[^"]*" class="sign-up-content__btn btn btn--base"~i',
        '<a href="#" data-auth-modal="login" class="sign-up-content__btn btn btn--base"',
        $footer
    );
    $footer = preg_replace(
        '~<a href="[^"]*freelancer/register[^"]*" class="sign-up-content__btn btn btn--base"~i',
        '<a href="#" data-auth-modal="signup" class="sign-up-content__btn btn btn--base"',
        $footer
    );

    return $footer;
}

function publicTemplateFooterHtml(): string
{
    $html = publicTemplateGetHtml();
    if ($html === '' || !preg_match('#<footer class="footer-area">[\s\S]*?</footer>#i', $html, $matches)) {
        return '';
    }

    return publicTemplateApplyFooterTransforms($matches[0]);
}

function publicTemplateRenderHeader(): void
{
    echo publicTemplateHeaderHtml();
    echo '<main class="public-site-main">';
}

function publicTemplateRenderFooter(): void
{
    echo '</main>';
    echo publicTemplateFooterHtml();
}
