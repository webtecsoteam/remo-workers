<?php
require_once __DIR__ . '/../includes/blog_public.php';
require_once __DIR__ . '/../includes/cms_pages.php';
require_once __DIR__ . '/../includes/seo_public.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = $id > 0 ? getPublishedBlogById($id) : null;

if (!$post) {
    http_response_code(404);
    include __DIR__ . '/../includes/404.php';
    exit;
}

$seoMeta = [
    'title' => $post['name'] . ' | Remoworkers',
    'description' => $post['excerpt'] ?? '',
    'canonical' => blogArticleUrl((int) $post['id']),
];

$activeNav = 'blog';

include __DIR__ . '/includes/header.php';
?>

<article class="sec cms-page blog-article-page">
  <div class="cms-page-inner blog-article-shell">
    <header class="blog-article-header">
      <p class="blog-article-back"><a href="<?php echo htmlspecialchars(blogHubUrl(), ENT_QUOTES, 'UTF-8'); ?>">← Back to all articles</a></p>
      <div class="blog-cat"><?php echo htmlspecialchars($post['category'] ?: 'Article', ENT_QUOTES, 'UTF-8'); ?></div>
      <h1 class="cms-page-title"><?php echo htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <div class="bmeta blog-article-meta"><?php echo htmlspecialchars($post['meta'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
    </header>

    <?php if (!empty($post['image_url'])): ?>
      <div class="blog-article-hero"><img src="<?php echo htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt=""></div>
    <?php endif; ?>

    <div class="cms-page-body blog-full blog-article-content">
      <?php echo $post['description']; ?>
    </div>
  </div>
</article>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
