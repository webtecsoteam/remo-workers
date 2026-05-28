<?php
require_once __DIR__ . '/../../includes/config.php';
$signupCountryOptionsHtml = buildCountryOptionsHtml(null, 'United Kingdom');

if (!isset($usePublicTemplate)) {
    $usePublicTemplate = true;
}
?>
<script>
window.COUNTRY_OPTIONS_HTML = <?php echo json_encode($signupCountryOptionsHtml, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<?php if ($usePublicTemplate): ?>
<script src="<?php echo baseUrl('assets/free-home/global/js/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/free-home/global/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/free-home/templates/basic/js/viewport.jquery.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/free-home/templates/basic/js/slick.min.js'); ?>"></script>
<script src="<?php echo baseUrl('assets/free-home/templates/basic/js/main.js'); ?>"></script>
<?php endif; ?>
<script defer src="<?php echo baseUrl('home/js/home-modals.js?v=1.0.4'); ?>"></script>
<script defer src="<?php echo baseUrl('home/js/home-auth.js?v=1.0.3'); ?>"></script>
<script defer src="<?php echo baseUrl('home/js/home-blog.js?v=1.0.3'); ?>"></script>
<script defer src="<?php echo baseUrl('home/js/home-talent.js?v=1.0.3'); ?>"></script>
<?php if ($usePublicTemplate): ?>
<script>
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
});

document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams(window.location.search);
  if (params.get('login') !== '1') return;
  if (typeof openModal === 'function') {
    openModal('login');
  }
});
</script>
<?php endif; ?>
<script>
(function loadZohoWhenIdle() {
  function inject() {
    if (document.getElementById('zsiqscript')) return;
    window.$zoho = window.$zoho || {};
    $zoho.salesiq = $zoho.salesiq || { ready: function () {} };
    var s = document.createElement('script');
    s.id = 'zsiqscript';
    s.src = 'https://salesiq.zohopublic.com/widget?wc=siq3522b6c8efa1866fa919f61c10976b22744d3361ea908b3985ef2a2bb0af56e8';
    s.defer = true;
    document.body.appendChild(s);
  }
  if ('requestIdleCallback' in window) {
    requestIdleCallback(inject, { timeout: 4000 });
  } else {
    window.addEventListener('load', function () { setTimeout(inject, 1500); });
  }
})();
</script>
</body>
</html>
