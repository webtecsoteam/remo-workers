<?php
require_once __DIR__ . '/../includes/cms_pages.php';
require_once __DIR__ . '/../includes/seo_public.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$page = getPublishedCmsPageBySlug($slug);

if (!$page) {
    http_response_code(404);
    include __DIR__ . '/../includes/404.php';
    exit;
}

$externalUrl = trim((string) ($page['link_target'] ?? ''));
if (($page['link_type'] ?? '') === 'external' && $externalUrl !== '' && preg_match('#^https?://#i', $externalUrl)) {
    header('Location: ' . $externalUrl, true, 302);
    exit;
}

$seoMeta = seoForCmsPage($page);
include __DIR__ . '/includes/header.php';
?>

<article class="cms-page sec">
  <div class="cms-page-inner">
    <h1 class="cms-page-title"><?php echo htmlspecialchars($page['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <div class="cms-page-body">
      <?php echo $page['description']; ?>
    </div>
  </div>
</article>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
