<?php
$pageTitle = '404 - Page Not Found | ' . APP_NAME;
$section = '';
include __DIR__ . '/header.php';
?>

<main class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1>Page Not Found</h1>
            <p>The page you're looking for doesn't exist or has been moved.</p>
            <div class="error-actions">
                <a href="<?php echo baseUrl(); ?>" class="btn btn-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="<?php echo baseUrl('upwork-client'); ?>" class="btn btn-outline">
                    <i class="fas fa-briefcase"></i> Hire Talent
                </a>
                <a href="<?php echo baseUrl('remoworkers-dashboard'); ?>" class="btn btn-outline">
                    <i class="fas fa-laptop-code"></i> Find Work
                </a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
