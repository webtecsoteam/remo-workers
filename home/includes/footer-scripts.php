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
  function openZohoChat() {
    if (!window.$zoho || !$zoho.salesiq) return false;
    try {
      if ($zoho.salesiq.chat && typeof $zoho.salesiq.chat.start === 'function') {
        $zoho.salesiq.chat.start();
        toggleCloseChatButton(true);
        return true;
      }
      if ($zoho.salesiq.floatwindow && typeof $zoho.salesiq.floatwindow.visible === 'function') {
        $zoho.salesiq.floatwindow.visible('show');
        toggleCloseChatButton(true);
        return true;
      }
    } catch (err) {}
    return false;
  }

  function closeZohoChat() {
    if (!window.$zoho || !$zoho.salesiq) return false;
    try {
      if ($zoho.salesiq.floatwindow && typeof $zoho.salesiq.floatwindow.visible === 'function') {
        $zoho.salesiq.floatwindow.visible('hide');
        toggleCloseChatButton(false);
        return true;
      }
      if ($zoho.salesiq.chat && typeof $zoho.salesiq.chat.end === 'function') {
        $zoho.salesiq.chat.end();
        toggleCloseChatButton(false);
        return true;
      }
    } catch (err) {}
    toggleCloseChatButton(false);
    return false;
  }

  function hideZohoFloatButton() {
    if (!window.$zoho || !$zoho.salesiq) return;
    try {
      if ($zoho.salesiq.floatbutton && typeof $zoho.salesiq.floatbutton.visible === 'function') {
        $zoho.salesiq.floatbutton.visible('hide');
      }
    } catch (err) {}
  }

  function injectHideFloatCss() {
    if (document.getElementById('zoho-float-hide-style')) return;
    var st = document.createElement('style');
    st.id = 'zoho-float-hide-style';
    st.textContent = '#zsiq_float,#zsiq_agtpic,.zsiq_floatmain{display:none!important;}#live-chat-close-btn{position:fixed;right:16px;bottom:16px;z-index:2147483000;background:#111827;color:#fff;border:0;border-radius:999px;padding:10px 14px;font-size:13px;line-height:1;cursor:pointer;display:none;box-shadow:0 6px 18px rgba(0,0,0,.24);}#live-chat-close-btn:hover{background:#000;}';
    document.head.appendChild(st);
  }

  function ensureCloseChatButton() {
    var btn = document.getElementById('live-chat-close-btn');
    if (btn) return btn;
    btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'live-chat-close-btn';
    btn.setAttribute('aria-label', 'Close live chat');
    btn.textContent = 'Close Chat';
    btn.addEventListener('click', function () {
      closeZohoChat();
    });
    document.body.appendChild(btn);
    return btn;
  }

  function toggleCloseChatButton(show) {
    var btn = ensureCloseChatButton();
    btn.style.display = show ? 'inline-flex' : 'none';
  }

  function bindLiveChatTriggers() {
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-live-chat-trigger="1"], .js-live-chat-trigger');
      if (!trigger) return;
      e.preventDefault();
      if (!openZohoChat()) {
        inject();
        var attempts = 0;
        var timer = setInterval(function () {
          attempts += 1;
          if (openZohoChat() || attempts >= 20) {
            clearInterval(timer);
          }
        }, 300);
      }
    });
  }

  function inject() {
    if (document.getElementById('zsiqscript')) return;
    injectHideFloatCss();
    window.$zoho = window.$zoho || {};
    $zoho.salesiq = $zoho.salesiq || {};
    $zoho.salesiq.ready = function () {
      hideZohoFloatButton();
    };
    var s = document.createElement('script');
    s.id = 'zsiqscript';
    s.src = 'https://salesiq.zohopublic.com/widget?wc=siq3522b6c8efa1866fa919f61c10976b22744d3361ea908b3985ef2a2bb0af56e8';
    s.defer = true;
    document.body.appendChild(s);
  }
  window.openZohoLiveChat = openZohoChat;
  window.closeZohoLiveChat = closeZohoChat;
  injectHideFloatCss();
  ensureCloseChatButton();
  bindLiveChatTriggers();
  if ('requestIdleCallback' in window) {
    requestIdleCallback(inject, { timeout: 4000 });
  } else {
    window.addEventListener('load', function () { setTimeout(inject, 1500); });
  }
})();
</script>
</body>
</html>
