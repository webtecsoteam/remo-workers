<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
$user = Auth::user();
if(!$user) { redirect(baseUrl()); }

$db = getDB();
$vDoc = $db->prepare("SELECT status FROM user_documents WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$vDoc->execute([$user['id']]);
$vStatus = $vDoc->fetchColumn() ?: 'unverified';
if(isset($user['is_verified']) && $user['is_verified']) $vStatus = 'approved';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upwork – Client Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Neue+Haas+Grotesk+Display+Pro:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo baseUrl("client/css/style.css"); ?>">
<script>const BASE_URL = '<?php echo baseUrl(); ?>';</script>
</head>
<body>

<!-- TOAST -->
<div class="toast" id="toast"><strong id="t-title"></strong><span id="t-msg"></span></div>

<!-- MODAL OVERLAY -->
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="mh"><h2 id="mh-title">Detail</h2><div class="mclose" onclick="closeModal()">✕</div></div>
    <div class="mc" id="mc-body"></div>
  </div>
</div>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeMobSidebar()"></div>

<!-- MOBILE FAB -->
<button class="mob-fab" id="mob-fab" style="display:none" onclick="openModal('post-job')" aria-label="Post a job">+</button>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <a class="sb-logo" href="<?php echo baseUrl(); ?>"><div class="sb-wordmark">up<em>work</em></div></a>
  <div class="sb-user">
    <div class="sb-av">NX</div>
    <div>
      <div class="sb-name">NexaFlow Inc.</div>
      <div class="sb-role">Client Account</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <div class="sb-item active" onclick="showPage('home',this)"><span class="ico">🏠</span>Home</div>
    <div class="sb-item" onclick="showPage('jobs',this)"><span class="ico">📋</span>My Jobs<span class="sb-badge g">3</span></div>
    <div class="sb-item" onclick="showPage('proposals',this)"><span class="ico">📩</span>Proposals<span class="sb-badge">12</span></div>
    <div class="sb-item" onclick="showPage('contracts',this)"><span class="ico">🤝</span>Contracts</div>
    <div class="sb-item" onclick="showPage('talent',this)"><span class="ico">👥</span>Talent</div>
    <div class="sb-section">Tools</div>
    <div class="sb-item" onclick="showPage('messages',this)"><span class="ico">💬</span>Messages<span class="sb-badge">4</span></div>
    <div class="sb-item" onclick="showPage('payments',this)"><span class="ico">💳</span>Payments</div>
    <div class="sb-item" onclick="showPage('reports',this)"><span class="ico">📊</span>Reports</div>
    <div class="sb-item" onclick="showPage('verification',this)"><span class="ico">🪪</span>Identity Verification</div>
    <div class="sb-item" onclick="toast('Uma AI','AI work assistant analyzing your active projects...')"><span class="ico">✨</span>AI Assistant</div>
    <div class="sb-section">Account</div>
    <div class="sb-item" onclick="toast('Settings','Account settings opened')"><span class="ico">⚙️</span>Settings</div>
    <div class="sb-item" onclick="toast('Help Center','Loading support articles...')"><span class="ico">❓</span>Help & Support</div>
  </nav>
  <div class="sb-footer">
    <a onclick="toast('Upgrade','Opening Business Plus details')">⬆️ Upgrade to Business Plus</a>
    <a href="<?php echo baseUrl('logout'); ?>">🚪 Sign Out</a>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <button class="mob-menu-btn" onclick="openMobSidebar()" aria-label="Open menu">☰</button>
    <div class="tb-title" id="page-title">Home</div>
    <div class="tb-search">
      <span class="tb-s-ico">🔍</span>
      <input type="text" placeholder="Search jobs, freelancers, contracts…">
    </div>
    <div class="tb-actions">
      <div class="tb-ico-btn" onclick="toast('Notifications','You have 4 unread notifications')">🔔<div class="notif-dot"></div></div>
      <div class="tb-ico-btn" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">💬</div>
      <button class="btn btn-g btn-sm" onclick="openModal('post-job')">+ Post a Job</button>
      <div class="tb-av" onclick="toast('Profile','Opening account settings')">NX</div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- ══ HOME PAGE ══ -->
    <div class="page active" id="page-home">
      <div class="pg-header" style="margin-bottom:18px">
        <div>
          <div class="pg-title">Welcome back, NexaFlow 👋</div>
          <div class="pg-sub">You have 4 unread messages and 12 new proposals waiting.</div>
        </div>
        <button class="btn btn-g btn-lg" onclick="openModal('post-job')">+ Post a Job</button>
      </div>

      <!-- Stat Cards -->
      <div class="stat-row">
        <div class="stat-c" onclick="showPage('contracts',document.querySelector('[onclick*=contracts]'))">
          <div class="stat-label">Active Contracts<div class="stat-icon">🤝</div></div>
          <div class="stat-val">8</div>
          <div class="stat-sub up">↑ 2 new this month</div>
        </div>
        <div class="stat-c" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'))">
          <div class="stat-label">Open Proposals<div class="stat-icon">📩</div></div>
          <div class="stat-val">12</div>
          <div class="stat-sub">Across 3 job posts</div>
        </div>
        <div class="stat-c" onclick="showPage('payments',document.querySelector('[onclick*=payments]'))">
          <div class="stat-label">Total Spent (May)<div class="stat-icon">💳</div></div>
          <div class="stat-val">$4,820</div>
          <div class="stat-sub up">↑ 18% vs last month</div>
        </div>
        <div class="stat-c" onclick="toast('Satisfaction','Based on completed contract reviews')">
          <div class="stat-label">Avg Rating Given<div class="stat-icon">⭐</div></div>
          <div class="stat-val">4.9</div>
          <div class="stat-sub">From 34 reviews given</div>
        </div>
      </div>

      <div class="g2">
        <!-- Active Contracts -->
        <div class="card">
          <div class="card-head">
            <h3>Active Contracts</h3>
            <button class="btn btn-w btn-sm" onclick="showPage('contracts',document.querySelector('[onclick*=contracts]'))">View all</button>
          </div>
          <div class="card-body" style="padding:0 20px">
            <div class="contract-row">
              <div class="av" style="background:#d1fae5;color:#065f46">AN</div>
              <div class="cr-info"><div class="cr-title">UI/UX Redesign — Dashboard</div><div class="cr-sub">Anika Nkosi · Hourly · Active</div></div>
              <div class="cr-amt">$90/hr<span>34.5 hrs logged</span></div>
            </div>
            <div class="contract-row">
              <div class="av" style="background:#dbeafe;color:#1e40af">JK</div>
              <div class="cr-info"><div class="cr-title">Backend API Development</div><div class="cr-sub">James Kowalski · Fixed · Milestone 2/3</div></div>
              <div class="cr-amt">$6,500<span>$4,200 released</span></div>
            </div>
            <div class="contract-row">
              <div class="av" style="background:#fef3c7;color:#92400e">LT</div>
              <div class="cr-info"><div class="cr-title">SEO Strategy & Content</div><div class="cr-sub">Lena Thornton · Hourly · Active</div></div>
              <div class="cr-amt">$65/hr<span>22.0 hrs logged</span></div>
            </div>
            <div class="contract-row">
              <div class="av" style="background:#ede9fe;color:#5b21b6">MP</div>
              <div class="cr-info"><div class="cr-title">AI Chatbot Integration</div><div class="cr-sub">Marcus Patel · Fixed · Milestone 1/2</div></div>
              <div class="cr-amt">$2,200<span>$1,100 released</span></div>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div class="card">
          <div class="card-head">
            <h3>Messages</h3>
            <button class="btn btn-w btn-sm" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">View all</button>
          </div>
          <div class="card-body" style="padding:6px 12px">
            <div class="msg-item unread" onclick="openModal('msg-anika')">
              <div class="av" style="background:#d1fae5;color:#065f46">AN</div>
              <div class="msg-meta">
                <div class="msg-name">Anika Nkosi<span class="msg-time">10 min ago</span></div>
                <div class="msg-text">I've completed the first set of screens — ready for your review!</div>
              </div>
              <div class="msg-dot"></div>
            </div>
            <div class="msg-item unread" onclick="openModal('msg-james')">
              <div class="av" style="background:#dbeafe;color:#1e40af">JK</div>
              <div class="msg-meta">
                <div class="msg-name">James Kowalski<span class="msg-time">2 hrs ago</span></div>
                <div class="msg-text">Milestone 2 is done — all tests passing. Please review and release.</div>
              </div>
              <div class="msg-dot"></div>
            </div>
            <div class="msg-item" onclick="toast('Message','Opening conversation with Lena Thornton')">
              <div class="av" style="background:#fef3c7;color:#92400e">LT</div>
              <div class="msg-meta">
                <div class="msg-name">Lena Thornton<span class="msg-time">Yesterday</span></div>
                <div class="msg-text">Here's the Q2 keyword strategy — let me know your thoughts</div>
              </div>
            </div>
            <div class="msg-item" onclick="toast('Support','Opening Upwork support message')">
              <div class="av" style="background:var(--uw-green-light);color:var(--uw-green)">UW</div>
              <div class="msg-meta">
                <div class="msg-name">Upwork Support<span class="msg-time">2 days ago</span></div>
                <div class="msg-text">Your dispute has been resolved in your favour.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Open Jobs -->
      <div class="sec-hd" style="margin-top:8px">
        <div class="sec-h">Open Job Posts</div>
        <button class="sec-link" onclick="showPage('jobs',document.querySelector('[onclick*=jobs]'))">View all jobs →</button>
      </div>

      <div class="job-card" onclick="openModal('job-1')">
        <div class="job-card-ico">🖥️</div>
        <div style="flex:1">
          <h4>Senior React Developer — Analytics Dashboard</h4>
          <p>Build interactive data visualizations, real-time WebSocket charts, and a filterable data table for our internal analytics platform.</p>
          <div class="job-meta">
            <span class="jm g">$8,000–$12,000</span>
            <span class="jm">Fixed price</span>
            <span class="jm">Remote</span>
            <span class="jm">8 proposals</span>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <span class="badge b-green">Open</span>
          <div style="font-size:11px;color:var(--uw-gray);margin-top:6px">Posted 2 days ago</div>
        </div>
      </div>

      <div class="job-card" onclick="openModal('job-2')">
        <div class="job-card-ico">🎨</div>
        <div style="flex:1">
          <h4>Brand Designer — Full Identity Redesign</h4>
          <p>Seeking an experienced brand designer for logo, color system, typography, and brand guidelines for our 2026 rebrand.</p>
          <div class="job-meta">
            <span class="jm g">$3,500–$6,000</span>
            <span class="jm">Fixed price</span>
            <span class="jm">Remote</span>
            <span class="jm">4 proposals</span>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <span class="badge b-green">Open</span>
          <div style="font-size:11px;color:var(--uw-gray);margin-top:6px">Posted 5 days ago</div>
        </div>
      </div>

      <!-- Spend Chart -->
      <div class="card" style="margin-top:6px">
        <div class="card-head">
          <h3>Monthly Spend</h3>
          <button class="sec-link" onclick="showPage('reports',document.querySelector('[onclick*=reports]'))">Full report →</button>
        </div>
        <div class="card-body">
          <div style="font-size:24px;font-weight:700;color:var(--uw-black);margin-bottom:6px">
            $4,820 <span style="font-size:13px;font-weight:400;color:var(--uw-gray)">May 2026</span>
          </div>
          <div class="chart-area">
            <div class="chart-bars">
              <div class="chart-bar" style="height:45%" onclick="toast('November','$2,100 spent')"></div>
              <div class="chart-bar" style="height:60%" onclick="toast('December','$2,800 spent')"></div>
              <div class="chart-bar" style="height:52%" onclick="toast('January','$2,400 spent')"></div>
              <div class="chart-bar" style="height:70%" onclick="toast('February','$3,250 spent')"></div>
              <div class="chart-bar" style="height:75%" onclick="toast('March','$3,500 spent')"></div>
              <div class="chart-bar" style="height:88%" onclick="toast('April','$4,100 spent')"></div>
              <div class="chart-bar active" style="height:100%" onclick="toast('May (current)','$4,820 spent so far')"></div>
            </div>
            <div class="chart-labels">
              <div class="chart-lbl">Nov</div><div class="chart-lbl">Dec</div><div class="chart-lbl">Jan</div>
              <div class="chart-lbl">Feb</div><div class="chart-lbl">Mar</div><div class="chart-lbl">Apr</div>
              <div class="chart-lbl">May</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ JOBS PAGE ══ -->
    <div class="page" id="page-jobs">
      <div class="pg-header">
        <div>
          <div class="pg-title">My Job Posts</div>
          <div class="pg-sub">3 open · 1 paused · 12 closed</div>
        </div>
        <button class="btn btn-g btn-lg" onclick="openModal('post-job')">+ Post a New Job</button>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">All</div>
        <div class="tab" onclick="setTab(this)">Open</div>
        <div class="tab" onclick="setTab(this)">Paused</div>
        <div class="tab" onclick="setTab(this)">Closed</div>
      </div>
      <div class="card" style="margin-bottom:0;overflow:auto">
        <table class="tbl">
          <thead><tr><th>Job Title</th><th>Type</th><th>Budget</th><th>Proposals</th><th>Posted</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <tr><td class="cl" onclick="openModal('job-1')">Senior React Developer — Analytics Dashboard</td><td>Fixed</td><td>$8,000–$12,000</td><td><strong style="color:var(--uw-green)">8</strong></td><td>May 12</td><td><span class="badge b-green">Open</span></td><td><button class="btn btn-w btn-sm" onclick="openModal('job-1')">View</button></td></tr>
            <tr><td class="cl" onclick="openModal('job-2')">Brand Designer — Full Identity Redesign</td><td>Fixed</td><td>$3,500–$6,000</td><td><strong style="color:var(--uw-green)">4</strong></td><td>May 9</td><td><span class="badge b-green">Open</span></td><td><button class="btn btn-w btn-sm" onclick="openModal('job-2')">View</button></td></tr>
            <tr><td class="cl" onclick="toast('Job opened','SEO & Content Strategist')">SEO & Content Strategist (Ongoing)</td><td>Hourly</td><td>$55–$80/hr</td><td><strong style="color:var(--uw-green)">12</strong></td><td>May 7</td><td><span class="badge b-green">Open</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Job','Viewing SEO Strategist post')">View</button></td></tr>
            <tr><td class="cl" onclick="toast('Job','Viewing paused job')">AI Chatbot — LLM Integration</td><td>Fixed</td><td>$8,000–$15,000</td><td>7</td><td>Apr 28</td><td><span class="badge b-yellow">Paused</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Job resumed','AI Chatbot job is now open again')">Resume</button></td></tr>
            <tr><td>UI/UX Freelancer for Mobile App</td><td>Fixed</td><td>$5,000</td><td>22</td><td>Mar 15</td><td><span class="badge b-gray">Closed</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Job reposted','New copy created as draft')">Repost</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ PROPOSALS PAGE ══ -->
    <div class="page" id="page-proposals">
      <div class="pg-header">
        <div>
          <div class="pg-title">Proposals</div>
          <div class="pg-sub">12 new proposals across 3 jobs</div>
        </div>
        <select style="padding:8px 14px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:13px;font-family:inherit;color:var(--uw-dark);background:white;outline:none" onchange="toast('Filter','Showing proposals for selected job')">
          <option>All Jobs</option><option>React Developer</option><option>Brand Designer</option><option>SEO Strategist</option>
        </select>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">New (12)</div>
        <div class="tab" onclick="setTab(this)">Shortlisted (3)</div>
        <div class="tab" onclick="setTab(this)">Archived</div>
      </div>

      <div class="prop-card" onclick="openModal('prop-anika')">
        <div class="prop-top">
          <div class="av" style="background:#d1fae5;color:#065f46;width:42px;height:42px;font-size:13px">AN</div>
          <div class="prop-info">
            <h4>Anika Nkosi <span class="uw-level-badge lvl-top-rated-plus">✦ Top Rated Plus</span></h4>
            <p>UI/UX Designer · 127 reviews · ★ 5.0 · $90/hr · Berlin, Germany</p>
          </div>
          <div style="margin-left:auto;text-align:right;flex-shrink:0">
            <div class="prop-rate">$5,800<span> fixed</span></div>
            <div style="font-size:11px;color:var(--uw-gray);margin-top:2px">For: Brand Designer</div>
          </div>
        </div>
        <div class="prop-body">"I specialize in brand identity systems and have redesigned 40+ brands across fintech and SaaS. I'd love to bring your 2026 brand vision to life with a comprehensive system that scales across every touchpoint."</div>
        <div class="prop-foot">
          <div style="font-size:11.5px;color:var(--uw-gray)">Submitted 1 hr ago · Est. delivery: 7 days</div>
          <div class="prop-actions">
            <button class="btn btn-w btn-sm" onclick="event.stopPropagation();toast('Archived','Proposal archived')">Archive</button>
            <button class="btn btn-o btn-sm" onclick="event.stopPropagation();toast('Shortlisted!','Anika added to your shortlist')">Shortlist</button>
            <button class="btn btn-w btn-sm" onclick="event.stopPropagation();openModal('dm-anika')">💬 Message</button>
            <button class="btn btn-g btn-sm" onclick="event.stopPropagation();openModal('hire-anika')">Hire →</button>
          </div>
        </div>
      </div>

      <div class="prop-card" onclick="openModal('prop-james')">
        <div class="prop-top">
          <div class="av" style="background:#dbeafe;color:#1e40af;width:42px;height:42px;font-size:13px">JK</div>
          <div class="prop-info">
            <h4>James Kowalski <span class="uw-level-badge lvl-expert-vetted">★ Expert-Vetted</span></h4>
            <p>Full Stack Engineer · 89 reviews · ★ 4.9 · $130/hr · Toronto, Canada</p>
          </div>
          <div style="margin-left:auto;text-align:right;flex-shrink:0">
            <div class="prop-rate">$130<span>/hr</span></div>
            <div style="font-size:11px;color:var(--uw-gray);margin-top:2px">For: React Developer</div>
          </div>
        </div>
        <div class="prop-body">"I've built 6 real-time analytics dashboards in the last 18 months, including one for a 50,000-user SaaS platform. I can start immediately and deliver milestone 1 within 5 business days."</div>
        <div class="prop-foot">
          <div style="font-size:11.5px;color:var(--uw-gray)">Submitted 3 hrs ago · Est. delivery: 14 days</div>
          <div class="prop-actions">
            <button class="btn btn-w btn-sm" onclick="event.stopPropagation();toast('Archived','Proposal archived')">Archive</button>
            <button class="btn btn-o btn-sm" onclick="event.stopPropagation();toast('Shortlisted!','James added to your shortlist')">Shortlist</button>
            <button class="btn btn-w btn-sm" onclick="event.stopPropagation();openModal('dm-james')">💬 Message</button>
            <button class="btn btn-g btn-sm" onclick="event.stopPropagation();openModal('hire-james')">Hire →</button>
          </div>
        </div>
      </div>

      <div class="prop-card" onclick="toast('Proposal','Opening Sofia Reyes proposal')">
        <div class="prop-top">
          <div class="av" style="background:#fef3c7;color:#92400e;width:42px;height:42px;font-size:13px">SR</div>
          <div class="prop-info">
            <h4>Sofia Reyes <span class="uw-level-badge lvl-rising">↑ Rising Talent</span></h4>
            <p>AI/ML Engineer · 22 reviews · ★ 4.7 · $85/hr · Mexico City</p>
          </div>
          <div style="margin-left:auto;text-align:right;flex-shrink:0">
            <div class="prop-rate">$10,500<span> fixed</span></div>
            <div style="font-size:11px;color:var(--uw-gray);margin-top:2px">For: React Developer</div>
          </div>
        </div>
        <div class="prop-body">"I have deep experience in LLM integrations and RAG architectures. For your analytics project, I'd combine React on the frontend with a Python FastAPI backend to power real-time AI insights."</div>
        <div class="prop-foot">
          <div style="font-size:11.5px;color:var(--uw-gray)">Submitted 5 hrs ago · Est. delivery: 12 days</div>
          <div class="prop-actions">
            <button class="btn btn-w btn-sm" onclick="event.stopPropagation();toast('Archived','Proposal archived')">Archive</button>
            <button class="btn btn-o btn-sm" onclick="event.stopPropagation();toast('Shortlisted!','Sofia added to your shortlist')">Shortlist</button>
            <button class="btn btn-w btn-sm" onclick="event.stopPropagation();openModal('dm-sofia')">💬 Message</button>
            <button class="btn btn-g btn-sm" onclick="event.stopPropagation();toast('Hire flow','Opening contract setup for Sofia')">Hire →</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ CONTRACTS PAGE ══ -->
    <div class="page" id="page-contracts">
      <div class="pg-header">
        <div>
          <div class="pg-title">Contracts</div>
          <div class="pg-sub">8 active contracts</div>
        </div>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">Active (8)</div>
        <div class="tab" onclick="setTab(this)">Completed (24)</div>
        <div class="tab" onclick="setTab(this)">Paused</div>
      </div>
      <div class="card" style="margin-bottom:0;overflow:auto">
        <table class="tbl">
          <thead><tr><th>Freelancer</th><th>Role</th><th>Type</th><th>Amount</th><th>Progress</th><th>Started</th><th>Actions</th></tr></thead>
          <tbody>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#d1fae5;color:#065f46">AN</div><div><div style="font-weight:600">Anika Nkosi</div><div style="font-size:11px;color:var(--uw-gray)">UI/UX Designer</div></div></div></td>
              <td>Dashboard Redesign</td><td><span class="badge b-blue">Hourly</span></td>
              <td><div style="font-weight:700">$90/hr</div><div style="font-size:11px;color:var(--uw-gray)">34.5 hrs logged</div></td>
              <td><div style="font-size:11.5px;color:var(--uw-gray);margin-bottom:4px">Active · No end date</div><div class="progress-bar"><div class="progress-fill" style="width:60%"></div></div></td>
              <td>Apr 28</td>
              <td><button class="btn btn-w btn-sm" onclick="openModal('contract-anika')">Details</button></td>
            </tr>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#dbeafe;color:#1e40af">JK</div><div><div style="font-weight:600">James Kowalski</div><div style="font-size:11px;color:var(--uw-gray)">Full Stack Engineer</div></div></div></td>
              <td>Backend API Dev</td><td><span class="badge b-purple">Fixed</span></td>
              <td><div style="font-weight:700">$6,500</div><div style="font-size:11px;color:var(--uw-gray)">$4,200 released</div></td>
              <td><div style="font-size:11.5px;color:var(--uw-gray);margin-bottom:4px">Milestone 2 of 3</div><div class="progress-bar"><div class="progress-fill" style="width:66%"></div></div></td>
              <td>May 1</td>
              <td style="display:flex;gap:6px;flex-wrap:wrap">
                <button class="btn btn-o btn-sm" onclick="openModal('fund-milestone-james')">Fund M3 $2,300</button>
                <button class="btn btn-g btn-sm" onclick="toast('Released ✓','$2,300 released to James')">Release $2,300</button>
              </td>
            </tr>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#fef3c7;color:#92400e">LT</div><div><div style="font-weight:600">Lena Thornton</div><div style="font-size:11px;color:var(--uw-gray)">SEO Strategist</div></div></div></td>
              <td>SEO & Content</td><td><span class="badge b-blue">Hourly</span></td>
              <td><div style="font-weight:700">$65/hr</div><div style="font-size:11px;color:var(--uw-gray)">22 hrs logged</div></td>
              <td><div style="font-size:11.5px;color:var(--uw-gray);margin-bottom:4px">Active · Ongoing</div><div class="progress-bar"><div class="progress-fill" style="width:45%"></div></div></td>
              <td>Apr 15</td>
              <td><button class="btn btn-w btn-sm" onclick="openModal('contract-lena')">Details</button></td>
            </tr>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#ede9fe;color:#5b21b6">MP</div><div><div style="font-weight:600">Marcus Patel</div><div style="font-size:11px;color:var(--uw-gray)">AI/ML Engineer</div></div></div></td>
              <td>AI Chatbot Build</td><td><span class="badge b-purple">Fixed</span></td>
              <td><div style="font-weight:700">$2,200</div><div style="font-size:11px;color:var(--uw-gray)">$1,100 released</div></td>
              <td><div style="font-size:11.5px;color:var(--uw-gray);margin-bottom:4px">Milestone 1 of 2</div><div class="progress-bar"><div class="progress-fill" style="width:50%"></div></div></td>
              <td>May 5</td>
              <td style="display:flex;gap:6px;flex-wrap:wrap">
                <button class="btn btn-o btn-sm" onclick="openModal('fund-milestone-marcus')">Fund M2 $1,100</button>
                <button class="btn btn-g btn-sm" onclick="toast('Released ✓','$1,100 released to Marcus')">Release $1,100</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ TALENT PAGE ══ -->
    <div class="page" id="page-talent">
      <div class="pg-header">
        <div>
          <div class="pg-title">Talent</div>
          <div class="pg-sub">Freelancers you've worked with or saved</div>
        </div>
        <button class="btn btn-g" onclick="toast('Find Talent','Opening talent search...')">🔍 Find New Talent</button>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">All Talent (12)</div>
        <div class="tab" onclick="setTab(this)">Saved (5)</div>
        <div class="tab" onclick="setTab(this)">Previously Hired (7)</div>
      </div>
      <div class="card" style="margin-bottom:0;overflow:auto">
        <table class="tbl">
          <thead><tr><th>Name</th><th>Skill</th><th>Rating</th><th>Rate</th><th>Status</th><th>Last Contract</th><th>Action</th></tr></thead>
          <tbody>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#d1fae5;color:#065f46">AN</div><div><div style="font-weight:600">Anika Nkosi</div><div style="font-size:11px;color:var(--uw-gray)">Berlin, Germany</div></div></div></td><td>UI/UX Design</td><td>★ 5.0 (127)</td><td>$90/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with Anika')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#dbeafe;color:#1e40af">JK</div><div><div style="font-weight:600">James Kowalski</div><div style="font-size:11px;color:var(--uw-gray)">Toronto, Canada</div></div></div></td><td>Full Stack Dev</td><td>★ 4.9 (89)</td><td>$130/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with James')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#fef3c7;color:#92400e">LT</div><div><div style="font-weight:600">Lena Thornton</div><div style="font-size:11px;color:var(--uw-gray)">London, UK</div></div></div></td><td>SEO / Marketing</td><td>★ 5.0 (203)</td><td>$65/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with Lena')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#ede9fe;color:#5b21b6">MP</div><div><div style="font-weight:600">Marcus Patel</div><div style="font-size:11px;color:var(--uw-gray)">Bangalore, India</div></div></div></td><td>AI / ML</td><td>★ 4.8 (41)</td><td>$110/hr</td><td><span class="badge b-green">Hired</span></td><td>Active now</td><td><button class="btn btn-w btn-sm" onclick="toast('Message sent','Chat opened with Marcus')">Message</button></td></tr>
            <tr><td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:#fce7f3;color:#9d174d">ZM</div><div><div style="font-weight:600">Zara Mehta</div><div style="font-size:11px;color:var(--uw-gray)">Dubai, UAE</div></div></div></td><td>Copywriting</td><td>★ 4.9 (74)</td><td>$55/hr</td><td><span class="badge b-gray">Saved</span></td><td>Never</td><td><button class="btn btn-g btn-sm" onclick="toast('Invite sent','Zara invited to your job post')">Invite</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ MESSAGES PAGE ══ -->
    <div class="page" id="page-messages">
      <div class="pg-header">
        <div class="pg-title">Messages</div>
      </div>
      <div style="background:white;border:1.5px solid var(--uw-border);border-radius:var(--radius);display:flex;min-height:480px;overflow:hidden">
        <!-- Sidebar list -->
        <div style="width:270px;border-right:1.5px solid var(--uw-border);flex-shrink:0">
          <div style="padding:12px 14px;border-bottom:1px solid var(--uw-border)">
            <input style="width:100%;padding:8px 12px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:12.5px;font-family:inherit;outline:none;background:var(--uw-bg)" placeholder="Search messages…">
          </div>
          <div style="padding:6px 0">
            <div class="msg-item unread" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening Anika Nkosi conversation')">
              <div class="av" style="background:#d1fae5;color:#065f46">AN</div>
              <div class="msg-meta"><div class="msg-name">Anika Nkosi<span class="msg-time">10m</span></div><div class="msg-text">Screens are ready for review!</div></div>
              <div class="msg-dot"></div>
            </div>
            <div class="msg-item unread" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening James Kowalski conversation')">
              <div class="av" style="background:#dbeafe;color:#1e40af">JK</div>
              <div class="msg-meta"><div class="msg-name">James Kowalski<span class="msg-time">2h</span></div><div class="msg-text">All tests passing — please review</div></div>
              <div class="msg-dot"></div>
            </div>
            <div class="msg-item" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening Lena Thornton conversation')">
              <div class="av" style="background:#fef3c7;color:#92400e">LT</div>
              <div class="msg-meta"><div class="msg-name">Lena Thornton<span class="msg-time">1d</span></div><div class="msg-text">Here's the Q2 keyword strategy</div></div>
            </div>
            <div class="msg-item" style="border-radius:0;margin:0;padding:12px 14px" onclick="toast('Chat','Opening Marcus Patel conversation')">
              <div class="av" style="background:#ede9fe;color:#5b21b6">MP</div>
              <div class="msg-meta"><div class="msg-name">Marcus Patel<span class="msg-time">2d</span></div><div class="msg-text">Milestone 1 submitted for review</div></div>
            </div>
          </div>
        </div>
        <!-- Chat window -->
        <div style="flex:1;display:flex;flex-direction:column">
          <div style="padding:14px 18px;border-bottom:1px solid var(--uw-border);display:flex;align-items:center;gap:12px">
            <div class="av" style="background:#d1fae5;color:#065f46;width:36px;height:36px">AN</div>
            <div>
              <div style="font-weight:700;font-size:14px">Anika Nkosi</div>
              <div style="font-size:12px;color:var(--uw-green);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;background:var(--uw-green);border-radius:50%;display:inline-block"></span>Online now</div>
            </div>
            <div style="margin-left:auto;display:flex;gap:8px">
              <button class="btn btn-w btn-sm" onclick="toast('Video call','Opening Upwork meeting room')">📹 Video Call</button>
              <button class="btn btn-w btn-sm" onclick="openModal('contract-anika')">View Contract</button>
            </div>
          </div>
          <div style="flex:1;padding:18px;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:10px">
              <div class="av" style="background:#d1fae5;color:#065f46;flex-shrink:0">AN</div>
              <div style="max-width:70%">
                <div style="background:var(--uw-bg);border:1.5px solid var(--uw-border);border-radius:0 var(--radius) var(--radius) var(--radius);padding:10px 14px;font-size:13px;line-height:1.6">Hi! I've completed the first set of dashboard screens — 6 screens total. Ready for your review!</div>
                <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px">10:24 AM</div>
              </div>
            </div>
            <div style="display:flex;gap:10px;flex-direction:row-reverse">
              <div class="av" style="background:var(--uw-green);color:#001e00;flex-shrink:0">NX</div>
              <div style="max-width:70%;text-align:right">
                <div style="background:var(--uw-green);color:white;border-radius:var(--radius) 0 var(--radius) var(--radius);padding:10px 14px;font-size:13px;line-height:1.6">Excellent! I'll review them this afternoon and send feedback by EOD.</div>
                <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px">10:31 AM</div>
              </div>
            </div>
          </div>
          <div style="padding:14px 18px;border-top:1px solid var(--uw-border);display:flex;gap:10px">
            <input style="flex:1;padding:9px 14px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:13px;font-family:inherit;outline:none" placeholder="Type a message…" onfocus="this.style.borderColor='var(--uw-green)'" onblur="this.style.borderColor='var(--uw-border)'">
            <button class="btn btn-g" onclick="toast('Sent','Message delivered')">Send</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ PAYMENTS PAGE ══ -->
    <div class="page" id="page-payments">
      <div class="pg-header">
        <div>
          <div class="pg-title">Payments</div>
          <div class="pg-sub">Billing balance, transaction history & payment methods</div>
        </div>
        <div style="display:flex;gap:10px">
          <button class="btn btn-w" onclick="openModal('manage-cards')">Manage Cards</button>
          <button class="btn btn-g" onclick="openModal('add-funds')">+ Add Funds</button>
        </div>
      </div>

      <div class="g3" style="margin-bottom:18px">
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Available Balance</div>
            <div style="font-size:30px;font-weight:700;color:var(--uw-black)">$1,250.00</div>
            <button class="btn btn-g btn-sm" style="margin-top:12px;width:100%;justify-content:center" onclick="openModal('add-funds')">Add Funds</button>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">In Escrow</div>
            <div style="font-size:30px;font-weight:700;color:#1d4ed8">$3,400.00</div>
            <div style="font-size:12px;color:var(--uw-gray);margin-top:8px">Protected milestone funds</div>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-body" style="text-align:center">
            <div style="font-size:11.5px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Spent This Month</div>
            <div style="font-size:30px;font-weight:700;color:var(--uw-black)">$4,820</div>
            <div style="font-size:12px;color:var(--uw-green);margin-top:8px;font-weight:600">↑ 18% vs April</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>Transaction History</h3></div>
        <table class="tbl">
          <thead><tr><th>Date</th><th>Description</th><th>Freelancer</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
            <tr><td>May 12, 2026</td><td>Milestone 2 Release</td><td>James Kowalski</td><td><span class="badge b-purple">Fixed</span></td><td style="font-weight:700;color:#dc2626">−$2,100</td><td><span class="badge b-green">Paid</span></td></tr>
            <tr><td>May 10, 2026</td><td>Weekly Billing (34.5 hrs)</td><td>Anika Nkosi</td><td><span class="badge b-blue">Hourly</span></td><td style="font-weight:700;color:#dc2626">−$3,105</td><td><span class="badge b-green">Paid</span></td></tr>
            <tr><td>May 7, 2026</td><td>Weekly Billing (22 hrs)</td><td>Lena Thornton</td><td><span class="badge b-blue">Hourly</span></td><td style="font-weight:700;color:#dc2626">−$1,430</td><td><span class="badge b-green">Paid</span></td></tr>
            <tr><td>May 5, 2026</td><td>Milestone 1 Release</td><td>Marcus Patel</td><td><span class="badge b-purple">Fixed</span></td><td style="font-weight:700;color:#dc2626">−$1,100</td><td><span class="badge b-green">Paid</span></td></tr>
            <tr><td>May 1, 2026</td><td>Funds Added via Visa ••4821</td><td>—</td><td><span class="badge b-teal">Deposit</span></td><td style="font-weight:700;color:#14a800">+$5,000</td><td><span class="badge b-green">Completed</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ REPORTS PAGE ══ -->
    <div class="page" id="page-reports">
      <div class="pg-header">
        <div class="pg-title">Reports</div>
        <div style="display:flex;gap:10px;align-items:center">
          <select style="padding:8px 14px;border:1.5px solid var(--uw-border);border-radius:50px;font-size:13px;font-family:inherit;color:var(--uw-dark);background:white;outline:none">
            <option>Last 30 days</option><option>Last 90 days</option><option>This Year</option><option>All Time</option>
          </select>
          <button class="btn btn-w" onclick="toast('Export','CSV download starting...')">Export CSV</button>
        </div>
      </div>

      <div class="g3" style="margin-bottom:18px">
        <div class="report-metric"><div class="rm-lbl">Total Spent (All Time)</div><div class="rm-val">$38,450</div></div>
        <div class="report-metric"><div class="rm-lbl">Contracts Completed</div><div class="rm-val">24</div></div>
        <div class="report-metric"><div class="rm-lbl">Avg Spend / Contract</div><div class="rm-val">$1,602</div></div>
      </div>

      <div class="g2">
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Spend by Category</h3></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px">
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Engineering</span><span style="font-weight:700">$18,200</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:75%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Design</span><span style="font-weight:700">$10,500</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:43%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>Marketing / SEO</span><span style="font-weight:700">$6,350</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:26%"></div></div></div>
              <div><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px"><span>AI / ML</span><span style="font-weight:700">$3,400</span></div><div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:14%"></div></div></div>
            </div>
          </div>
        </div>
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Account Summary</h3></div>
          <div class="card-body">
            <table class="tbl" style="font-size:13px">
              <tr><td style="color:var(--uw-gray)">Total jobs posted</td><td><strong>16</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Freelancers hired</td><td><strong>12</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Contracts completed</td><td><strong>24</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Total spent (all time)</td><td><strong>$38,450</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Total hours tracked</td><td><strong>812 hrs</strong></td></tr>
              <tr><td style="color:var(--uw-gray)">Disputes filed</td><td><strong style="color:var(--uw-green)">0</strong></td></tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ VERIFICATION PAGE ══ -->
    <div class="page" id="page-verification">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div>
          <div style="font-size:20px;font-weight:700">Identity Verification</div>
          <div style="font-size:13px;color:var(--uw-gray);margin-top:3px">Verify your identity to ensure a safe and secure marketplace.</div>
        </div>
        <?php if($vStatus === 'approved'): ?>
          <span class="badge b-green" style="font-size:12px;padding:5px 12px">✅ Verified</span>
        <?php elseif($vStatus === 'pending'): ?>
          <span class="badge b-yellow" style="font-size:12px;padding:5px 12px">⏳ Pending</span>
        <?php else: ?>
          <span class="badge b-gray" style="font-size:12px;padding:5px 12px">🛡️ Unverified</span>
        <?php endif; ?>
      </div>

      <?php if($vStatus === 'approved'): ?>
        <div class="card" style="border:2px solid var(--uw-green);background:var(--uw-green-light)">
          <div class="card-body" style="text-align:center;padding:36px 24px">
            <div style="font-size:52px;margin-bottom:16px">✅</div>
            <div style="font-size:20px;font-weight:700;margin-bottom:8px;color:var(--uw-green)">Identity Verified!</div>
            <div style="font-size:13.5px;color:var(--uw-gray);line-height:1.7;max-width:380px;margin:0 auto 20px">Your identity has been successfully verified. Your account is now fully active and trusted by freelancers.</div>
            <div>
              <button class="btn btn-g" onclick="showPage('home',document.querySelector('[onclick*=home]'))">Back to Dashboard</button>
            </div>
          </div>
        </div>
      <?php elseif($vStatus === 'pending'): ?>
        <div class="card" style="border:1px solid #fde68a;background:#fffbeb">
          <div class="card-body" style="text-align:center;padding:36px 24px">
            <div style="font-size:52px;margin-bottom:16px">⏳</div>
            <div style="font-size:20px;font-weight:700;margin-bottom:8px;color:#92400e">Verification Under Review</div>
            <div style="font-size:13.5px;color:#92400e;line-height:1.7;max-width:380px;margin:0 auto 20px">Your identity documents have been received and are currently under review. This process typically takes 1-3 business days. We'll notify you once it's complete.</div>
            <div style="display:inline-flex;align-items:center;gap:8px;background:white;border:1px solid #fde68a;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#b27b16">
              Status: <strong>Pending Review</strong>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Multi-step Design for Clients -->
        <div style="display:flex;align-items:center;gap:0;margin-bottom:22px;background:white;border:1px solid var(--uw-border);border-radius:10px;overflow:hidden">
          <div id="cvstep-1" style="flex:1;padding:14px 16px;border-right:1px solid var(--uw-border);cursor:pointer;transition:background .15s;background:var(--uw-green-light)" onclick="switchCVStep(1)">
            <div style="display:flex;align-items:center;gap:8px">
              <div id="cvstep-1-ico" style="width:24px;height:24px;border-radius:50%;background:var(--uw-green);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">1</div>
              <div>
                <div style="font-size:12px;font-weight:700">Choose ID</div>
                <div style="font-size:11px;color:var(--uw-gray)">Select document</div>
              </div>
            </div>
          </div>
          <div id="cvstep-2" style="flex:1;padding:14px 16px;border-right:1px solid var(--uw-border);cursor:pointer;transition:background .15s" onclick="switchCVStep(2)">
            <div style="display:flex;align-items:center;gap:8px">
              <div id="cvstep-2-ico" style="width:24px;height:24px;border-radius:50%;background:var(--uw-border);color:var(--uw-gray);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">2</div>
              <div>
                <div style="font-size:12px;font-weight:700">Upload</div>
                <div style="font-size:11px;color:var(--uw-gray)">Photo or scan</div>
              </div>
            </div>
          </div>
          <div id="cvstep-3" style="flex:1;padding:14px 16px;cursor:pointer;transition:background .15s" onclick="switchCVStep(3)">
            <div style="display:flex;align-items:center;gap:8px">
              <div id="cvstep-3-ico" style="width:24px;height:24px;border-radius:50%;background:var(--uw-border);color:var(--uw-gray);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">3</div>
              <div>
                <div style="font-size:12px;font-weight:700">Review</div>
                <div style="font-size:11px;color:var(--uw-gray)">Final check</div>
              </div>
            </div>
          </div>
        </div>

        <div id="cvpanel-1" class="card" style="margin-bottom:16px">
          <div class="card-head"><h3>Step 1 — Choose Document Type</h3></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
              <div class="doc-type-card" id="cdtype-passport" onclick="selectCDocType('Passport','cdtype-passport')" style="border:2px solid var(--uw-border);border-radius:10px;padding:16px;cursor:pointer;text-align:center">
                <div style="font-size:34px;margin-bottom:8px">🛂</div>
                <div style="font-weight:700;font-size:13px">Passport</div>
              </div>
              <div class="doc-type-card" id="cdtype-national-id" onclick="selectCDocType('National ID','cdtype-national-id')" style="border:2px solid var(--uw-border);border-radius:10px;padding:16px;cursor:pointer;text-align:center">
                <div style="font-size:34px;margin-bottom:8px">🪪</div>
                <div style="font-weight:700;font-size:13px">National ID</div>
              </div>
            </div>
            <div id="cdtype-selected-bar" style="display:none;margin-top:16px;background:var(--uw-green-light);border:1px solid var(--uw-green);border-radius:8px;padding:10px 14px;align-items:center;gap:10px">
              <span id="cdtype-selected-text" style="font-size:13px;font-weight:600;color:var(--uw-green)">Passport selected</span>
              <button class="btn btn-sm btn-g" style="margin-left:auto" onclick="switchCVStep(2)">Next →</button>
            </div>
          </div>
        </div>

        <div id="cvpanel-2" class="card" style="margin-bottom:16px;display:none">
          <div class="card-head"><h3>Step 2 — Upload</h3></div>
          <div class="card-body">
            <div id="cvdrop" style="border:2px dashed var(--uw-border);border-radius:10px;padding:30px;text-align:center;background:#fafafa;cursor:pointer" onclick="document.getElementById('cvinput').click()">
              <div id="cv-text" style="font-size:13px;font-weight:600">Click to upload ID photo</div>
              <input type="file" id="cvinput" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="handleCVFile(this)">
            </div>
            <div style="display:flex;gap:10px;margin-top:20px">
              <button class="btn btn-w" onclick="switchCVStep(1)">← Back</button>
              <button class="btn btn-g" id="cvnext-2" style="flex:1;justify-content:center" onclick="switchCVStep(3)" disabled>Next →</button>
            </div>
          </div>
        </div>

        <div id="cvpanel-3" class="card" style="margin-bottom:16px;display:none">
          <div class="card-head"><h3>Step 3 — Submit</h3></div>
          <div class="card-body">
            <div style="background:var(--uw-bg);padding:14px;border-radius:8px;margin-bottom:20px;border:1px solid var(--uw-border)">
              <div style="font-size:12px;color:var(--uw-gray)">Document: <strong id="cvreview-type">Passport</strong></div>
              <div style="font-size:12px;color:var(--uw-gray)">File: <strong id="cvreview-file">none</strong></div>
            </div>
            <div style="display:flex;gap:10px">
              <button class="btn btn-w" onclick="switchCVStep(2)">← Back</button>
              <button class="btn btn-g" id="v-submit-btn" style="flex:1;justify-content:center" onclick="submitClientFinalVerification()">
                <span id="v-btn-text">Submit for Review</span>
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ══ MOBILE BOTTOM NAV ══ -->
<nav class="mob-bottom-nav" id="mob-bottom-nav">
  <div class="mob-bottom-nav-inner">
    <button class="mob-nav-item active" id="mbn-home" onclick="showPage('home',document.querySelector('[onclick*=\'home\']'));setMobNav('home')">
      <span class="mn-ico">🏠</span>Home
    </button>
    <button class="mob-nav-item" id="mbn-jobs" onclick="showPage('jobs',document.querySelector('[onclick*=\'jobs\']'));setMobNav('jobs')">
      <span class="mn-ico">📋</span>Jobs
    </button>
    <button class="mob-nav-item" id="mbn-proposals" onclick="showPage('proposals',document.querySelector('[onclick*=\'proposals\']'));setMobNav('proposals')">
      <span class="mn-ico">📩</span>Proposals
    </button>
    <button class="mob-nav-item" id="mbn-messages" onclick="showPage('messages',document.querySelector('[onclick*=\'messages\']'));setMobNav('messages')">
      <span class="mn-ico">💬</span>Messages
    </button>
    <button class="mob-nav-item" id="mbn-more" onclick="openMobSidebar()">
      <span class="mn-ico">☰</span>More
    </button>
  </div>
</nav>
<?php include __DIR__ . '/includes/footer.php'; ?>
