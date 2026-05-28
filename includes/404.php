<?php
$seoMeta = [
    'title' => '404 - Page Not Found | Remoworkers',
    'description' => 'The page you are looking for does not exist or has been moved.',
];
$activeNav = '';

include __DIR__ . '/../home/includes/header.php';
?>

<style>
.error-page-wrap { max-width: 640px; margin: 60px auto 80px; padding: 0 24px; text-align: center; }
.error-page-code { font-family: 'Instrument Serif', serif; font-size: clamp(72px, 14vw, 120px); line-height: 1; color: var(--g, #14a800); margin-bottom: 12px; }
.error-page-wrap h1 { font-family: 'Instrument Serif', serif; font-size: clamp(28px, 5vw, 40px); font-weight: 400; margin-bottom: 12px; }
.error-page-wrap p { color: var(--muted, #617a5a); font-size: 15px; margin-bottom: 28px; }
.error-page-actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
.error-page-actions .btn { min-width: 140px; }
</style>

<div class="error-page-wrap">
    <div class="error-page-code">404</div>
    <h1>Page Not Found</h1>
    <p>The page you're looking for doesn't exist or has been moved.</p>
    <div class="error-page-actions">
        <a href="<?php echo baseUrl(); ?>" class="btn btn--base">Back to Home</a>
        <a href="<?php echo baseUrl('remoworkers-dashboard#find-work'); ?>" class="btn btn-outline-light" style="border:1.5px solid var(--border,#dce8d8);color:var(--dark,#111)">Find Jobs</a>
        <a href="<?php echo baseUrl('talents'); ?>" class="btn btn-outline-light" style="border:1.5px solid var(--border,#dce8d8);color:var(--dark,#111)">Find Talents</a>
    </div>
</div>

<?php include __DIR__ . '/../home/includes/site-footer.php'; ?>
<?php include __DIR__ . '/../home/includes/footer.php'; ?>
