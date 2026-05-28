<?php
/**
 * Google Analytics (GA4) — output only when enabled in admin platform settings.
 */
if (!function_exists('getPlatformSettingString')) {
    require_once __DIR__ . '/config.php';
}

$gaEnabled = getPlatformSettingString('google_analytics_enabled', '0') === '1';
$gaId = trim(getPlatformSettingString('google_analytics_id', ''));

if (!$gaEnabled || $gaId === '') {
    return;
}

if (!preg_match('/^G-[A-Z0-9]{4,}$/i', $gaId)) {
    return;
}

$gaIdEsc = htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8');
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaIdEsc; ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo $gaIdEsc; ?>');
</script>
