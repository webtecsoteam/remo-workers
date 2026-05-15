
Warning: Module "imagick" is already loaded in Unknown on line 0

const BASE_URL = '';
// ─── CLIENT DATABASE ───────────────────────────────────────────────
// Add any client here. Their spent badge appears automatically on every
// job they post — no other changes needed.
const CLIENTS = {
  'clearpath-finance': { name:'ClearPath Finance', rating:'4.9', hires:18, location:'Berlin, Germany', spent:'$89K+' },
  'launchpad-hq':      { name:'Launchpad HQ',      rating:'5.0', hires:8,  location:'London, UK',      spent:'$41K+' },
  'edtech-platform':   { name:'EdTech Platform',    rating:'4.8', hires:4,  location:'Toronto, Canada', spent:'$24K+' },
};

// ─── JOB LISTINGS ─────────────────────────────────────────────────
const JOBS = ;

// ─── SPENT BADGE HELPER ───────────────────────────────────────────
function spentBadge(clientId) {
  const c = CLIENTS[clientId];
  if (!c || !c.spent) return '';
  return `💰 ${c.spent} spent`;
}

// ─── JOB ROW RENDERER ─────────────────────────────────────────────
function jobRowHTML(job, compact = false) {
  const c = CLIENTS[job.clientId] || {};
  const clientMeta = `Payment verified · ★ ${c.rating || '—'} client · ${c.hires || 0} hires · ${c.location || ''}`;
  const matchTag = job.match ? `${job.match} match` : '';
  const titleSize = compact ? '13.5px' : '14px';
  const descSize  = compact ? '12.5px' : '13px';
  const metaSize  = compact ? '12px'   : '12.5px';

  const badges = [
    `${job.type}${job.rate ? ' · ' + job.rate : ''}`,
    ...job.meta.map(m => `${m}`)
  ].join('');

  const applyBtn = `Apply Now`;

  const rightCol = compact
    ? `
        Posted ${job.posted}
        ${job.meta[1] || ''}
        ⚡ Costs ${job.connects} Connects
      `
    : `
        ${applyBtn}
        ⚡ ${job.connects} Connects
        Posted ${job.posted}
      `;

  const clickAction = job.id ? `openModal('${job.id}')` : `toast('Job','Opening ${job.title}')`;

  return `
    
      
        ${job.title}${matchTag}
        ${job.desc}
        ${clientMeta}${spentBadge(job.clientId)}
        ${badges}
      
      ${rightCol}
    
  `;
}

// ─── RENDER INTO BOTH CONTAINERS ─────────────────────────────────
function renderJobs() {
  // Home dashboard: first 2 jobs, compact style
  document.getElementById('home-job-list').innerHTML =
    JOBS.slice(0, 2).map(j => jobRowHTML(j, true)).join('');
  // Find Work page: all jobs, full style
  document.getElementById('findwork-job-list').innerHTML =
    JOBS.map(j => jobRowHTML(j, false)).join('');
}

const MODALS={
'connects':{t:'Connects',b:`

  🔗
  
     Connects
    Available to apply for jobs
  


42 of 80 Connects used

Buy more Connects


  
    10
    KES 150
    ~$1.15/connect
  
  
    20
    KES 280
    ~$1.07/connect
  
  
    40
    KES 520 · Best value
    Save 10%
  
  
    80
    KES 960
    Save 15%
  



  
    40 Connects selected
    KES 520
  



  
  Pay with Paystack →



  
  Secured by Paystack · Cards, M-Pesa & more accepted


  Each job requires 2–6 Connects to apply. Unused Connects are refunded if a job closes without a hire within 90 days.
`},
'apply-job': {
    t: 'Submit Proposal',
    b: `
      
        
        
           · You have  Connects
        
        Your Bid Amount ($)
        Cover Letter *
        
          Cancel
          
            Submit Proposal →
          
        
      
    `
  },
'contract-detail':{t:'Contract — NexaFlow Inc.',b:`TypeHourly · $90/hrHours This Week8.5 hrsTotal Earned$3,105Weekly LimitNo limitLog TimeHours to LogWork DescriptionLog HoursMessage Client📹 Video CallPause Contract`},
'fintech-milestones':{t:'Mobile App Redesign (iOS) — FinTech Co.',b:`

  FT
  FinTech Co.Fixed-price contract · ★ 4.9 · Payment verified
  $4,500Total contract value


Milestones



  
    
      ✓
      
        Milestone 1 — Wireframes &amp; Information Architecture
        Approved by client · May 7, 2026
      
    
    
      $2,250
      Released
    
  




  
    
      2
      
        Milestone 2 — High-Fidelity Screens &amp; Prototype
        Due: May 20, 2026 · Funded &amp; ready to submit
      
    
    
      $2,250
      Funded
    
  

  
    Scope of work
    
      12 high-fidelity iOS screens in Figma
      Interactive prototype with core user flows
      Responsive variants for iPhone 14 &amp; SE
      Developer-ready handoff with specs &amp; assets
    
  

  
    
    📤 Submit Work for Review

    
      Work Summary (describe what you've completed)
      
    

    
      Deliverable Links
      
      
    

    
      Attachments (optional — PDFs, ZIPs, screenshots)
      
        📎
        Drag & drop files here or browse
        PDF, ZIP, PNG · max 25 MB each
      
      
      
    

    
      ⚠️ Important: Once submitted, the client has 14 days to review and approve. If they don't respond, the milestone is auto-approved and funds are released. You can send one revision request per milestone.
    

    
      Cancel
      🚀 Submit Milestone for Approval →
    
  

  
    💬 Message Client
    📤 Submit Work for Approval →
  



  💬 Message Client
  📹 Video Call

`},
'msg-nexaflow':{t:'Message from NexaFlow Inc.',b:`NXNexaFlow Inc.Active contract · ★ 5.0"These look amazing, Anika! The new navigation pattern is exactly what we had in mind and the component library documentation is incredibly thorough. Can we hop on a 20-min call to walk through the mobile variants together? Are you free Thursday at 3pm Berlin time?"Open Chat📹 Start Video Call`},
'edit-profile':{t:'Edit Profile',b:`

  Full Name
  Title / Headline


  Hourly Rate ($/hr)
  Country / Location


  Languages
  AvailabilityAvailable for WorkLimited AvailabilityNot Available

Bio / OverviewSenior UI/UX Designer with 8+ years crafting user-centered digital products for startups and Fortune 500 companies. Specialized in design systems, mobile apps, and Webflow development...
Portfolio / Website URL
LinkedIn Profile

  Cancel
  Save Changes →
`},
'new-service':{t:'Create a Service Package',b:`Service TitleBase Price ($)Delivery (days)What's IncludedPublish Service →`},
'service-1':{t:'UI/UX Design System Build',b:`Price$1,800Orders12Rating★ 5.0PauseEdit`}
};
MODALS['change-password'] = {
  t: '🔑 Change Password',
  b: `
    
      
        🔒 Choose a strong password — at least 8 characters with a mix of letters, numbers, and symbols.
      
      
        Current Password
        
          
          👁
        
      
      
        New Password
        
          
          👁
        
        
          
        
        
      
      
        Confirm New Password
        
          
          👁
        
        
      
    
    
      Cancel
      Update Password →
    
  `
};

function togglePwVis(inputId, eyeEl) {
  const inp = document.getElementById(inputId);
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  eyeEl.textContent = inp.type === 'password' ? '👁' : '🙈';
}

function checkPwStrength(val) {
  const bar = document.getElementById('pw-strength-bar');
  const fill = document.getElementById('pw-strength-fill');
  const lbl = document.getElementById('pw-strength-label');
  if (!bar || !fill || !lbl) return;
  if (!val) { bar.style.display = 'none'; lbl.style.display = 'none'; return; }
  bar.style.display = 'block'; lbl.style.display = 'block';
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/\d/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    { pct: 20,  bg: '#dc2626', text: 'Very weak' },
    { pct: 40,  bg: '#f97316', text: 'Weak' },
    { pct: 60,  bg: '#eab308', text: 'Fair' },
    { pct: 80,  bg: '#84cc16', text: 'Strong' },
    { pct: 100, bg: '#14a800', text: 'Very strong ✓' },
  ];
  const lvl = levels[Math.max(0, score - 1)] || levels[0];
  fill.style.width = lvl.pct + '%';
  fill.style.background = lvl.bg;
  lbl.style.color = lvl.bg;
  lbl.textContent = lvl.text;
  checkPwMatch();
}

function checkPwMatch() {
  const nv = document.getElementById('pw-new')?.value || '';
  const cv = document.getElementById('pw-confirm')?.value || '';
  const msg = document.getElementById('pw-match-msg');
  if (!msg || !cv) { if (msg) msg.style.display = 'none'; return; }
  msg.style.display = 'block';
  if (nv === cv) {
    msg.textContent = '✓ Passwords match';
    msg.style.color = '#14a800';
  } else {
    msg.textContent = '✗ Passwords do not match';
    msg.style.color = '#dc2626';
  }
}

function checkAndApply(modalId, connects, jobId) {
  const job = JOBS.find(j => j.id == jobId);
  if (!job) return toast('Error', 'Job not found');

  openModal('apply-job');
  
  // Set job context in modal after it opens
  setTimeout(() => {
    const titleEl = document.getElementById('mh-title');
    if (titleEl) titleEl.innerText = 'Submit Proposal — ' + job.title;
    
    const idEl = document.getElementById('ap-job-id');
    if (idEl) idEl.value = job.id;
    
    const metaEl = document.getElementById('ap-job-meta');
    if (metaEl) metaEl.innerText = `${job.type} · ${job.rate} · Costs 4 Connects`;
  }, 50);
}

async function submitProposal() {
  const jobId = document.getElementById('ap-job-id').value;
  const bid = document.getElementById('ap-bid').value;
  const cover = document.getElementById('ap-cover').value;
  const btn = document.getElementById('ap-submit-btn');
  const btnText = document.getElementById('ap-btn-text');

  if (!bid || !cover) return toast('Error', 'Please fill in all fields');

  btn.disabled = true;
  btnText.innerText = 'Submitting...';

  const fd = new FormData();
  fd.append('job_id', jobId);
  fd.append('bid_amount', bid);
  fd.append('cover_letter', cover);

  try {
    const res = await fetch(BASE_URL + '/freelancer/actions/submit_proposal.php', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();

    if (data.success) {
      toast('Success!', 'Your proposal has been submitted.');
      // Update UI connects
      const newBal = data.new_connects;
      document.querySelectorAll('.user-connects-bal').forEach(el => el.textContent = newBal);
      const sbConnects = document.getElementById('sb-connects-val');
      if(sbConnects) sbConnects.textContent = newBal;
      const navConnects = document.getElementById('sb-nav-connects-val');
      if(navConnects) navConnects.textContent = newBal;
      const countDisplay = document.getElementById('connects-count-display');
      if(countDisplay) countDisplay.textContent = newBal + ' Connects';
      
      closeModal();
    } else {
      toast('Error', data.error);
      btn.disabled = false;
      btnText.innerText = 'Submit Proposal →';
    }
  } catch (e) {
    toast('Error', 'Submission failed. Please try again.');
    btn.disabled = false;
    btnText.innerText = 'Submit Proposal →';
  }
}


function submitPasswordReset() {
  const cur  = document.getElementById('pw-current')?.value.trim();
  const nw   = document.getElementById('pw-new')?.value;
  const conf = document.getElementById('pw-confirm')?.value;
  if (!cur) {
    toast('Required', 'Please enter your current password');
    document.getElementById('pw-current')?.focus();
    return;
  }
  if (!nw || nw.length < 8) {
    toast('Too short', 'New password must be at least 8 characters');
    document.getElementById('pw-new')?.focus();
    return;
  }
  if (nw !== conf) {
    toast('Mismatch', 'New passwords do not match');
    document.getElementById('pw-confirm')?.focus();
    return;
  }
  // Simulate server call
  const btn = document.getElementById('pw-save-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'Updating…'; btn.style.opacity = '.7'; }
  setTimeout(() => {
    closeModal();
    toast('Password updated ✓', 'Your new password is active — you may need to re-login on other devices');
  }, 900);
}

function openModal(id){const m=MODALS[id];if(!m)return toast('Detail','Loading...');document.getElementById('mh-title').textContent=m.t;document.getElementById('mc-body').innerHTML=m.b;document.getElementById('overlay').classList.add('open');document.body.style.overflow='hidden';}
function closeModal(){document.getElementById('overlay').classList.remove('open');document.body.style.overflow='';}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
let tt;
function toast(title,msg){const el=document.getElementById('toast');document.getElementById('t-title').textContent=title;document.getElementById('t-msg').textContent=msg?(' — '+msg):'';el.classList.add('show');clearTimeout(tt);tt=setTimeout(()=>el.classList.remove('show'),3500);}
function toggleSidebar() {
  const sb = document.getElementById('main-sidebar');
  const ov = document.getElementById('mob-overlay');
  if(sb) sb.classList.toggle('mob-open');
  if(ov) ov.classList.toggle('open');
}
function showPage(id,navEl){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  const pg=document.getElementById('page-'+id);
  if(pg){pg.classList.add('active');pg.scrollTop=0;}
  document.querySelectorAll('.sb-item').forEach(i=>i.classList.remove('active'));
  if(navEl)navEl.classList.add('active');
  // Sync mobile bottom nav active state
  document.querySelectorAll('.mob-nav-item').forEach(el=>el.classList.remove('active'));
  const mobNavId={'home':'home','find-work':'find-work','proposals':'find-work','contracts':'contracts','messages':'messages','earnings':'earnings','catalog':'profile','profile':'profile','reports':'earnings'}[id]||id;
  const mobEl=document.getElementById('mn-'+mobNavId);
  if(mobEl)mobEl.classList.add('active');
  const titles={home:'Dashboard','find-work':'Find Work',proposals:'My Proposals',contracts:'My Contracts',messages:'Messages',earnings:'Earnings',catalog:'My Services',profile:'My Profile',reports:'Payment Reports',verification:'ID Verification'};
  const titleEl = document.getElementById('page-title');
  if(titleEl) titleEl.textContent=titles[id]||id;
  if(id==='reports')setTimeout(renderReports,50);
  if(id==='profile')setTimeout(renderSuggestedSkills,50);
  // Scroll main content to top on mobile
  if(window.innerWidtht.classList.remove('on'));el.classList.add('on');}
const EARNINGS_INFO = {
  wip: `🕐 Work in Progress — $765.00
    Hours you've logged this billing week on active hourly contracts. These funds are not yet billed — they become billable after the weekly billing period closes (Sunday midnight UTC). The amount updates in real time as you log more hours.
    Current week: 8.5 hrs × $90/hr = $765 · NexaFlow Inc.`,

  review: `🔍 In Review — $1,350.00
    The billing week has ended and your hours have been submitted. Clients have a 5-day dispute window to review your work diary and raise a dispute if needed. If no dispute is filed, funds automatically move to Pending.
    Week May 5–11 · 15 hrs · DataStack · Dispute window closes May 16`,

  pending: `⏳ Pending — $2,550.00
    Funds in the 5-day security hold before they become available to withdraw. This includes:
    • Hourly billing that passed the review window
    • Bonuses paid by clients (skip review and go straight here)
    • Fixed-price milestones approved by the client
    $500 bonus from NexaFlow Inc. · $2,050 hourly billing · Earliest release: May 18`,

  available: `✅ Available — $12,800.00
    Funds that have cleared all holds and are ready to withdraw. You can transfer to your bank, PayPal, Payoneer, or other connected payment method at any time. Withdrawals typically arrive in 1–5 business days depending on your method.
    Last payment received: $2,950.00 on May 12`,
};

function showEarningsInfo(key) {
  const panel = document.getElementById('earnings-info-panel');
  if (panel.dataset.open === key) {
    panel.style.display = 'none';
    panel.dataset.open = '';
  } else {
    panel.innerHTML = EARNINGS_INFO[key] || '';
    panel.style.display = 'block';
    panel.dataset.open = key;
  }
}

renderJobs();
setTimeout(()=>toast('Welcome back, Anika!','3 unread messages · New client invite received'),1000);

// ════════════════════════════════════════════════════════════
//  PAYSTACK CONNECTS PURCHASE
// ════════════════════════════════════════════════════════════

// Live freelancer state
const freelancerState = {
  connects: ,
  maxConnects: 80,
  email: '',
  name: '',
};

// Selected package state
let selectedPkg = { connects: 40, amountKES: 520, pkgId: 'pkg-40' };

function selectConnectPkg(connects, amountKES, pkgId) {
  selectedPkg = { connects, amountKES, pkgId };

  // Reset all package borders
  document.querySelectorAll('.connect-pkg').forEach(el => {
    el.style.border = '1px solid var(--border)';
    el.style.background = 'white';
  });

  // Highlight chosen — keep "Best value" green treatment
  const chosen = document.getElementById(pkgId);
  if (chosen) {
    chosen.style.border = '2px solid var(--g)';
    chosen.style.background = 'var(--gl)';
  }

  // Show summary bar
  const summaryBar = document.getElementById('connect-selected-pkg');
  if (summaryBar) {
    summaryBar.style.display = 'block';
    document.getElementById('pkg-summary-text').textContent = `${connects} Connects`;
    document.getElementById('pkg-summary-price').textContent = `KES ${amountKES.toLocaleString()}`;
  }

  // Show pay button
  const payBtn = document.getElementById('paystack-pay-btn');
  if (payBtn) payBtn.style.display = 'flex';
}

function initiatePaystackPayment() {
  const { connects, amountKES } = selectedPkg;

  // Paystack expects amount in lowest denomination (kobo for NGN, pesewas for GHS, cents for KES)
  // KES uses cents: multiply by 100
  const amountCents = amountKES * 100;

  const handler = PaystackPop.setup({
    // ⚠️ Replace with your live public key from dashboard.paystack.com
    key: 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    email: freelancerState.email,
    amount: amountCents,
    currency: 'KES',
    ref: 'WB-CNX-' + Date.now() + '-' + Math.floor(Math.random() * 1000000),
    metadata: {
      custom_fields: [
        { display_name: 'Product', variable_name: 'product', value: 'Remoworkers Connects' },
        { display_name: 'Connects', variable_name: 'connects', value: String(connects) },
        { display_name: 'Freelancer', variable_name: 'freelancer', value: freelancerState.name },
      ]
    },
    callback: function(response) {
      // Payment successful — update UI
      onPaystackSuccess(response, connects, amountKES);
    },
    onClose: function() {
      toast('Payment cancelled', 'You closed the payment window — no charge made');
    }
  });

  handler.openIframe();
}

function onPaystackSuccess(response, connects, amountKES) {
  // Update local connects balance
  freelancerState.connects += connects;

  // Update sidebar stat
  const sidebarConnects = document.querySelector('.sb-stat:nth-child(2) .sb-stat-val');
  if (sidebarConnects) sidebarConnects.textContent = freelancerState.connects;

  // Update sidebar nav label
  const navConnects = document.querySelector('.sb-item[onclick*="connects"]');
  if (navConnects) navConnects.innerHTML = `🔗Connects (${freelancerState.connects})`;

  // Update the stat card on dashboard
  document.querySelectorAll('.stat-val').forEach(el => {
    if (el.textContent.trim() === String(freelancerState.connects - connects)) {
      el.textContent = freelancerState.connects;
    }
  });

  // Rewrite modal body with success screen
  const mc = document.getElementById('mc-body');
  if (mc) {
    const newTotal = freelancerState.connects;
    const fillPct = Math.min(100, Math.round((newTotal / (newTotal + 38)) * 100));
    mc.innerHTML = `
      
        ✅
        Payment Successful!
        
          ${connects} Connects have been added to your account.
          Transaction ref: ${response.reference}
        
        
          ${newTotal} Connects
          Available balance
          
        
        
          💳 KES ${amountKES.toLocaleString()} charged via Paystack
          📧 Receipt sent to ${freelancerState.email}
          ⚡ Connects are available immediately
        
        
          🔍 Find Jobs to Apply →
        
        
          Close
        
      `;
  }

  toast('🎉 Connects added!', `${connects} Connects purchased · Balance: ${freelancerState.connects}`);
}

// Also update apply modal banners to reflect current connects balance dynamically
function updateApplyModalConnects() {
  ['apply-a','apply-b'].forEach(key => {
    if (MODALS[key]) {
      MODALS[key].b = MODALS[key].b.replace(
        /You have \d+/g,
        `You have ${freelancerState.connects}`
      );
    }
  });
}
// Call once on load
updateApplyModalConnects();

// ════════════════════════════════════════════════════════════
//  SKILL SELECTOR — full Upwork 2025 categories/subcategories
// ════════════════════════════════════════════════════════════

const SKILL_TREE = {
  'Accounting & Consulting': {
    icon: '📊',
    subs: {
      'Personal & Professional Coaching': ['Career Coaching','Personal Coaching'],
      'Accounting & Bookkeeping': ['Accounting','Bookkeeping'],
      'Financial Planning': ['Financial Analysis & Modeling','Financial Management/CFO'],
      'Recruiting & Human Resources': ['HR Administration','Recruiting & Talent Sourcing','Training & Development'],
      'Management Consulting & Analysis': ['Business Analysis & Strategy','Instructional Design','Management Consulting'],
      'Other - Accounting & Consulting': ['Tax Preparation'],
    }
  },
  'Admin Support': {
    icon: '🗂️',
    subs: {
      'Data Entry & Transcription Services': ['Data Entry','Manual Transcription'],
      'Virtual Assistance': ['Executive Virtual Assistance','Legal Virtual Assistance','Medical Virtual Assistance','Ecommerce Management','Personal Virtual Assistance','General Virtual Assistance'],
      'Project Management': ['Business Project Management','Supply Chain & Logistics Project Management','Construction & Engineering Project Management','Development & IT Project Management','Healthcare Project Management','Digital Project Management'],
      'Market Research & Product Reviews': ['Web & Software Product Research','Market Research','General Research Services','Product Reviews','Qualitative Research','Quantitative Research'],
    }
  },
  'Customer Service': {
    icon: '🎧',
    subs: {
      'Community Management & Tagging': ['Community Management','Content Moderation','Visual Tagging & Processing'],
      'Customer Service & Tech Support': ['Customer Onboarding','Email, Phone & Chat Support','Customer Success','IT Support','Tech Support'],
    }
  },
  'Data Science & Analytics': {
    icon: '📈',
    subs: {
      'Data Analysis & Testing': ['Data Analytics','Data Visualization','Experimentation & Testing'],
      'Data Extraction/ETL': ['Data Extraction','Data Processing'],
      'Data Mining & Management': ['Data Engineering','Data Mining'],
      'AI & Machine Learning': ['Generative AI Modeling','AI Data Annotation & Labeling','Deep Learning','Knowledge Representation','Machine Learning'],
    }
  },
  'Design & Creative': {
    icon: '🎨',
    subs: {
      'Art & Illustration': ['Portraits & Caricatures','Cartoons & Comics','Fine Art','Illustration','Pattern Design'],
      'Audio & Music Production': ['AI Speech & Audio Generation','Audio Editing','Audio Production','Songwriting & Music Composition','Music Production'],
      'Branding & Logo Design': ['Brand Identity Design','Logo Design'],
      'NFT, AR/VR & Game Art': ['NFT Art','Game Art','AR/VR Design'],
      'Graphic, Editorial & Presentation Design': ['AI Image Generation & Editing','Art Direction','Creative Direction','Editorial Design','Graphic Design','Image Editing','Packaging Design','Presentation Design'],
      'Performing Arts': ['Acting','Music Performance','Singing','Voice Talent'],
      'Photography': ['Local Photography','Product Photography'],
      'Product Design': ['Fashion Design','Jewelry Design','Product & Industrial Design'],
      'Video & Animation': ['AI Video Generation & Editing','Motion Graphics','3D Animation','2D Animation','Video Editing','Videography','Video Production','Visual Effects'],
    }
  },
  'Engineering & Architecture': {
    icon: '🏗️',
    subs: {
      'Building & Landscape Architecture': ['Architectural Design','Landscape Architecture'],
      'Chemical Engineering': ['Chemical & Process Engineering'],
      'Civil & Structural Engineering': ['Building Information Modeling','Civil Engineering','Structural Engineering'],
      'Electrical & Electronic Engineering': ['Electrical Engineering','Electronic Engineering'],
      'Interior & Trade Show Design': ['Trade Show Design','Interior Design'],
      'Energy & Mechanical Engineering': ['Energy Engineering','Mechanical Engineering'],
      'Physical Sciences': ['Biology','Chemistry','Mathematics','Physics','STEM Tutoring'],
      '3D Modeling & CAD': ['CAD','3D Modeling & Rendering'],
      'Contract Manufacturing': ['Logistics & Supply Chain Management','Sourcing & Procurement'],
    }
  },
  'IT & Networking': {
    icon: '🖧',
    subs: {
      'Database Management & Administration': ['Database Administration'],
      'ERP/CRM Software': ['Business Applications Development','Systems Engineering'],
      'Information Security & Compliance': ['IT Compliance','Information Security','Network Security'],
      'Network & System Administration': ['Network Administration','Systems Administration'],
      'DevOps & Solution Architecture': ['Cloud Engineering','DevOps Engineering','Solution Architecture'],
    }
  },
  'Legal': {
    icon: '⚖️',
    subs: {
      'Corporate & Contract Law': ['Business & Corporate Law','Intellectual Property Law','Paralegal Services'],
      'International & Immigration Law': ['Immigration Law','International Law'],
      'Finance & Tax Law': ['Securities & Finance Law','Tax Law'],
      'Public Law': ['Labor & Employment Law','Regulatory Law'],
    }
  },
  'Sales & Marketing': {
    icon: '📣',
    subs: {
      'Digital Marketing': ['Display Advertising','Campaign Management','Email Marketing','Marketing Automation','Search Engine Marketing','SEO','Social Media Marketing'],
      'Lead Generation & Telemarketing': ['Sales & Business Development','Lead Generation','Telemarketing'],
      'Marketing, PR & Brand Strategy': ['Brand Strategy','Content Strategy','Marketing Strategy','Public Relations','Social Media Strategy'],
    }
  },
  'Translation': {
    icon: '🌐',
    subs: {
      'Language Tutoring & Interpretation': ['Live Interpretation','Sign Language Interpretation','Language Tutoring'],
      'Translation & Localization Services': ['Language Localization','Legal Document Translation','Medical Document Translation','Technical Document Translation','General Translation Services'],
    }
  },
  'Web, Mobile & Software Dev': {
    icon: '💻',
    subs: {
      'Blockchain, NFT & Cryptocurrency': ['Blockchain & NFT Development','Crypto Coins & Tokens','Crypto Wallet Development'],
      'AI Apps & Integration': ['AI Chatbot Development','AI Integration'],
      'Desktop Application Development': ['Desktop Software Development'],
      'Ecommerce Development': ['Ecommerce Website Development'],
      'Game Design & Development': ['Video Game Development'],
      'Mobile Development': ['Mobile App Development','Mobile Game Development'],
      'Other - Software Development': ['AR/VR Development','Database Development','Emerging Tech','Firmware Development','Coding Tutoring'],
      'Product Management & Scrum': ['Product Management','Scrum Leadership'],
      'QA Testing': ['Automation Testing','Manual Testing'],
      'Scripts & Utilities': ['Scripting & Automation'],
      'Web & Mobile Design': ['Mobile Design','Prototyping','UX/UI Design','Web Design'],
      'Web Development': ['Back-End Development','CMS Development','Front-End Development','Full Stack Development'],
    }
  },
  'Writing': {
    icon: '✍️',
    subs: {
      'Sales & Marketing Copywriting': ['Ad & Email Copywriting','Marketing Copywriting','Sales Copywriting'],
      'Content Writing': ['Web & UX Writing','Article & Blog Writing','AI Content Writing','Creative Writing','Ghostwriting','Scriptwriting','Writing Tutoring'],
      'Editing & Proofreading Services': ['Proofreading','Copy Editing'],
      'Professional & Business Writing': ['Academic & Research Writing','Legal Writing','Medical Writing','Resume & Cover Letter Writing','Business & Proposal Writing','Grant Writing','Technical Writing'],
    }
  },
};

const MAX_SKILLS = 15;
let selectedSkills = new Set();
let activeCat = null, activeSub = null;

function openSkillSelector() {
  document.getElementById('skill-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('skill-search').value = '';
  renderCatCol();
  renderSelectedPreview();
  // default: select first category
  const firstCat = Object.keys(SKILL_TREE)[0];
  selectCat(firstCat);
}
function closeSkillSelector() {
  document.getElementById('skill-overlay').classList.remove('open');
  document.body.style.overflow = '';
}

function renderCatCol(filter) {
  const col = document.getElementById('cat-col');
  col.innerHTML = Object.entries(SKILL_TREE).map(([cat, data]) => {
    if (filter && !cat.toLowerCase().includes(filter)) return '';
    const count = countSelected(cat);
    return `
      ${s}${c?` ${c}`:''}
    `;
  }).join('');
  renderSpecCol(sub);
}

function renderSpecCol(sub) {
  const specs = SKILL_TREE[activeCat].subs[sub] || [];
  const col = document.getElementById('skill-col');
  const canAdd = selectedSkills.size < MAX_SKILLS;
  col.innerHTML = `
    ${activeCat} › ${sub}
    ${specs.map(spec => {
      const sel = selectedSkills.has(spec);
      const disabled = !sel && !canAdd;
      const action = disabled
        ? `toast('Limit','You can select up to ${MAX_SKILLS} skills')`
        : `toggleSkill('${spec.replace(/'/g,"\\'")}')`;
      return `
        ${spec}
        ${sel?'✓':''}
      `;
    }).join('')}`;
}

function toggleSkill(spec) {
  if (selectedSkills.has(spec)) {
    selectedSkills.delete(spec);
  } else {
    if (selectedSkills.size >= MAX_SKILLS) {
      toast('Limit reached', `You can add up to ${MAX_SKILLS} skills`); return;
    }
    selectedSkills.add(spec);
  }
  if (activeSub) renderSpecCol(activeSub);
  renderCatCol();
  if (activeCat) selectCat(activeCat); // refresh counts
  if (activeSub) renderSpecCol(activeSub);
  renderSelectedPreview();
}


function renderSelectedPreview() {
  const el = document.getElementById('selected-preview');
  const arr = [...selectedSkills];
  el.innerHTML = arr.length
    ? arr.map(s=>`
        ${s}  {
    Object.entries(data.subs).forEach(([sub, specs]) => {
      specs.forEach(spec => {
        if (spec.toLowerCase().includes(query) || sub.toLowerCase().includes(query) || cat.toLowerCase().includes(query)) {
          results.push({cat, sub, spec});
        }
      });
    });
  });
  catCol.innerHTML = 'Search results';
  subCol.innerHTML = '';
  skillCol.innerHTML = results.length
    ? results.map(({cat, sub, spec}) => {
        const sel = selectedSkills.has(spec);
        return `0?'var(--g)':r.net