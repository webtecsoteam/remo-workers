<?php include __DIR__ . '/includes/header.php'; ?>
<body>


<div class="toast" id="toast"><strong id="t-title"></strong><span id="t-msg"></span></div>
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal"><div class="mh"><h2 id="mh-title">Detail</h2><div class="mclose" onclick="closeModal()">✕</div></div><div class="mc" id="mc-body"></div></div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar">
  <a class="sb-logo" href="<?php echo baseUrl(); ?>"><div class="sb-logo-mark">
      <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M17 2L31 10V24L17 32L3 24V10L17 2Z" fill="#c8f135" opacity="0.12"/>
        <path d="M17 2L31 10V24L17 32L3 24V10L17 2Z" stroke="#c8f135" stroke-width="1.2" stroke-linejoin="round"/>
        <text x="9.5" y="23" font-family="'Plus Jakarta Sans',sans-serif" font-size="16" font-weight="800" fill="#c8f135" letter-spacing="-1">R</text>
        <circle cx="27" cy="9" r="3.5" fill="#c8f135"/>
        <circle cx="27" cy="9" r="2" fill="#16281a"/>
      </svg>
    </div>
    <div class="sb-logo-wordmark">
      <span class="sb-logo-text">Remo<em>workers</em></span>
      <span class="sb-logo-tagline">Freelancer Platform</span>
    </div>
  </a>
  <div class="sb-user">
    <div class="sb-user-top">
      <div class="sb-av">AN</div>
      <div><div class="sb-name">Anika Nkosi</div><div class="sb-role">UI/UX Designer · Top Rated Plus</div></div>
    </div>
    <div class="sb-stats">
      <div class="sb-stat" onclick="toast('Job Success Score','96% based on last 24 months of contracts')"><div class="sb-stat-val">96%</div><div class="sb-stat-lbl">Job Success</div></div>
      <div class="sb-stat" onclick="openModal('connects')"><div class="sb-stat-val">42</div><div class="sb-stat-lbl">Connects</div></div>
      <div class="sb-stat" onclick="showPage('earnings',document.querySelector('[onclick*=earnings]'))"><div class="sb-stat-val">$14.2k</div><div class="sb-stat-lbl">Earned (May)</div></div>
      <div class="sb-stat" onclick="toast('Profile','Your profile is 78% complete')"><div class="sb-stat-val">★ 5.0</div><div class="sb-stat-lbl">Rating</div></div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Work</div>
    <div class="sb-item active" onclick="showPage('home',this)"><span class="sb-ico">🏠</span>Dashboard</div>
    <div class="sb-item" onclick="showPage('find-work',this)"><span class="sb-ico">🔍</span>Find Work</div>
    <div class="sb-item" onclick="showPage('proposals',this)"><span class="sb-ico">📩</span>My Proposals<span class="sb-badge green">8</span></div>
    <div class="sb-item" onclick="showPage('contracts',this)"><span class="sb-ico">🤝</span>My Contracts</div>
    <div class="sb-section">Manage</div>
    <div class="sb-item" onclick="showPage('messages',this)"><span class="sb-ico">💬</span>Messages<span class="sb-badge">3</span></div>
    <div class="sb-item" onclick="showPage('earnings',this)"><span class="sb-ico">💳</span>Earnings</div>
    <div class="sb-item" onclick="showPage('catalog',this)"><span class="sb-ico">📦</span>My Services</div>
    <div class="sb-item" onclick="showPage('profile',this)"><span class="sb-ico">👤</span>My Profile</div>
    <div class="sb-section">Tools</div>
    <div class="sb-item" onclick="showPage('reports',this)"><span class="sb-ico">📊</span>Payment Reports</div>
    <div class="sb-item" onclick="showPage('verification',this)"><span class="sb-ico">🪪</span>ID Verification<span class="sb-badge" style="background:#f59e0b">!</span></div>
    <div class="sb-item" onclick="openModal('connects')"><span class="sb-ico">🔗</span>Connects (42)</div>
    <div class="sb-item" onclick="toast('Help','Loading help center...')"><span class="sb-ico">❓</span>Help Center</div>
  </nav>
  <div class="sb-footer">
    <a onclick="toast('Availability','Set to Available for Work')">🟢 Available for Work</a>
    <a onclick="openModal('change-password')">🔑 Change Password</a>
    <a href="<?php echo baseUrl(); ?>">🚪 Log Out</a>
  </div>
</aside>

<!-- Mobile sidebar overlay -->
<div class="mob-sidebar-overlay" id="mob-overlay" onclick="closeMobSidebar()"></div>

<!-- Mobile bottom nav -->
<nav class="mob-nav">
  <div class="mob-nav-inner">
    <button class="mob-nav-item active" id="mn-home" onclick="showPage('home',null);setMobNav('home');closeMobSidebar()">
      <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H5a1 1 0 01-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>
      Home
    </button>
    <button class="mob-nav-item" id="mn-find-work" onclick="showPage('find-work',null);setMobNav('find-work');closeMobSidebar()">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      Jobs
    </button>
    <button class="mob-nav-item" id="mn-contracts" onclick="showPage('contracts',null);setMobNav('contracts');closeMobSidebar()">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Contracts
    </button>
    <button class="mob-nav-item" id="mn-messages" onclick="showPage('messages',null);setMobNav('messages');closeMobSidebar()">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      <span class="mob-nav-badge">3</span>
      Messages
    </button>
    <button class="mob-nav-item" id="mn-earnings" onclick="showPage('earnings',null);setMobNav('earnings');closeMobSidebar()">
      <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      Earnings
    </button>
    <button class="mob-nav-item" id="mn-profile" onclick="showPage('profile',null);setMobNav('profile');closeMobSidebar()">
      <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profile
    </button>
  </div>
</nav>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <button class="mob-menu-btn" onclick="toggleMobSidebar()" aria-label="Menu">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="mob-topbar-logo">
      <svg width="26" height="26" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M17 2L31 10V24L17 32L3 24V10L17 2Z" fill="#14a800" opacity="0.12"/>
        <path d="M17 2L31 10V24L17 32L3 24V10L17 2Z" stroke="#14a800" stroke-width="1.5" stroke-linejoin="round"/>
        <text x="9.5" y="23" font-family="'Plus Jakarta Sans',sans-serif" font-size="16" font-weight="800" fill="#14a800" letter-spacing="-1">R</text>
        <circle cx="27" cy="9" r="3.5" fill="#14a800"/>
        <circle cx="27" cy="9" r="2" fill="white"/>
      </svg>
      <span style="font-size:16px;font-weight:800;color:var(--forest);letter-spacing:-.4px">Remo<span style="color:var(--g)">workers</span></span>
    </div>
    <div class="tb-title" id="page-title">Dashboard</div>
    <div class="tb-search"><span class="tb-search-ico">🔍</span><input type="text" placeholder="Search jobs, clients, messages…" onfocus="showPage('find-work',document.querySelector('[onclick*=find-work]'))"></div>
    <div class="tb-actions">
      <div class="tb-ico-btn" onclick="toast('Notifications','You have 3 unread notifications')">🔔<div class="tb-notif-dot"></div></div>
      <div class="tb-ico-btn" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">💬</div>
      <button class="btn btn-g btn-sm" onclick="showPage('find-work',document.querySelector('[onclick*=find-work]'))">🔍 Find Work</button>
      <div class="tb-av" onclick="showPage('profile',document.querySelector('[onclick*=profile]'))">AN</div>
    </div>
  </div>

  <!-- Mobile search bar -->
  <div class="mob-searchbar">
    <div class="mob-searchbar-wrap">
      <span class="mob-searchbar-ico">🔍</span>
      <input type="text" placeholder="Search jobs, clients, messages…" onfocus="showPage('find-work',document.querySelector('[onclick*=find-work]'))">
    </div>
  </div>

  <div class="content">

    <!-- HOME -->
    <div class="page active" id="page-home">
      <!-- Mobile greeting -->
      <div style="padding:16px 16px 0;display:none" class="mob-greeting">
        <div style="font-size:18px;font-weight:700;margin-bottom:2px">Good morning, Anika 👋</div>
        <div style="font-size:13px;color:var(--muted)">Here's your work overview</div>
      </div>
      <!-- Stats -->
      <div class="stat-row">
        <div class="stat-c" onclick="showPage('earnings',document.querySelector('[onclick*=earnings]'))">
          <div class="stat-label">Earnings This Month <span>💰</span></div>
          <div class="stat-val">$14,210</div>
          <div class="stat-sub up">↑ 23% vs last month</div>
        </div>
        <div class="stat-c" onclick="showPage('contracts',document.querySelector('[onclick*=contracts]'))">
          <div class="stat-label">Active Contracts <span>🤝</span></div>
          <div class="stat-val">3</div>
          <div class="stat-sub">2 hourly · 1 fixed</div>
        </div>
        <div class="stat-c" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'))">
          <div class="stat-label">Pending Proposals <span>📩</span></div>
          <div class="stat-val">8</div>
          <div class="stat-sub">Waiting for client response</div>
        </div>
        <div class="stat-c" onclick="openModal('connects')">
          <div class="stat-label">Connects Left <span>🔗</span></div>
          <div class="stat-val">42</div>
          <div class="stat-sub">Buy more · Earn more</div>
        </div>
      </div>

      <div class="g2">
        <!-- Active Contracts -->
        <div class="card">
          <div class="card-head"><h3>Active Contracts</h3><button class="btn btn-w btn-sm" onclick="showPage('contracts',document.querySelector('[onclick*=contracts]'))">View all</button></div>
          <div class="card-body" style="padding:0 18px">
            <div class="contract-row">
              <div class="av" style="background:#dbeafe;color:#1e40af">NX</div>
              <div class="cr-info"><div class="cr-title">UI/UX Redesign — Dashboard</div><div class="cr-sub">NexaFlow Inc. · Hourly · Active</div></div>
              <div class="cr-amount">$90/hr<span>34.5 hrs logged</span></div>
            </div>
            <div class="contract-row">
              <div class="av" style="background:#d1fae5;color:#065f46">FT</div>
              <div class="cr-info"><div class="cr-title">Mobile App Redesign (iOS)</div><div class="cr-sub">FinTech Co. · Fixed · Milestone 1/2</div></div>
              <div class="cr-amount">$4,500<span>$2,250 released</span></div>
            </div>
            <div class="contract-row">
              <div class="av" style="background:#fef3c7;color:#92400e">DS</div>
              <div class="cr-info"><div class="cr-title">Design System — SaaS Platform</div><div class="cr-sub">DataStack · Hourly · Active</div></div>
              <div class="cr-amount">$95/hr<span>18 hrs logged</span></div>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div class="card">
          <div class="card-head"><h3>Messages</h3><button class="btn btn-w btn-sm" onclick="showPage('messages',document.querySelector('[onclick*=messages]'))">View all</button></div>
          <div class="card-body" style="padding:0 12px">
            <div class="msg-item unread" onclick="openModal('msg-nexaflow')"><div class="av" style="background:#dbeafe;color:#1e40af">NX</div><div class="msg-meta"><div class="msg-name">NexaFlow Inc.<span class="msg-time">15m</span></div><div class="msg-text">Love the screens! Can we hop on a call?</div></div><div class="msg-dot"></div></div>
            <div class="msg-item unread" onclick="toast('Message','Opening FinTech Co conversation')"><div class="av" style="background:#d1fae5;color:#065f46">FT</div><div class="msg-meta"><div class="msg-name">FinTech Co.<span class="msg-time">2h</span></div><div class="msg-text">Milestone 1 looks great — approved!</div></div><div class="msg-dot"></div></div>
            <div class="msg-item" onclick="toast('Message','Opening DataStack conversation')"><div class="av" style="background:#fef3c7;color:#92400e">DS</div><div class="msg-meta"><div class="msg-name">DataStack<span class="msg-time">Yesterday</span></div><div class="msg-text">When can you start on the component library?</div></div></div>
            <div class="msg-item" onclick="toast('Message','Remoworkers notification')"><div class="av" style="background:var(--gl);color:var(--g)">WB</div><div class="msg-meta"><div class="msg-name">Remoworkers<span class="msg-time">2d</span></div><div class="msg-text">You've earned the Top Rated Plus badge! 🎉</div></div></div>
          </div>
        </div>
      </div>

      <!-- Recommended Jobs -->
      <div class="sec-header" style="margin-top:6px">
        <div class="sec-h">Recommended Jobs <span style="background:var(--gl);color:var(--g);font-size:11px;padding:2px 8px;border-radius:4px;margin-left:8px;font-weight:600">AI Matched</span></div>
        <button class="sec-link" onclick="showPage('find-work',document.querySelector('[onclick*=find-work]'))">Browse all jobs →</button>
      </div>
      <div id="home-job-list"></div>

      <!-- Earnings Chart -->
      <div class="card" style="margin-top:6px">
        <div class="card-head"><h3>Earnings Overview</h3><button class="sec-link" onclick="showPage('earnings',document.querySelector('[onclick*=earnings]'))">Full breakdown →</button></div>
        <div class="card-body">
          <div style="font-size:22px;font-weight:700;margin-bottom:4px">$14,210 <span style="font-size:13px;font-weight:400;color:var(--muted)">May 2026</span></div>
          <div class="chart-area">
            <div class="chart-bars">
              <div class="chart-bar" style="height:42%" onclick="toast('November','$5,900 earned')"></div>
              <div class="chart-bar" style="height:55%" onclick="toast('December','$7,700 earned')"></div>
              <div class="chart-bar" style="height:64%" onclick="toast('January','$8,960 earned')"></div>
              <div class="chart-bar" style="height:72%" onclick="toast('February','$10,080 earned')"></div>
              <div class="chart-bar" style="height:83%" onclick="toast('March','$11,620 earned')"></div>
              <div class="chart-bar" style="height:82%" onclick="toast('April','$11,500 earned')"></div>
              <div class="chart-bar active" style="height:100%" onclick="toast('May (current)','$14,210 earned so far')"></div>
            </div>
            <div class="chart-labels"><div class="chart-lbl">Nov</div><div class="chart-lbl">Dec</div><div class="chart-lbl">Jan</div><div class="chart-lbl">Feb</div><div class="chart-lbl">Mar</div><div class="chart-lbl">Apr</div><div class="chart-lbl">May</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- FIND WORK -->
    <div class="page" id="page-find-work">
      <div style="font-size:20px;font-weight:700;margin-bottom:14px">Find Work</div>
      <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <input type="text" placeholder="Search jobs by keyword, skill, or category…" style="flex:1;min-width:240px;padding:9px 13px;border:1px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none">
        <select style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:white;outline:none" onchange="toast('Filter','Results filtered')"><option>Any Experience Level</option><option>Entry</option><option>Intermediate</option><option>Expert</option></select>
        <select style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:white;outline:none" onchange="toast('Filter','Results filtered')"><option>Any Job Type</option><option>Fixed Price</option><option>Hourly</option></select>
        <select style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:white;outline:none" onchange="toast('Filter','Results filtered')"><option>Any Budget</option><option>$1–$500</option><option>$500–$5,000</option><option>$5,000+</option></select>
        <button class="btn btn-g" onclick="toast('Search','Showing 1,240 matching jobs')">Search</button>
      </div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">Best Matches</div>
        <div class="tab" onclick="setTab(this)">Most Recent</div>
        <div class="tab" onclick="setTab(this)">Saved Jobs (4)</div>
      </div>
      <div id="findwork-job-list"></div>
    </div>

    <!-- PROPOSALS -->
    <div class="page" id="page-proposals">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">My Proposals <span style="font-size:14px;font-weight:400;color:var(--muted)">(8 active)</span></div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">Active (8)</div>
        <div class="tab" onclick="setTab(this)">Archived (5)</div>
        <div class="tab" onclick="setTab(this)">Accepted (3)</div>
      </div>
      <table class="tbl" style="background:white;border-radius:10px;overflow:hidden;border:1px solid var(--border)">
        <thead><tr><th>Job Title</th><th>Client</th><th>Your Bid</th><th>Connects Used</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <tr><td class="cl" onclick="openModal('job-a')">Senior UI/UX Designer — Fintech SaaS</td><td>ClearPath Finance</td><td>$7,500 fixed</td><td>6</td><td>May 13</td><td><span class="badge b-yellow">Viewed by client</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Withdrawn','Proposal removed')">Withdraw</button></td></tr>
          <tr><td class="cl" onclick="openModal('job-b')">Product Designer — Mobile App</td><td>Launchpad HQ</td><td>$90/hr</td><td>4</td><td>May 11</td><td><span class="badge b-blue">Pending</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Withdrawn','Proposal removed')">Withdraw</button></td></tr>
          <tr><td class="cl" onclick="toast('Job','Viewing Webflow website proposal')">Webflow Website Build</td><td>Bloom Agency</td><td>$3,200 fixed</td><td>4</td><td>May 10</td><td><span class="badge b-yellow">Viewed by client</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Withdrawn','Proposal removed')">Withdraw</button></td></tr>
          <tr><td>Design System Audit</td><td>CoreStack</td><td>$85/hr</td><td>4</td><td>May 8</td><td><span class="badge b-blue">Pending</span></td><td><button class="btn btn-w btn-sm" onclick="toast('Withdrawn','Proposal removed')">Withdraw</button></td></tr>
          <tr><td>App Onboarding UX</td><td>GrowFast</td><td>$2,800 fixed</td><td>2</td><td>May 6</td><td><span class="badge b-green">Interview invited</span></td><td><button class="btn btn-g btn-sm" onclick="toast('Accepted!','Interview accepted — check messages')">Accept Interview</button></td></tr>
        </tbody>
      </table>
    </div>

    <!-- CONTRACTS -->
    <div class="page" id="page-contracts">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">My Contracts <span style="font-size:14px;font-weight:400;color:var(--muted)">(3 active)</span></div>
      <div class="tab-bar">
        <div class="tab on" onclick="setTab(this)">Active (3)</div>
        <div class="tab" onclick="setTab(this)">Completed (18)</div>
        <div class="tab" onclick="setTab(this)">Paused</div>
      </div>
      <table class="tbl" style="background:white;border-radius:10px;overflow:hidden;border:1px solid var(--border)">
        <thead><tr><th>Client</th><th>Project</th><th>Type</th><th>Earnings</th><th>Progress</th><th>Started</th><th>Action</th></tr></thead>
        <tbody>
          <tr>
            <td><div style="display:flex;align-items:center;gap:8px"><div class="av" style="background:#dbeafe;color:#1e40af">NX</div><div><div style="font-weight:600">NexaFlow Inc.</div><div style="font-size:11px;color:var(--muted)">★ 5.0 · Verified</div></div></div></td>
            <td>UI/UX Dashboard Redesign</td><td><span class="badge b-blue">Hourly</span></td>
            <td><div style="font-weight:700">$3,105</div><div style="font-size:11px;color:var(--muted)">34.5 hrs · $90/hr</div></td>
            <td><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Active · No end date</div><div class="progress-bar"><div class="progress-fill" style="width:60%"></div></div></td>
            <td>Apr 28</td><td><button class="btn btn-w btn-sm" onclick="openModal('contract-detail')">Details</button></td>
          </tr>
          <tr>
            <td><div style="display:flex;align-items:center;gap:8px"><div class="av" style="background:#d1fae5;color:#065f46">FT</div><div><div style="font-weight:600">FinTech Co.</div><div style="font-size:11px;color:var(--muted)">★ 4.9 · Verified</div></div></div></td>
            <td>Mobile App Redesign (iOS)</td><td><span class="badge b-purple">Fixed</span></td>
            <td><div style="font-weight:700">$4,500</div><div style="font-size:11px;color:var(--muted)">$2,250 earned</div></td>
            <td><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Milestone 1 of 2 done</div><div class="progress-bar"><div class="progress-fill" style="width:50%"></div></div></td>
            <td>May 1</td><td><button class="btn btn-g btn-sm" onclick="openModal('fintech-milestones')">View Milestones</button></td>
          </tr>
          <tr>
            <td><div style="display:flex;align-items:center;gap:8px"><div class="av" style="background:#fef3c7;color:#92400e">DS</div><div><div style="font-weight:600">DataStack</div><div style="font-size:11px;color:var(--muted)">★ 4.8 · Verified</div></div></div></td>
            <td>Design System Build</td><td><span class="badge b-blue">Hourly</span></td>
            <td><div style="font-weight:700">$1,710</div><div style="font-size:11px;color:var(--muted)">18 hrs · $95/hr</div></td>
            <td><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Active · Ongoing</div><div class="progress-bar"><div class="progress-fill" style="width:35%"></div></div></td>
            <td>May 5</td><td><button class="btn btn-w btn-sm" onclick="toast('Work diary','Opening time tracker...')">Log Time</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MESSAGES -->
    <div class="page" id="page-messages">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">Messages</div>
      <div style="background:white;border:1px solid var(--border);border-radius:10px;display:flex;min-height:420px;overflow:hidden">
        <div style="width:260px;border-right:1px solid var(--border);flex-shrink:0">
          <div style="padding:12px 14px;border-bottom:1px solid var(--border)"><input style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:6px;font-size:12.5px;font-family:inherit;outline:none" placeholder="Search messages…"></div>
          <div style="padding:6px 0">
            <div class="msg-item unread" style="border-radius:0;margin:0;padding:12px 14px"><div class="av" style="background:#dbeafe;color:#1e40af">NX</div><div class="msg-meta"><div class="msg-name">NexaFlow Inc.<span class="msg-time">15m</span></div><div class="msg-text">Love the screens! Can we hop on a call?</div></div><div class="msg-dot"></div></div>
            <div class="msg-item unread" style="border-radius:0;margin:0;padding:12px 14px"><div class="av" style="background:#d1fae5;color:#065f46">FT</div><div class="msg-meta"><div class="msg-name">FinTech Co.<span class="msg-time">2h</span></div><div class="msg-text">Milestone 1 approved — great work!</div></div><div class="msg-dot"></div></div>
            <div class="msg-item" style="border-radius:0;margin:0;padding:12px 14px"><div class="av" style="background:#fef3c7;color:#92400e">DS</div><div class="msg-meta"><div class="msg-name">DataStack<span class="msg-time">1d</span></div><div class="msg-text">When can you start on the component library?</div></div></div>
          </div>
        </div>
        <div style="flex:1;display:flex;flex-direction:column">
          <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px"><div class="av" style="background:#dbeafe;color:#1e40af">NX</div><div><div style="font-size:13.5px;font-weight:700">NexaFlow Inc.</div><div style="font-size:11.5px;color:var(--muted)">Active contract · ★ 5.0</div></div><div style="margin-left:auto;display:flex;gap:8px"><button class="btn btn-w btn-sm" onclick="toast('Video call','Starting Remoworkers meeting...')">📹 Video Call</button><button class="btn btn-w btn-sm" onclick="openModal('contract-detail')">🤝 Contract</button></div></div>
          <div style="flex:1;padding:18px;display:flex;flex-direction:column;gap:12px;overflow-y:auto;max-height:280px">
            <div style="display:flex;gap:10px;flex-direction:row-reverse"><div class="tb-av" style="flex-shrink:0">AN</div><div style="background:var(--g);color:white;border-radius:10px 10px 0 10px;padding:10px 14px;max-width:320px;font-size:13px;line-height:1.6">Hi! I've completed the first 6 dashboard screens — they're in the Figma file ready for review. I also set up the component library with tokens for spacing, color, and typography.</div></div>
            <div style="display:flex;gap:10px"><div class="av" style="background:#dbeafe;color:#1e40af;flex-shrink:0">NX</div><div style="background:var(--off);border-radius:10px 10px 10px 0;padding:10px 14px;max-width:320px;font-size:13px;line-height:1.6">These look amazing! Love the new navigation pattern especially. Can we hop on a 20-minute call to discuss the mobile variants?</div></div>
          </div>
          <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px"><input style="flex:1;padding:9px 12px;border:1px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none" placeholder="Type a message…" onkeydown="if(event.key==='Enter')toast('Message sent','Your message has been delivered')"><button class="btn btn-g" onclick="toast('Message sent','Your message has been delivered')">Send</button></div>
        </div>
      </div>
    </div>

    <!-- EARNINGS -->
    <div class="page" id="page-earnings">
      <div style="font-size:20px;font-weight:700;margin-bottom:6px">Earnings & Payments</div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:20px">All times in your local timezone · Hourly billing week ends Sunday midnight UTC</div>

      <!-- 4-column status row -->
      <div style="background:white;border:1px solid var(--border);border-radius:12px;margin-bottom:20px;overflow:hidden">
        <div style="padding:18px 22px 14px;border-bottom:1px solid var(--border)"><div style="font-size:17px;font-weight:700">Overview</div></div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid var(--border)" class="earn-overview-grid">
          <div style="padding:20px 22px;border-right:1px solid var(--border);cursor:pointer" onclick="showEarningsInfo('wip')">
            <div style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">Work in progress <span title="Hours logged this week, not yet billed" style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;border:1.5px solid var(--muted2);font-size:10px;color:var(--muted2);cursor:help;flex-shrink:0">?</span></div>
            <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--dark)">$765.00</div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:5px">8.5 hrs · NexaFlow Inc.</div>
          </div>
          <div style="padding:20px 22px;border-right:1px solid var(--border);cursor:pointer" onclick="showEarningsInfo('review')">
            <div style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">In review <span title="5-day dispute window open" style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;border:1.5px solid var(--muted2);font-size:10px;color:var(--muted2);cursor:help;flex-shrink:0">?</span></div>
            <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--dark)">$1,350.00</div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:5px">Closes May 16</div>
          </div>
          <div style="padding:20px 22px;border-right:1px solid var(--border);cursor:pointer" onclick="showEarningsInfo('pending')">
            <div style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">Pending <span title="5-day security hold" style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;border:1.5px solid var(--muted2);font-size:10px;color:var(--muted2);cursor:help;flex-shrink:0">?</span></div>
            <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--dark)">$2,550.00</div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:5px">$500 bonus · clears May 18</div>
          </div>
          <div style="padding:20px 22px;cursor:pointer" onclick="showEarningsInfo('available')">
            <div style="font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">Available</div>
            <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--g)">$12,800.00</div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:5px">Last: $2,950.00</div>
          </div>
        </div>
        <div id="earnings-info-panel" style="display:none;padding:16px 22px;background:var(--off);border-top:1px solid var(--border);font-size:13px;color:var(--dark3);line-height:1.7"></div>
      </div>

      <!-- Withdraw -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-head"><h3>Withdraw Earnings</h3></div>
        <div class="card-body">
          <div class="g2">
            <div>
              <div style="font-size:13px;font-weight:600;margin-bottom:8px">Available: $12,800.00</div>
              <select style="width:100%;padding:8px 11px;border:1px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none;margin-bottom:10px">
                <option>Direct Bank Transfer (ACH)</option><option>PayPal</option><option>Payoneer</option><option>Wire Transfer</option><option>Local Payment Method</option>
              </select>
              <button class="btn btn-g" style="width:100%;justify-content:center;padding:10px" onclick="toast('Withdrawal initiated','$12,800 will arrive in 3–5 business days')">Withdraw $12,800 →</button>
            </div>
            <div style="background:var(--off);border-radius:9px;padding:14px">
              <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Service Fees</div>
              <div style="font-size:13px;color:var(--dark3);line-height:1.8">Lifetime: <strong>$82,400</strong><br>Fee tier: <strong style="color:var(--g)">5%</strong> · Below 5% at $100K<br><span style="font-size:12px;color:var(--muted)">You keep 95% above $10K/client</span></div>
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div style="font-size:14px;font-weight:700">Transaction History</div>
        <button class="btn btn-w btn-sm" onclick="showPage('reports',document.querySelector('[onclick*=reports]'))">📊 Full Payment Reports →</button>
      </div>
      <div class="card">
        <table class="tbl">
          <thead><tr><th>Date</th><th>Description</th><th>Client</th><th>Gross</th><th>Fee</th><th>Net</th><th>Status</th></tr></thead>
          <tbody>
            <tr style="background:#f8fafc"><td>This week</td><td>Hourly — 8.5 hrs (in progress)</td><td>NexaFlow Inc.</td><td>$765.00</td><td>—</td><td>—</td><td><span class="badge" style="background:#e2e8f0;color:#475569">In Progress</span></td></tr>
            <tr style="background:#fffbeb"><td>May 11</td><td>Hourly billing — 15 hrs</td><td>DataStack</td><td>$1,350.00</td><td>$68</td><td>$1,283</td><td><span class="badge" style="background:#fef3c7;color:#92400e">In Review</span></td></tr>
            <tr style="background:#eff6ff"><td>May 12</td><td>🎁 Bonus payment</td><td>NexaFlow Inc.</td><td>$500.00</td><td>$0</td><td>$500.00</td><td><span class="badge b-blue">Pending</span></td></tr>
            <tr style="background:#eff6ff"><td>May 9</td><td>Hourly billing — 23 hrs</td><td>NexaFlow Inc.</td><td>$2,070.00</td><td>$104</td><td>$1,966</td><td><span class="badge b-blue">Pending</span></td></tr>
            <tr><td>May 12</td><td>Hourly billing (34.5 hrs)</td><td>NexaFlow Inc.</td><td>$3,105</td><td>$155</td><td>$2,950</td><td><span class="badge b-green">Paid</span></td></tr>
            <tr><td>May 8</td><td>Milestone 1 — Mobile App</td><td>FinTech Co.</td><td>$2,250</td><td>$113</td><td>$2,137</td><td><span class="badge b-green">Paid</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         PAYMENT REPORTS PAGE
    ══════════════════════════════════════════════════ -->
    <div class="page" id="page-reports">

      <!-- Page header -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <div>
          <div style="font-size:20px;font-weight:700;margin-bottom:4px">Payment Reports</div>
          <div style="font-size:13px;color:var(--muted)">Full breakdown of earnings, bonuses, fees & withdrawals</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <select id="rpt-period" onchange="renderReports()" style="padding:7px 11px;border:1px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none;background:white;cursor:pointer">
            <option value="may2026">May 2026</option>
            <option value="apr2026">April 2026</option>
            <option value="mar2026">March 2026</option>
            <option value="q1">Q1 2026 (Jan–Mar)</option>
            <option value="2025">Full Year 2025</option>
          </select>
          <button class="btn btn-w btn-sm" onclick="exportReportCSV()">⬇ Export CSV</button>
          <button class="btn btn-w btn-sm" onclick="toast('PDF','Generating PDF statement...')">🖨 Print / PDF</button>
        </div>
      </div>

      <!-- KPI summary cards -->
      <div id="rpt-kpi-row" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px"></div>

      <!-- 2-column: bar chart + bonus breakdown -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

        <!-- Weekly earnings bar chart -->
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3 id="rpt-chart-title">Weekly Earnings — May 2026</h3></div>
          <div class="card-body">
            <div id="rpt-bar-chart" style="display:flex;align-items:flex-end;gap:6px;height:110px;padding-bottom:4px"></div>
            <div id="rpt-bar-labels" style="display:flex;gap:6px;margin-top:6px"></div>
            <div style="display:flex;gap:16px;margin-top:12px;font-size:12px">
              <div style="display:flex;align-items:center;gap:5px"><div style="width:10px;height:10px;border-radius:2px;background:var(--g)"></div>Hourly</div>
              <div style="display:flex;align-items:center;gap:5px"><div style="width:10px;height:10px;border-radius:2px;background:#8b5cf6"></div>Fixed</div>
              <div style="display:flex;align-items:center;gap:5px"><div style="width:10px;height:10px;border-radius:2px;background:#f59e0b"></div>Bonus</div>
            </div>
          </div>
        </div>

        <!-- Bonus payments panel -->
        <div class="card" style="margin-bottom:0">
          <div class="card-head">
            <h3>🎁 Bonus Payments</h3>
            <span id="rpt-bonus-total" style="font-size:13px;font-weight:700;color:#8b5cf6"></span>
          </div>
          <div id="rpt-bonus-list" class="card-body" style="padding:0"></div>
        </div>
      </div>

      <!-- Earnings by client donut + by type -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

        <!-- By client -->
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Earnings by Client</h3></div>
          <div class="card-body" id="rpt-client-breakdown"></div>
        </div>

        <!-- By payment type -->
        <div class="card" style="margin-bottom:0">
          <div class="card-head"><h3>Earnings by Type</h3></div>
          <div class="card-body" id="rpt-type-breakdown"></div>
        </div>
      </div>

      <!-- Full transaction ledger -->
      <div class="card">
        <div class="card-head">
          <h3>Transaction Ledger</h3>
          <div style="display:flex;gap:8px;align-items:center">
            <select id="rpt-type-filter" onchange="renderLedger()" style="padding:5px 9px;border:1px solid var(--border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;background:white">
              <option value="all">All types</option>
              <option value="hourly">Hourly</option>
              <option value="fixed">Fixed / Milestone</option>
              <option value="bonus">Bonus</option>
              <option value="withdrawal">Withdrawal</option>
              <option value="refund">Refund</option>
            </select>
            <select id="rpt-status-filter" onchange="renderLedger()" style="padding:5px 9px;border:1px solid var(--border);border-radius:6px;font-size:12px;font-family:inherit;outline:none;background:white">
              <option value="all">All statuses</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="review">In Review</option>
              <option value="progress">In Progress</option>
              <option value="withdrawn">Withdrawn</option>
            </select>
            <span id="rpt-ledger-count" style="font-size:12px;color:var(--muted)"></span>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table class="tbl" style="min-width:700px">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Client</th>
                <th>Gross</th>
                <th>Fee (5%)</th>
                <th>Net</th>
                <th>Status</th>
                <th>Ref</th>
              </tr>
            </thead>
            <tbody id="rpt-ledger-body"></tbody>
          </table>
        </div>
        <!-- Totals footer -->
        <div id="rpt-ledger-footer" style="padding:12px 18px;background:var(--off);border-top:1.5px solid var(--border);display:flex;gap:28px;font-size:13px;flex-wrap:wrap"></div>
      </div>

    </div>

    <!-- MY SERVICES (CATALOG) -->
    <div class="page" id="page-catalog">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px"><div style="font-size:20px;font-weight:700">My Services</div><button class="btn btn-g btn-lg" onclick="openModal('new-service')">+ Add a Service</button></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
        <div style="background:white;border:1px solid var(--border);border-radius:10px;overflow:hidden;cursor:pointer;transition:all .2s" onclick="openModal('service-1')" onmouseover="this.style.borderColor='#14a800'" onmouseout="this.style.borderColor='#e2e8e0'">
          <div style="height:100px;background:#e8f5e3;display:flex;align-items:center;justify-content:center;font-size:44px">🎨</div>
          <div style="padding:14px 16px"><div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--g);margin-bottom:5px">Design</div><div style="font-size:14px;font-weight:700;margin-bottom:8px">UI/UX Design System Build</div><div style="font-size:12px;color:var(--muted);margin-bottom:10px">Complete design system: components, tokens, Figma library, usage guidelines.</div><div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:10px"><span style="font-size:12.5px;color:var(--muted)">From</span><span style="font-size:15px;font-weight:700">$1,800</span></div></div>
        </div>
        <div style="background:white;border:1px solid var(--border);border-radius:10px;overflow:hidden;cursor:pointer;transition:all .2s" onclick="openModal('service-1')" onmouseover="this.style.borderColor='#14a800'" onmouseout="this.style.borderColor='#e2e8e0'">
          <div style="height:100px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:44px">📱</div>
          <div style="padding:14px 16px"><div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--g);margin-bottom:5px">Design</div><div style="font-size:14px;font-weight:700;margin-bottom:8px">Mobile App UI — iOS/Android</div><div style="font-size:12px;color:var(--muted);margin-bottom:10px">Full mobile app UI design with interactive prototype, developer specs, and assets.</div><div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:10px"><span style="font-size:12.5px;color:var(--muted)">From</span><span style="font-size:15px;font-weight:700">$2,400</span></div></div>
        </div>
        <div style="background:white;border:2px dashed var(--border);border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px;cursor:pointer;transition:border-color .2s;min-height:200px" onclick="openModal('new-service')" onmouseover="this.style.borderColor='#14a800'" onmouseout="this.style.borderColor='#e2e8e0'">
          <div style="font-size:36px;margin-bottom:10px">➕</div>
          <div style="font-size:14px;font-weight:700;margin-bottom:4px">Add a Service</div>
          <div style="font-size:12.5px;color:var(--muted);text-align:center">Create a package clients can buy instantly</div>
        </div>
      </div>
    </div>

    <!-- PROFILE -->
    <div class="page" id="page-profile">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div style="font-size:20px;font-weight:700">My Profile</div>
        <button class="btn btn-w btn-sm" onclick="openModal('edit-profile')">✏️ Edit Profile</button>
      </div>

      <!-- Profile header card -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-body">
          <div style="display:flex;gap:16px;align-items:flex-start">
            <div style="width:68px;height:68px;border-radius:50%;background:#c8f135;color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;flex-shrink:0">AN</div>
            <div style="flex:1;min-width:0">
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <div>
                  <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:pointer;group" onclick="openModal('edit-profile')" title="Click to edit profile">
                    <div style="font-size:19px;font-weight:700">Anika Nkosi</div>
                    <span style="font-size:11px;color:var(--g);border:1px solid var(--g);border-radius:4px;padding:1px 7px;font-weight:600;opacity:0.7">✏️ Edit</span>
                  </div>
                  <!-- Subtitle row — click opens edit modal -->
                  <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-bottom:8px;cursor:pointer" onclick="openModal('edit-profile')" title="Click to edit profile">
                    <span id="field-title" style="font-size:13.5px;color:var(--muted)">Senior UI/UX Designer</span>
                    <span style="color:var(--border);font-size:13px">·</span>
                    <span id="field-rate" style="font-size:13.5px;color:var(--muted)">$90/hr</span>
                    <span style="color:var(--border);font-size:13px">·</span>
                    <span id="field-location" style="font-size:13.5px;color:var(--muted)">🇩🇪 Berlin, Germany</span>
                  </div>
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <span class="badge b-green">✦ Top Rated Plus</span>
                    <span class="badge b-gray">✓ ID Verified</span>
                    <span class="badge b-blue">🟢 Available</span>
                    <span class="badge b-gray">★ 5.0 · 48 reviews</span>
                  </div>
                </div>
                <div style="display:flex;gap:12px;flex-shrink:0">
                  <div style="text-align:center">
                    <div class="jss-ring" style="margin:0 auto 4px"><div class="jss-inner">96%</div></div>
                    <div style="font-size:10px;color:var(--muted)">JSS</div>
                  </div>
                  <div style="text-align:center">
                    <div class="profile-ring" style="width:56px;height:56px;margin:0 auto 4px" onclick="toast('Profile','78% complete — add portfolio to reach 90%')"><div class="profile-ring-inner" style="width:44px;height:44px"><div class="profile-ring-val" style="font-size:14px">78%</div><div class="profile-ring-lbl">done</div></div></div>
                    <div style="font-size:10px;color:var(--muted)">Profile</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="g2" style="align-items:start">
        <!-- LEFT: Skills (main focus) -->
        <div>

          <!-- ══ SKILLS SECTION ══ -->
          <div class="card" style="margin-bottom:16px">
            <div class="card-head">
              <div style="display:flex;align-items:center;gap:8px">
                <h3>Skills & Expertise</h3>
                <span id="skill-count-badge" style="background:var(--gl);color:var(--g);font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px">7 / 15</span>
              </div>
              <button class="btn btn-g btn-sm" onclick="openSkillSelector()">+ Browse All Skills</button>
            </div>
            <div class="card-body">

              <!-- Quick-add inline input -->
              <div style="margin-bottom:14px">
                <div style="position:relative">
                  <input id="quick-skill-input" type="text" placeholder="Type a skill and press Enter to add…"
                    style="width:100%;padding:9px 40px 9px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none;transition:border-color .15s"
                    onfocus="this.style.borderColor='var(--g)';showQuickSuggestions(this.value)"
                    onblur="this.style.borderColor='var(--border)';setTimeout(hideQuickSuggestions,180)"
                    oninput="showQuickSuggestions(this.value)"
                    onkeydown="if(event.key==='Enter'){quickAddSkill(this.value);this.value='';hideQuickSuggestions()}else if(event.key==='Escape'){this.value='';hideQuickSuggestions()}">
                  <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--muted2);pointer-events:none">↵</span>
                  <!-- Autocomplete dropdown -->
                  <div id="quick-suggestions" style="display:none;position:absolute;top:100%;left:0;right:0;background:white;border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:500;max-height:200px;overflow-y:auto;margin-top:4px"></div>
                </div>
              </div>

              <!-- Suggested skills -->
              <div style="margin-bottom:14px">
                <div style="font-size:11.5px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">✨ Suggested for you</div>
                <div id="suggested-skills-row" style="display:flex;flex-wrap:wrap;gap:6px"></div>
              </div>

              <!-- Current skills -->
              <div>
                <div style="font-size:11.5px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Your Skills</div>
                <div id="profile-skills-display" style="display:flex;flex-wrap:wrap;gap:6px">
                  <span class="skill-tag">Figma <span class="skill-remove" onclick="removeSkill('Figma')">×</span></span>
                  <span class="skill-tag">Webflow <span class="skill-remove" onclick="removeSkill('Webflow')">×</span></span>
                  <span class="skill-tag">Design Systems <span class="skill-remove" onclick="removeSkill('Design Systems')">×</span></span>
                  <span class="skill-tag">Prototyping <span class="skill-remove" onclick="removeSkill('Prototyping')">×</span></span>
                  <span class="skill-tag">User Research <span class="skill-remove" onclick="removeSkill('User Research')">×</span></span>
                  <span class="skill-tag">Framer <span class="skill-remove" onclick="removeSkill('Framer')">×</span></span>
                  <span class="skill-tag">Motion Design <span class="skill-remove" onclick="removeSkill('Motion Design')">×</span></span>
                </div>
                <div id="profile-skills-empty" style="display:none;text-align:center;padding:20px 0;color:var(--muted);font-size:13px">No skills added yet — use the search above or browse all skills</div>
              </div>
            </div>
          </div>

          <!-- Profile completeness -->
          <div class="card">
            <div class="card-head"><h3>Profile Completeness</h3></div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;padding:8px 10px;background:var(--off);border-radius:7px"><span style="font-size:16px">✅</span><span style="flex:1">Professional photo</span><span style="font-size:11px;color:var(--g);font-weight:600">Done</span></div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;padding:8px 10px;background:var(--off);border-radius:7px"><span style="font-size:16px">✅</span><span style="flex:1">Skills & expertise</span><span style="font-size:11px;color:var(--g);font-weight:600">Done</span></div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;padding:8px 10px;background:#fffbeb;border-radius:7px;cursor:pointer" onclick="toast('Portfolio','Add 3+ portfolio pieces to boost invites by 40%')"><span style="font-size:16px">⚠️</span><span style="flex:1">Portfolio samples</span><span style="font-size:11.5px;color:var(--g);font-weight:600;border:1px solid var(--g);border-radius:5px;padding:2px 8px">+ Add</span></div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;padding:8px 10px;background:#fffbeb;border-radius:7px;cursor:pointer" onclick="toast('Bio','A strong bio can increase job invitations by 30%')"><span style="font-size:16px">⚠️</span><span style="flex:1">Detailed bio / overview</span><span style="font-size:11.5px;color:var(--g);font-weight:600;border:1px solid var(--g);border-radius:5px;padding:2px 8px">+ Add</span></div>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT: Profile info + stats -->
        <div>
          <div class="card" style="margin-bottom:14px">
            <div class="card-head"><h3>Profile Info</h3></div>
            <div class="card-body">
              <table class="tbl" style="font-size:13px">
                <tr><td style="color:var(--muted);width:120px">Rate</td><td><strong>$90/hr</strong></td></tr>
                <tr><td style="color:var(--muted)">Location</td><td>🇩🇪 Berlin, Germany</td></tr>
                <tr><td style="color:var(--muted)">Member since</td><td>January 2020</td></tr>
                <tr><td style="color:var(--muted)">Languages</td><td>English (Fluent), German (Conversational)</td></tr>
                <tr><td style="color:var(--muted)">Total earned</td><td><strong>$82,400+</strong></td></tr>
              </table>
            </div>
          </div>
          <div class="card">
            <div class="card-head"><h3>Job Success Score</h3></div>
            <div class="card-body">
              <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
                <div class="jss-ring"><div class="jss-inner">96%</div></div>
                <div><div style="font-size:16px;font-weight:700;color:var(--g)">Excellent</div><div style="font-size:12.5px;color:var(--muted)">Top 5% of all designers</div></div>
              </div>
              <div style="font-size:12.5px;color:var(--muted);line-height:1.7">Your JSS is based on all completed contracts in the past 24 months. Maintaining above 90% qualifies you for Top Rated status.</div>
            </div>
          </div>

          <!-- ID Verification card (profile page shortcut) -->
          <div class="card" style="margin-top:14px;border:1.5px solid #fde68a;background:#fffbeb">
            <div class="card-body" style="padding:14px 16px">
              <div style="display:flex;align-items:center;gap:12px">
                <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">🪪</div>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:700;margin-bottom:2px">Identity Not Verified</div>
                  <div style="font-size:12px;color:#92400e;line-height:1.5">Verify your ID to unlock higher earning limits and build client trust.</div>
                </div>
                <button class="btn btn-sm" style="background:#f59e0b;color:white;border:none;flex-shrink:0" onclick="showPage('verification',document.querySelector('[onclick*=verification]'))">Verify →</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════
         ID VERIFICATION PAGE
    ══════════════════════════════════════════════ -->
    <div class="page" id="page-verification">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div>
          <div style="font-size:20px;font-weight:700">Identity Verification</div>
          <div style="font-size:13px;color:var(--muted);margin-top:3px">Verify your identity to build trust and unlock full platform features.</div>
        </div>
        <span class="badge b-yellow" style="font-size:12px;padding:5px 12px">⏳ Pending</span>
      </div>

      <!-- Why verify banner -->
      <div style="background:linear-gradient(135deg,#16281a 0%,#1f3a23 100%);border-radius:12px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <div style="font-size:36px">🛡️</div>
        <div style="flex:1;min-width:200px">
          <div style="font-size:15px;font-weight:700;color:white;margin-bottom:6px">Why verify your identity?</div>
          <div style="display:flex;flex-wrap:wrap;gap:10px">
            <span style="font-size:12px;color:rgba(255,255,255,.75);display:flex;align-items:center;gap:5px"><span style="color:#c8f135">✓</span> Earn the "ID Verified" badge</span>
            <span style="font-size:12px;color:rgba(255,255,255,.75);display:flex;align-items:center;gap:5px"><span style="color:#c8f135">✓</span> Higher withdrawal limits</span>
            <span style="font-size:12px;color:rgba(255,255,255,.75);display:flex;align-items:center;gap:5px"><span style="color:#c8f135">✓</span> Increased client confidence</span>
            <span style="font-size:12px;color:rgba(255,255,255,.75);display:flex;align-items:center;gap:5px"><span style="color:#c8f135">✓</span> Access to enterprise contracts</span>
          </div>
        </div>
      </div>

      <!-- Steps progress -->
      <div style="display:flex;align-items:center;gap:0;margin-bottom:22px;background:white;border:1px solid var(--border);border-radius:10px;overflow:hidden">
        <div id="vstep-1" style="flex:1;padding:14px 16px;border-right:1px solid var(--border);cursor:pointer;transition:background .15s;background:var(--gl)" onclick="switchVStep(1)">
          <div style="display:flex;align-items:center;gap:8px">
            <div id="vstep-1-ico" style="width:24px;height:24px;border-radius:50%;background:var(--g);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">1</div>
            <div>
              <div style="font-size:12px;font-weight:700">Choose Document</div>
              <div style="font-size:11px;color:var(--muted)">Select ID type</div>
            </div>
          </div>
        </div>
        <div id="vstep-2" style="flex:1;padding:14px 16px;border-right:1px solid var(--border);cursor:pointer;transition:background .15s" onclick="switchVStep(2)">
          <div style="display:flex;align-items:center;gap:8px">
            <div id="vstep-2-ico" style="width:24px;height:24px;border-radius:50%;background:var(--border);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">2</div>
            <div>
              <div style="font-size:12px;font-weight:700">Upload Document</div>
              <div style="font-size:11px;color:var(--muted)">Front & back / photo page</div>
            </div>
          </div>
        </div>
        <div id="vstep-3" style="flex:1;padding:14px 16px;cursor:pointer;transition:background .15s" onclick="switchVStep(3)">
          <div style="display:flex;align-items:center;gap:8px">
            <div id="vstep-3-ico" style="width:24px;height:24px;border-radius:50%;background:var(--border);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">3</div>
            <div>
              <div style="font-size:12px;font-weight:700">Review & Submit</div>
              <div style="font-size:11px;color:var(--muted)">Confirm & send</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step panels -->

      <!-- STEP 1: Choose document type -->
      <div id="vpanel-1" class="card" style="margin-bottom:16px">
        <div class="card-head"><h3>Step 1 — Choose Document Type</h3></div>
        <div class="card-body">
          <div style="font-size:13px;color:var(--muted);margin-bottom:16px">Select the government-issued document you want to use for verification.</div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px" id="doc-type-grid">

            <div class="doc-type-card" id="dtype-passport" onclick="selectDocType('passport','dtype-passport')" style="border:2px solid var(--border);border-radius:10px;padding:16px;cursor:pointer;transition:all .2s;text-align:center">
              <div style="font-size:34px;margin-bottom:8px">🛂</div>
              <div style="font-size:13px;font-weight:700;margin-bottom:3px">Passport</div>
              <div style="font-size:11.5px;color:var(--muted)">Photo page only</div>
            </div>

            <div class="doc-type-card" id="dtype-national-id" onclick="selectDocType('national-id','dtype-national-id')" style="border:2px solid var(--border);border-radius:10px;padding:16px;cursor:pointer;transition:all .2s;text-align:center">
              <div style="font-size:34px;margin-bottom:8px">🪪</div>
              <div style="font-size:13px;font-weight:700;margin-bottom:3px">National ID</div>
              <div style="font-size:11.5px;color:var(--muted)">Front & back</div>
            </div>

            <div class="doc-type-card" id="dtype-drivers" onclick="selectDocType('drivers','dtype-drivers')" style="border:2px solid var(--border);border-radius:10px;padding:16px;cursor:pointer;transition:all .2s;text-align:center">
              <div style="font-size:34px;margin-bottom:8px">🚗</div>
              <div style="font-size:13px;font-weight:700;margin-bottom:3px">Driver's Licence</div>
              <div style="font-size:11.5px;color:var(--muted)">Front & back</div>
            </div>

            <div class="doc-type-card" id="dtype-residence" onclick="selectDocType('residence','dtype-residence')" style="border:2px solid var(--border);border-radius:10px;padding:16px;cursor:pointer;transition:all .2s;text-align:center">
              <div style="font-size:34px;margin-bottom:8px">📋</div>
              <div style="font-size:13px;font-weight:700;margin-bottom:3px">Residence Permit</div>
              <div style="font-size:11.5px;color:var(--muted)">Front & back</div>
            </div>

          </div>
          <div id="dtype-selected-bar" style="display:none;margin-top:16px;background:var(--gl);border:1px solid #c3e6c3;border-radius:8px;padding:10px 14px;display:none;align-items:center;gap:10px">
            <span style="font-size:16px">✅</span>
            <span id="dtype-selected-text" style="font-size:13px;font-weight:600;color:var(--g)">Passport selected</span>
            <button class="btn btn-sm btn-g" style="margin-left:auto" onclick="switchVStep(2)">Next: Upload →</button>
          </div>
        </div>
      </div>

      <!-- STEP 2: Upload -->
      <div id="vpanel-2" class="card" style="margin-bottom:16px;display:none">
        <div class="card-head">
          <h3>Step 2 — Upload Your Document</h3>
          <span id="upload-doc-label" class="badge b-green">Passport</span>
        </div>
        <div class="card-body">
          <div style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.65">
            Upload a clear, colour photo or scan. Make sure all four corners are visible and text is readable. Files must be <strong>JPG, PNG, or PDF</strong> and under <strong>10 MB</strong>.
          </div>

          <!-- Front / photo page upload -->
          <div style="margin-bottom:16px">
            <div style="font-size:12.5px;font-weight:700;margin-bottom:8px" id="front-label">📄 Front side / Photo page</div>
            <div id="vdrop-front"
              ondragover="event.preventDefault();this.style.borderColor='var(--g)';this.style.background='var(--gl)'"
              ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--off)'"
              ondrop="handleVDrop(event,'front')"
              style="border:2px dashed var(--border);border-radius:10px;padding:28px;text-align:center;background:var(--off);cursor:pointer;transition:all .2s"
              onclick="document.getElementById('vinput-front').click()">
              <div id="vfront-preview" style="display:none;flex-direction:column;align-items:center;gap:8px"></div>
              <div id="vfront-placeholder">
                <div style="font-size:32px;margin-bottom:8px">📤</div>
                <div style="font-size:13px;font-weight:600;margin-bottom:4px">Drag & drop or click to upload</div>
                <div style="font-size:12px;color:var(--muted)">JPG, PNG, PDF — max 10 MB</div>
              </div>
            </div>
            <input type="file" id="vinput-front" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="handleVFileInput(this.files,'front')">
          </div>

          <!-- Back side (hidden for passport) -->
          <div id="vback-section" style="margin-bottom:16px">
            <div style="font-size:12.5px;font-weight:700;margin-bottom:8px">📄 Back side</div>
            <div id="vdrop-back"
              ondragover="event.preventDefault();this.style.borderColor='var(--g)';this.style.background='var(--gl)'"
              ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--off)'"
              ondrop="handleVDrop(event,'back')"
              style="border:2px dashed var(--border);border-radius:10px;padding:28px;text-align:center;background:var(--off);cursor:pointer;transition:all .2s"
              onclick="document.getElementById('vinput-back').click()">
              <div id="vback-preview" style="display:none;flex-direction:column;align-items:center;gap:8px"></div>
              <div id="vback-placeholder">
                <div style="font-size:32px;margin-bottom:8px">📤</div>
                <div style="font-size:13px;font-weight:600;margin-bottom:4px">Drag & drop or click to upload</div>
                <div style="font-size:12px;color:var(--muted)">JPG, PNG, PDF — max 10 MB</div>
              </div>
            </div>
            <input type="file" id="vinput-back" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="handleVFileInput(this.files,'back')">
          </div>

          <!-- Tips -->
          <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;font-size:12.5px;color:#1e40af;line-height:1.7;margin-bottom:16px">
            <strong>📸 Photo tips</strong><br>
            • Use a flat surface with good lighting — no flash glare<br>
            • All four corners of the document must be visible<br>
            • Do not crop, edit, or add filters<br>
            • The document must not be expired
          </div>

          <div style="display:flex;gap:10px">
            <button class="btn btn-w" onclick="switchVStep(1)">← Back</button>
            <button class="btn btn-g" style="flex:1;justify-content:center" id="vnext-2" onclick="validateAndGoStep3()" disabled style="opacity:.5">Next: Review →</button>
          </div>
        </div>
      </div>

      <!-- STEP 3: Review & Submit -->
      <div id="vpanel-3" class="card" style="margin-bottom:16px;display:none">
        <div class="card-head"><h3>Step 3 — Review & Submit</h3></div>
        <div class="card-body">
          <div style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.65">Please review your uploaded documents before submitting. Once submitted, verification typically takes <strong>1–3 business days</strong>.</div>

          <div id="vreview-content" style="margin-bottom:18px"></div>

          <!-- Personal details confirm -->
          <div style="background:var(--off);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:18px">
            <div style="font-size:12.5px;font-weight:700;margin-bottom:10px;color:var(--dark)">Confirm your details match your document</div>
            <div class="g2" style="gap:10px">
              <div class="fg" style="margin-bottom:0"><label>Full legal name</label><input type="text" value="Anika Nkosi" id="vlegal-name"></div>
              <div class="fg" style="margin-bottom:0"><label>Date of birth</label><input type="date" value="1992-04-18" id="vdob"></div>
              <div class="fg" style="margin-bottom:0"><label>Nationality</label><input type="text" value="German" id="vnationality"></div>
              <div class="fg" style="margin-bottom:0"><label>Document number</label><input type="text" placeholder="e.g. A12345678" id="vdoc-number"></div>
            </div>
          </div>

          <!-- Consent -->
          <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:12.5px;color:var(--dark3);line-height:1.6;margin-bottom:18px">
            <input type="checkbox" id="vconsent" style="margin-top:2px;accent-color:var(--g);width:15px;height:15px;flex-shrink:0" onchange="toggleVSubmit()">
            I consent to Remoworkers processing my personal data and identity document for the purpose of identity verification, in accordance with the <a href="#" style="color:var(--g)">Privacy Policy</a> and <a href="#" style="color:var(--g)">Terms of Service</a>.
          </label>

          <div style="display:flex;gap:10px">
            <button class="btn btn-w" onclick="switchVStep(2)">← Back</button>
            <button class="btn btn-g" style="flex:1;justify-content:center;opacity:.45" id="vsubmit-btn" disabled onclick="submitVerification()">🛡️ Submit for Verification</button>
          </div>
        </div>
      </div>

      <!-- Submitted state (hidden until submit) -->
      <div id="vpanel-done" style="display:none">
        <div class="card" style="border:2px solid var(--g);background:var(--gl)">
          <div class="card-body" style="text-align:center;padding:36px 24px">
            <div style="font-size:52px;margin-bottom:16px">🎉</div>
            <div style="font-size:20px;font-weight:700;margin-bottom:8px;color:var(--forest)">Verification Submitted!</div>
            <div style="font-size:13.5px;color:#166534;line-height:1.7;max-width:380px;margin:0 auto 20px">Your identity documents have been received. Our team will review your submission within <strong>1–3 business days</strong>. You'll receive an email notification once verified.</div>
            <div style="display:inline-flex;align-items:center;gap:8px;background:white;border:1px solid #c3e6c3;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;color:var(--g);margin-bottom:20px">
              <span>🔒</span> Status: <strong>Under Review</strong>
            </div>
            <div>
              <button class="btn btn-g" onclick="showPage('profile',document.querySelector('[onclick*=profile]'))">← Back to Profile</button>
            </div>
          </div>
        </div>
      </div>

    </div><!-- end #page-verification -->

  </div>
</div>

<!-- ══════════════════════════════════════════════════
     SKILL SELECTOR OVERLAY
══════════════════════════════════════════════════ -->
<div id="skill-overlay" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
  <div style="background:white;border-radius:14px;width:min(820px,96vw);max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden">

    <!-- Header -->
    <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <div>
        <div style="font-size:16px;font-weight:700">Add Skills</div>
        <div style="font-size:12.5px;color:var(--muted);margin-top:2px">Browse by category · up to 15 skills</div>
      </div>
      <button onclick="closeSkillSelector()" style="background:none;border:none;font-size:22px;color:var(--muted);cursor:pointer;line-height:1;padding:4px">×</button>
    </div>

    <!-- Search bar -->
    <div style="padding:12px 22px;border-bottom:1px solid var(--border);flex-shrink:0">
      <input id="skill-search" type="text" placeholder="Search skills (e.g. Python, Logo Design, SEO…)"
        style="width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none"
        oninput="filterSkills(this.value)"
        onfocus="this.style.borderColor='var(--g)'" onblur="this.style.borderColor='var(--border)'">
    </div>

    <!-- Body: 3-column layout -->
    <div style="display:grid;grid-template-columns:190px 210px 1fr;flex:1;overflow:hidden;min-height:0">

      <!-- Col 1: Main categories -->
      <div id="cat-col" style="border-right:1px solid var(--border);overflow-y:auto;padding:8px 0"></div>

      <!-- Col 2: Subcategories -->
      <div id="subcat-col" style="border-right:1px solid var(--border);overflow-y:auto;padding:8px 0">
        <div style="padding:20px;font-size:12.5px;color:var(--muted);text-align:center">← Select a category</div>
      </div>

      <!-- Col 3: Skills/specialties to pick -->
      <div id="skill-col" style="overflow-y:auto;padding:12px 16px">
        <div style="font-size:12.5px;color:var(--muted);text-align:center;padding:20px">← Select a subcategory</div>
      </div>
    </div>

    <!-- Footer: selected skills + save -->
    <div style="padding:14px 22px;border-top:1px solid var(--border);flex-shrink:0;background:var(--off)">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <div style="font-size:12px;color:var(--muted);font-weight:600;white-space:nowrap">Selected:</div>
        <div id="selected-preview" style="display:flex;flex-wrap:wrap;gap:5px;flex:1"></div>
        <button onclick="saveSkills()" class="btn btn-g" style="padding:9px 20px;white-space:nowrap">Save Skills</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>