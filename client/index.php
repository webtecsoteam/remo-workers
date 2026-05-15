<?php if(!function_exists('baseUrl')) { require_once __DIR__ . '/../includes/config.php'; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upwork – Client Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Neue+Haas+Grotesk+Display+Pro:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo baseUrl("client/css/style.css"); ?>">
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
    <div class="sb-item" onclick="toast('Uma AI','AI work assistant analyzing your active projects...')"><span class="ico">✨</span>AI Assistant</div>
    <div class="sb-section">Account</div>
    <div class="sb-item" onclick="toast('Settings','Account settings opened')"><span class="ico">⚙️</span>Settings</div>
    <div class="sb-item" onclick="toast('Help Center','Loading support articles...')"><span class="ico">❓</span>Help & Support</div>
  </nav>
  <div class="sb-footer">
    <a onclick="toast('Upgrade','Opening Business Plus details')">⬆️ Upgrade to Business Plus</a>
    <a href="<?php echo baseUrl(); ?>">🚪 Sign Out</a>
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
<script>
let availableBalance = 1250.00;

// ─── MODALS ───
function fundMilestoneBody(cfg){
  const bal = availableBalance;
  const canCover = bal >= cfg.amount;
  const shortage = Math.max(0, cfg.amount - bal);
  return `
  <div style="display:flex;gap:10px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:14px 16px;margin-bottom:16px">
    <div class="av" style="background:${cfg.avatarBg};color:${cfg.avatarColor};width:38px;height:38px">${cfg.initials}</div>
    <div style="flex:1">
      <div style="font-weight:700">${cfg.name}</div>
      <div style="font-size:12px;color:var(--uw-gray)">${cfg.role} · ${cfg.contract}</div>
    </div>
    <div style="text-align:right">
      <div style="font-size:18px;font-weight:700">$${cfg.amount.toLocaleString()}</div>
      <div style="font-size:11.5px;color:var(--uw-gray)">${cfg.milestone}</div>
    </div>
  </div>
  <div style="font-size:13px;font-weight:700;margin-bottom:10px">Choose Funding Source</div>
  <div class="pay-method ${canCover?'selected':''}" id="pm-balance" onclick="selectFundSource('balance','${cfg.amount}')">
    <div class="pay-method-icon" style="background:var(--uw-black);border-color:var(--uw-black)">💰</div>
    <div class="pay-method-info">
      <div class="pay-method-name">Upwork Balance</div>
      <div class="pay-method-sub">Available: <strong style="color:${canCover?'var(--uw-green)':'#dc2626'}">$${bal.toFixed(2)}</strong>${canCover?' · Covers full amount ✓':` · Shortfall: $${shortage.toFixed(2)}`}</div>
    </div>
    ${canCover?'<span class="pay-method-badge">RECOMMENDED</span>':'<span style="font-size:10px;background:#fee2e2;color:#991b1b;padding:2px 7px;border-radius:4px;font-weight:700;white-space:nowrap">PARTIAL ONLY</span>'}
  </div>
  <div class="pay-method ${!canCover?'selected':''}" id="pm-card" onclick="selectFundSource('card','${cfg.amount}')">
    <div class="pay-method-icon">💳</div>
    <div class="pay-method-info"><div class="pay-method-name">Visa ending in 4821</div><div class="pay-method-sub">Expires 09/27 · Primary card</div></div>
    <span class="pay-method-badge">PRIMARY</span>
  </div>
  <div class="pay-method" onclick="selectFundSource('card2','${cfg.amount}')">
    <div class="pay-method-icon">🏦</div>
    <div class="pay-method-info"><div class="pay-method-name">Mastercard ending in 3392</div><div class="pay-method-sub">Expires 03/26</div></div>
  </div>
  ${!canCover?`<div class="split-divider">or split payment</div>
  <div class="pay-method" onclick="selectFundSource('split','${cfg.amount}')">
    <div class="pay-method-icon" style="font-size:14px">⚡</div>
    <div class="pay-method-info"><div class="pay-method-name">Split: Balance + Card</div><div class="pay-method-sub">$${bal.toFixed(2)} from balance + $${shortage.toFixed(2)} from Visa ••4821</div></div>
    <span class="pay-method-badge">RECOMMENDED</span>
  </div>`:''}
  <div class="fund-summary">
    <div class="fund-summary-row"><span style="color:var(--uw-gray)">Milestone</span><span>${cfg.milestone}</span></div>
    <div class="fund-summary-row"><span style="color:var(--uw-gray)">Upwork Service Fee</span><span>$0.00</span></div>
    <div class="fund-summary-row total"><span>Total funded to escrow</span><span style="color:var(--uw-green)">$${cfg.amount.toLocaleString()}</span></div>
  </div>
  <div style="font-size:12px;color:var(--uw-gray);margin-bottom:14px;display:flex;align-items:center;gap:6px">🔒 Funds are held in Upwork escrow and released only when you approve the work.</div>
  <button class="btn btn-g" style="width:100%;justify-content:center;padding:11px;font-size:14px" onclick="confirmFundMilestone(${cfg.amount},'${cfg.name}','${cfg.milestone}')">Confirm & Fund Milestone →</button>`;
}

function selectFundSource(id, amount){
  document.querySelectorAll('.pay-method').forEach(el=>el.classList.remove('selected'));
  const m = document.getElementById('pm-'+id);
  if(m) m.classList.add('selected');
}
function confirmFundMilestone(amount, name, milestone){
  availableBalance = Math.max(0, availableBalance - amount);
  toast('Milestone Funded! ✓',`$${amount.toLocaleString()} held in escrow for ${name}`);
  closeModal();
}
function selectPayMethod(el){document.querySelectorAll('.pay-method').forEach(x=>x.classList.remove('selected'));el.classList.add('selected');}
function handleAddFunds(){
  const v=parseFloat(document.getElementById('add-funds-amount')?.value||0);
  if(v<50){toast('Minimum $50','Please enter at least $50 to add');return;}
  availableBalance+=v;
  toast('Funds Added!',`$${v.toFixed(2)} added. New balance: $${availableBalance.toFixed(2)}`);
  closeModal();
}

// ─── DM MODAL BUILDER ───
function buildDmModal(cfg){
  const historyHtml = cfg.history.map(msg => {
    const isMe = msg.from === 'me';
    return `<div style="display:flex;gap:10px;flex-direction:${isMe?'row-reverse':'row'};margin-bottom:14px">
      <div class="av" style="background:${isMe?'var(--uw-green)':cfg.avatarBg};color:${isMe?'#001e00':cfg.avatarColor};flex-shrink:0;width:32px;height:32px">${isMe?'NX':cfg.initials}</div>
      <div style="max-width:75%">
        <div style="background:${isMe?'var(--uw-green)':'var(--uw-bg)'};color:${isMe?'white':'var(--uw-dark)'};border:${isMe?'none':'1.5px solid var(--uw-border)'};border-radius:${isMe?'12px 2px 12px 12px':'2px 12px 12px 12px'};padding:10px 14px;font-size:13px;line-height:1.6">${msg.text}</div>
        <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px;text-align:${isMe?'right':'left'}">${msg.time}</div>
      </div>
    </div>`;
  }).join('');

  return `
  <div style="display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1.5px solid var(--uw-border);margin-bottom:14px">
    <div class="av" style="background:${cfg.avatarBg};color:${cfg.avatarColor};width:44px;height:44px;font-size:14px;flex-shrink:0">${cfg.initials}</div>
    <div style="flex:1;min-width:0">
      <div style="font-weight:700;font-size:14px">${cfg.name} <span class="uw-level-badge ${cfg.badgeCls}" style="font-size:10px">${cfg.badge}</span></div>
      <div style="font-size:12px;color:var(--uw-gray);margin-top:1px">${cfg.role} · ${cfg.rating} (${cfg.reviews} reviews) · ${cfg.rate} · ${cfg.location}</div>
    </div>
    <button class="btn btn-o btn-sm" onclick="openModal('${cfg.hireModal}')">Hire →</button>
  </div>

  <div style="background:var(--uw-green-light);border:1.5px solid var(--uw-green-mid);border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
    <span style="font-size:14px">📋</span>
    <div>
      <div style="font-size:11px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.05em">Proposal for</div>
      <div style="font-size:13px;font-weight:600;color:var(--uw-black)">${cfg.proposalFor}</div>
    </div>
    <div style="margin-left:auto;font-size:13px;font-weight:700;color:var(--uw-green);white-space:nowrap">${cfg.proposalAmount}</div>
  </div>

  <div id="dm-chat-${cfg.initials}" style="min-height:160px;max-height:260px;overflow-y:auto;padding:4px 2px;margin-bottom:12px">
    ${historyHtml}
    <div id="dm-sent-msgs-${cfg.initials}"></div>
  </div>

  <div style="border-top:1.5px solid var(--uw-border);padding-top:14px">
    <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
      <button class="btn btn-w btn-sm" onclick="insertQuickReply('${cfg.initials}','Thanks for your proposal! Could you share some relevant examples of past work?')">📎 Ask for samples</button>
      <button class="btn btn-w btn-sm" onclick="insertQuickReply('${cfg.initials}','When are you available for a quick intro call?')">📅 Ask availability</button>
      <button class="btn btn-w btn-sm" onclick="insertQuickReply('${cfg.initials}','Can you break down your approach to this project?')">💡 Ask approach</button>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end">
      <textarea id="dm-input-${cfg.initials}" style="flex:1;padding:10px 13px;border:1.5px solid var(--uw-border);border-radius:10px;font-size:13px;font-family:inherit;outline:none;resize:none;min-height:60px;line-height:1.55;transition:border-color .15s" placeholder="${cfg.placeholder}" onfocus="this.style.borderColor='var(--uw-green)'" onblur="this.style.borderColor='var(--uw-border)'" onkeydown="if(event.key==='Enter'&&(event.metaKey||event.ctrlKey)){sendDm('${cfg.initials}');return false}"></textarea>
      <button class="btn btn-g" style="padding:10px 18px;align-self:flex-end;flex-shrink:0" onclick="sendDm('${cfg.initials}')">Send</button>
    </div>
    <div style="font-size:11px;color:var(--uw-gray2);margin-top:6px">Press Ctrl+Enter or ⌘+Enter to send</div>
  </div>`;
}

function sendDm(initials){
  const input = document.getElementById('dm-input-'+initials);
  const container = document.getElementById('dm-sent-msgs-'+initials);
  const chat = document.getElementById('dm-chat-'+initials);
  if(!input||!container||!chat) return;
  const text = input.value.trim();
  if(!text) return;
  const now = new Date();
  const time = now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  const msgEl = document.createElement('div');
  msgEl.style.cssText = 'display:flex;gap:10px;flex-direction:row-reverse;margin-bottom:14px';
  msgEl.innerHTML = `
    <div class="av" style="background:var(--uw-green);color:#001e00;flex-shrink:0;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px">NX</div>
    <div style="max-width:75%">
      <div style="background:var(--uw-green);color:white;border-radius:12px 2px 12px 12px;padding:10px 14px;font-size:13px;line-height:1.6">${text.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
      <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px;text-align:right">Just now · ${time}</div>
    </div>`;
  container.appendChild(msgEl);
  input.value = '';
  input.style.height = 'auto';
  chat.scrollTop = chat.scrollHeight;
  toast('Message sent','Your message was delivered');
}

function insertQuickReply(initials, text){
  const input = document.getElementById('dm-input-'+initials);
  if(input){ input.value = text; input.focus(); }
}

const MODALS = {
  'post-job':{t:'Post a New Job',b:`
    <div class="fg"><label>Job Title</label><input type="text" placeholder="e.g. Senior React Developer for Analytics Dashboard"></div>

    <div class="fg">
      <label>Category</label>
      <select id="pj-cat" onchange="updateSubcats()">
        <option value="">— Select a category —</option>
        <option value="Accounting &amp; Consulting">Accounting &amp; Consulting</option>
        <option value="Admin Support">Admin Support</option>
        <option value="Customer Service">Customer Service</option>
        <option value="Data Science &amp; Analytics">Data Science &amp; Analytics</option>
        <option value="Design &amp; Creative">Design &amp; Creative</option>
        <option value="Engineering &amp; Architecture">Engineering &amp; Architecture</option>
        <option value="IT &amp; Networking">IT &amp; Networking</option>
        <option value="Legal">Legal</option>
        <option value="Sales &amp; Marketing">Sales &amp; Marketing</option>
        <option value="Translation">Translation</option>
        <option value="Web, Mobile &amp; Software Dev">Web, Mobile &amp; Software Dev</option>
        <option value="Writing">Writing</option>
      </select>
    </div>

    <div class="fg" id="pj-subcat-wrap" style="display:none">
      <label>Subcategory</label>
      <select id="pj-subcat" onchange="updateSpecialties()">
        <option value="">— Select a subcategory —</option>
      </select>
    </div>

    <div class="fg" id="pj-spec-wrap" style="display:none">
      <label>Specialty</label>
      <select id="pj-spec">
        <option value="">— Select a specialty —</option>
      </select>
    </div>

    <div class="fg"><label>Billing Type</label><select id="pj-billing-type" onchange="updatePostJobFields()"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option><option value="monthly">Monthly Retainer</option></select></div>
    <div id="pj-fixed-fields">
      <div class="fg"><label>Budget Range ($)</label><div style="display:flex;gap:10px"><input type="number" placeholder="Min" style="flex:1"><input type="number" placeholder="Max" style="flex:1"></div></div>
    </div>
    <div id="pj-hourly-fields" style="display:none">
      <div class="fg"><label>Hourly Rate Range ($/hr)</label><div style="display:flex;gap:10px"><input type="number" placeholder="Min" style="flex:1"><input type="number" placeholder="Max" style="flex:1"></div></div>
    </div>
    <div id="pj-monthly-fields" style="display:none">
      <div class="fg"><label>Monthly Budget ($/mo)</label><input type="number" placeholder="e.g. 3000"></div>
    </div>
    <div class="fg"><label>Project Description</label><textarea placeholder="Describe the scope, goals, and requirements of your project…"></textarea></div>
    <div class="fg"><label>Required Skills</label><input type="text" placeholder="e.g. React, Node.js, TypeScript, PostgreSQL"></div>
    <div class="fg"><label>Experience Level</label><select><option>Entry Level</option><option selected>Intermediate</option><option>Expert</option></select></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:4px;padding:11px" onclick="submitPostJob()">Post Job →</button>
  `},
  'job-1':{t:'Senior React Developer — Analytics Dashboard',b:`
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
      <span class="badge b-green">Open</span><span class="badge b-blue">Fixed Price</span><span class="badge b-gray">Remote</span>
    </div>
    <div class="g2" style="margin-bottom:14px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Budget</div><div style="font-weight:700">$8,000–$12,000</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Proposals</div><div style="font-weight:700;color:var(--uw-green)">8 received</div></div>
    </div>
    <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:14px">Build a production-ready analytics dashboard with real-time WebSocket charts, interactive data visualizations, and a filterable data table. Must be responsive and integrate with our REST API.</div>
    <div class="fg"><label>Required Skills</label><div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px"><span class="badge b-gray">React</span><span class="badge b-gray">TypeScript</span><span class="badge b-gray">WebSockets</span><span class="badge b-gray">D3.js</span><span class="badge b-gray">REST APIs</span></div></div>
    <div style="display:flex;gap:8px;margin-top:16px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Job paused','Job is now paused')">Pause Job</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'));closeModal()">View Proposals (8)</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Editing','Opening job editor')">Edit Job</button>
    </div>
  `},
  'job-2':{t:'Brand Designer — Full Identity Redesign',b:`
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"><span class="badge b-green">Open</span><span class="badge b-purple">Fixed Price</span><span class="badge b-gray">Remote</span></div>
    <div class="g2" style="margin-bottom:14px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Budget</div><div style="font-weight:700">$3,500–$6,000</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Proposals</div><div style="font-weight:700;color:var(--uw-green)">4 received</div></div>
    </div>
    <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:14px">Full brand identity redesign for our 2026 rebrand — including logo, color system, typography, brand guidelines, and social media templates. Looking for a senior brand designer with a strong portfolio.</div>
    <div style="display:flex;gap:8px;margin-top:16px">
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'));closeModal()">View Proposals (4)</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Editing','Opening job editor')">Edit Job</button>
    </div>
  `},
  'prop-anika':{t:'Proposal — Anika Nkosi',b:`
    <div style="display:flex;gap:12px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#d1fae5;color:#065f46;width:44px;height:44px;font-size:14px">AN</div>
      <div style="flex:1">
        <div style="font-weight:700;font-size:15px">Anika Nkosi <span class="uw-level-badge lvl-top-rated-plus">✦ Top Rated Plus</span></div>
        <div style="font-size:12px;color:var(--uw-gray)">UI/UX Designer · ★ 5.0 · 127 reviews · Berlin, Germany</div>
      </div>
      <div style="text-align:right"><div style="font-size:18px;font-weight:700">$5,800</div><div style="font-size:11px;color:var(--uw-gray)">Fixed price</div></div>
    </div>
    <div style="font-size:13.5px;line-height:1.75;color:#374151;background:var(--uw-bg);border-radius:8px;padding:14px;margin-bottom:16px;border:1.5px solid var(--uw-border)">"I specialize in brand identity systems and have redesigned 40+ brands across fintech and SaaS. I'd love to bring your 2026 brand vision to life with a comprehensive system that scales across every touchpoint — including logo, typography, color system, and comprehensive brand guidelines."</div>
    <div style="margin-bottom:16px"><div style="font-size:12px;font-weight:700;color:var(--uw-gray);margin-bottom:6px">EST. TIMELINE</div><div style="font-weight:600">7 business days</div></div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Archived','Proposal archived')">Archive</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Shortlisted','Anika added to shortlist')">Shortlist</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="openModal('hire-anika')">Hire Anika →</button>
    </div>
  `},
  'prop-james':{t:'Proposal — James Kowalski',b:`
    <div style="display:flex;gap:12px;align-items:center;background:#eff6ff;border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#dbeafe;color:#1e40af;width:44px;height:44px;font-size:14px">JK</div>
      <div style="flex:1">
        <div style="font-weight:700;font-size:15px">James Kowalski <span class="uw-level-badge lvl-expert-vetted">★ Expert-Vetted</span></div>
        <div style="font-size:12px;color:var(--uw-gray)">Full Stack Engineer · ★ 4.9 · 89 reviews · Toronto, Canada</div>
      </div>
      <div style="text-align:right"><div style="font-size:18px;font-weight:700">$130/hr</div><div style="font-size:11px;color:var(--uw-gray)">Hourly rate</div></div>
    </div>
    <div style="font-size:13.5px;line-height:1.75;color:#374151;background:var(--uw-bg);border-radius:8px;padding:14px;margin-bottom:16px;border:1.5px solid var(--uw-border)">"I've built 6 real-time analytics dashboards in the last 18 months, including one for a 50,000-user SaaS platform using React, WebSockets, and D3. I can start immediately and deliver milestone 1 within 5 business days."</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Archived','Proposal archived')">Archive</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Shortlisted','James added to shortlist')">Shortlist</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="openModal('hire-james')">Hire James →</button>
    </div>
  `},
  'hire-anika':{t:'Hire Anika Nkosi',b:`
    <div style="display:flex;gap:10px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:12px 14px;margin-bottom:16px">
      <div class="av" style="background:#d1fae5;color:#065f46">AN</div>
      <div><div style="font-weight:700">Anika Nkosi</div><div style="font-size:12px;color:var(--uw-gray)">UI/UX Designer · ★ 5.0</div></div>
    </div>
    <div class="fg"><label>Contract Type</label><select id="hire-anika-contract-type" onchange="toggleHireFields('hire-anika')"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option><option value="monthly">Monthly Retainer</option></select></div>
    <div id="hire-anika-fixed-fields">
      <div class="fg"><label>Total Contract Amount ($)</label><input type="number" placeholder="e.g. 5800"></div>
      <div class="fg"><label>Milestone Name</label><input type="text" placeholder="e.g. Brand Identity Delivery"></div>
    </div>
    <div id="hire-anika-hourly-fields" style="display:none"><div class="fg"><label>Hourly Rate ($)</label><input type="number" value="90"></div><div class="fg"><label>Weekly Hour Limit</label><input type="number" placeholder="e.g. 20"></div></div>
    <div id="hire-anika-monthly-fields" style="display:none"><div class="fg"><label>Monthly Rate ($)</label><input type="number" placeholder="e.g. 3600"></div></div>
    <div class="fg"><label>Start Date</label><input type="date"></div>
    <div class="fg"><label>Project Description / Scope</label><textarea placeholder="Describe what you need Anika to deliver…"></textarea></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:8px;padding:11px" onclick="toast('Contract Sent! 🎉','Anika has been notified and has 48 hours to accept');closeModal()">Send Contract Offer →</button>
  `},
  'hire-james':{t:'Hire James Kowalski',b:`
    <div style="display:flex;gap:10px;align-items:center;background:#eff6ff;border-radius:8px;padding:12px 14px;margin-bottom:16px">
      <div class="av" style="background:#dbeafe;color:#1e40af">JK</div>
      <div><div style="font-weight:700">James Kowalski</div><div style="font-size:12px;color:var(--uw-gray)">Full Stack Engineer · ★ 4.9</div></div>
    </div>
    <div class="fg"><label>Contract Type</label><select id="hire-james-contract-type" onchange="toggleHireFields('hire-james')"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option></select></div>
    <div id="hire-james-fixed-fields">
      <div class="fg"><label>Total Contract Amount ($)</label><input type="number" placeholder="e.g. 8000"></div>
      <div class="fg"><label>First Milestone</label><input type="text" placeholder="e.g. Milestone 1 — API Foundation"></div>
      <div class="fg"><label>Milestone Amount ($)</label><input type="number" placeholder="e.g. 2500"></div>
    </div>
    <div id="hire-james-hourly-fields" style="display:none"><div class="fg"><label>Hourly Rate ($)</label><input type="number" value="130"></div></div>
    <div class="fg"><label>Start Date</label><input type="date"></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:8px;padding:11px" onclick="toast('Contract Sent! 🎉','James has been notified and has 48 hours to accept');closeModal()">Send Contract Offer →</button>
  `},
  'hire-sofia':{t:'Hire Sofia Reyes',b:`
    <div style="display:flex;gap:10px;align-items:center;background:#fef9ec;border-radius:8px;padding:12px 14px;margin-bottom:16px">
      <div class="av" style="background:#fef3c7;color:#92400e">SR</div>
      <div><div style="font-weight:700">Sofia Reyes</div><div style="font-size:12px;color:var(--uw-gray)">AI/ML Engineer · ★ 4.7</div></div>
    </div>
    <div class="fg"><label>Contract Type</label><select id="hire-sofia-contract-type" onchange="toggleHireFields('hire-sofia')"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option></select></div>
    <div id="hire-sofia-fixed-fields">
      <div class="fg"><label>Total Contract Amount ($)</label><input type="number" placeholder="e.g. 10500"></div>
      <div class="fg"><label>First Milestone</label><input type="text" placeholder="e.g. Milestone 1 — AI Backend Setup"></div>
      <div class="fg"><label>Milestone Amount ($)</label><input type="number" placeholder="e.g. 3500"></div>
    </div>
    <div id="hire-sofia-hourly-fields" style="display:none"><div class="fg"><label>Hourly Rate ($)</label><input type="number" value="85"></div></div>
    <div class="fg"><label>Start Date</label><input type="date"></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:8px;padding:11px" onclick="toast('Contract Sent! 🎉','Sofia has been notified and has 48 hours to accept');closeModal()">Send Contract Offer →</button>
  `},
  'contract-anika':{t:'Contract — Anika Nkosi',b:`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Type</div><div style="font-weight:700">Hourly · $90/hr</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Hours Logged</div><div style="font-weight:700">34.5 hrs</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Billed to Date</div><div style="font-weight:700">$3,105</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Started</div><div style="font-weight:700">Apr 28, 2026</div></div>
    </div>
    <div style="font-size:13.5px;font-weight:700;margin-bottom:8px">Work Diary — This Week</div>
    <div style="background:var(--uw-bg);border-radius:8px;padding:12px;font-size:13px;color:#374151;line-height:1.7;margin-bottom:16px;border:1.5px solid var(--uw-border)">
      <em>"Anika logged 8.5 hrs this week. Primary focus: mobile responsive variants of dashboard screens (~5h). Secondary: component library documentation in Figma (~3.5h). On track for end of week delivery."</em>
      <div style="font-size:11px;color:var(--uw-gray);margin-top:4px">— AI Work Summary</div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Paused','Contract paused')">Pause Contract</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Message sent','Chat opened with Anika')">Message</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Upwork room...')">📹 Video Call</button>
    </div>
  `},
  'contract-lena':{t:'Contract — Lena Thornton',b:`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Type</div><div style="font-weight:700">Hourly · $65/hr</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Hours Logged</div><div style="font-weight:700">22 hrs</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Billed to Date</div><div style="font-weight:700">$1,430</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Started</div><div style="font-weight:700">Apr 15, 2026</div></div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Paused','Contract paused')">Pause Contract</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Message sent','Chat opened with Lena')">Message Lena</button>
    </div>
  `},
  'msg-anika':{t:'Message from Anika Nkosi',b:`
    <div style="display:flex;gap:12px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#d1fae5;color:#065f46;width:42px;height:42px">AN</div>
      <div><div style="font-weight:700">Anika Nkosi</div><div style="font-size:12px;color:var(--uw-green);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;background:var(--uw-green);border-radius:50%;display:inline-block"></span>Online now</div></div>
    </div>
    <div style="background:var(--uw-bg);border-radius:8px;padding:14px;font-size:13.5px;color:#374151;line-height:1.75;margin-bottom:16px;border:1.5px solid var(--uw-border)">"Hi! I've completed the first set of dashboard screens — 6 screens total including the main overview, analytics detail, settings, and mobile variants. I've also set up Figma comment threads for each screen. Ready for your review whenever you have a moment!"</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="showPage('messages',document.querySelector('[onclick*=messages]'));closeModal()">Open Chat</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Upwork meeting room...')">📹 Video Call</button>
    </div>
  `},
  'msg-james':{t:'Message from James Kowalski',b:`
    <div style="display:flex;gap:12px;align-items:center;background:#eff6ff;border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#dbeafe;color:#1e40af;width:42px;height:42px">JK</div>
      <div><div style="font-weight:700">James Kowalski</div><div style="font-size:12px;color:var(--uw-gray)">Last seen 2 hours ago</div></div>
    </div>
    <div style="background:var(--uw-bg);border-radius:8px;padding:14px;font-size:13.5px;color:#374151;line-height:1.75;margin-bottom:16px;border:1.5px solid var(--uw-border)">"Milestone 2 is complete — all 47 unit tests passing, integration tests green, and I've pushed the final code to the repo. Please review and release the milestone payment when ready. I can also do a quick walkthrough call before you release."</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Chat','Chat with James opened')">Reply</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Video call','Opening meeting with James')">📹 Call</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Milestone Released ✓','$2,300 released to James Kowalski');closeModal()">Release Milestone $2,300 →</button>
    </div>
  `},
  'fund-milestone-james':{t:'Fund Milestone — James Kowalski',b:fundMilestoneBody({name:'James Kowalski',initials:'JK',avatarBg:'#dbeafe',avatarColor:'#1e40af',role:'Full Stack Engineer',milestone:'Milestone 3 — Final Delivery',amount:2300,contract:'Backend API Development'})},
  'fund-milestone-marcus':{t:'Fund Milestone — Marcus Patel',b:fundMilestoneBody({name:'Marcus Patel',initials:'MP',avatarBg:'#ede9fe',avatarColor:'#5b21b6',role:'AI/ML Engineer',milestone:'Milestone 2 — AI Chatbot Build',amount:1100,contract:'AI Chatbot Integration'})},
  'add-funds':{t:'Add Funds to Balance',b:`
    <div class="balance-pill">💰 Current Balance: $1,250.00</div>
    <div class="fg"><label>Amount to Add ($)</label><input type="number" placeholder="e.g. 500" min="50" id="add-funds-amount" oninput="document.getElementById('add-total').textContent='$'+(parseFloat(this.value)||0).toFixed(2)"></div>
    <div class="fg"><label>Charge to</label></div>
    <div class="pay-method selected" onclick="selectPayMethod(this)">
      <div class="pay-method-icon">💳</div>
      <div class="pay-method-info"><div class="pay-method-name">Visa ending in 4821</div><div class="pay-method-sub">Expires 09/27 · Primary</div></div>
      <span class="pay-method-badge">PRIMARY</span>
    </div>
    <div class="pay-method" onclick="selectPayMethod(this)">
      <div class="pay-method-icon">🏦</div>
      <div class="pay-method-info"><div class="pay-method-name">Mastercard ending in 3392</div><div class="pay-method-sub">Expires 03/26</div></div>
    </div>
    <div class="fund-summary">
      <div class="fund-summary-row"><span style="color:var(--uw-gray)">Amount</span><span id="add-total">$0.00</span></div>
      <div class="fund-summary-row"><span style="color:var(--uw-gray)">Processing fee</span><span>$0.00</span></div>
      <div class="fund-summary-row total"><span>New balance after deposit</span><span style="color:var(--uw-green)">$1,250.00</span></div>
    </div>
    <button class="btn btn-g" style="width:100%;justify-content:center;padding:11px" onclick="handleAddFunds()">Add Funds →</button>
  `},
  'manage-cards':{t:'Payment Methods',b:`
    <div style="margin-bottom:14px">
      <div style="font-size:13px;font-weight:700;margin-bottom:10px">Saved Cards</div>
      <div class="pay-method selected"><div class="pay-method-icon">💳</div><div class="pay-method-info"><div class="pay-method-name">Visa ending in 4821</div><div class="pay-method-sub">Expires 09/27</div></div><span class="pay-method-badge">PRIMARY</span></div>
      <div class="pay-method"><div class="pay-method-icon">🏦</div><div class="pay-method-info"><div class="pay-method-name">Mastercard ending in 3392</div><div class="pay-method-sub">Expires 03/26</div></div><button class="btn btn-w btn-sm" onclick="toast('Card removed','Mastercard ending in 3392 removed')" style="margin-left:auto">Remove</button></div>
    </div>
    <button class="btn btn-o" style="width:100%;justify-content:center" onclick="toast('Add card','Secure card entry form opening...')">+ Add a New Card</button>
  `},

  'dm-anika':{t:'Message Anika Nkosi',b:buildDmModal({
    initials:'AN', avatarBg:'#d1fae5', avatarColor:'#065f46',
    name:'Anika Nkosi', role:'UI/UX Designer', badge:'✦ Top Rated Plus', badgeCls:'lvl-top-rated-plus',
    rate:'$90/hr', location:'Berlin, Germany', rating:'★ 5.0', reviews:127,
    hireModal:'hire-anika',
    proposalFor:'Brand Designer — Full Identity Redesign',
    proposalAmount:'$5,800 fixed',
    history:[
      {from:'them', text:"Hi! I submitted my proposal for your brand redesign project. I\u2019d love to learn more about your vision for the 2026 rebrand \u2014 do you have any brand references or mood boards I could look at?", time:'1 hr ago'},
    ],
    placeholder:"Ask about their experience, timeline, availability…"
  })},

  'dm-james':{t:'Message James Kowalski',b:buildDmModal({
    initials:'JK', avatarBg:'#dbeafe', avatarColor:'#1e40af',
    name:'James Kowalski', role:'Full Stack Engineer', badge:'★ Expert-Vetted', badgeCls:'lvl-expert-vetted',
    rate:'$130/hr', location:'Toronto, Canada', rating:'★ 4.9', reviews:89,
    hireModal:'hire-james',
    proposalFor:'Senior React Developer — Analytics Dashboard',
    proposalAmount:'$130/hr',
    history:[
      {from:'them', text:"I just submitted my proposal \u2014 happy to hop on a quick call to walk you through my approach to real-time dashboards. I\u2019ve built 6 in the last 18 months and can share some live demos.", time:'3 hrs ago'},
    ],
    placeholder:"Ask about their tech stack, availability, or past projects…"
  })},

  'dm-sofia':{t:'Message Sofia Reyes',b:buildDmModal({
    initials:'SR', avatarBg:'#fef3c7', avatarColor:'#92400e',
    name:'Sofia Reyes', role:'AI/ML Engineer', badge:'↑ Rising Talent', badgeCls:'lvl-rising',
    rate:'$85/hr', location:'Mexico City', rating:'★ 4.7', reviews:22,
    hireModal:'hire-sofia',
    proposalFor:'Senior React Developer — Analytics Dashboard',
    proposalAmount:'$10,500 fixed',
    history:[
      {from:'them', text:"Thanks for posting this project! I submitted a proposal combining React on the frontend with FastAPI for real-time AI insights. I\u2019d be happy to share a short prototype I built for a similar use case \u2014 just let me know!", time:'5 hrs ago'},
    ],
    placeholder:"Ask about their AI/ML experience, approach, or availability…"
  })}
};

function openModal(id){
  const m=MODALS[id];
  if(!m){toast('Detail','Opening details...');return;}
  document.getElementById('mh-title').textContent=m.t;
  document.getElementById('mc-body').innerHTML=m.b;
  document.getElementById('overlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeModal(){
  document.getElementById('overlay').classList.remove('open');
  document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});

let tt;
function toast(title,msg){
  const el=document.getElementById('toast');
  document.getElementById('t-title').textContent=title;
  document.getElementById('t-msg').textContent=msg?(' — '+msg):'';
  el.classList.add('show');
  clearTimeout(tt);
  tt=setTimeout(()=>el.classList.remove('show'),3500);
}

// ── MOBILE SIDEBAR ──
function openMobSidebar(){
  document.querySelector('.sidebar').classList.add('mob-open');
  document.getElementById('sidebar-overlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeMobSidebar(){
  document.querySelector('.sidebar').classList.remove('mob-open');
  document.getElementById('sidebar-overlay').classList.remove('open');
  document.body.style.overflow='';
}

function setMobNav(id){
  document.querySelectorAll('.mob-nav-item').forEach(b=>b.classList.remove('active'));
  const btn=document.getElementById('mbn-'+id);
  if(btn) btn.classList.add('active');
}

function showPage(id,navEl){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.getElementById('page-'+id).classList.add('active');
  document.querySelectorAll('.sb-item').forEach(i=>i.classList.remove('active'));
  if(navEl)navEl.classList.add('active');
  const titles={home:'Home',jobs:'My Jobs',proposals:'Proposals',contracts:'Contracts',talent:'Talent',messages:'Messages',payments:'Payments',reports:'Reports'};
  document.getElementById('page-title').textContent=titles[id]||id;
  closeMobSidebar();
  // sync bottom nav
  const mobMap={home:'home',jobs:'jobs',proposals:'proposals',messages:'messages'};
  document.querySelectorAll('.mob-nav-item').forEach(b=>b.classList.remove('active'));
  const mbn=document.getElementById('mbn-'+(mobMap[id]||''));
  if(mbn) mbn.classList.add('active');
}

function setTab(el){
  el.closest('.tab-bar').querySelectorAll('.tab').forEach(t=>t.classList.remove('on'));
  el.classList.add('on');
}

// ─── UPWORK CATEGORY DATA ───
const UW_CATS = {
  "Accounting & Consulting": {
    "Personal & Professional Coaching": ["Career Coaching","Personal Coaching"],
    "Accounting & Bookkeeping": ["Accounting","Bookkeeping"],
    "Financial Planning": ["Financial Analysis & Modeling","Financial Management/CFO"],
    "Recruiting & Human Resources": ["HR Administration","Recruiting & Talent Sourcing","Training & Development"],
    "Management Consulting & Analysis": ["Business Analysis & Strategy","Instructional Design","Management Consulting"],
    "Other - Accounting & Consulting": ["Tax Preparation"]
  },
  "Admin Support": {
    "Data Entry & Transcription Services": ["Data Entry","Manual Transcription"],
    "Virtual Assistance": ["Executive Virtual Assistance","Legal Virtual Assistance","Medical Virtual Assistance","Ecommerce Management","Personal Virtual Assistance","General Virtual Assistance"],
    "Project Management": ["Business Project Management","Supply Chain & Logistics Project Management","Construction & Engineering Project Management","Development & IT Project Management","Healthcare Project Management","Digital Project Management"],
    "Market Research & Product Reviews": ["Web & Software Product Research","Market Research","General Research Services","Product Reviews","Qualitative Research","Quantitative Research"]
  },
  "Customer Service": {
    "Community Management & Tagging": ["Community Management","Content Moderation","Visual Tagging & Processing"],
    "Customer Service & Tech Support": ["Customer Onboarding","Email, Phone & Chat Support","Customer Success","IT Support","Tech Support"]
  },
  "Data Science & Analytics": {
    "Data Analysis & Testing": ["Data Analytics","Data Visualization","Experimentation & Testing"],
    "Data Extraction/ETL": ["Data Extraction","Data Processing"],
    "Data Mining & Management": ["Data Engineering","Data Mining"],
    "AI & Machine Learning": ["Generative AI Modeling","AI Data Annotation & Labeling","Deep Learning","Knowledge Representation","Machine Learning"]
  },
  "Design & Creative": {
    "Art & Illustration": ["Portraits & Caricatures","Cartoons & Comics","Fine Art","Illustration","Pattern Design"],
    "Audio & Music Production": ["AI Speech & Audio Generation","Audio Editing","Audio Production","Songwriting & Music Composition","Music Production"],
    "Branding & Logo Design": ["Brand Identity Design","Logo Design"],
    "NFT, AR/VR & Game Art": ["NFT Art","Game Art","AR/VR Design"],
    "Graphic, Editorial & Presentation Design": ["AI Image Generation & Editing","Art Direction","Creative Direction","Editorial Design","Graphic Design","Image Editing","Packaging Design","Presentation Design"],
    "Performing Arts": ["Acting","Music Performance","Singing","Voice Talent"],
    "Photography": ["Local Photography","Product Photography"],
    "Product Design": ["Fashion Design","Jewelry Design","Product & Industrial Design"],
    "Video & Animation": ["AI Video Generation & Editing","Motion Graphics","3D Animation","2D Animation","Video Editing","Videography","Video Production","Visual Effects"]
  },
  "Engineering & Architecture": {
    "Building & Landscape Architecture": ["Architectural Design","Landscape Architecture"],
    "Chemical Engineering": ["Chemical & Process Engineering"],
    "Civil & Structural Engineering": ["Building Information Modeling","Civil Engineering","Structural Engineering"],
    "Electrical & Electronic Engineering": ["Electrical Engineering","Electronic Engineering"],
    "Interior & Trade Show Design": ["Trade Show Design","Interior Design"],
    "Energy & Mechanical Engineering": ["Energy Engineering","Mechanical Engineering"],
    "Physical Sciences": ["Biology","Chemistry","Mathematics","Physics","STEM Tutoring"],
    "3D Modeling & CAD": ["CAD","3D Modeling & Rendering"],
    "Contract Manufacturing": ["Logistics & Supply Chain Management","Sourcing & Procurement"]
  },
  "IT & Networking": {
    "Database Management & Administration": ["Database Administration"],
    "ERP/CRM Software": ["Business Applications Development","Systems Engineering"],
    "Information Security & Compliance": ["IT Compliance","Information Security","Network Security"],
    "Network & System Administration": ["Network Administration","Systems Administration"],
    "DevOps & Solution Architecture": ["Cloud Engineering","DevOps Engineering","Solution Architecture"]
  },
  "Legal": {
    "Corporate & Contract Law": ["Business & Corporate Law","Intellectual Property Law","Paralegal Services"],
    "International & Immigration Law": ["Immigration Law","International Law"],
    "Finance & Tax Law": ["Securities & Finance Law","Tax Law"],
    "Public Law": ["Labor & Employment Law","Regulatory Law"]
  },
  "Sales & Marketing": {
    "Digital Marketing": ["Display Advertising","Campaign Management","Email Marketing","Marketing Automation","Search Engine Marketing","SEO","Social Media Marketing"],
    "Lead Generation & Telemarketing": ["Sales & Business Development","Lead Generation","Telemarketing"],
    "Marketing, PR & Brand Strategy": ["Brand Strategy","Content Strategy","Marketing Strategy","Public Relations","Social Media Strategy"]
  },
  "Translation": {
    "Language Tutoring & Interpretation": ["Live Interpretation","Sign Language Interpretation","Language Tutoring"],
    "Translation & Localization Services": ["Language Localization","Legal Document Translation","Medical Document Translation","Technical Document Translation","General Translation Services"]
  },
  "Web, Mobile & Software Dev": {
    "Blockchain, NFT & Cryptocurrency": ["Blockchain & NFT Development","Crypto Coins & Tokens","Crypto Wallet Development"],
    "AI Apps & Integration": ["AI Chatbot Development","AI Integration"],
    "Desktop Application Development": ["Desktop Software Development"],
    "Ecommerce Development": ["Ecommerce Website Development"],
    "Game Design & Development": ["Video Game Development"],
    "Mobile Development": ["Mobile App Development","Mobile Game Development"],
    "Other - Software Development": ["AR/VR Development","Database Development","Emerging Tech","Firmware Development","Coding Tutoring"],
    "Product Management & Scrum": ["Product Management","Scrum Leadership"],
    "QA Testing": ["Automation Testing","Manual Testing"],
    "Scripts & Utilities": ["Scripting & Automation"],
    "Web & Mobile Design": ["Mobile Design","Prototyping","UX/UI Design","Web Design"],
    "Web Development": ["Back-End Development","CMS Development","Front-End Development","Full Stack Development"]
  },
  "Writing": {
    "Sales & Marketing Copywriting": ["Ad & Email Copywriting","Marketing Copywriting","Sales Copywriting"],
    "Content Writing": ["Web & UX Writing","Article & Blog Writing","AI Content Writing","Creative Writing","Ghostwriting","Scriptwriting","Writing Tutoring"],
    "Editing & Proofreading Services": ["Proofreading","Copy Editing"],
    "Professional & Business Writing": ["Academic & Research Writing","Legal Writing","Medical Writing","Resume & Cover Letter Writing","Business & Proposal Writing","Grant Writing","Technical Writing"]
  }
};

function updateSubcats(){
  const cat = (document.getElementById('pj-cat')||{}).value;
  const subcatSel = document.getElementById('pj-subcat');
  const specSel = document.getElementById('pj-spec');
  const subcatWrap = document.getElementById('pj-subcat-wrap');
  const specWrap = document.getElementById('pj-spec-wrap');
  if(!cat){subcatWrap.style.display='none';specWrap.style.display='none';return;}
  const subcats = Object.keys(UW_CATS[cat]||{});
  subcatSel.innerHTML='<option value="">— Select a subcategory —</option>'+subcats.map(s=>`<option value="${s}">${s}</option>`).join('');
  subcatWrap.style.display='block';
  specSel.innerHTML='<option value="">— Select a specialty —</option>';
  specWrap.style.display='none';
}

function updateSpecialties(){
  const cat = (document.getElementById('pj-cat')||{}).value;
  const subcat = (document.getElementById('pj-subcat')||{}).value;
  const specSel = document.getElementById('pj-spec');
  const specWrap = document.getElementById('pj-spec-wrap');
  if(!cat||!subcat){specWrap.style.display='none';return;}
  const specs = (UW_CATS[cat]||{})[subcat]||[];
  specSel.innerHTML='<option value="">— Select a specialty —</option>'+specs.map(s=>`<option value="${s}">${s}</option>`).join('');
  specWrap.style.display='block';
}

function updatePostJobFields(){
  const v=(document.getElementById('pj-billing-type')||{}).value;
  ['fixed','hourly','monthly'].forEach(k=>{
    const el=document.getElementById('pj-'+k+'-fields');
    if(el)el.style.display=(k===v)?'block':'none';
  });
}
function submitPostJob(){
  toast('Posted! 🎉','Your job is live — expect proposals soon');
  closeModal();
}
function toggleHireFields(prefix){
  const sel=document.getElementById(prefix+'-contract-type');
  if(!sel)return;
  const v=sel.value;
  ['fixed','hourly','monthly'].forEach(k=>{
    const el=document.getElementById(prefix+'-'+k+'-fields');
    if(el)el.style.display=(k===v)?'block':'none';
  });
}

setTimeout(()=>toast('Welcome back, NexaFlow!','You have 4 unread messages and 12 new proposals'),1000);
</script>
</body>
</html>
