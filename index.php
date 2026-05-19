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

    case 'login':
        include __DIR__ . '/actions/login.php';
        break;

    case 'register':
        include __DIR__ . '/actions/register.php';
        break;

    case 'logout':
        require_once __DIR__ . '/includes/classes/Auth.php';
        Auth::logout();
        redirect(baseUrl());
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
