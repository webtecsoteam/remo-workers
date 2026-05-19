<?php if(!function_exists('baseUrl')) { require_once __DIR__ . '/../../includes/config.php'; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Remoworkers – Freelancer Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://js.paystack.co/v1/inline.js"></script>
<link rel="stylesheet" href="<?php echo baseUrl("freelancer/css/style.css?v=" . time()); ?>">
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
  }
}
</script>
</head>
<body>
