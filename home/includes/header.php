<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Remoworkers – Where Great Work Gets Done</title>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo baseUrl("home/css/style.css?v=1.0.1"); ?>">
<link rel="icon" type="image/png" href="<?php echo baseUrl("favicon.png?v=1.0.0"); ?>">
<script>const APP_URL = '<?php echo baseUrl(); ?>';</script>
</head>
<body>
<!-- TOAST -->
<div class="notif-toast" id="toast"><div class="nt-ico" style="background:#e8f5e3">🎉</div><div class="nt-text"><strong id="toast-title">Welcome!</strong><span id="toast-msg">Discover great talent</span></div></div>
<!-- MODAL -->
<div class="overlay" id="overlay" onclick="closeModal(event)"><div class="modal" id="modal"><div class="modal-head"><h2 id="modal-title">Details</h2><button class="modal-close" onclick="closeModal()">✕</button></div><div class="modal-body" id="modal-body"></div></div></div>
<!-- TOPBAR -->
<div class="topbar" id="topbar"><span>🚀 <strong>New:</strong> AI-powered talent matching — find the right freelancer in minutes</span><a onclick="openModal('ai-matching')">Try it free →</a><span class="topbar-close" onclick="this.parentElement.remove()">×</span></div>
<!-- NAV -->
<nav id="nav">
  <a class="logo" href="<?php echo baseUrl(); ?>"><span class="logo-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M6 20c0-4 2.7-7 6-7s6 3 6 7"/><path d="M19 8c1.5.8 2.5 2.4 2.5 4.2 0 1.5-.6 2.9-1.6 3.8"/><path d="M5 8C3.5 8.8 2.5 10.4 2.5 12.2c0 1.5.6 2.9 1.6 3.8"/></svg></span><span class="logo-remo">Remo</span><span class="logo-workers">workers</span></a>
  <div class="nav-sep"></div>
  <ul class="nl">
    <li>
      <a href="<?php echo baseUrl('client'); ?>">Find Talent <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
      <div class="dd">
        <div class="dd-gl">Ways to hire</div>
        <a onclick="openModal('talent-marketplace')"><span class="dd-ico">🔍</span><span class="dd-t"><strong>Talent Marketplace</strong><span>Browse & hire freelancers</span></span></a>
        <a onclick="openModal('project-catalog-modal')"><span class="dd-ico">📋</span><span class="dd-t"><strong>Project Catalog</strong><span>Pre-scoped project packages</span></span></a>
        <a onclick="openModal('talent-scout')"><span class="dd-ico">🎯</span><span class="dd-t"><strong>Talent Scout</strong><span>We source talent for you</span></span></a>
        <div class="dd-sep"></div>
        <div class="dd-gl">Enterprise</div>
        <a onclick="openModal('enterprise')"><span class="dd-ico">🏢</span><span class="dd-t"><strong>Business Solutions</strong><span>Managed talent at scale</span></span></a>
        <a onclick="openModal('ai-matching')"><span class="dd-ico">🤖</span><span class="dd-t"><strong>AI Matching <span class="nbadge">NEW</span></strong><span>Smart recommendations</span></span></a>
      </div>
    </li>
    <li>
      <a href="<?php echo baseUrl('remoworkers-dashboard'); ?>">Find Work <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
      <div class="dd">
        <div class="dd-gl">For freelancers</div>
        <a onclick="openModal('browse-jobs')"><span class="dd-ico">💼</span><span class="dd-t"><strong>Browse Jobs</strong><span>Thousands of open projects</span></span></a>
        <a onclick="openModal('sell-services')"><span class="dd-ico">📦</span><span class="dd-t"><strong>Sell Services</strong><span>Create your own packages</span></span></a>
        <a onclick="openModal('connects')"><span class="dd-ico">🔗</span><span class="dd-t"><strong>Connects</strong><span>Apply with intent tokens</span></span></a>
        <a onclick="openModal('blog-1')"><span class="dd-ico">📈</span><span class="dd-t"><strong>Career Resources</strong><span>Guides & certifications</span></span></a>
        <a onclick="openModal('certifications')"><span class="dd-ico">🏅</span><span class="dd-t"><strong>Skill Assessments</strong><span>Earn verified badges</span></span></a>
      </div>
    </li>
    <li>
      <a onclick="toggleDd(this)">Why Remoworkers <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></a>
      <div class="dd">
        <div class="dd-gl">Platform</div>
        <a onclick="openModal('trust-safety')"><span class="dd-ico">🛡️</span><span class="dd-t"><strong>Trust & Safety</strong><span>Payments & dispute protection</span></span></a>
        <a onclick="openModal('uma-scout')"><span class="dd-ico">✨</span><span class="dd-t"><strong>Uma™ AI Agent</strong><span>AI-powered hiring & work</span></span></a>
        <a onclick="openModal('video-meetings')"><span class="dd-ico">📹</span><span class="dd-t"><strong>Video Meetings</strong><span>Built-in calls & contracts</span></span></a>
        <a onclick="openModal('integrations')"><span class="dd-ico">🔗</span><span class="dd-t"><strong>Integrations</strong><span>Slack, Jira, GitHub & more</span></span></a>
        <div class="dd-sep"></div>
        <div class="dd-gl">Resources</div>
        <a onclick="openModal('blog-all')"><span class="dd-ico">📚</span><span class="dd-t"><strong>Blog & Resources</strong><span>Guides, tips & insights</span></span></a>
        <a onclick="openModal('help-center')"><span class="dd-ico">❓</span><span class="dd-t"><strong>Help Center</strong><span>24/7 support team</span></span></a>
      </div>
    </li>
    <li><a onclick="openModal('enterprise')">Enterprise <span class="nbadge">Pro</span></a></li>
    <li><a onclick="openModal('pricing')">Pricing</a></li>
  </ul>
  <div class="na">
    <?php 
    require_once __DIR__ . '/../../includes/classes/Auth.php';
    $user = Auth::user();
    if ($user): 
    ?>
        <div class="user-pill">
            <div class="user-av">
                <?php echo substr($user['name'], 0, 1); ?>
            </div>
            <div class="user-name">
                <?php echo $user['name']; ?>
            </div>
        </div>
        <?php if ($user['role'] === 'client'): ?>
            <a class="btn btn-dark dash-btn" href="<?php echo baseUrl('client'); ?>">Dashboard</a>
        <?php else: ?>
            <a class="btn btn-dark dash-btn" href="<?php echo baseUrl('remoworkers-dashboard'); ?>">Dashboard</a>
        <?php endif; ?>
        <a class="btn btn-ghost logout-btn" href="<?php echo baseUrl('logout'); ?>">Log Out</a>
    <?php else: ?>
        <a class="btn btn-ghost login-btn" onclick="openModal('login')">Log In</a>
        <a class="btn btn-outline signup-btn" onclick="openModal('signup')">Sign Up Free</a>
        <a class="btn btn-dark post-btn" onclick="openModal('login')">Post a Job</a>
    <?php endif; ?>
    <button class="menu-toggle" id="menuToggle" onclick="toggleMobileMenu()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
</nav>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mm-head">
        <a class="logo" href="<?php echo baseUrl(); ?>"><span class="logo-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M6 20c0-4 2.7-7 6-7s6 3 6 7"/><path d="M19 8c1.5.8 2.5 2.4 2.5 4.2 0 1.5-.6 2.9-1.6 3.8"/><path d="M5 8C3.5 8.8 2.5 10.4 2.5 12.2c0 1.5.6 2.9 1.6 3.8"/></svg></span><span class="logo-remo">Remo</span><span class="logo-workers">workers</span></a>
        <button class="mm-close" onclick="toggleMobileMenu()">✕</button>
    </div>
    <div class="mm-body">
        <div class="mm-section">
            <h4>For Clients</h4>
            <a onclick="openModal('talent-marketplace');toggleMobileMenu()">Talent Marketplace</a>
            <a onclick="openModal('project-catalog-modal');toggleMobileMenu()">Project Catalog</a>
            <a onclick="openModal('talent-scout');toggleMobileMenu()">Talent Scout</a>
            <a onclick="openModal('enterprise');toggleMobileMenu()">Enterprise</a>
        </div>
        <div class="mm-section">
            <h4>For Freelancers</h4>
            <a onclick="openModal('browse-jobs');toggleMobileMenu()">Browse Jobs</a>
            <a onclick="openModal('sell-services');toggleMobileMenu()">Sell Services</a>
            <a onclick="openModal('certifications');toggleMobileMenu()">Skill Assessments</a>
        </div>
        <div class="mm-section">
            <h4>Company</h4>
            <a onclick="openModal('trust-safety');toggleMobileMenu()">Trust & Safety</a>
            <a onclick="openModal('blog-all');toggleMobileMenu()">Blog & Resources</a>
            <a onclick="openModal('pricing');toggleMobileMenu()">Pricing</a>
        </div>
        <div class="mm-actions">
            <?php if ($user): ?>
                <a class="btn btn-green btn-full" href="<?php echo $user['role'] === 'client' ? baseUrl('client') : baseUrl('remoworkers-dashboard'); ?>">Dashboard</a>
                <a class="btn btn-outline btn-full" href="<?php echo baseUrl('logout'); ?>" style="margin-top:10px">Log Out</a>
            <?php else: ?>
                <a class="btn btn-green btn-full" onclick="openModal('signup');toggleMobileMenu()">Sign Up Free</a>
                <a class="btn btn-outline btn-full" onclick="openModal('login');toggleMobileMenu()" style="margin-top:10px">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
    document.body.style.overflow = document.getElementById('mobileMenu').classList.contains('open') ? 'hidden' : '';
}
</script>