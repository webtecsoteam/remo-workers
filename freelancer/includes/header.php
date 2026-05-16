<?php if(!function_exists('baseUrl')) { require_once __DIR__ . '/../../includes/config.php'; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Remoworkers – Freelancer Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://js.paystack.co/v1/inline.js"></script>
<link rel="stylesheet" href="<?php echo baseUrl("freelancer/css/style.css"); ?>">
<link rel="icon" type="image/png" href="<?php echo baseUrl("favicon.png?v=1.0.0"); ?>">
<script>const BASE_URL = '<?php echo baseUrl(); ?>';</script>
<script>
window.showPage = function(id) {
  if (!id) id = 'home';
  document.querySelectorAll('.page').forEach(function(p) { p.classList.remove('active'); });
  var pg = document.getElementById('page-' + id);
  if (pg) {
    pg.classList.add('active');
    window.scrollTo(0, 0);
    if (window.history.pushState) history.pushState(null, null, '#' + id);
  }
  document.querySelectorAll('.sb-item').forEach(function(i) { i.classList.remove('active'); });
  var navEl = document.getElementById('nav-' + id);
  if (navEl) navEl.classList.add('active');
  document.querySelectorAll('.mob-nav-item').forEach(function(i) { i.classList.remove('active'); });
  var mobItem = document.querySelector('.mob-nav-item[onclick*="\'' + id + '\'"]');
  if (mobItem) mobItem.classList.add('active');
  var titles = {
    home: 'Dashboard', 'find-work': 'Find Work', proposals: 'My Proposals',
    contracts: 'My Contracts', messages: 'Messages', earnings: 'Earnings',
    catalog: 'My Services', profile: 'My Profile', reports: 'Payment Reports',
    verification: 'ID Verification'
  };
  var titleEl = document.getElementById('page-title');
  if (titleEl) titleEl.textContent = titles[id] || id;
  var sb = document.getElementById('main-sidebar');
  var ov = document.getElementById('mob-overlay');
  if (sb) sb.classList.remove('mob-open');
  if (ov) ov.classList.remove('open');
};
</script>
</head>
<body>
