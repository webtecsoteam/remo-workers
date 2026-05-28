<?php
require_once __DIR__ . '/../includes/config.php';

$templatePath = null;
$templateCandidates = [];
$templateCandidates[] = BASE_PATH . '/home/template/index.php';
$configuredTemplatePath = trim((string) env('HOMEPAGE_TEMPLATE_PATH', ''));
if ($configuredTemplatePath !== '') {
    $templateCandidates[] = $configuredTemplatePath;
}
$templateCandidates[] = dirname(BASE_PATH) . '/free/index.php';
$templateCandidates[] = BASE_PATH . '/free/index.php';

foreach ($templateCandidates as $candidate) {
    if (is_readable($candidate)) {
        $templatePath = $candidate;
        break;
    }
}

if ((bool) env('HOMEPAGE_TEMPLATE_DEBUG', false)) {
    error_log(
        '[homepage-template] selected=' . ($templatePath ?? 'NONE')
        . ' candidates=' . implode(' | ', $templateCandidates)
    );
}

$html = $templatePath !== null ? @file_get_contents($templatePath) : false;

if ($html === false) {
    http_response_code(500);
    echo 'Unable to load homepage template.';
    exit;
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

// Normalize blog links to the dedicated blog page everywhere.
$blogUrl = htmlspecialchars(baseUrl('blog'), ENT_QUOTES, 'UTF-8');
$html = preg_replace('#href=(["\'])blogs\1#i', 'href="' . $blogUrl . '"', $html);
$html = preg_replace('#href=(["\'])[^"\']*blogs[^"\']*\1#i', 'href="' . $blogUrl . '"', $html);
$html = preg_replace('#onclick=(["\'])openBlogHub\((.*?)\)\1#i', 'href="' . $blogUrl . '"', $html);

// Remove template slider scripts and inject them later in guaranteed order.
$html = preg_replace('#<script[^>]*src=(["\'])[^"\']*/templates/basic/js/main\.js[^"\']*\1[^>]*>\s*</script>#i', '', $html);
$html = preg_replace('#<script[^>]*src=(["\'])[^"\']*/templates/basic/js/slick\.min\.js[^"\']*\1[^>]*>\s*</script>#i', '', $html);

// Remove header language/country switcher dropdowns from imported theme.
$html = preg_replace(
    '/<div class="custom--dropdown">[\s\S]*?<li class="dropdown-list__item langSel"[\s\S]*?<\/ul>\s*<\/div>/i',
    '',
    $html
);

// Force homepage logo and favicon to project assets.
$brandLogoUrl = baseUrl('assets/logo.png');
$brandFaviconUrl = baseUrl('favicon.png');
$html = preg_replace('/(src|href)=("|\')[^"\']*logo_icon\/logo\.png("|\')/i', '$1=$2' . $brandLogoUrl . '$3', $html);
$html = preg_replace('/(content)=("|\')[^"\']*logo_icon\/logo\.png("|\')/i', '$1=$2' . $brandLogoUrl . '$3', $html);
$html = preg_replace('/(src|href)=("|\')[^"\']*logo_icon\/favicon\.png("|\')/i', '$1=$2' . $brandFaviconUrl . '$3', $html);
$html = preg_replace('/(content)=("|\')[^"\']*logo_icon\/favicon\.png("|\')/i', '$1=$2' . $brandFaviconUrl . '$3', $html);

// Remove Google and Tawk injected scripts/widgets from template output.
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

require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/blog_public.php';
require_once __DIR__ . '/../includes/cms_pages.php';
require_once __DIR__ . '/../includes/home_categories.php';
require_once __DIR__ . '/../includes/home_featured_talent.php';
require_once __DIR__ . '/includes/public_template.php';
$authUser = Auth::user();
$isLoggedIn = $authUser !== null;
$authUserNameJson = json_encode($authUser['name'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($authUserNameJson === false) {
    $authUserNameJson = '""';
}
$dashboardUrl = baseUrl($isLoggedIn && (($authUser['role'] ?? '') === 'client') ? 'client' : 'remoworkers-dashboard');
$logoutUrl = baseUrl('logout');
$signupCountryOptionsHtml = buildCountryOptionsHtml(null, 'United Kingdom');
$signupCountryOptionsJson = json_encode($signupCountryOptionsHtml, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($signupCountryOptionsJson === false) {
    $signupCountryOptionsJson = '""';
}

$homeCategories = [];
try {
    $homeCategories = getHomeCategoriesWithCounts();
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('Homepage categories render failed: ' . $e->getMessage());
    }
}

$homeCategoryStats = [];
foreach ($homeCategories as $cat) {
    $modalKey = (string) ($cat['modal_key'] ?? '');
    if ($modalKey === '') {
        continue;
    }
    $homeCategoryStats[$modalKey] = [
        'skills_count' => (int) ($cat['skills_count'] ?? 0),
        'open_jobs_count' => (int) ($cat['open_jobs_count'] ?? 0),
        'freelancers_count' => (int) ($cat['freelancers_count'] ?? 0),
    ];
}

$homeCategoryStatsJson = json_encode($homeCategoryStats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($homeCategoryStatsJson === false) {
    $homeCategoryStatsJson = '{}';
}

$categoryCardsHtml = '';
foreach ($homeCategories as $cat) {
    $name = htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $emoji = htmlspecialchars((string) ($cat['emoji'] ?? '💼'), ENT_QUOTES, 'UTF-8');
    $modalKey = htmlspecialchars((string) ($cat['modal_key'] ?? ''), ENT_QUOTES, 'UTF-8');
    $imageUrl = trim((string) ($cat['image_url'] ?? ''));
    $openJobsCount = max(0, (int) ($cat['open_jobs_count'] ?? 0));
    $jobsLabel = number_format($openJobsCount) . ' Job' . ($openJobsCount === 1 ? '' : 's');
    $jobsLabelEscaped = htmlspecialchars($jobsLabel, ENT_QUOTES, 'UTF-8');

    if ($name === '' || $modalKey === '') {
        continue;
    }

    $thumbHtml = '<div style="min-height:90px;display:flex;align-items:center;justify-content:center;background:#f0f8ee;font-size:30px;">' . $emoji . '</div>';
    if ($imageUrl !== '') {
        $thumbHtml = '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $name . '">';
    }

    $jobsUrl = htmlspecialchars(baseUrl('jobs?home_category=' . rawurlencode((string) ($cat['id'] ?? ''))), ENT_QUOTES, 'UTF-8');
    $categoryCardsHtml .= '<a href="' . $jobsUrl . '" class="category-item">'
        . '<div class="category-item__thumb">' . $thumbHtml . '</div>'
        . '<div class="category-item__content"><h5 class="category-item__title">' . $name . '</h5><p class="category-item__text">' . $jobsLabelEscaped . '</p></div>'
        . '</a>';
}

if ($categoryCardsHtml !== '') {
    $categorySectionHtml = '<div class="category-section my-120"><div class="container"><div class="category-slider">'
        . $categoryCardsHtml
        . '</div></div></div>';
    $html = preg_replace(
        '#<div class="category-section my-120">[\s\S]*?(?=<div class="how-wowrk-section my-120">)#i',
        $categorySectionHtml,
        $html,
        1
    );
}

$latestBlogs = getPublishedBlogs(null, 6);
$latestBlogSectionHtml = '<section class="blog my-120"><div class="container"><div class="row"><div class="col-lg-12"><div class="section-heading two"><h2 class="section-heading__title">Our Latest Blog Post</h2></div></div></div><div class="row gy-4 justify-content-center">';
if (!$latestBlogs) {
    $latestBlogSectionHtml .= '<div class="col-lg-12"><p class="text-center mb-0">No published blog posts yet.</p></div>';
} else {
    foreach ($latestBlogs as $post) {
        $articleUrl = baseUrl('blog/' . (int) ($post['id'] ?? 0));
        $thumbUrl = trim((string) ($post['image_url'] ?? ''));
        $title = htmlspecialchars((string) ($post['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $meta = htmlspecialchars((string) ($post['meta'] ?? ''), ENT_QUOTES, 'UTF-8');
        $excerpt = htmlspecialchars((string) ($post['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8');

        $latestBlogSectionHtml .= '<div class="col-xl-4 col-sm-6"><a href="' . htmlspecialchars($articleUrl, ENT_QUOTES, 'UTF-8') . '" class="blog-item">';
        $latestBlogSectionHtml .= '<div class="blog-item__thumb">';
        if ($thumbUrl !== '') {
            $latestBlogSectionHtml .= '<img src="' . htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') . '" class="fit-image" alt="' . $title . '">';
        } else {
            $latestBlogSectionHtml .= '<div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted" style="min-height:220px;padding:18px;text-align:center">' . $title . '</div>';
        }
        $latestBlogSectionHtml .= '</div>';
        $latestBlogSectionHtml .= '<div class="blog-item__content">';
        $latestBlogSectionHtml .= '<h6 class="blog-item__title">' . $title . '</h6>';
        $latestBlogSectionHtml .= '<ul class="text-list flex-align"><li class="text-list__item"><i class="las la-clock"></i>' . $meta . '</li></ul>';
        $latestBlogSectionHtml .= '<p>' . $excerpt . '</p>';
        $latestBlogSectionHtml .= '</div></a></div>';
    }
}
$latestBlogSectionHtml .= '</div></div></section>';

$html = preg_replace(
    '#<section class="blog my-120">[\s\S]*?</section>#i',
    $latestBlogSectionHtml,
    $html,
    1
);

// Replace only the cards inside existing "Top Rated Freelancers" section.
$topFreelancers = getFeaturedFreelancers(12);
$topRatedCardsHtml = '';
foreach ($topFreelancers as $t) {
    $name = htmlspecialchars((string) ($t['name'] ?? 'Freelancer'), ENT_QUOTES, 'UTF-8');
    $title = trim((string) ($t['title'] ?? ''));
    $designation = htmlspecialchars($title !== '' ? $title : 'Freelancer', ENT_QUOTES, 'UTF-8');
    $profileUrl = htmlspecialchars((string) ($t['profile_url'] ?? baseUrl('remoworkers-dashboard')), ENT_QUOTES, 'UTF-8');
    $avatar = (string) ($t['avatar_url'] ?? '');
    $avatarSrc = $avatar !== '' ? htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') : htmlspecialchars(baseUrl('assets/free-home/images/user/avatar.png'), ENT_QUOTES, 'UTF-8');
    $skills = $t['skills'] ?? [];
    if (!is_array($skills)) {
        $skills = [];
    }
    $skillsHtml = '';
    foreach (array_slice($skills, 0, 3) as $skill) {
        if (!is_string($skill) || trim($skill) === '') continue;
        $skillsHtml .= '<li class="skill-list__item"><span class="skill-list__link">' . htmlspecialchars($skill, ENT_QUOTES, 'UTF-8') . '</span></li>';
    }
    if ($skillsHtml !== '') {
        $skillsHtml = '<ul class="skill-list">' . $skillsHtml . '</ul>';
    }

    $ratingVal = (string) ($t['rating'] ?? '0.0');
    $ratingFloat = is_numeric($ratingVal) ? (float) $ratingVal : 0.0;
    $starsHtml = '';
    for ($i = 0; $i < 5; $i++) {
        $starsHtml .= '<li class="review-rating-list__item"> <i class="las la-star"></i> </li>';
    }
    $ratingHtml = '<ul class="text-list review-rating-list mb-0">' . $starsHtml
        . '<li class="text-list__item">' . htmlspecialchars(number_format($ratingFloat, 2), ENT_QUOTES, 'UTF-8') . '/5</li></ul>';

    $topRatedCardsHtml .= '<div class="freelancer-item">'
        . '<div class="freelancer-item__thumb"><img src="' . $avatarSrc . '" alt="' . $name . '"></div>'
        . '<div class="freelancer-item__content">'
        . '<h6 class="freelancer-item__name"> ' . $name . '</h6>'
        . '<span class="freelancer-item__designation">' . $designation . '</span>'
        . $ratingHtml
        . $skillsHtml
        . '<div class="freelancer-item__btn"><a href="' . $profileUrl . '" class="btn--base btn btn--sm">View Profile</a></div>'
        . '</div></div>';
}

if ($topRatedCardsHtml !== '') {
    $topRatedSectionHtml = '<div class="best-freelancer-section py-120 my-120">'
        . '<div class="container">'
        . '<div class="row">'
        . '<div class="col-lg-12">'
        . '<div class="section-heading style-left highlight">'
        . '<h2 class="section-heading__title s-highlight" data-s-break="-1" data-s-length="1">Top Rated <span class="text--base">Freelancers</span></h2>'
        . '<p class="section-heading__desc"> Access top-rated professionals. Browse profiles, read reviews, and hire the best talent.</p>'
        . '</div></div></div>'
        . '<div class="best-freelancer">' . $topRatedCardsHtml . '</div>'
        . '<div class="counter-up-wrapper">'
        . '<div class="counterup-item ">'
        . '<div class="counterup-item__content"><div class="counterup-wrapper"><span class="counterup-item__icon"><i class="far fa-star"></i></span><div class="content"><div class="counterup-item__number"><h5 class="counterup-item__title">96 Million</h5></div><span class="counterup-item__text mb-0">Top Rated freelancers, covering 8,766 skills</span></div></div></div>'
        . '<div class="counterup-item__content"><div class="counterup-wrapper"><span class="counterup-item__icon"><i class="fa-solid fa-sack-dollar"></i></span><div class="content"><div class="counterup-item__number"><h5 class="counterup-item__title">110 Million</h5></div><span class="counterup-item__text mb-0">Every year earned by top freelancers earning over $7,000/m</span></div></div></div>'
        . '<div class="counterup-item__content"><div class="counterup-wrapper"><span class="counterup-item__icon"><i class="fas fa-hourglass-half"></i></span><div class="content"><div class="counterup-item__number"><h5 class="counterup-item__title">3 Minute</h5></div><span class="counterup-item__text mb-0">Find task a freelancer, with 90% of projects completed in 7 days</span></div></div></div>'
        . '</div></div>'
        . '</div></div>';

    $html = preg_replace(
        '#<div class="best-freelancer-section[\s\S]*?(?=<section\b)#i',
        $topRatedSectionHtml,
        $html,
        1
    );
}

if (preg_match('#<div class="preloader"[\s\S]*?</header>#i', $html, $headerMatch)) {
    $html = preg_replace(
        '#<div class="preloader"[\s\S]*?</header>#i',
        publicTemplateApplyHeaderNav($headerMatch[0]),
        $html,
        1
    );
}

if (preg_match('#<footer class="footer-area">[\s\S]*?</footer>#i', $html, $footerMatch)) {
    $html = preg_replace(
        '#<footer class="footer-area">[\s\S]*?</footer>#i',
        publicTemplateApplyFooterTransforms($footerMatch[0]),
        $html,
        1
    );
}

$inject = <<<'HTML'
<script>const APP_URL = "__APP_URL__";</script>
<script>window.COUNTRY_OPTIONS_HTML = __COUNTRY_OPTIONS_HTML__;</script>
<script>window.HOME_CATEGORY_STATS = __HOME_CATEGORY_STATS__;</script>
<link rel="stylesheet" href="__UI_ALERTS_CSS__">
<link rel="stylesheet" href="__HOME_MODALS_CSS__">
<div class="overlay" id="overlay" onclick="closeModal(event)"><div class="modal" id="modal"><div class="modal-head"><h2 id="modal-title">Details</h2><button class="modal-close" onclick="closeModal()">✕</button></div><div class="modal-body" id="modal-body"></div></div></div>
<script src="__JQUERY_JS__"></script>
<script src="__BOOTSTRAP_JS__"></script>
<script src="__SELECT2_JS__"></script>
<script src="__VIEWPORT_JS__"></script>
<script src="__SLICK_JS__"></script>
<script src="__MAIN_JS__"></script>
<script defer src="__UI_ALERTS_JS__"></script>
<script defer src="__HOME_MODALS_JS__"></script>
<script defer src="__HOME_AUTH_JS__"></script>
<script>
const HEADER_AUTH = {
  isLoggedIn: __IS_LOGGED_IN__,
  name: __AUTH_USER_NAME__,
  dashboardUrl: "__DASHBOARD_URL__",
  logoutUrl: "__LOGOUT_URL__"
};

document.addEventListener('DOMContentLoaded', function () {
  const routeForm = document.getElementById('dynamic-route');
  const routeSelect = document.getElementById('target-area');
  if (routeForm) {
    const jobsUrl = APP_URL.replace(/\/+$/, '') + '/jobs';
    const talentsUrl = APP_URL.replace(/\/+$/, '') + '/talents';
    const searchInput = routeForm.querySelector('input[name="search"]');

    const resolveTarget = function () {
      const selectedOption = routeSelect ? routeSelect.options[routeSelect.selectedIndex] : null;
      const selectedValue = (routeSelect ? String(routeSelect.value || '') : '').trim();
      const optionRedirect = selectedOption ? String(selectedOption.getAttribute('data-redirect') || '') : '';
      const normalizedRedirect = optionRedirect.toLowerCase();
      if (selectedValue === '2' || normalizedRedirect.endsWith('/talents') || normalizedRedirect.indexOf('talent') !== -1) {
        return talentsUrl;
      }
      return jobsUrl;
    };

    routeForm.setAttribute('action', resolveTarget());
    if (routeSelect) {
      routeSelect.addEventListener('change', function () {
        routeForm.setAttribute('action', resolveTarget());
      });
    }

    routeForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const keyword = searchInput ? String(searchInput.value || '').trim() : '';
      const targetBase = resolveTarget();
      const target = new URL(targetBase, window.location.origin);
      if (keyword !== '') {
        target.searchParams.set('search', keyword);
      }
      window.location.href = target.toString();
    });
  }

  const params = new URLSearchParams(window.location.search);
  if (params.get('login') === '1' && typeof openModal === 'function') {
    openModal('login');
  }

  if (!HEADER_AUTH.isLoggedIn) return;
  document.querySelectorAll('.login-registration-list').forEach(function (list) {
    const authAnchors = list.querySelectorAll('a[href]');
    authAnchors.forEach(function (a) {
      const href = (a.getAttribute('href') || '').replace(/\/+$/, '');
      if (
        href.endsWith('freelancer/login') ||
        href.endsWith('freelancer/register') ||
        href.endsWith('/login') ||
        href.endsWith('/register')
      ) {
        const li = a.closest('li');
        if (li) li.remove();
      }
    });

    if (list.querySelector('.js-auth-user')) return;

    const nameLi = document.createElement('li');
    nameLi.className = 'login-registration-list__item js-auth-user';
    nameLi.innerHTML = '<span class="login-registration-list__link" style="cursor:default;opacity:.85">' + (HEADER_AUTH.name || 'User') + '</span>';

    const dashboardLi = document.createElement('li');
    dashboardLi.className = 'login-registration-list__item js-auth-user';
    dashboardLi.innerHTML = '<a href="' + HEADER_AUTH.dashboardUrl + '" class="btn btn--base btn--sm">Dashboard</a>';

    const logoutLi = document.createElement('li');
    logoutLi.className = 'login-registration-list__item js-auth-user';
    logoutLi.innerHTML = '<a href="' + HEADER_AUTH.logoutUrl + '" class="login-registration-list__link">Logout</a>';

    list.insertBefore(nameLi, list.firstChild);
    list.appendChild(dashboardLi);
    list.appendChild(logoutLi);
  });
});

document.addEventListener('click', function (e) {
  const a = e.target.closest('a[href]');
  if (!a) return;
  const href = a.getAttribute('href') || '';
  if (href.endsWith('freelancer/login') || href === 'freelancer/login' || href.endsWith('/login')) {
    e.preventDefault();
    if (typeof openModal === 'function') openModal('login');
  }
  if (href.endsWith('freelancer/register') || href === 'freelancer/register' || href.endsWith('/register')) {
    e.preventDefault();
    if (typeof openModal === 'function') openModal('signup');
  }
  const normalizedHref = href.replace(/\/+$/, '');
  if (!__IS_LOGGED_IN__ && (normalizedHref.endsWith('/client') || normalizedHref === 'client')) {
    e.preventDefault();
    if (typeof openModal === 'function') openModal('login');
    return;
  }
});
</script>
HTML;

$inject = str_replace('__COUNTRY_OPTIONS_HTML__', $signupCountryOptionsJson, $inject);
$inject = str_replace('__HOME_CATEGORY_STATS__', $homeCategoryStatsJson, $inject);
$inject = str_replace('__IS_LOGGED_IN__', $isLoggedIn ? 'true' : 'false', $inject);
$inject = str_replace('__AUTH_USER_NAME__', $authUserNameJson, $inject);
$inject = str_replace('__DASHBOARD_URL__', htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__LOGOUT_URL__', htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__APP_URL__', htmlspecialchars(baseUrl(), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__UI_ALERTS_CSS__', htmlspecialchars(baseUrl('assets/css/ui-alerts.css'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__HOME_MODALS_CSS__', htmlspecialchars(baseUrl('home/css/home-modals.css?v=1.0.1'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__JQUERY_JS__', htmlspecialchars(baseUrl('assets/free-home/global/js/jquery-3.7.1.min.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__BOOTSTRAP_JS__', htmlspecialchars(baseUrl('assets/free-home/global/js/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__SELECT2_JS__', htmlspecialchars(baseUrl('assets/free-home/global/js/select2.min.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__VIEWPORT_JS__', htmlspecialchars(baseUrl('assets/free-home/templates/basic/js/viewport.jquery.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__SLICK_JS__', htmlspecialchars(baseUrl('assets/free-home/templates/basic/js/slick.min.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__MAIN_JS__', htmlspecialchars(baseUrl('assets/free-home/templates/basic/js/main.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__UI_ALERTS_JS__', htmlspecialchars(baseUrl('assets/js/ui-alerts.js'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__HOME_MODALS_JS__', htmlspecialchars(baseUrl('home/js/home-modals.js?v=1.0.5'), ENT_QUOTES, 'UTF-8'), $inject);
$inject = str_replace('__HOME_AUTH_JS__', htmlspecialchars(baseUrl('home/js/home-auth.js?v=1.0.4'), ENT_QUOTES, 'UTF-8'), $inject);

$html = str_replace('</body>', $inject . '</body>', $html);
echo $html;