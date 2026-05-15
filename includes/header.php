<?php
/**
 * Common Header
 */
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RemoWorkers - Connect with top remote talent worldwide">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo baseUrl('assets/css/style.css'); ?>">
    
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?php echo baseUrl($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="main-navbar">
        <div class="container">
            <a href="<?php echo baseUrl(); ?>" class="navbar-brand">
                <span class="brand-icon"><i class="fas fa-globe"></i></span>
                <span class="brand-text"><?php echo APP_NAME; ?></span>
            </a>
            
            <button class="navbar-toggle" id="navbar-toggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            
            <div class="navbar-menu" id="navbar-menu">
                <a href="<?php echo baseUrl(); ?>" class="nav-link <?php echo $section === '' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="<?php echo baseUrl('client'); ?>" class="nav-link <?php echo $section === 'client' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i> Hire Talent
                </a>
                <a href="<?php echo baseUrl('remoworkers-dashboard'); ?>" class="nav-link <?php echo $section === 'remoworkers-dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-laptop-code"></i> Find Work
                </a>
            </div>
        </div>
    </nav>
