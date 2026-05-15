<?php include __DIR__ . '/includes/header.php'; ?>

<!-- HERO -->
<section class="hero">
  <div class="hero-left au">
    <div class="hero-pill" onclick="openModal('ai-matching')"><span class="hero-pill-dot"></span>Rated #1 Freelance Platform 2026</div>
    <h1>How <em>brilliant</em><br>work gets done</h1>
    <p class="hero-sub">Access 5 million+ vetted professionals across every skill. Post your job free — get proposals in hours, not weeks.</p>
    <div class="search-box">
      <input type="text" placeholder="Search any skill, service or tool…" id="hs" onkeydown="if(event.key==='Enter')doSearch()">
      <div class="search-sep"></div>
      <select id="search-cat"><option>Any category</option><option>Development</option><option>Design</option><option>Marketing</option><option>Writing</option><option>AI & ML</option></select>
      <button class="search-btn" onclick="doSearch()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Search</button>
    </div>
    <div class="popular">
      <span class="pop-lbl">Trending:</span>
      <span class="pop-tag" onclick="searchFor('Web Design')">Web Design</span>
      <span class="pop-tag" onclick="searchFor('React Dev')">React Dev</span>
      <span class="pop-tag" onclick="searchFor('AI Prompting')">AI Prompting</span>
      <span class="pop-tag" onclick="searchFor('Copywriting')">Copywriting</span>
      <span class="pop-tag" onclick="searchFor('Logo Design')">Logo Design</span>
    </div>
  </div>

</section>
<!-- TRUST BAR -->
<div class="trust-bar">
  <span class="trust-lbl">Trusted by</span>
  <div class="trust-track-wrap"><div class="trust-track">
    <span class="tl" onclick="showToast('Microsoft','Enterprise client since 2021 — 500+ projects/year')">Microsoft</span>
    <span class="tl" onclick="showToast('Airbnb','Design & engineering teams powered by Remoworkers talent')">Airbnb</span>
    <span class="tl" onclick="showToast('Nasdaq','Financial tech development & compliance projects')">Nasdaq</span>
    <span class="tl" onclick="showToast('GE Healthcare','Industrial engineering & tech projects')">GE Healthcare</span>
    <span class="tl" onclick="showToast('Glassdoor','Writing, design, and product talent')">Glassdoor</span>
    <span class="tl" onclick="showToast('Dropbox','Design systems & full-stack development')">Dropbox</span>
    <span class="tl" onclick="showToast('Shopify','eCommerce development & app ecosystem')">Shopify</span>
    <span class="tl" onclick="showToast('Payoneer','Payment processing & financial integration')">Payoneer</span>
    <span class="tl" onclick="showToast('T-Mobile','Telecom tech, digital marketing & content')">T-Mobile</span>
    <span class="tl" onclick="showToast('Samsung','Consumer electronics product & design teams')">Samsung</span>
    <span class="tl" onclick="showToast('Microsoft','Enterprise client since 2021 — 500+ projects/year')">Microsoft</span>
    <span class="tl" onclick="showToast('Airbnb','Design & engineering teams powered by Remoworkers talent')">Airbnb</span>
    <span class="tl" onclick="showToast('Nasdaq','Financial tech development & compliance projects')">Nasdaq</span>
    <span class="tl" onclick="showToast('GE Healthcare','Industrial engineering & tech projects')">GE Healthcare</span>
    <span class="tl" onclick="showToast('Glassdoor','Writing, design, and product talent')">Glassdoor</span>
    <span class="tl" onclick="showToast('Dropbox','Design systems & full-stack development')">Dropbox</span>
    <span class="tl" onclick="showToast('Shopify','eCommerce development & app ecosystem')">Shopify</span>
    <span class="tl" onclick="showToast('Payoneer','Payment processing & financial integration')">Payoneer</span>
    <span class="tl" onclick="showToast('T-Mobile','Telecom tech, digital marketing & content')">T-Mobile</span>
    <span class="tl" onclick="showToast('Samsung','Consumer electronics product & design teams')">Samsung</span>
  </div></div>
</div>

<!-- HOW IT WORKS -->
<section class="sec">
  <div class="sec-lbl">Simple process</div>
  <div class="sec-title">Everything you need,<br>none of the hassle</div>
  <p class="sec-sub">Post a job or find work in minutes. Our platform handles the rest.</p>
  <div class="tabs-wrap">
    <button class="tabt on" onclick="switchTab(this,'hire')">I'm Hiring</button>
    <button class="tabt" onclick="switchTab(this,'work')">I'm Looking for Work</button>
  </div>
  <div id="tab-hire" class="hiw-grid">
    <div class="hiw-c" onclick="openModal('post-job')"><div class="hiw-step">01</div><div class="hiw-ico">📝</div><h3>Post your job for free</h3><p>Describe your project in minutes. Our smart form guides you through skills, timeline, and budget. No credit card needed.</p><span class="hiw-link">Post a job now →</span></div>
    <div class="hiw-c" onclick="openModal('talent-marketplace')"><div class="hiw-step">02</div><div class="hiw-ico">🤝</div><h3>Review top proposals</h3><p>Receive proposals from vetted freelancers within hours. Browse detailed profiles, portfolios, ratings, and full work history.</p><span class="hiw-link">Browse talent →</span></div>
    <div class="hiw-c" onclick="openModal('trust-safety')"><div class="hiw-step">03</div><div class="hiw-ico">🔒</div><h3>Pay safely, always</h3><p>Use Milestone Payments to hold funds in escrow. Release payment only when you're 100% satisfied with the delivered work.</p><span class="hiw-link">Learn about protection →</span></div>
  </div>
  <div id="tab-work" class="hiw-grid" style="display:none">
    <div class="hiw-c" onclick="openModal('signup')"><div class="hiw-step">01</div><div class="hiw-ico">👤</div><h3>Build a standout profile</h3><p>Showcase your skills, portfolio, certifications, and work history. Take skill assessments to earn trusted badges.</p><span class="hiw-link">Create profile →</span></div>
    <div class="hiw-c" onclick="openModal('browse-jobs')"><div class="hiw-step">02</div><div class="hiw-ico">🔍</div><h3>Find the right projects</h3><p>Browse thousands of live jobs or set smart alerts. Filter by budget, project type, client reputation, and more.</p><span class="hiw-link">Browse jobs →</span></div>
    <div class="hiw-c" onclick="openModal('trust-safety')"><div class="hiw-step">03</div><div class="hiw-ico">💳</div><h3>Get paid, reliably</h3><p>Work on your terms. Withdraw via bank transfer, PayPal, wire, or local methods in 190+ countries worldwide.</p><span class="hiw-link">Payment options →</span></div>
  </div>
</section>
<!-- CATEGORIES -->
<section class="sec sec-alt">
  <div class="sec-lbl">Browse by category</div>
  <div class="sec-title">Talent across<br>every discipline</div>
  <div class="cat-grid">
    <div class="cat-c" onclick="openModal('cat-dev')"><span class="cat-hot">🔥 Hot</span><span class="cat-em">🖥️</span><h3>Development & IT</h3><p>Web, mobile, cloud</p><span class="cat-ct">1,853 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-design')"><span class="cat-em">🎨</span><h3>Design & Creative</h3><p>UI, branding, illustration</p><span class="cat-ct">954 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-ai')"><span class="cat-hot">🔥 Hot</span><span class="cat-em">🤖</span><h3>AI & Machine Learning</h3><p>LLMs, data science, MLOps</p><span class="cat-ct">294 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-marketing')"><span class="cat-em">📈</span><h3>Sales & Marketing</h3><p>SEO, ads, strategy</p><span class="cat-ct">392 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-writing')"><span class="cat-em">✍️</span><h3>Writing & Translation</h3><p>Content, copy, localization</p><span class="cat-ct">514 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-finance')"><span class="cat-em">🔢</span><h3>Finance & Accounting</h3><p>Bookkeeping, CFO, tax</p><span class="cat-ct">214 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-data')"><span class="cat-em">📊</span><h3>Data Science</h3><p>Analytics, visualization, BI</p><span class="cat-ct">183 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-video')"><span class="cat-em">🎬</span><h3>Video & Animation</h3><p>Editing, motion, 3D</p><span class="cat-ct">266 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-audio')"><span class="cat-em">🎵</span><h3>Music & Audio</h3><p>Production, voiceover</p><span class="cat-ct">128 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-legal')"><span class="cat-em">⚖️</span><h3>Legal</h3><p>Contracts, IP, compliance</p><span class="cat-ct">140 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-support')"><span class="cat-em">🎧</span><h3>Customer Support</h3><p>Chat, email, CRM</p><span class="cat-ct">175 skills</span></div>
    <div class="cat-c" onclick="openModal('cat-eng')"><span class="cat-em">🏗️</span><h3>Engineering</h3><p>CAD, architecture, civil</p><span class="cat-ct">312 skills</span></div>
  </div>
</section>
<!-- PROJECT CATALOG -->
<section class="sec">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px">
    <div><div class="sec-lbl">Project catalog</div><div class="sec-title" style="margin-bottom:0">Ready-made service<br>packages</div></div>
    <a class="btn btn-outline" onclick="openModal('project-catalog-modal')">View all packages →</a>
  </div>
  <div class="pc-grid">
    <div class="pc-c" onclick="openModal('pkg-logo')"><div class="pc-img">🎨</div><div class="pc-body"><div class="pc-cat">Design</div><div class="pc-title">Professional Logo Design + Brand Guidelines</div><div class="pc-seller"><div class="pc-sav" style="background:#d1fae5;color:#065f46">AN</div><div><div class="pc-sname">Anika N.</div><div class="pc-srating"><span>★</span> 5.0 (127)</div></div></div><div class="pc-ft"><div><div class="pc-price-lbl">Starting at</div><div class="pc-price">$299</div></div><span style="font-size:11.5px;color:#617a5a">3-day delivery</span></div></div></div>
    <div class="pc-c" onclick="openModal('pkg-webapp')"><div class="pc-img">🖥️</div><div class="pc-body"><div class="pc-cat">Development</div><div class="pc-title">Full-Stack Web App — React + Node + PostgreSQL</div><div class="pc-seller"><div class="pc-sav" style="background:#dbeafe;color:#1e40af">JK</div><div><div class="pc-sname">James K.</div><div class="pc-srating"><span>★</span> 4.9 (89)</div></div></div><div class="pc-ft"><div><div class="pc-price-lbl">Starting at</div><div class="pc-price">$1,500</div></div><span style="font-size:11.5px;color:#617a5a">14-day delivery</span></div></div></div>
    <div class="pc-c" onclick="openModal('pkg-seo')"><div class="pc-img">📈</div><div class="pc-body"><div class="pc-cat">Marketing</div><div class="pc-title">SEO Audit + 3-Month Keyword Strategy Plan</div><div class="pc-seller"><div class="pc-sav" style="background:#fef3c7;color:#92400e">LT</div><div><div class="pc-sname">Lena T.</div><div class="pc-srating"><span>★</span> 5.0 (203)</div></div></div><div class="pc-ft"><div><div class="pc-price-lbl">Starting at</div><div class="pc-price">$449</div></div><span style="font-size:11.5px;color:#617a5a">5-day delivery</span></div></div></div>
    <div class="pc-c" onclick="openModal('pkg-ai')"><div class="pc-img">🤖</div><div class="pc-body"><div class="pc-cat">AI & ML</div><div class="pc-title">Custom AI Chatbot with RAG + LLM Integration</div><div class="pc-seller"><div class="pc-sav" style="background:#ede9fe;color:#5b21b6">MP</div><div><div class="pc-sname">Marcus P.</div><div class="pc-srating"><span>★</span> 4.8 (41)</div></div></div><div class="pc-ft"><div><div class="pc-price-lbl">Starting at</div><div class="pc-price">$2,200</div></div><span style="font-size:11.5px;color:#617a5a">10-day delivery</span></div></div></div>
  </div>
</section>
<!-- TOP TALENT -->
<section class="sec sec-alt">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div><div class="sec-lbl">Featured talent</div><div class="sec-title" style="margin-bottom:0">Work with the best</div></div>
    <a class="btn btn-outline" onclick="openModal('talent-marketplace')">View all talent →</a>
  </div>
  <div class="talent-filters">
    <button class="tf on" onclick="setF(this)">All</button>
    <button class="tf" onclick="setF(this);openModal('cat-dev')">Development</button>
    <button class="tf" onclick="setF(this);openModal('cat-design')">Design</button>
    <button class="tf" onclick="setF(this);openModal('cat-marketing')">Marketing</button>
    <button class="tf" onclick="setF(this);openModal('cat-writing')">Writing</button>
    <button class="tf" onclick="setF(this);openModal('cat-ai')">AI & ML</button>
    <button class="tf" onclick="setF(this);openModal('cat-finance')">Finance</button>
    <button class="tf" onclick="setF(this);openModal('cat-video')">Video</button>
  </div>
  <div class="t-grid">
    <div class="tc"><span class="tc-save" onclick="toggleSave(this,event)">♡</span><div class="tc-top"><div class="tc-av" style="background:#d1fae5;color:#065f46">AN</div><span class="tc-badge tr">✦ Top Rated Plus</span></div><div class="tc-name">Anika Nkosi</div><div class="tc-role">Senior UI/UX Designer & Design Lead</div><div class="tc-row"><div class="tc-m"><span class="tc-mv">★ 5.0</span><span class="tc-ml">Rating</span></div><div class="tc-m"><span class="tc-mv">127</span><span class="tc-ml">Reviews</span></div><div class="tc-m"><span class="tc-mv">98%</span><span class="tc-ml">Job Success</span></div></div><div class="tc-div"></div><div class="tc-tags"><span class="tc-tag">Figma</span><span class="tc-tag">Webflow</span><span class="tc-tag">Prototyping</span><span class="tc-tag">Design Systems</span></div><div class="tc-ft"><span class="tc-rate">$90<span>/hr</span></span><button class="tc-btn" onclick="openModal('prof-anika')">View Profile</button></div></div>
    <div class="tc"><span class="tc-save" onclick="toggleSave(this,event)">♡</span><div class="tc-top"><div class="tc-av" style="background:#dbeafe;color:#1e40af">JK</div><span class="tc-badge ev">★ Expert Vetted</span></div><div class="tc-name">James Kowalski</div><div class="tc-role">Full Stack Engineer — React, Node.js, AWS</div><div class="tc-row"><div class="tc-m"><span class="tc-mv">★ 4.9</span><span class="tc-ml">Rating</span></div><div class="tc-m"><span class="tc-mv">89</span><span class="tc-ml">Reviews</span></div><div class="tc-m"><span class="tc-mv">96%</span><span class="tc-ml">Job Success</span></div></div><div class="tc-div"></div><div class="tc-tags"><span class="tc-tag">React</span><span class="tc-tag">TypeScript</span><span class="tc-tag">Node.js</span><span class="tc-tag">AWS</span></div><div class="tc-ft"><span class="tc-rate">$130<span>/hr</span></span><button class="tc-btn" onclick="openModal('prof-james')">View Profile</button></div></div>
    <div class="tc"><span class="tc-save" onclick="toggleSave(this,event)">♡</span><div class="tc-top"><div class="tc-av" style="background:#fef3c7;color:#92400e">LT</div><span class="tc-badge tr">✦ Top Rated</span></div><div class="tc-name">Lena Thornton</div><div class="tc-role">SEO Strategist & Content Marketing Lead</div><div class="tc-row"><div class="tc-m"><span class="tc-mv">★ 5.0</span><span class="tc-ml">Rating</span></div><div class="tc-m"><span class="tc-mv">203</span><span class="tc-ml">Reviews</span></div><div class="tc-m"><span class="tc-mv">100%</span><span class="tc-ml">Job Success</span></div></div><div class="tc-div"></div><div class="tc-tags"><span class="tc-tag">SEO</span><span class="tc-tag">Copywriting</span><span class="tc-tag">Content Strategy</span></div><div class="tc-ft"><span class="tc-rate">$65<span>/hr</span></span><button class="tc-btn" onclick="openModal('prof-lena')">View Profile</button></div></div>
    <div class="tc"><span class="tc-save" onclick="toggleSave(this,event)">♡</span><div class="tc-top"><div class="tc-av" style="background:#ede9fe;color:#5b21b6">MP</div><span class="tc-badge rs">↑ Rising Talent</span></div><div class="tc-name">Marcus Patel</div><div class="tc-role">AI/ML Engineer & Data Scientist</div><div class="tc-row"><div class="tc-m"><span class="tc-mv">★ 4.8</span><span class="tc-ml">Rating</span></div><div class="tc-m"><span class="tc-mv">41</span><span class="tc-ml">Reviews</span></div><div class="tc-m"><span class="tc-mv">95%</span><span class="tc-ml">Job Success</span></div></div><div class="tc-div"></div><div class="tc-tags"><span class="tc-tag">Python</span><span class="tc-tag">PyTorch</span><span class="tc-tag">LLMs</span><span class="tc-tag">RAG</span></div><div class="tc-ft"><span class="tc-rate">$110<span>/hr</span></span><button class="tc-btn" onclick="openModal('prof-marcus')">View Profile</button></div></div>
  </div>
  <div style="display:flex;justify-content:center;margin-top:24px"><a class="btn btn-dark btn-lg" onclick="openModal('talent-marketplace')">Explore all talent →</a></div>
</section>

<!-- WHY US -->
<section class="sec">
  <div class="why-grid">
    <div>
      <div class="sec-lbl">Why Remoworkers</div>
      <div class="sec-title">The smarter way<br>to get work done</div>
      <p class="sec-sub">Built-in tools, protections, and trust signals — so you can focus entirely on the work.</p>
      <div class="why-feats">
        <div class="wf" onclick="openModal('trust-safety')"><div class="wf-ico">✅</div><div class="wf-t"><h4>Verified talent, real results</h4><p>Every profile features verified reviews, skill assessments, work history, and portfolio samples.</p></div></div>
        <div class="wf" onclick="openModal('trust-safety')"><div class="wf-ico">🛡️</div><div class="wf-t"><h4>Payment Protection — guaranteed</h4><p>Funds held in escrow, released only on your approval. Full dispute resolution included.</p></div></div>
        <div class="wf" onclick="openModal('post-job')"><div class="wf-ico">⚡</div><div class="wf-t"><h4>Hire in under 24 hours</h4><p>Post a job and receive qualified proposals from pre-vetted talent within a few hours.</p></div></div>
        <div class="wf" onclick="openModal('talent-marketplace')"><div class="wf-ico">🌍</div><div class="wf-t"><h4>Global talent, local expertise</h4><p>Access professionals in 180+ countries — from solo freelancers to full agencies.</p></div></div>
      </div>
    </div>
    <div class="why-visual">
      <div class="wv-main" onclick="openModal('trust-safety')">
        <h3>Remoworkers Guarantee™</h3>
        <p>Not satisfied with delivered work? We'll help find a resolution — including full refunds on fixed-price contracts if no milestone is ever approved. Your satisfaction is our standard.</p>
        <div class="wv-tags"><span class="wv-tag">💳 Escrow Protection</span><span class="wv-tag">🔒 Dispute Resolution</span><span class="wv-tag">🔄 Full Refund Policy</span></div>
      </div>
      <div class="wv-mini">
        <div class="wv-m" onclick="openModal('app-modal')"><div class="wv-mi">📱</div><h4>Mobile App</h4><p>Manage projects, messages, and payments on the go.</p></div>
        <div class="wv-m" onclick="openModal('enterprise')"><div class="wv-mi">📊</div><h4>Spend Analytics</h4><p>Real-time dashboards for tracking budgets and hours.</p></div>
        <div class="wv-m" onclick="openModal('integrations')"><div class="wv-mi">🔗</div><h4>Integrations</h4><p>Connect Slack, Jira, GitHub, and 50+ tools.</p></div>
        <div class="wv-m" onclick="openModal('ai-matching')"><div class="wv-mi">🤖</div><h4>AI Matching</h4><p>Smart recommendations based on your project needs.</p></div>
      </div>
    </div>
  </div>
</section>
<!-- ENTERPRISE -->
<section class="sec sec-alt">
  <div class="ent-grid">
    <div>
      <div class="ent-metrics">
        <div style="font-size:11px;font-weight:600;color:#8aa082;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Platform metrics</div>
        <div class="em" onclick="openModal('stats-talent')"><div class="em-ico">⚡</div><div><div class="em-n">2.4h</div><div class="em-l">Average time to first proposal</div></div></div>
        <div class="em" onclick="openModal('talent-marketplace')"><div class="em-ico">🌍</div><div><div class="em-n">180+</div><div class="em-l">Countries with active talent</div></div></div>
        <div class="em" onclick="openModal('stats-rating')"><div class="em-ico">📊</div><div><div class="em-n">92%</div><div class="em-l">Client satisfaction rate</div></div></div>
        <div class="em" onclick="openModal('trust-safety')"><div class="em-ico">💰</div><div><div class="em-n">$0</div><div class="em-l">Risk with Payment Protection</div></div></div>
        <div class="em" onclick="openModal('stats-talent')"><div class="em-ico">🔄</div><div><div class="em-n">67%</div><div class="em-l">Clients rehire the same talent</div></div></div>
      </div>
    </div>
    <div>
      <div class="sec-lbl">Enterprise</div>
      <div class="sec-title">Scale your team<br>without limits</div>
      <p class="sec-sub" style="margin-bottom:0">Remoworkers Enterprise gives your organization a managed platform for accessing pre-vetted talent at scale — with dedicated support and compliance tools.</p>
      <ul class="ent-list">
        <li>Dedicated talent success manager</li>
        <li>Custom contract templates & NDA workflows</li>
        <li>Consolidated billing & spend analytics dashboard</li>
        <li>SSO, team roles & granular permissions</li>
        <li>Exclusive access to Expert Vetted talent pool</li>
        <li>Priority 24/7 support with guaranteed SLA</li>
        <li>ATS integrations & compliance reporting</li>
      </ul>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a class="btn btn-dark btn-lg" onclick="openModal('enterprise')">Contact enterprise sales</a>
        <a class="btn btn-outline" onclick="openModal('pricing')">See all features</a>
      </div>
    </div>
  </div>
</section>
<!-- SKILLS -->
<section class="sec">
  <div class="sec-lbl">Popular skills</div>
  <div class="sec-title">Find experts in<br>any technology</div>
  <div class="skills-cloud">
    <span class="sk-pill hot" onclick="openModal('skill-react')">React <span class="sk-c">48K jobs</span></span>
    <span class="sk-pill hot" onclick="openModal('skill-python')">Python <span class="sk-c">62K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-node')">Node.js <span class="sk-c">31K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-wp')">WordPress <span class="sk-c">54K jobs</span></span>
    <span class="sk-pill hot" onclick="openModal('skill-ai')">ChatGPT / LLMs <span class="sk-c">18K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-shopify')">Shopify <span class="sk-c">29K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-logo')">Logo Design <span class="sk-c">36K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-ios')">iOS Development <span class="sk-c">22K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-copy')">Copywriting <span class="sk-c">41K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-data')">Data Analysis <span class="sk-c">27K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-video')">Video Editing <span class="sk-c">34K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-seo')">SEO <span class="sk-c">45K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-figma')">Figma <span class="sk-c">25K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-aws')">AWS / Cloud <span class="sk-c">19K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-vue')">Vue.js <span class="sk-c">14K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-social')">Social Media <span class="sk-c">38K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-book')">Bookkeeping <span class="sk-c">16K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-3d')">3D Modeling <span class="sk-c">12K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-trans')">Translation <span class="sk-c">21K jobs</span></span>
    <span class="sk-pill" onclick="openModal('skill-cyber')">Cybersecurity <span class="sk-c">9K jobs</span></span>
  </div>
</section>
<!-- BLOG -->
<section class="sec sec-alt">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px">
    <div><div class="sec-lbl">Resources & blog</div><div class="sec-title" style="margin-bottom:0">Insights to help<br>you grow</div></div>
    <a class="btn btn-outline" onclick="openModal('blog-all')">View all articles →</a>
  </div>
  <div class="blog-grid">
    <div class="blog-c" onclick="openModal('blog-0')"><div class="blog-img">🧠</div><div class="blog-body"><div class="blog-cat">Hiring Guide</div><div class="blog-title">How to write a job post that attracts top-tier talent</div><div class="blog-excerpt">The best freelancers are selective. Here's what separates a job post that gets ignored from one that lands 30 strong proposals in 24 hours.</div><div class="blog-meta">8 min read · May 2026</div></div></div>
    <div class="blog-c" onclick="openModal('blog-1')"><div class="blog-img">🤖</div><div class="blog-body"><div class="blog-cat">AI & Future of Work</div><div class="blog-title">The 10 most in-demand AI skills on Remoworkers right now</div><div class="blog-excerpt">From RAG pipelines to AI image generation, we analyzed 2 million job posts to find the skills clients are paying a premium for in 2026.</div><div class="blog-meta">5 min read · April 2026</div></div></div>
    <div class="blog-c" onclick="openModal('blog-2')"><div class="blog-img">💡</div><div class="blog-body"><div class="blog-cat">Freelancer Tips</div><div class="blog-title">How to build a profile that converts — from Top Rated freelancers</div><div class="blog-excerpt">We asked 50 Top Rated Plus freelancers what changed after they revamped their profile. The results were surprising and actionable.</div><div class="blog-meta">7 min read · April 2026</div></div></div>
  </div>
</section>
<!-- APP -->
<section class="app-sec">
  <div class="app-text">
    <h2>Work from anywhere<br>with our mobile app</h2>
    <p>Manage proposals, message talent, track milestones, approve work, and get paid — all from your phone. Rated 4.8★ on the App Store.</p>
    <div class="app-btns">
      <div class="abt" onclick="openModal('app-modal')"><span class="abt-ic">🍎</span><div class="abt-tx"><small>Download on the</small><strong>App Store</strong></div></div>
      <div class="abt" onclick="openModal('app-modal')"><span class="abt-ic">▶️</span><div class="abt-tx"><small>Get it on</small><strong>Google Play</strong></div></div>
    </div>
  </div>
  <div class="app-feats">
    <div class="af" onclick="openModal('app-modal')"><div class="af-ic">💬</div><h4>Instant Messaging</h4><p>Chat directly with clients or freelancers in real-time with secure file sharing.</p></div>
    <div class="af" onclick="openModal('app-modal')"><div class="af-ic">🔔</div><h4>Smart Notifications</h4><p>Never miss a proposal, message, milestone, or payment notification.</p></div>
    <div class="af" onclick="openModal('app-modal')"><div class="af-ic">⏱️</div><h4>Time Tracker</h4><p>Automatic time logs with activity screenshots for hourly contracts.</p></div>
    <div class="af" onclick="openModal('app-modal')"><div class="af-ic">💳</div><h4>Payments</h4><p>Fund milestones, release payments, and withdraw earnings instantly.</p></div>
  </div>
</section>

<!-- UMA AI SECTION -->
<section class="sec" style="background:linear-gradient(135deg,#16281a 0%,#1e3422 100%);position:relative;overflow:hidden">
  <div style="position:absolute;top:-80px;right:-80px;width:400px;height:400px;border-radius:50%;background:rgba(200,241,53,.05)"></div>
  <div style="position:absolute;bottom:-60px;left:-60px;width:300px;height:300px;border-radius:50%;background:rgba(200,241,53,.04)"></div>
  <div style="max-width:900px;margin:0 auto;text-align:center;position:relative;z-index:2">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(200,241,53,.12);border:1px solid rgba(200,241,53,.25);color:#c8f135;font-size:12px;font-weight:700;padding:6px 16px;border-radius:20px;margin-bottom:20px;letter-spacing:.05em">✨ NEW — Spring 2026</div>
    <h2 style="font-family:'Instrument Serif',serif;font-size:48px;color:white;letter-spacing:-1px;line-height:1.1;margin-bottom:16px;font-weight:400">Meet <em style="color:#c8f135;font-style:italic">Uma™</em> — Your AI work agent</h2>
    <p style="font-size:17px;color:rgba(255,255,255,.6);line-height:1.75;margin-bottom:28px;max-width:580px;margin-left:auto;margin-right:auto">Uma scopes your project, shortlists the best talent, generates contracts from your meetings, and keeps every project moving — automatically.</p>
    <div class="uma-grid">
      <div class="uma-card" onclick="openModal('uma-scout')">
        <div style="font-size:36px;margin-bottom:14px">🎯</div>
        <h4 style="font-size:15px;font-weight:700;color:white;margin-bottom:8px">Uma Recruiter Shortlisting</h4>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.65">Automatically identifies and surfaces the most relevant professionals for your project — available on all plans.</p>
        <span class="uma-link">Learn more →</span>
      </div>
      <div class="uma-card" onclick="openModal('uma-history')">
        <div style="font-size:36px;margin-bottom:14px">📋</div>
        <h4 style="font-size:15px;font-weight:700;color:white;margin-bottom:8px">Work History Summaries</h4>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.65">Uma synthesizes each freelancer's relevant past work into a clear, easy-to-scan summary — so you can compare candidates in seconds.</p>
        <span class="uma-link">Learn more →</span>
      </div>
      <div class="uma-card" onclick="openModal('uma-contract')">
        <div style="font-size:36px;margin-bottom:14px">📝</div>
        <h4 style="font-size:15px;font-weight:700;color:white;margin-bottom:8px">In-Meeting Contract Generator</h4>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.65">Key details from your video call are captured by Uma and used to auto-generate a contract draft — move from discussion to hire in minutes.</p>
        <span class="uma-link">Learn more →</span>
      </div>
      <div class="uma-card" onclick="openModal('uma-diary')">
        <div style="font-size:36px;margin-bottom:14px">⏱️</div>
        <h4 style="font-size:15px;font-weight:700;color:white;margin-bottom:8px">Work Diary Summaries</h4>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.65">Uma automatically summarizes hourly work activity into daily and weekly progress updates, so you always know what's happening.</p>
        <span class="uma-link">Learn more →</span>
      </div>
      <div class="uma-card" onclick="openModal('uma-continuity')">
        <div style="font-size:36px;margin-bottom:14px">🔄</div>
        <h4 style="font-size:15px;font-weight:700;color:white;margin-bottom:8px">Project Continuity</h4>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.65">If a hire isn't the right fit, Uma finds a replacement while preserving all project context — no disruption, no lost work.</p>
        <span class="uma-link">Learn more →</span>
      </div>
      <div class="uma-card" style="position:relative;overflow:hidden" onclick="openModal('uma-chatgpt')">
        <div style="position:absolute;top:12px;right:12px;background:#c8f135;color:#16281a;font-size:9.5px;font-weight:700;padding:2px 8px;border-radius:10px">NEW</div>
        <div style="font-size:36px;margin-bottom:14px">🤖</div>
        <h4 style="font-size:15px;font-weight:700;color:white;margin-bottom:8px">Remoworkers in ChatGPT</h4>
        <p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.65">Describe your project in ChatGPT and discover relevant Remoworkers talent, draft job posts, and hire — without leaving ChatGPT.</p>
        <span class="uma-link">Connect now →</span>
      </div>
    </div>
    <a class="btn btn-lime btn-lg" onclick="openModal('uma-scout')" style="margin-right:12px">Try Uma™ Free</a>
    <a class="btn" style="background:rgba(255,255,255,.1);color:white;padding:14px 32px;border-radius:40px;font-size:15px;border:1px solid rgba(255,255,255,.2)" onclick="openModal('uma-history')">See all Uma features</a>
  </div>
</section>

<!-- WORK TYPES -->
<section class="sec sec-alt">
  <div style="text-align:center;margin-bottom:22px">
    <div class="sec-lbl" style="text-align:center">Every way to work</div>
    <div class="sec-title" style="text-align:center;margin-bottom:12px">Freelance, fractional,<br>or fully payrolled</div>
    <p class="sec-sub" style="text-align:center;margin:0 auto;max-width:500px">Remoworkers supports every type of independent engagement — from a quick project to a full-time contract with payroll compliance.</p>
  </div>
  <div class="wt-grid">
    <div class="wt-card" onclick="openModal('work-type-freelance')">
      <div style="font-size:44px;margin-bottom:18px">🚀</div>
      <h3 style="font-size:19px;font-weight:700;margin-bottom:10px">Freelance</h3>
      <p style="font-size:14px;color:#617a5a;line-height:1.75;margin-bottom:16px">Hire independently for a specific project or ongoing work. Pay hourly or fixed-price. Full payment protection included.</p>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:20px">
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>Hourly or fixed-price contracts</li>
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>Milestone-based escrow payments</li>
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>No long-term commitment</li>
      </ul>
      <span style="font-size:13px;font-weight:600;color:#14a800">Explore Freelance →</span>
    </div>
    <div class="wt-card featured" onclick="openModal('work-type-fractional')">
      <div style="position:absolute;top:16px;right:16px;background:#c8f135;color:#16281a;font-size:10px;font-weight:700;padding:3px 10px;border-radius:12px">Most Popular</div>
      <div style="font-size:44px;margin-bottom:18px">⚡</div>
      <h3 style="font-size:19px;font-weight:700;margin-bottom:10px">Fractional</h3>
      <p style="font-size:14px;color:#617a5a;line-height:1.75;margin-bottom:16px">Get a senior expert part-time — a fractional CMO, CTO, or CFO — without the overhead of a full-time hire.</p>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:20px">
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>Senior-level expertise, flexible hours</li>
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>10–30 hrs/week commitment</li>
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>60% less than full-time equivalent</li>
      </ul>
      <span style="font-size:13px;font-weight:600;color:#14a800">Explore Fractional →</span>
    </div>
    <div class="wt-card" onclick="openModal('work-type-payroll')">
      <div style="font-size:44px;margin-bottom:18px">🏢</div>
      <h3 style="font-size:19px;font-weight:700;margin-bottom:10px">Payrolled</h3>
      <p style="font-size:14px;color:#617a5a;line-height:1.75;margin-bottom:16px">Hire a freelancer as a compliant W-2 employee through Remoworkers's payroll services. We handle taxes, benefits, and compliance.</p>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:20px">
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>Full-time contract compliance</li>
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>Payroll taxes & benefits handled</li>
        <li style="font-size:13px;color:#333;display:flex;align-items:center;gap:8px"><span style="color:#14a800;font-weight:700">✓</span>Convert freelancers to employees</li>
      </ul>
      <span style="font-size:13px;font-weight:600;color:#14a800">Explore Payroll →</span>
    </div>
  </div>
</section>

<!-- VIDEO MEETINGS + DIRECT CONTRACTS -->
<section class="sec">
  <div class="tools-grid">
    <div>
      <div class="sec-lbl">Built-in collaboration tools</div>
      <div class="sec-title">Video meetings,<br>contracts, work diaries</div>
      <p class="sec-sub" style="margin-bottom:22px">Remoworkers includes the tools you need to manage every project — from first conversation to final payment — without leaving the platform.</p>
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="tool-row" onclick="openModal('video-meetings')">
          <div class="tool-row-ico">📹</div>
          <div><h4 style="font-size:15px;font-weight:700;margin-bottom:5px">Video Meetings</h4><p style="font-size:13.5px;color:#617a5a;line-height:1.65">Built-in video calls with AI transcripts, instant recaps, and Uma's in-meeting contract generator. Mobile compatible.</p></div>
        </div>
        <div class="tool-row" onclick="openModal('direct-contract')">
          <div class="tool-row-ico">📄</div>
          <div><h4 style="font-size:15px;font-weight:700;margin-bottom:5px">Direct Contracts</h4><p style="font-size:13.5px;color:#617a5a;line-height:1.65">Already found someone? Bring them onto Remoworkers and get escrow protection, billing, and work tools — on any engagement.</p></div>
        </div>
        <div class="tool-row" onclick="openModal('uma-diary')">
          <div class="tool-row-ico">📒</div>
          <div><h4 style="font-size:15px;font-weight:700;margin-bottom:5px">Work Diary & Time Tracker</h4><p style="font-size:13.5px;color:#617a5a;line-height:1.65">Automatic 10-minute interval screenshots and keystroke activity logs for hourly contracts. Uma summarizes it all for you daily.</p></div>
        </div>
        <div class="tool-row" onclick="openModal('connects')">
          <div class="tool-row-ico">🔗</div>
          <div><h4 style="font-size:15px;font-weight:700;margin-bottom:5px">Connects — Apply with Intent</h4><p style="font-size:13.5px;color:#617a5a;line-height:1.65">Freelancers use Connects to apply for jobs — a lightweight token system that filters serious applicants from mass spammers.</p></div>
        </div>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="meeting-card" onclick="openModal('video-meetings')">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
          <div style="width:44px;height:44px;border-radius:50%;background:#d1fae5;color:#065f46;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px">AN</div>
          <div><div style="font-size:13.5px;font-weight:700;color:white">Video Call with Anika Nkosi</div><div style="font-size:11px;color:rgba(255,255,255,.4)">Today, 3:00 PM · 28 min</div></div>
          <div style="margin-left:auto;background:rgba(200,241,53,.15);color:#c8f135;font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px">AI Recap Ready</div>
        </div>
        <div style="background:rgba(255,255,255,.06);border-radius:12px;padding:14px;margin-bottom:14px">
          <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Uma's Meeting Summary</div>
          <p style="font-size:13px;color:rgba(255,255,255,.7);line-height:1.65">"Discussed logo redesign scope — 3 concepts, brand colors, and full guidelines. Budget agreed: $450. Timeline: 5 days."</p>
        </div>
        <div style="display:flex;gap:8px">
          <button onclick="event.stopPropagation();openModal('uma-contract')" style="flex:1;background:#c8f135;color:#16281a;border:none;padding:10px;border-radius:10px;font-size:12.5px;font-weight:700;cursor:pointer;font-family:inherit">✍️ Generate Contract</button>
          <button onclick="event.stopPropagation();showToast('Transcript ready','Full meeting transcript available in your dashboard')" style="flex:1;background:rgba(255,255,255,.1);color:white;border:none;padding:10px;border-radius:10px;font-size:12.5px;font-weight:600;cursor:pointer;font-family:inherit">📄 View Transcript</button>
        </div>
      </div>
      <div class="mini-tiles">
        <div class="mini-tile" onclick="openModal('connects')">
          <div style="font-size:28px;margin-bottom:8px">🔗</div>
          <h4 style="font-size:13px;font-weight:700;margin-bottom:4px">Connects Balance</h4>
          <p style="font-size:12px;color:#617a5a">You have <strong style="color:#14a800">42 Connects</strong>. Apply for up to 14 jobs.</p>
        </div>
        <div class="mini-tile" onclick="openModal('bonus-tip')">
          <div style="font-size:28px;margin-bottom:8px">🎁</div>
          <h4 style="font-size:13px;font-weight:700;margin-bottom:4px">Send a Bonus</h4>
          <p style="font-size:12px;color:#617a5a">Reward great work with a tip on top of the contract payment.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CERTIFICATIONS + AGENCIES -->
<section class="sec sec-alt">
  <div class="cert-agency-grid">
    <div>
      <div class="sec-lbl">Skill certifications</div>
      <div class="sec-title">Earn badges that<br>build instant trust</div>
      <p class="sec-sub" style="margin-bottom:28px">Take verified skill assessments to earn badges that appear prominently on your profile — helping you stand out to clients at a glance.</p>
      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:28px">
        <div class="cert-item" onclick="openModal('certifications')">
          <div style="width:44px;height:44px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">🏅</div>
          <div style="flex:1"><div style="font-size:14px;font-weight:700;margin-bottom:3px">React.js — Advanced</div><div style="font-size:12px;color:#617a5a">Verified skill assessment · Top 15% of test takers</div></div>
          <div style="background:#e8f5e3;color:#0a6b00;font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px">Verified ✓</div>
        </div>
        <div class="cert-item" onclick="openModal('certifications')">
          <div style="width:44px;height:44px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">🤖</div>
          <div style="flex:1"><div style="font-size:14px;font-weight:700;margin-bottom:3px">AI Prompt Engineering</div><div style="font-size:12px;color:#617a5a">Remoworkers Certified · Top 8% of test takers</div></div>
          <div style="background:#e8f5e3;color:#0a6b00;font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px">Verified ✓</div>
        </div>
        <div class="cert-item" onclick="openModal('certifications')">
          <div style="width:44px;height:44px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">📈</div>
          <div style="flex:1"><div style="font-size:14px;font-weight:700;margin-bottom:3px">SEO — Comprehensive</div><div style="font-size:12px;color:#617a5a">Google-certified · 150+ questions</div></div>
          <div style="background:#e8f5e3;color:#0a6b00;font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px">Verified ✓</div>
        </div>
      </div>
      <a class="btn btn-green" onclick="openModal('certifications')">Take a skill assessment →</a>
    </div>
    <div>
      <div class="sec-lbl">Agency accounts</div>
      <div class="sec-title">Hire a team,<br>not just a freelancer</div>
      <p class="sec-sub" style="margin-bottom:28px">Remoworkers Agencies give you access to full teams with a lead contact who manages delivery — perfect for larger, ongoing projects.</p>
      <div class="agency-card" onclick="openModal('agencies')">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
          <div style="font-size:28px">🏢</div>
          <div><div style="font-size:15px;font-weight:700;color:white">Pixel & Co. Design Agency</div><div style="font-size:12px;color:rgba(255,255,255,.45)">Berlin, Germany · 8 team members · ★ 5.0</div></div>
        </div>
        <div style="display:flex;gap:-8px;margin-bottom:12px">
          <div style="width:34px;height:34px;border-radius:50%;background:#d1fae5;color:#065f46;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;border:2px solid #16281a">AN</div>
          <div style="width:34px;height:34px;border-radius:50%;background:#dbeafe;color:#1e40af;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;border:2px solid #16281a;margin-left:-8px">JK</div>
          <div style="width:34px;height:34px;border-radius:50%;background:#fef3c7;color:#92400e;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;border:2px solid #16281a;margin-left:-8px">LT</div>
          <div style="width:34px;height:34px;border-radius:50%;background:#ede9fe;color:#5b21b6;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;border:2px solid #16281a;margin-left:-8px">+5</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <span style="background:rgba(200,241,53,.12);border:1px solid rgba(200,241,53,.22);color:#c8f135;font-size:11px;padding:3px 10px;border-radius:12px">UI/UX Design</span>
          <span style="background:rgba(200,241,53,.12);border:1px solid rgba(200,241,53,.22);color:#c8f135;font-size:11px;padding:3px 10px;border-radius:12px">Webflow</span>
          <span style="background:rgba(200,241,53,.12);border:1px solid rgba(200,241,53,.22);color:#c8f135;font-size:11px;padding:3px 10px;border-radius:12px">Branding</span>
          <span style="background:rgba(200,241,53,.12);border:1px solid rgba(200,241,53,.22);color:#c8f135;font-size:11px;padding:3px 10px;border-radius:12px">Motion Design</span>
        </div>
      </div>
      <a class="btn btn-dark" onclick="openModal('agencies')">Browse all agencies →</a>
    </div>
  </div>
</section>
<!-- CTA -->
<section class="cta-sec">
  <h2>Start hiring — or<br>start <em>earning</em> — today</h2>
  <p>Join 5 million professionals. No commitment. No credit card required to post a job.</p>
  <div class="cta-acts">
    <a class="btn btn-lime btn-lg" onclick="openModal('login')">Hire a Freelancer</a>
    <a class="btn btn-white btn-lg" onclick="openModal('signup')">Find Work</a>
  </div>
  <div class="cta-trust">
    <span class="ct-item" onclick="openModal('post-job')">Free to post a job</span>
    <span class="ct-item" onclick="openModal('pricing')">No subscription required</span>
    <span class="ct-item" onclick="openModal('trust-safety')">Payment Protection included</span>
    <span class="ct-item" onclick="openModal('help-center')">24/7 support</span>
  </div>
</section>
<!-- FOOTER -->
<footer>
  <div class="ft">
    <div>
      <a class="logo" onclick="scrollToTop()" style="filter:brightness(1.1)"><span class="logo-icon" style="box-shadow:0 2px 8px rgba(200,241,53,.2)"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M6 20c0-4 2.7-7 6-7s6 3 6 7"/><path d="M19 8c1.5.8 2.5 2.4 2.5 4.2 0 1.5-.6 2.9-1.6 3.8"/><path d="M5 8C3.5 8.8 2.5 10.4 2.5 12.2c0 1.5.6 2.9 1.6 3.8"/></svg></span><span class="logo-remo" style="color:#fff">Remo</span><span class="logo-workers" style="color:#c8f135">workers</span></a>
      <p class="fb-desc">The world's work marketplace. Connecting businesses with independent talent across 180+ countries.</p>
      <div class="fb-social">
        <div class="fsoc" onclick="showToast('Follow us on X','@Remoworkers for daily updates')">𝕏</div>
        <div class="fsoc" onclick="showToast('Connect on LinkedIn','Remoworkers official page')">in</div>
        <div class="fsoc" onclick="showToast('Like us on Facebook','Remoworkers community')">f</div>
        <div class="fsoc" onclick="showToast('Watch on YouTube','Tutorials, tips & stories')">▶</div>
        <div class="fsoc" onclick="showToast('Follow on Instagram','Behind the scenes & talent')">📸</div>
      </div>
    </div>
    <div class="fc-col"><h5>For Clients</h5><ul><li><a onclick="openModal('post-job')">How to Hire</a></li><li><a onclick="openModal('talent-marketplace')">Talent Marketplace</a></li><li><a onclick="openModal('project-catalog-modal')">Project Catalog</a></li><li><a onclick="openModal('talent-scout')">Talent Scout</a></li><li><a onclick="openModal('enterprise')">Enterprise Solutions</a></li><li><a onclick="openModal('trust-safety')">Payment Protection</a></li><li><a onclick="openModal('pricing')">Pricing</a></li></ul></div>
    <div class="fc-col"><h5>For Talent</h5><ul><li><a onclick="openModal('browse-jobs')">How to Find Work</a></li><li><a onclick="openModal('browse-jobs')">Browse Jobs</a></li><li><a onclick="openModal('sell-services')">Sell Services</a></li><li><a onclick="openModal('signup')">Build Your Profile</a></li><li><a onclick="showToast('Community Forum','Connect with 50K+ freelancers worldwide')">Community Forum</a></li><li><a onclick="openModal('blog-1')">Career Resources</a></li><li><a onclick="showToast('Certifications','Earn verified skill badges for your profile')">Certifications</a></li></ul></div>
    <div class="fc-col"><h5>Resources</h5><ul><li><a onclick="openModal('help-center')">Help Center</a></li><li><a onclick="openModal('blog-all')">Blog & Insights</a></li><li><a onclick="openModal('test-0')">Success Stories</a></li><li><a onclick="openModal('blog-0')">Hiring Guides</a></li><li><a onclick="showToast('Templates','Download free NDA & contract templates')">Templates</a></li><li><a onclick="openModal('trust-safety')">Trust & Safety</a></li><li><a onclick="showToast('Community','Join 50K+ professionals')">Community</a></li></ul></div>
    <div class="fc-col"><h5>Company</h5><ul><li><a onclick="showToast('About Remoworkers','Founded 2020 · 180+ countries · 5M+ users')">About Us</a></li><li><a onclick="showToast('We\'re Hiring!','25 open roles in product, engineering & design')">Careers</a></li><li><a onclick="showToast('Press Room','Media kit, press releases & brand assets')">Press Room</a></li><li><a onclick="showToast('Investor Relations','Financial reports & governance')">Investor Relations</a></li><li><a onclick="showToast('Partner Program','Earn 20% commission as a Remoworkers partner')">Partners</a></li><li><a onclick="showToast('Affiliate Program','Promote Remoworkers and earn rewards')">Affiliates</a></li><li><a onclick="openModal('help-center')">Contact Us</a></li></ul></div>
  </div>
  <div class="fb-bottom">
    <span class="fb-copy">© 2026 Remoworkers Inc. All rights reserved.</span>
    <div class="fb-badges">
      <div class="fbb" onclick="openModal('trust-safety')">🔒 SSL Secured</div>
      <div class="fbb" onclick="openModal('trust-safety')">✅ GDPR Compliant</div>
      <div class="fbb" onclick="openModal('trust-safety')">🏆 ISO 27001</div>
    </div>
    <div class="fb-legal">
      <a onclick="showToast('Privacy Policy','We never sell your data. Ever.')">Privacy Policy</a>
      <a onclick="showToast('Terms of Service','Remoworkers ToS — last updated March 2026')">Terms of Service</a>
      <a onclick="showToast('Cookie Settings','Manage your cookie preferences')">Cookie Settings</a>
      <a onclick="showToast('Accessibility','WCAG 2.1 AA compliant platform')">Accessibility</a>
    </div>
  </div>
</footer>

<?php include __DIR__ . '/includes/footer.php'; ?>