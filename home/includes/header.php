<?php
require_once __DIR__ . '/../../includes/seo_public.php';
require_once __DIR__ . '/public_template.php';

if (!isset($usePublicTemplate)) {
    $usePublicTemplate = true;
}
if (!isset($isHome)) {
    $isHome = false;
}
if (!isset($seoMeta)) {
    $seoMeta = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php renderSeoMetaTags($seoMeta, $isHome); ?>
<?php include __DIR__ . '/../../includes/google-analytics.php'; ?>
<?php if ($usePublicTemplate): ?>
<link rel="stylesheet" href="<?php echo baseUrl('assets/free-home/global/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/free-home/global/css/all.min.css'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/free-home/global/css/line-awesome.min.css'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/free-home/templates/basic/css/slick.css'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/free-home/templates/basic/css/main.css'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/free-home/templates/basic/css/custom.css'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('home/css/public-pages.css?v=1.0.2'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('home/css/home-modals.css?v=1.0.1'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/ui-alerts.css'); ?>">
<?php if (!empty($pageExtraCss)): ?>
<?php foreach ($pageExtraCss as $cssFile): ?>
<link rel="stylesheet" href="<?php echo baseUrl($cssFile); ?>">
<?php endforeach; ?>
<?php endif; ?>
<?php else: ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"></noscript>
<link rel="stylesheet" href="<?php echo baseUrl('home/css/style.css?v=1.0.4'); ?>">
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/ui-alerts.css'); ?>">
<?php endif; ?>
<link rel="icon" type="image/png" href="<?php echo baseUrl('favicon.png?v=1.0.0'); ?>">
<script>const APP_URL = '<?php echo baseUrl(); ?>';</script>
<script defer src="<?php echo baseUrl('assets/js/ui-alerts.js'); ?>"></script>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/classes/Auth.php'; $user = Auth::user(); ?>
<div class="overlay" id="overlay" onclick="closeModal(event)"><div class="modal" id="modal"><div class="modal-head"><h2 id="modal-title">Details</h2><button class="modal-close" onclick="closeModal()">✕</button></div><div class="modal-body" id="modal-body"></div></div></div>
<?php if ($usePublicTemplate): ?>
<?php publicTemplateRenderHeader(); ?>
<?php endif; ?>
