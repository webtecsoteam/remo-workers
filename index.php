<?php
/**
 * =============================================
 * RemoWorkers - Main Router
 * =============================================
 * Routes:
 *   /                        → Home (home.php)
 *   /client/*         → Client section (client folder)
 *   /remoworkers-dashboard/* → Freelancer section (freelancer folder)
 */

require_once __DIR__ . '/includes/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    handleCorsPreflight();
}

// Get the current route
$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';

// Parse the route segments
$segments = $route ? explode('/', $route) : [];
$section = $segments[0] ?? '';
$page = $segments[1] ?? 'index';

// =============================================
// ROUTE HANDLING
// =============================================

switch ($section) {
    case 'ccpayment-webhook-deposit':
        include __DIR__ . '/actions/ccpayment_webhook_deposit.php';
        break;

    case 'ccpayment-webhook-withdraw':
        include __DIR__ . '/actions/ccpayment_webhook_withdraw.php';
        break;

    // ------------------------------------------
    // Route 1: Home Page (home.php)
    // ------------------------------------------
    case '':
        include __DIR__ . '/home/index.php';
        break;

    case 'j':
        $jobId = decodeJobId($page);
        if ($jobId > 0) {
            $_GET['id'] = $jobId;
            include __DIR__ . '/home/job.php';
        } else {
            redirect(baseUrl());
        }
        break;

    case 'f':
        $freelancerId = decodeFreelancerId($page);
        if ($freelancerId > 0) {
            $_GET['id'] = $freelancerId;
            include __DIR__ . '/home/freelancer-profile.php';
        } else {
            redirect(baseUrl());
        }
        break;

    case 'login':
        include __DIR__ . '/actions/login.php';
        break;

    case 'post-job':
        require_once __DIR__ . '/includes/classes/Auth.php';
        $viewer = Auth::user();
        if (!$viewer) {
            $_SESSION['post_job_after_login'] = true;
            redirect(baseUrl('?login=1'));
        }
        if (($viewer['role'] ?? '') === 'client') {
            redirect(baseUrl('client#post-job'));
        }
        redirect(baseUrl('remoworkers-dashboard'));
        break;

    case 'register':
        include __DIR__ . '/actions/register.php';
        break;

    case 'logout':
        require_once __DIR__ . '/includes/classes/Auth.php';
        Auth::logout();
        redirect(baseUrl());
        break;

    case 'page':
        $_GET['slug'] = $page;
        include __DIR__ . '/home/page.php';
        break;

    case 'blog':
        if ($page !== '' && ctype_digit($page)) {
            $_GET['id'] = (int) $page;
            include __DIR__ . '/home/blog-article.php';
        } else {
            include __DIR__ . '/home/blog.php';
        }
        break;

    case 'jobs':
        include __DIR__ . '/home/jobs.php';
        break;

    case 'talents':
        include __DIR__ . '/home/talents.php';
        break;

    case 'verification':
        include __DIR__ . '/home/verification.php';
        break;

    case 'upload-doc':
        include __DIR__ . '/actions/upload_doc.php';
        break;

    case 'verify-email':
        include __DIR__ . '/actions/verify_email.php';
        break;

    case 'reset-password':
        include __DIR__ . '/home/reset_password.php';
        break;

    // ------------------------------------------
    // Route 2: Client Section (/client/*)
    // ------------------------------------------
    case 'client':
        $clientPage = __DIR__ . '/client/' . $page . '.php';
        if (file_exists($clientPage)) {
            include $clientPage;
        } else {
            include __DIR__ . '/client/index.php';
        }
        break;

    case 'admin':
        $adminPage = __DIR__ . '/admin/' . $page . '.php';
        if (file_exists($adminPage)) {
            include $adminPage;
        } else {
            include __DIR__ . '/admin/index.php';
        }
        break;

    // ------------------------------------------
    // Route 3: Freelancer Dashboard (/remoworkers-dashboard/*)
    // ------------------------------------------
    case 'remoworkers-dashboard':
        if ($page === 'j' && !empty($segments[2])) {
            $jobId = decodeJobId($segments[2]);
            if ($jobId > 0) {
                $_GET['id'] = $jobId;
                include __DIR__ . '/freelancer/job.php';
                break;
            }
            redirect(baseUrl('remoworkers-dashboard#find-work'));
        }
        $freelancerPage = __DIR__ . '/freelancer/' . $page . '.php';
        if (file_exists($freelancerPage)) {
            include $freelancerPage;
        } else {
            include __DIR__ . '/freelancer/index.php';
        }
        break;

    // ------------------------------------------
    // 404 - Page Not Found
    // ------------------------------------------
    default:
        http_response_code(404);
        include __DIR__ . '/includes/404.php';
        break;
}
