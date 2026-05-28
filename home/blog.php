<?php
require_once __DIR__ . '/../includes/blog_public.php';
require_once __DIR__ . '/../includes/cms_pages.php';
require_once __DIR__ . '/../includes/seo_public.php';

$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$posts = getPublishedBlogs($category !== '' ? $category : null);
$filters = blogFilterOptionsForApi();

$seoMeta = [
    'title' => ($category !== '' && strcasecmp($category, 'all') !== 0)
        ? $category . ' | Blog | Remoworkers'
        : 'Blog & Insights | Remoworkers',
    'description' => 'Guides, hiring tips, and insights from the Remoworkers team.',
    'canonical' => blogHubUrl($category !== '' ? $category : null),
];

$activeNav = 'blog';

include __DIR__ . '/includes/header.php';
?>

<section class="sec cms-page blog-hub-page">
  <div class="cms-page-inner blog-hub-shell">
    <header class="blog-hub-hero">
      <div class="sec-lbl">Resources</div>
      <h1 class="cms-page-title">Blog &amp; Insights</h1>
      <p class="blog-hub-intro">Guides, tips, and stories to help you hire smarter and grow your freelance career.</p>
    </header>

    <div class="blog-listing-panel">
      <div class="blog-listing-head">
        <h2 class="blog-listing-title">Latest Articles</h2>
        <div class="talent-filters blog-filters" role="tablist" aria-label="Filter articles by category">
          <?php foreach ($filters as $filter): ?>
            <?php
            $active = ($category === '' && $filter['id'] === 'all')
                || ($category !== '' && strcasecmp($category, $filter['id']) === 0);
            $href = blogHubUrl($filter['id'] === 'all' ? null : $filter['id']);
            ?>
            <a class="tf<?php echo $active ? ' on' : ''; ?>" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="blog-grid">
        <?php if (!$posts): ?>
          <div class="blog-grid-status">No published articles in this category yet. Check back soon.</div>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <a class="blog-c blog-c-link" href="<?php echo htmlspecialchars(blogArticleUrl((int) $post['id']), ENT_QUOTES, 'UTF-8'); ?>">
              <div class="blog-img<?php echo !empty($post['image_url']) ? ' has-img' : ''; ?>">
                <?php if (!empty($post['image_url'])): ?>
                  <img src="<?php echo htmlspecialchars($post['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php else: ?>
                  <?php echo htmlspecialchars($post['emoji'] ?? '📚', ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </div>
              <div class="blog-body">
                <div class="blog-cat"><?php echo htmlspecialchars($post['category'] ?: 'Article', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="blog-title"><?php echo htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="blog-excerpt"><?php echo htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="blog-meta"><?php echo htmlspecialchars($post['meta'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
