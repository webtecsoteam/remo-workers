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
