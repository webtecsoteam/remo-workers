<script>
// ─── CLIENT DATABASE ───────────────────────────────────────────────
// Add any client here. Their spent badge appears automatically on every
// job they post — no other changes needed.
const CLIENTS = {
  'clearpath-finance': { name:'ClearPath Finance', rating:'4.9', hires:18, location:'Berlin, Germany', spent:'$89K+' },
  'launchpad-hq':      { name:'Launchpad HQ',      rating:'5.0', hires:8,  location:'London, UK',      spent:'$41K+' },
  'edtech-platform':   { name:'EdTech Platform',    rating:'4.8', hires:4,  location:'Toronto, Canada', spent:'$24K+' },
};

// ─── JOB LISTINGS ─────────────────────────────────────────────────
// To add a new job: just push a new object here.
// The spent badge is injected automatically from CLIENTS above.
const JOBS = [
  {
    id: 'job-a',
    title: 'Senior UI/UX Designer — Fintech SaaS Redesign',
    desc: 'Redesign our 3-year-old fintech dashboard. New design system, 8 core screens, Figma handoff, component library. Strong portfolio required.',
    clientId: 'clearpath-finance',
    type: 'Fixed Price', typeBadge: 'b-purple',
    rate: '$6,000–$9,000', rateLabel: 'Fixed Price',
    meta: ['14 days', '3 proposals'],
    connects: 6, posted: '1hr ago', match: '95%', matchBadge: 'b-green',
    applyModal: 'apply-a',
  },
  {
    id: 'job-b',
    title: 'Product Designer — Mobile App (Ongoing)',
    desc: 'Ongoing product designer for fast-growing startup. 10–15hrs/week, iOS-first design, Figma, close collaboration with engineering team.',
    clientId: 'launchpad-hq',
    type: 'Hourly', typeBadge: 'b-blue',
    rate: '$80–$100/hr',
    meta: ['Ongoing', '7 proposals'],
    connects: 4, posted: '4hrs ago', match: '88%', matchBadge: 'b-blue',
    applyModal: 'apply-b',
  },
  {
    id: 'job-c',
    title: 'Fractional Design Lead — EdTech Platform',
    desc: 'Looking for a fractional design lead to own product design across 3 teams. 20hrs/week. Strong design systems and team leadership experience required.',
    clientId: 'edtech-platform',
    type: 'Hourly', typeBadge: 'b-blue',
    rate: '$100–$140/hr',
    meta: ['20 hrs/week', '5 proposals'],
    connects: 6, posted: '6hrs ago', match: null,
    applyModal: null,
  },
];

// ─── SPENT BADGE HELPER ───────────────────────────────────────────
function spentBadge(clientId) {
  const c = CLIENTS[clientId];
  if (!c || !c.spent) return '';
  return `<span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:11px;font-weight:600;padding:2px 7px;border-radius:5px;margin-left:6px">💰 ${c.spent} spent</span>`;
}

// ─── JOB ROW RENDERER ─────────────────────────────────────────────
function jobRowHTML(job, compact = false) {
  const c = CLIENTS[job.clientId] || {};
  const clientMeta = `Payment verified · ★ ${c.rating || '—'} client · ${c.hires || 0} hires · ${c.location || ''}`;
  const matchTag = job.match ? `<span class="badge ${job.matchBadge}" style="margin-left:6px">${job.match} match</span>` : '';
  const titleSize = compact ? '13.5px' : '14px';
  const descSize  = compact ? '12.5px' : '13px';
  const metaSize  = compact ? '12px'   : '12.5px';

  const badges = [
    `<span class="badge ${job.typeBadge}">${job.type}${job.rate ? ' · ' + job.rate : ''}</span>`,
    ...job.meta.map(m => `<span class="badge b-gray">${m}</span>`)
  ].join('');

  const applyBtn = job.applyModal
    ? `<button class="btn btn-g btn-sm" onclick="event.stopPropagation();checkAndApply('${job.applyModal}',${job.connects})">Apply Now</button>`
    : `<button class="btn btn-g btn-sm" onclick="event.stopPropagation();checkAndApply(null,${job.connects})">Apply Now</button>`;

  const rightCol = compact
    ? `<div style="text-align:right;flex-shrink:0">
        <div style="font-size:11.5px;color:var(--muted)">Posted ${job.posted}</div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:2px">${job.meta[1] || ''}</div>
        <div style="font-size:11px;color:var(--g);font-weight:600;margin-top:4px">⚡ Costs ${job.connects} Connects</div>
      </div>`
    : `<div style="text-align:right;flex-shrink:0">
        ${applyBtn}
        <div style="font-size:11px;color:var(--g);font-weight:600;margin-top:6px">⚡ ${job.connects} Connects</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px">Posted ${job.posted}</div>
      </div>`;

  const clickAction = job.id ? `openModal('${job.id}')` : `toast('Job','Opening ${job.title}')`;

  return `<div class="job-row" onclick="${clickAction}">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px">
      <div style="flex:1">
        <h4 style="font-size:${titleSize};font-weight:700;margin-bottom:4px">${job.title}${matchTag}</h4>
        <p style="font-size:${descSize};color:var(--muted);line-height:1.55;margin-bottom:${compact?'8px':'10px'}">${job.desc}</p>
        <div style="font-size:${metaSize};color:var(--muted);margin-bottom:8px">${clientMeta}${spentBadge(job.clientId)}</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">${badges}</div>
      </div>
      ${rightCol}
    </div>
  </div>`;
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
<div style="display:flex;align-items:center;gap:14px;background:var(--gl);border-radius:9px;padding:16px;margin-bottom:16px">
  <div style="font-size:36px">🔗</div>
  <div>
    <div style="font-size:22px;font-weight:700" id="connects-count-display">42 Connects</div>
    <div style="font-size:13px;color:var(--muted)">Available to apply for jobs</div>
  </div>
</div>
<div class="connects-bar"><div class="connects-fill" id="connects-fill-bar" style="width:52%"></div></div>
<div style="font-size:12px;color:var(--muted);margin-bottom:18px;margin-top:4px" id="connects-used-label">42 of 80 Connects used</div>

<div style="font-size:13.5px;font-weight:700;margin-bottom:12px">Buy more Connects</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
  <div id="pkg-10" class="connect-pkg" style="border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:all .15s" onclick="selectConnectPkg(10,150,'pkg-10')">
    <div style="font-size:22px;font-weight:700">10</div>
    <div style="font-size:12px;color:var(--muted)">KES 150</div>
    <div style="font-size:11px;color:var(--muted2);margin-top:2px">~$1.15/connect</div>
  </div>
  <div id="pkg-20" class="connect-pkg" style="border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:all .15s" onclick="selectConnectPkg(20,280,'pkg-20')">
    <div style="font-size:22px;font-weight:700">20</div>
    <div style="font-size:12px;color:var(--muted)">KES 280</div>
    <div style="font-size:11px;color:var(--muted2);margin-top:2px">~$1.07/connect</div>
  </div>
  <div id="pkg-40" class="connect-pkg" style="border:1.5px solid var(--g);background:var(--gl);border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:all .15s" onclick="selectConnectPkg(40,520,'pkg-40')">
    <div style="font-size:22px;font-weight:700">40</div>
    <div style="font-size:12px;color:var(--g);font-weight:600">KES 520 · Best value</div>
    <div style="font-size:11px;color:var(--g);margin-top:2px;font-weight:500">Save 10%</div>
  </div>
  <div id="pkg-80" class="connect-pkg" style="border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center;cursor:pointer;transition:all .15s" onclick="selectConnectPkg(80,960,'pkg-80')">
    <div style="font-size:22px;font-weight:700">80</div>
    <div style="font-size:12px;color:var(--muted)">KES 960</div>
    <div style="font-size:11px;color:var(--muted2);margin-top:2px">Save 15%</div>
  </div>
</div>

<div id="connect-selected-pkg" style="display:none;background:var(--off);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:14px;font-size:13px">
  <div style="display:flex;align-items:center;justify-content:space-between">
    <div><strong id="pkg-summary-text">40 Connects</strong> selected</div>
    <div style="font-size:15px;font-weight:700;color:var(--g)" id="pkg-summary-price">KES 520</div>
  </div>
</div>

<button id="paystack-pay-btn" class="btn btn-g" style="width:100%;justify-content:center;padding:11px;font-size:13.5px;display:none" onclick="initiatePaystackPayment()">
  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
  Pay with Paystack →
</button>

<div style="display:flex;align-items:center;gap:6px;margin-top:10px;justify-content:center">
  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9aaa9a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
  <span style="font-size:11.5px;color:var(--muted2)">Secured by Paystack · Cards, M-Pesa & more accepted</span>
</div>

<div style="font-size:12px;color:var(--muted);line-height:1.65;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
  Each job requires 2–6 Connects to apply. Unused Connects are refunded if a job closes without a hire within 90 days.
</div>`},
'job-a':{t:'Senior UI/UX Designer — Fintech SaaS Redesign',b:`<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px"><span class="badge b-purple">Fixed Price</span><span class="badge b-gray">3 proposals</span><span class="badge b-green">Payment Verified</span><span class="badge b-gray">95% match</span></div><div style="background:var(--off);border-radius:8px;padding:14px;font-size:13.5px;color:#333;line-height:1.75;margin-bottom:14px">Redesign our 3-year-old fintech dashboard. Deliverables: new design system in Figma, 8 core app screens with responsive variants, full component library, and developer handoff documentation. Must have direct SaaS dashboard experience. Bonus if you've worked in fintech.</div><div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px"><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Budget</div><div style="font-weight:700">$6,000–$9,000</div></div><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Timeline</div><div style="font-weight:700">14 days</div></div><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Connects</div><div style="font-weight:700;color:var(--g)">6</div></div></div><div style="font-size:13px;color:var(--muted);margin-bottom:14px">Posted by ClearPath Finance · ★ 4.9 · 18 hires · Berlin, Germany · Payment verified · <span style="color:#15803d;font-weight:600">💰 $89K+ spent</span></div><button class="btn btn-g" style="width:100%;justify-content:center;padding:10px" onclick="openModal('apply-a')">Submit Proposal (6 Connects) →</button>`},
'job-b':{t:'Product Designer — Mobile App (Ongoing)',b:`<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px"><span class="badge b-blue">Hourly</span><span class="badge b-gray">7 proposals</span><span class="badge b-green">Payment Verified</span><span class="badge b-gray">88% match</span></div><div style="background:var(--off);border-radius:8px;padding:14px;font-size:13.5px;color:#333;line-height:1.75;margin-bottom:14px">We're a fast-growing startup looking for an experienced product designer to join our team on an ongoing basis. You'll own the full design lifecycle for our iOS app — from research and wireframes through high-fidelity Figma designs and developer collaboration. 10–15 hrs/week with possibility to expand.</div><div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px"><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Rate</div><div style="font-weight:700">$80–$100/hr</div></div><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Hours/Week</div><div style="font-weight:700">10–15 hrs</div></div><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Connects</div><div style="font-weight:700;color:var(--g)">4</div></div></div><div style="font-size:13px;color:var(--muted);margin-bottom:14px">Posted by Launchpad HQ · ★ 5.0 · 8 hires · London, UK · Payment verified · <span style="color:#15803d;font-weight:600">💰 $41K+ spent</span></div><button class="btn btn-g" style="width:100%;justify-content:center;padding:10px" onclick="openModal('apply-b')">Submit Proposal (4 Connects) →</button>`},
'apply-a':{t:'Submit Proposal — Fintech SaaS Redesign',b:`<div style="background:var(--off);border-radius:8px;padding:12px;margin-bottom:16px;font-size:13px;color:var(--muted)">Fixed Price · $6,000–$9,000 · 6 Connects required · You have 42</div><div class="fg"><label>Your Proposed Rate ($)</label><input type="number" placeholder="e.g. 7500" value="7500"></div><div class="fg"><label>Estimated Delivery (days)</label><input type="number" placeholder="e.g. 14" value="12"></div><div class="fg"><label>Cover Letter *</label><textarea style="min-height:120px" placeholder="Describe your approach to this specific project, highlight relevant experience, and explain why you're the right fit. Be specific — clients receive many generic proposals.">I specialize in fintech SaaS design systems and have completed 14 similar projects in the last 2 years. For this project, I'd start with a design audit of your current interface, then propose 2 design directions before committing to the full system build...</textarea></div><div class="fg"><label>Attachments (portfolio, case study)</label><input type="text" placeholder="Paste Figma or portfolio link…"></div><div style="display:flex;gap:8px"><button class="btn btn-w" style="flex:1;justify-content:center" onclick="closeModal()">Cancel</button><button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Proposal submitted! 🎉','6 Connects used — client will be notified');closeModal()">Submit Proposal →</button></div>`},
'apply-b':{t:'Submit Proposal — Mobile App Designer',b:`<div style="background:var(--off);border-radius:8px;padding:12px;margin-bottom:16px;font-size:13px;color:var(--muted)">Hourly Rate · $80–$100/hr · 4 Connects required · You have 42</div><div class="fg"><label>Your Hourly Rate ($/hr)</label><input type="number" placeholder="e.g. 90" value="90"></div><div class="fg"><label>Hours Available Per Week</label><select><option>10–15 hrs/week</option><option>15–20 hrs/week</option><option>20–30 hrs/week</option></select></div><div class="fg"><label>Cover Letter *</label><textarea style="min-height:120px" placeholder="Tell the client why you're perfect for this role...">I've designed 12 iOS apps over the past 4 years, 3 of which are in the App Store with 100K+ downloads. I'm immediately available and can dedicate 12hrs/week to your project. My process: weekly Figma updates, async video walkthroughs of each design, and rapid iteration based on your feedback.</textarea></div><div style="display:flex;gap:8px"><button class="btn btn-w" style="flex:1;justify-content:center" onclick="closeModal()">Cancel</button><button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Proposal submitted! 🎉','4 Connects used — client notified');closeModal()">Submit Proposal →</button></div>`},
'contract-detail':{t:'Contract — NexaFlow Inc.',b:`<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px"><div class="stat-c" style="padding:12px"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Type</div><div style="font-weight:700">Hourly · $90/hr</div></div><div class="stat-c" style="padding:12px"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Hours This Week</div><div style="font-weight:700">8.5 hrs</div></div><div class="stat-c" style="padding:12px"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Total Earned</div><div style="font-weight:700">$3,105</div></div><div class="stat-c" style="padding:12px"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Weekly Limit</div><div style="font-weight:700">No limit</div></div></div><div style="font-size:13.5px;font-weight:700;margin-bottom:8px">Log Time</div><div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px"><div class="fg" style="margin:0"><label>Hours to Log</label><input type="number" placeholder="e.g. 2.5"></div><div class="fg" style="margin:0"><label>Work Description</label><input type="text" placeholder="e.g. Mobile responsive screens"></div></div><button class="btn btn-g" style="margin-bottom:16px" onclick="toast('Time logged','2.5 hrs added to your work diary ✓')">Log Hours</button><div style="display:flex;gap:8px"><button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Message','Chat with NexaFlow opened')">Message Client</button><button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Remoworkers meeting...')">📹 Video Call</button><button class="btn btn-r" style="flex:1;justify-content:center" onclick="toast('Paused','Contract paused — client notified')">Pause Contract</button></div>`},
'fintech-milestones':{t:'Mobile App Redesign (iOS) — FinTech Co.',b:`
<div style="display:flex;align-items:center;gap:10px;background:var(--gl);border-radius:9px;padding:13px 16px;margin-bottom:18px">
  <div class="av" style="background:#d1fae5;color:#065f46;width:36px;height:36px;font-size:12px">FT</div>
  <div style="flex:1"><div style="font-weight:700;font-size:13.5px">FinTech Co.</div><div style="font-size:12px;color:var(--g)">Fixed-price contract · ★ 4.9 · Payment verified</div></div>
  <div style="text-align:right"><div style="font-size:18px;font-weight:800;color:var(--dark)">$4,500</div><div style="font-size:11px;color:var(--muted)">Total contract value</div></div>
</div>

<div style="font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:12px">Milestones</div>

<!-- Milestone 1 — Done -->
<div style="border:1px solid #bbf7d0;background:#f0fdf4;border-radius:10px;padding:16px;margin-bottom:10px">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
    <div style="display:flex;align-items:center;gap:10px">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--g);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;flex-shrink:0">✓</div>
      <div>
        <div style="font-weight:700;font-size:13.5px">Milestone 1 — Wireframes &amp; Information Architecture</div>
        <div style="font-size:12px;color:#166534;margin-top:2px">Approved by client · May 7, 2026</div>
      </div>
    </div>
    <div style="text-align:right;flex-shrink:0">
      <div style="font-weight:700;font-size:15px;color:var(--g)">$2,250</div>
      <span class="badge b-green" style="font-size:10px">Released</span>
    </div>
  </div>
</div>

<!-- Milestone 2 — Funded / Ready to submit -->
<div id="m2-card" style="border:2px solid var(--g);background:white;border-radius:10px;padding:16px;margin-bottom:10px;box-shadow:0 2px 12px rgba(20,168,0,.1)">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:14px">
    <div style="display:flex;align-items:center;gap:10px">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--g);display:flex;align-items:center;justify-content:center;color:white;font-size:13px;font-weight:700;flex-shrink:0">2</div>
      <div>
        <div style="font-weight:700;font-size:13.5px">Milestone 2 — High-Fidelity Screens &amp; Prototype</div>
        <div style="font-size:12px;color:var(--muted);margin-top:2px">Due: May 20, 2026 · <strong style="color:var(--g)">Funded &amp; ready to submit</strong></div>
      </div>
    </div>
    <div style="text-align:right;flex-shrink:0">
      <div style="font-weight:700;font-size:15px">$2,250</div>
      <span class="badge b-yellow" style="font-size:10px">Funded</span>
    </div>
  </div>

  <div id="m2-scope" style="background:var(--off);border-radius:8px;padding:12px;font-size:13px;color:var(--dark3);line-height:1.7;margin-bottom:14px">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:6px">Scope of work</div>
    <ul style="margin:0;padding-left:18px">
      <li>12 high-fidelity iOS screens in Figma</li>
      <li>Interactive prototype with core user flows</li>
      <li>Responsive variants for iPhone 14 &amp; SE</li>
      <li>Developer-ready handoff with specs &amp; assets</li>
    </ul>
  </div>

  <div id="m2-submit-form" style="display:none">
    <div style="height:1px;background:var(--border);margin-bottom:14px"></div>
    <div style="font-size:13.5px;font-weight:700;margin-bottom:12px;color:var(--dark)">📤 Submit Work for Review</div>

    <div class="fg">
      <label>Work Summary <span style="color:var(--muted);font-weight:400">(describe what you've completed)</span></label>
      <textarea id="m2-summary" style="min-height:100px" placeholder="e.g. Completed all 12 iOS screens with full Figma component structure, interactive prototype covering 4 core flows, and a Zeplin handoff with spacing tokens and assets exported at 1x/2x/3x…"></textarea>
    </div>

    <div class="fg">
      <label>Deliverable Links</label>
      <input id="m2-link1" type="text" placeholder="Figma file link (view access)" style="margin-bottom:8px">
      <input id="m2-link2" type="text" placeholder="Prototype / InVision link (optional)">
    </div>

    <div class="fg">
      <label>Attachments <span style="color:var(--muted);font-weight:400">(optional — PDFs, ZIPs, screenshots)</span></label>
      <div id="m2-dropzone" style="border:2px dashed var(--border);border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:all .2s;background:var(--off)" onclick="document.getElementById('m2-file-input').click()" ondragover="event.preventDefault();this.style.borderColor='var(--g)';this.style.background='var(--gl)'" ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--off)'" ondrop="handleM2Drop(event)">
        <div style="font-size:22px;margin-bottom:6px">📎</div>
        <div style="font-size:13px;color:var(--muted)">Drag & drop files here or <span style="color:var(--g);font-weight:600">browse</span></div>
        <div style="font-size:11.5px;color:var(--muted2);margin-top:3px">PDF, ZIP, PNG · max 25 MB each</div>
      </div>
      <input type="file" id="m2-file-input" multiple accept=".pdf,.zip,.png,.jpg,.fig" style="display:none" onchange="handleM2Files(this.files)">
      <div id="m2-file-list" style="margin-top:8px;display:flex;flex-direction:column;gap:5px"></div>
    </div>

    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px;font-size:12.5px;color:#78350f;margin-bottom:14px;line-height:1.6">
      ⚠️ <strong>Important:</strong> Once submitted, the client has <strong>14 days</strong> to review and approve. If they don't respond, the milestone is auto-approved and funds are released. You can send one revision request per milestone.
    </div>

    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="cancelM2Submit()">Cancel</button>
      <button class="btn btn-g" style="flex:2;justify-content:center;padding:10px;font-size:13.5px" onclick="submitM2()">🚀 Submit Milestone for Approval →</button>
    </div>
  </div>

  <div id="m2-actions" style="display:flex;gap:8px">
    <button class="btn btn-w btn-sm" onclick="toast('Message','Opening FinTech Co. chat...')">💬 Message Client</button>
    <button class="btn btn-g" style="flex:1;justify-content:center" onclick="showM2Form()">📤 Submit Work for Approval →</button>
  </div>
</div>

<div style="display:flex;gap:8px;margin-top:6px">
  <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Message','Opening FinTech Co. chat...')">💬 Message Client</button>
  <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Remoworkers meeting...')">📹 Video Call</button>
</div>
`},
'msg-nexaflow':{t:'Message from NexaFlow Inc.',b:`<div style="display:flex;gap:12px;align-items:center;background:var(--gl);border-radius:9px;padding:14px;margin-bottom:16px"><div class="av" style="background:#dbeafe;color:#1e40af">NX</div><div><div style="font-weight:700">NexaFlow Inc.</div><div style="font-size:12px;color:var(--g)">Active contract · ★ 5.0</div></div></div><div style="background:var(--off);border-radius:8px;padding:14px;font-size:13.5px;color:#333;line-height:1.75;margin-bottom:16px">"These look amazing, Anika! The new navigation pattern is exactly what we had in mind and the component library documentation is incredibly thorough. Can we hop on a 20-min call to walk through the mobile variants together? Are you free Thursday at 3pm Berlin time?"</div><div style="display:flex;gap:8px"><button class="btn btn-w" style="flex:1;justify-content:center" onclick="showPage('messages',document.querySelector('[onclick*=messages]'));closeModal()">Open Chat</button><button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Remoworkers meeting room...')">📹 Start Video Call</button></div>`},
'edit-profile':{t:'Edit Profile',b:`
<div class="g2">
  <div class="fg"><label>Full Name</label><input type="text" value="Anika Nkosi"></div>
  <div class="fg"><label>Title / Headline</label><input type="text" value="Senior UI/UX Designer — SaaS &amp; Fintech Specialist"></div>
</div>
<div class="g2">
  <div class="fg"><label>Hourly Rate ($/hr)</label><input type="number" value="90"></div>
  <div class="fg"><label>Country / Location</label><input type="text" value="Berlin, Germany"></div>
</div>
<div class="g2">
  <div class="fg"><label>Languages</label><input type="text" value="English (Fluent), German (Conversational)"></div>
  <div class="fg"><label>Availability</label><select><option>Available for Work</option><option>Limited Availability</option><option>Not Available</option></select></div>
</div>
<div class="fg"><label>Bio / Overview</label><textarea style="min-height:130px">Senior UI/UX Designer with 8+ years crafting user-centered digital products for startups and Fortune 500 companies. Specialized in design systems, mobile apps, and Webflow development...</textarea></div>
<div class="fg"><label>Portfolio / Website URL</label><input type="text" placeholder="https://yourportfolio.com" value="https://anikankosi.design"></div>
<div class="fg"><label>LinkedIn Profile</label><input type="text" placeholder="https://linkedin.com/in/yourname" value="https://linkedin.com/in/anikankosi"></div>
<div style="display:flex;gap:8px;margin-top:6px">
  <button class="btn btn-w" style="flex:1;justify-content:center" onclick="closeModal()">Cancel</button>
  <button class="btn btn-g" style="flex:2;justify-content:center;padding:10px" onclick="toast('Saved ✓','Profile updated successfully');closeModal()">Save Changes →</button>
</div>`},
'new-service':{t:'Create a Service Package',b:`<div class="fg"><label>Service Title</label><input type="text" placeholder="e.g. I will design a complete Figma design system"></div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="fg"><label>Base Price ($)</label><input type="number" placeholder="1800"></div><div class="fg"><label>Delivery (days)</label><input type="number" placeholder="10"></div></div><div class="fg"><label>What's Included</label><textarea placeholder="List deliverables clearly..."></textarea></div><button class="btn btn-g" style="width:100%;justify-content:center;padding:10px;margin-top:4px" onclick="toast('Service published!','Now live in the Project Catalog');closeModal()">Publish Service →</button>`},
'service-1':{t:'UI/UX Design System Build',b:`<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:16px"><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Price</div><div style="font-weight:700">$1,800</div></div><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Orders</div><div style="font-weight:700;color:var(--g)">12</div></div><div class="stat-c" style="padding:11px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:3px">Rating</div><div style="font-weight:700">★ 5.0</div></div></div><div style="display:flex;gap:8px"><button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Paused','Service hidden from catalog')">Pause</button><button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Editing','Opening service editor...')">Edit</button></div>`}
};
MODALS['change-password'] = {
  t: '🔑 Change Password',
  b: `
    <div style="margin-bottom:18px">
      <div style="background:var(--gl);border:1px solid #c3e6c3;border-radius:8px;padding:11px 14px;font-size:12.5px;color:#166534;line-height:1.6;margin-bottom:18px">
        🔒 Choose a strong password — at least 8 characters with a mix of letters, numbers, and symbols.
      </div>
      <div class="fg">
        <label>Current Password</label>
        <div style="position:relative">
          <input type="password" id="pw-current" placeholder="Enter your current password" autocomplete="current-password">
          <span onclick="togglePwVis('pw-current',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:15px;user-select:none">👁</span>
        </div>
      </div>
      <div class="fg">
        <label>New Password</label>
        <div style="position:relative">
          <input type="password" id="pw-new" placeholder="At least 8 characters" autocomplete="new-password" oninput="checkPwStrength(this.value)">
          <span onclick="togglePwVis('pw-new',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:15px;user-select:none">👁</span>
        </div>
        <div id="pw-strength-bar" style="height:4px;border-radius:4px;margin-top:6px;background:var(--border);overflow:hidden;display:none">
          <div id="pw-strength-fill" style="height:100%;border-radius:4px;width:0%;transition:width .3s,background .3s"></div>
        </div>
        <div id="pw-strength-label" style="font-size:11.5px;margin-top:4px;color:var(--muted);display:none"></div>
      </div>
      <div class="fg" style="margin-bottom:0">
        <label>Confirm New Password</label>
        <div style="position:relative">
          <input type="password" id="pw-confirm" placeholder="Re-enter new password" autocomplete="new-password" oninput="checkPwMatch()">
          <span onclick="togglePwVis('pw-confirm',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:15px;user-select:none">👁</span>
        </div>
        <div id="pw-match-msg" style="font-size:11.5px;margin-top:4px;display:none"></div>
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="closeModal()">Cancel</button>
      <button class="btn btn-g" id="pw-save-btn" style="flex:2;justify-content:center;padding:10px" onclick="submitPasswordReset()">Update Password →</button>
    </div>
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
  document.getElementById('page-title').textContent=titles[id]||id;
  if(id==='reports')setTimeout(renderReports,50);
  if(id==='profile')setTimeout(renderSuggestedSkills,50);
  // Scroll main content to top on mobile
  if(window.innerWidth<=900){window.scrollTo({top:0,behavior:'smooth'});}
}
function setTab(el){el.closest('.tab-bar').querySelectorAll('.tab').forEach(t=>t.classList.remove('on'));el.classList.add('on');}
const EARNINGS_INFO = {
  wip: `<strong>🕐 Work in Progress — $765.00</strong><br>
    Hours you've logged this billing week on active hourly contracts. These funds are <em>not yet billed</em> — they become billable after the weekly billing period closes (Sunday midnight UTC). The amount updates in real time as you log more hours.<br>
    <span style="color:var(--muted);font-size:12px">Current week: 8.5 hrs × $90/hr = $765 · NexaFlow Inc.</span>`,

  review: `<strong>🔍 In Review — $1,350.00</strong><br>
    The billing week has ended and your hours have been submitted. Clients have a <strong>5-day dispute window</strong> to review your work diary and raise a dispute if needed. If no dispute is filed, funds automatically move to Pending.<br>
    <span style="color:var(--muted);font-size:12px">Week May 5–11 · 15 hrs · DataStack · Dispute window closes May 16</span>`,

  pending: `<strong>⏳ Pending — $2,550.00</strong><br>
    Funds in the <strong>5-day security hold</strong> before they become available to withdraw. This includes:<br>
    • <strong>Hourly billing</strong> that passed the review window<br>
    • <strong>Bonuses</strong> paid by clients (skip review and go straight here)<br>
    • <strong>Fixed-price milestones</strong> approved by the client<br>
    <span style="color:var(--muted);font-size:12px">$500 bonus from NexaFlow Inc. · $2,050 hourly billing · Earliest release: May 18</span>`,

  available: `<strong>✅ Available — $12,800.00</strong><br>
    Funds that have cleared all holds and are ready to withdraw. You can transfer to your bank, PayPal, Payoneer, or other connected payment method at any time. Withdrawals typically arrive in 1–5 business days depending on your method.<br>
    <span style="color:var(--muted);font-size:12px">Last payment received: $2,950.00 on May 12</span>`,
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
  connects: 42,
  maxConnects: 80,
  email: 'anika.nkosi@remoworkers.io',
  name: 'Anika Nkosi',
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
  if (navConnects) navConnects.innerHTML = `<span class="sb-ico">🔗</span>Connects (${freelancerState.connects})`;

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
      <div style="text-align:center;padding:24px 16px">
        <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px">✅</div>
        <div style="font-size:19px;font-weight:700;margin-bottom:6px">Payment Successful!</div>
        <div style="font-size:13.5px;color:var(--muted);margin-bottom:20px;line-height:1.6">
          <strong>${connects} Connects</strong> have been added to your account.<br>
          Transaction ref: <code style="font-size:11.5px;background:var(--off);padding:2px 6px;border-radius:4px">${response.reference}</code>
        </div>
        <div style="background:var(--gl);border:1px solid #c3e6c3;border-radius:10px;padding:16px;margin-bottom:20px">
          <div style="font-size:28px;font-weight:800;color:var(--g)">${newTotal} Connects</div>
          <div style="font-size:12.5px;color:var(--g);margin-top:4px">Available balance</div>
          <div class="connects-bar" style="margin-top:10px"><div class="connects-fill" style="width:${fillPct}%"></div></div>
        </div>
        <div style="font-size:12.5px;color:var(--muted);background:var(--off);border-radius:8px;padding:12px;margin-bottom:20px;text-align:left;line-height:1.7">
          💳 KES ${amountKES.toLocaleString()} charged via Paystack<br>
          📧 Receipt sent to ${freelancerState.email}<br>
          ⚡ Connects are available immediately
        </div>
        <button class="btn btn-g" style="width:100%;justify-content:center;padding:11px" onclick="closeModal();showPage('find-work',document.querySelector('[onclick*=find-work]'))">
          🔍 Find Jobs to Apply →
        </button>
        <button class="btn btn-w" style="width:100%;justify-content:center;padding:9px;margin-top:8px" onclick="closeModal()">
          Close
        </button>
      </div>`;
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
let selectedSkills = new Set(['Figma','Webflow','Design Systems','Prototyping','User Research','Framer','Motion Design']);
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
    return `<div class="cat-item${activeCat===cat?' active':''}" onclick="selectCat('${cat.replace(/'/g,"\\'")}')">
      <span class="cat-icon">${data.icon}</span>${cat}${count?` <span style="background:var(--g);color:white;font-size:10px;padding:1px 6px;border-radius:8px;margin-left:4px">${count}</span>`:''}
    </div>`;
  }).join('');
}

function countSelected(cat) {
  let n = 0;
  Object.values(SKILL_TREE[cat].subs).forEach(specs => specs.forEach(s => { if(selectedSkills.has(s)) n++; }));
  return n;
}

function selectCat(cat) {
  activeCat = cat; activeSub = null;
  renderCatCol();
  const subs = SKILL_TREE[cat].subs;
  const col = document.getElementById('subcat-col');
  col.innerHTML = Object.keys(subs).map(sub => {
    const c = subs[sub].filter(s=>selectedSkills.has(s)).length;
    return `<div class="subcat-item${activeSub===sub?' active':''}" onclick="selectSub('${sub.replace(/'/g,"\\'")}')">
      ${sub}${c?` <span style="background:var(--g);color:white;font-size:10px;padding:1px 5px;border-radius:8px;margin-left:4px">${c}</span>`:''}
    </div>`;
  }).join('');
  document.getElementById('skill-col').innerHTML = '<div style="padding:20px;font-size:12.5px;color:var(--muted);text-align:center">← Select a subcategory</div>';
}

function selectSub(sub) {
  activeSub = sub;
  // re-render subcat col to update active state
  const subs = SKILL_TREE[activeCat].subs;
  const col = document.getElementById('subcat-col');
  col.innerHTML = Object.keys(subs).map(s => {
    const c = subs[s].filter(x=>selectedSkills.has(x)).length;
    return `<div class="subcat-item${activeSub===s?' active':''}" onclick="selectSub('${s.replace(/'/g,"\\'")}')">
      ${s}${c?` <span style="background:var(--g);color:white;font-size:10px;padding:1px 5px;border-radius:8px;margin-left:4px">${c}</span>`:''}
    </div>`;
  }).join('');
  renderSpecCol(sub);
}

function renderSpecCol(sub) {
  const specs = SKILL_TREE[activeCat].subs[sub] || [];
  const col = document.getElementById('skill-col');
  const canAdd = selectedSkills.size < MAX_SKILLS;
  col.innerHTML = `
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.06em;margin-bottom:10px">${activeCat} › ${sub}</div>
    ${specs.map(spec => {
      const sel = selectedSkills.has(spec);
      const disabled = !sel && !canAdd;
      const action = disabled
        ? `toast('Limit','You can select up to ${MAX_SKILLS} skills')`
        : `toggleSkill('${spec.replace(/'/g,"\\'")}')`;
      return `<div class="spec-item${sel?' selected':''}" onclick="${action}" style="${disabled?'opacity:.45;cursor:not-allowed':''}">
        <span>${spec}</span>
        <span class="spec-check">${sel?'✓':''}</span>
      </div>`;
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

function checkAndApply(modalId, connectsRequired) {
  if (freelancerState.connects < connectsRequired) {
    // Not enough connects — open purchase modal with shortage message
    openModal('connects');
    // Show warning after modal renders
    setTimeout(() => {
      const mc = document.getElementById('mc-body');
      const warning = document.createElement('div');
      warning.style.cssText = 'background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:11px 14px;font-size:13px;color:#92400e;margin-bottom:14px;line-height:1.6';
      warning.innerHTML = `⚠️ You need <strong>${connectsRequired} Connects</strong> to apply for this job but only have <strong>${freelancerState.connects}</strong>. Buy more below.`;
      mc.insertBefore(warning, mc.firstChild);
    }, 50);
    return;
  }
  if (modalId) {
    updateApplyModalConnects();
    openModal(modalId);
  } else {
    toast('Apply', `Opening proposal — costs ${connectsRequired} Connects`);
  }
}

function renderSelectedPreview() {
  const el = document.getElementById('selected-preview');
  const arr = [...selectedSkills];
  el.innerHTML = arr.length
    ? arr.map(s=>`<span style="display:inline-flex;align-items:center;gap:4px;background:#e8f5e3;color:#14a800;border:1px solid #c3e6c3;border-radius:5px;padding:2px 8px;font-size:11.5px;font-weight:500">
        ${s} <span style="cursor:pointer;color:#6b7c6b;font-size:13px" onclick="toggleSkill('${s.replace(/'/g,"\\'")}')">×</span>
      </span>`).join('')
    : '<span style="font-size:12.5px;color:var(--muted)">No skills selected yet</span>';
  el.innerHTML += `<span style="font-size:11.5px;color:var(--muted);white-space:nowrap;margin-left:auto;align-self:center">${arr.length}/${MAX_SKILLS}</span>`;
}

function saveSkills() {
  const display = document.getElementById('profile-skills-display');
  const empty   = document.getElementById('profile-skills-empty');
  if (display) {
    display.innerHTML = [...selectedSkills].map(s =>
      `<span class="skill-tag">${s} <span class="skill-remove" onclick="removeSkill('${s.replace(/'/g,"\\'")}')">×</span></span>`
    ).join('');
    display.style.display = selectedSkills.size ? 'flex' : 'none';
  }
  if (empty) empty.style.display = selectedSkills.size ? 'none' : 'block';
  const badge = document.getElementById('skill-count-badge');
  if (badge) badge.textContent = selectedSkills.size + ' / ' + MAX_SKILLS;
  closeSkillSelector();
  toast('Skills saved \u2713', selectedSkills.size + ' skills updated on your profile');
}

function removeSkill(skill) {
  selectedSkills.delete(skill);
  saveSkills();
  renderSuggestedSkills();
}

// ── QUICK-ADD (inline input on profile page) ──────────────────────
const SUGGESTED_FOR_DESIGNER = [
  'UX/UI Design','Wireframing','Adobe XD','Sketch','User Testing',
  'Information Architecture','Accessibility Design','Design Thinking',
  'Usability Testing','Interaction Design','Visual Design','Typography',
  'Color Theory','Responsive Design','Design Research'
];

function renderSuggestedSkills() {
  const el = document.getElementById('suggested-skills-row');
  if (!el) return;
  const shown = SUGGESTED_FOR_DESIGNER.filter(s => !selectedSkills.has(s)).slice(0, 8);
  el.innerHTML = shown.map(s =>
    `<button onclick="quickAddSkill('${s.replace(/'/g,"\\'")}');renderSuggestedSkills()"
      style="display:inline-flex;align-items:center;gap:4px;background:white;border:1.5px dashed var(--border);color:var(--dark3);border-radius:6px;padding:3px 10px;font-size:12.5px;font-weight:500;cursor:pointer;transition:all .15s;font-family:inherit"
      onmouseover="this.style.borderColor='var(--g)';this.style.color='var(--g)'"
      onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--dark3)'">
      + ${s}
    </button>`
  ).join('');
}

function quickAddSkill(val) {
  const skill = (val || '').trim();
  if (!skill) return;
  if (selectedSkills.has(skill)) { toast('Already added', '"' + skill + '" is already in your skills'); return; }
  if (selectedSkills.size >= MAX_SKILLS) { toast('Limit reached', 'You can add up to ' + MAX_SKILLS + ' skills'); return; }
  selectedSkills.add(skill);
  saveSkills();
  renderSuggestedSkills();
  toast('Skill added \u2713', '"' + skill + '" added to your profile');
}

function getAllSkillNames() {
  const all = [];
  Object.values(SKILL_TREE).forEach(cat => {
    Object.values(cat.subs).forEach(specs => specs.forEach(s => all.push(s)));
  });
  return all;
}

function showQuickSuggestions(q) {
  const el = document.getElementById('quick-suggestions');
  if (!el) return;
  const query = (q || '').trim().toLowerCase();
  if (!query) { el.style.display = 'none'; return; }
  const matches = getAllSkillNames()
    .filter(s => s.toLowerCase().includes(query) && !selectedSkills.has(s))
    .slice(0, 8);
  if (!matches.length) { el.style.display = 'none'; return; }
  el.style.display = 'block';
  el.innerHTML = matches.map(s =>
    '<div onclick="quickAddSkill(\'' + s.replace(/'/g,"\\'") + '\');document.getElementById(\'quick-skill-input\').value=\'\';hideQuickSuggestions()" style="padding:9px 14px;font-size:13px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .1s" onmouseover="this.style.background=\'var(--off)\'" onmouseout="this.style.background=\'white\'">' + s + '</div>'
  ).join('');
}

function hideQuickSuggestions() {
  const el = document.getElementById('quick-suggestions');
  if (el) el.style.display = 'none';
}

function filterSkills(q) {
  const query = q.toLowerCase().trim();
  if (!query) {
    renderCatCol();
    if (activeCat) selectCat(activeCat);
    if (activeSub) renderSpecCol(activeSub);
    return;
  }
  // Search all specialties across all cats/subs
  const catCol = document.getElementById('cat-col');
  const subCol = document.getElementById('subcat-col');
  const skillCol = document.getElementById('skill-col');
  let results = [];
  Object.entries(SKILL_TREE).forEach(([cat, data]) => {
    Object.entries(data.subs).forEach(([sub, specs]) => {
      specs.forEach(spec => {
        if (spec.toLowerCase().includes(query) || sub.toLowerCase().includes(query) || cat.toLowerCase().includes(query)) {
          results.push({cat, sub, spec});
        }
      });
    });
  });
  catCol.innerHTML = '<div style="padding:10px 16px;font-size:11.5px;color:var(--muted)">Search results</div>';
  subCol.innerHTML = '';
  skillCol.innerHTML = results.length
    ? results.map(({cat, sub, spec}) => {
        const sel = selectedSkills.has(spec);
        return `<div class="spec-item${sel?' selected':''}" onclick="toggleSkill('${spec.replace(/'/g,"\\'")}')">
          <div><div style="font-size:13px">${spec}</div><div style="font-size:11px;color:var(--muted)">${cat} › ${sub}</div></div>
          <span class="spec-check">${sel?'✓':''}</span>
        </div>`;
      }).join('')
    : '<div style="padding:20px;font-size:13px;color:var(--muted);text-align:center">No matching skills found</div>';
}

// ════════════════════════════════════════════════════════════
//  PAYMENT REPORTS ENGINE
// ════════════════════════════════════════════════════════════

const RPT_DATA = {
  may2026: {
    label: 'May 2026',
    chartTitle: 'Weekly Earnings — May 2026',
    weeks: ['May 1–4','May 5–11','May 12','May 13–14'],
    hourly:  [2070, 1350, 3105, 765],
    fixed:   [2250, 0,    0,    0],
    bonus:   [0,    0,    500,  0],
    transactions: [
      {date:'May 14',type:'hourly', desc:'Hourly — 8.5 hrs (in progress)',    client:'NexaFlow Inc.',  gross:765,   fee:0,   net:0,    status:'progress', ref:'WB-2026051401'},
      {date:'May 11',type:'hourly', desc:'Hourly billing — 15 hrs (week May 5–11)', client:'DataStack',      gross:1350,  fee:68,  net:1283, status:'review',   ref:'WB-2026051101'},
      {date:'May 12',type:'bonus',  desc:'🎁 Bonus — Exceptional delivery',   client:'NexaFlow Inc.',  gross:500,   fee:0,   net:500,  status:'paid',     ref:'WB-2026051202'},
      {date:'May 9', type:'hourly', desc:'Hourly billing — 23 hrs (week Apr 28–May 4)', client:'NexaFlow Inc.',  gross:2070,  fee:104, net:1966, status:'pending',  ref:'WB-2026050901'},
      {date:'May 12',type:'hourly', desc:'Hourly billing — 34.5 hrs',         client:'NexaFlow Inc.',  gross:3105,  fee:155, net:2950, status:'paid',     ref:'WB-2026051201'},
      {date:'May 8', type:'fixed',  desc:'Milestone 1 — Mobile App Redesign', client:'FinTech Co.',    gross:2250,  fee:113, net:2137, status:'paid',     ref:'WB-2026050801'},
      {date:'May 6', type:'hourly', desc:'Hourly billing — 18 hrs',           client:'DataStack',      gross:1710,  fee:86,  net:1624, status:'paid',     ref:'WB-2026050601'},
      {date:'May 3', type:'withdrawal', desc:'Withdrawal — Direct Bank Transfer', client:'—',           gross:-5711, fee:0,   net:-5711,status:'withdrawn', ref:'WD-2026050301'},
    ],
  },
  apr2026: {
    label: 'April 2026',
    chartTitle: 'Weekly Earnings — April 2026',
    weeks: ['Apr 1–6','Apr 7–13','Apr 14–20','Apr 21–30'],
    hourly:  [1800, 2700, 2520, 1440],
    fixed:   [0,    3200, 0,    4000],
    bonus:   [250,  0,    0,    300],
    transactions: [
      {date:'Apr 30',type:'bonus',  desc:'🎁 Bonus — Fast turnaround',        client:'Bloom Agency',   gross:300,   fee:0,   net:300,  status:'paid',     ref:'WB-2026043001'},
      {date:'Apr 28',type:'fixed',  desc:'Milestone 2 — Brand System',        client:'Bloom Agency',   gross:4000,  fee:200, net:3800, status:'paid',     ref:'WB-2026042801'},
      {date:'Apr 20',type:'hourly', desc:'Hourly billing — 28 hrs',           client:'NexaFlow Inc.',  gross:2520,  fee:126, net:2394, status:'paid',     ref:'WB-2026042001'},
      {date:'Apr 14',type:'fixed',  desc:'Webflow Build — Phase 1',           client:'Bloom Agency',   gross:3200,  fee:160, net:3040, status:'paid',     ref:'WB-2026041401'},
      {date:'Apr 13',type:'hourly', desc:'Hourly billing — 30 hrs',           client:'NexaFlow Inc.',  gross:2700,  fee:135, net:2565, status:'paid',     ref:'WB-2026041301'},
      {date:'Apr 6', type:'hourly', desc:'Hourly billing — 20 hrs',           client:'NexaFlow Inc.',  gross:1800,  fee:90,  net:1710, status:'paid',     ref:'WB-2026040601'},
      {date:'Apr 5', type:'bonus',  desc:'🎁 Bonus — Design quality',         client:'NexaFlow Inc.',  gross:250,   fee:0,   net:250,  status:'paid',     ref:'WB-2026040501'},
      {date:'Apr 25',type:'withdrawal', desc:'Withdrawal — PayPal',           client:'—',              gross:-8000, fee:0,   net:-8000,status:'withdrawn', ref:'WD-2026042501'},
    ],
  },
  mar2026: {
    label: 'March 2026',
    chartTitle: 'Weekly Earnings — March 2026',
    weeks: ['Mar 1–8','Mar 9–15','Mar 16–22','Mar 23–31'],
    hourly:  [3240, 2880, 2520, 2970],
    fixed:   [1800, 0,    2800, 0],
    bonus:   [0,    750,  0,    200],
    transactions: [
      {date:'Mar 31',type:'hourly', desc:'Hourly billing — 33 hrs',           client:'NexaFlow Inc.',  gross:2970,  fee:149, net:2821, status:'paid',     ref:'WB-2026033101'},
      {date:'Mar 22',type:'fixed',  desc:'App Onboarding UX — Final',         client:'GrowFast',       gross:2800,  fee:140, net:2660, status:'paid',     ref:'WB-2026032201'},
      {date:'Mar 19',type:'bonus',  desc:'🎁 Bonus — Ahead of schedule',      client:'DataStack',      gross:200,   fee:0,   net:200,  status:'paid',     ref:'WB-2026031901'},
      {date:'Mar 15',type:'hourly', desc:'Hourly billing — 32 hrs',           client:'NexaFlow Inc.',  gross:2880,  fee:144, net:2736, status:'paid',     ref:'WB-2026031501'},
      {date:'Mar 10',type:'bonus',  desc:'🎁 Bonus — Outstanding work',       client:'NexaFlow Inc.',  gross:750,   fee:0,   net:750,  status:'paid',     ref:'WB-2026031001'},
      {date:'Mar 8', type:'hourly', desc:'Hourly billing — 36 hrs',           client:'NexaFlow Inc.',  gross:3240,  fee:162, net:3078, status:'paid',     ref:'WB-2026030801'},
      {date:'Mar 5', type:'fixed',  desc:'Design System — Phase 1',           client:'DataStack',      gross:1800,  fee:90,  net:1710, status:'paid',     ref:'WB-2026030501'},
      {date:'Mar 28',type:'withdrawal', desc:'Withdrawal — Payoneer',         client:'—',              gross:-9000, fee:0,   net:-9000,status:'withdrawn', ref:'WD-2026032801'},
    ],
  },
  q1: {
    label: 'Q1 2026',
    chartTitle: 'Monthly Earnings — Q1 2026',
    weeks: ['January','February','March'],
    hourly:  [9200, 10080, 11610],
    fixed:   [3600, 4500,  4600],
    bonus:   [500,  800,   950],
    transactions: [
      {date:'Mar 2026',type:'hourly', desc:'Total hourly billings — March',   client:'Multiple',       gross:11610, fee:581, net:11029,status:'paid',     ref:'Q1-MAR-HR'},
      {date:'Mar 2026',type:'fixed',  desc:'Total fixed/milestone — March',   client:'Multiple',       gross:4600,  fee:230, net:4370, status:'paid',     ref:'Q1-MAR-FX'},
      {date:'Mar 2026',type:'bonus',  desc:'🎁 Total bonuses — March',        client:'Multiple',       gross:950,   fee:0,   net:950,  status:'paid',     ref:'Q1-MAR-BN'},
      {date:'Feb 2026',type:'hourly', desc:'Total hourly billings — February',client:'Multiple',       gross:10080, fee:504, net:9576, status:'paid',     ref:'Q1-FEB-HR'},
      {date:'Feb 2026',type:'fixed',  desc:'Total fixed/milestone — February',client:'Multiple',       gross:4500,  fee:225, net:4275, status:'paid',     ref:'Q1-FEB-FX'},
      {date:'Feb 2026',type:'bonus',  desc:'🎁 Total bonuses — February',     client:'Multiple',       gross:800,   fee:0,   net:800,  status:'paid',     ref:'Q1-FEB-BN'},
      {date:'Jan 2026',type:'hourly', desc:'Total hourly billings — January', client:'Multiple',       gross:9200,  fee:460, net:8740, status:'paid',     ref:'Q1-JAN-HR'},
      {date:'Jan 2026',type:'fixed',  desc:'Total fixed/milestone — January', client:'Multiple',       gross:3600,  fee:180, net:3420, status:'paid',     ref:'Q1-JAN-FX'},
      {date:'Jan 2026',type:'bonus',  desc:'🎁 Total bonuses — January',      client:'Multiple',       gross:500,   fee:0,   net:500,  status:'paid',     ref:'Q1-JAN-BN'},
    ],
  },
  '2025': {
    label: 'Full Year 2025',
    chartTitle: 'Monthly Earnings — 2025',
    weeks: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    hourly:  [5400,6120,7200,7560,8100,8640,9000,9450,9900,10440,10800,11250],
    fixed:   [1800,2400,2700,3000,3600,2700,3200,3600,2800,3200,3600,4000],
    bonus:   [0,200,300,250,400,500,350,600,400,750,550,800],
    transactions: [
      {date:'Dec 2025',type:'hourly', desc:'Hourly billings — December',      client:'Multiple',       gross:11250, fee:563, net:10688,status:'paid',     ref:'2025-DEC-HR'},
      {date:'Dec 2025',type:'bonus',  desc:'🎁 Year-end bonus payments',      client:'Multiple',       gross:800,   fee:0,   net:800,  status:'paid',     ref:'2025-DEC-BN'},
      {date:'Dec 2025',type:'fixed',  desc:'Fixed/milestone — December',      client:'Multiple',       gross:4000,  fee:200, net:3800, status:'paid',     ref:'2025-DEC-FX'},
      {date:'Nov 2025',type:'hourly', desc:'Hourly billings — November',      client:'Multiple',       gross:10800, fee:540, net:10260,status:'paid',     ref:'2025-NOV-HR'},
      {date:'Oct 2025',type:'hourly', desc:'Hourly billings — October',       client:'Multiple',       gross:10440, fee:522, net:9918, status:'paid',     ref:'2025-OCT-HR'},
      {date:'Sep 2025',type:'hourly', desc:'Hourly billings — September',     client:'Multiple',       gross:9900,  fee:495, net:9405, status:'paid',     ref:'2025-SEP-HR'},
      {date:'2025 H1', type:'hourly', desc:'H1 hourly billings (Jan–Jun)',    client:'Multiple',       gross:44820, fee:2241,net:42579,status:'paid',     ref:'2025-H1-HR'},
      {date:'2025 H1', type:'bonus',  desc:'🎁 H1 bonus payments (Jan–Jun)', client:'Multiple',       gross:1650,  fee:0,   net:1650, status:'paid',     ref:'2025-H1-BN'},
    ],
  },
};

// Client breakdowns per period
const RPT_CLIENTS = {
  may2026:  [{name:'NexaFlow Inc.',  color:'#1e40af', bg:'#dbeafe', pct:60, amt:9290},
             {name:'DataStack',       color:'#92400e', bg:'#fef3c7', pct:22, amt:3060},
             {name:'FinTech Co.',     color:'#065f46', bg:'#d1fae5', pct:18, amt:2250}],
  apr2026:  [{name:'NexaFlow Inc.',  color:'#1e40af', bg:'#dbeafe', pct:42, amt:5520},
             {name:'Bloom Agency',    color:'#5b21b6', bg:'#ede9fe', pct:41, amt:7500},
             {name:'DataStack',       color:'#92400e', bg:'#fef3c7', pct:17, amt:1800}],
  mar2026:  [{name:'NexaFlow Inc.',  color:'#1e40af', bg:'#dbeafe', pct:53, amt:9860},
             {name:'DataStack',       color:'#92400e', bg:'#fef3c7', pct:26, amt:4800},
             {name:'GrowFast',        color:'#166534', bg:'#dcfce7', pct:21, amt:2800}],
  q1:       [{name:'NexaFlow Inc.',  color:'#1e40af', bg:'#dbeafe', pct:55, amt:30860},
             {name:'DataStack',       color:'#92400e', bg:'#fef3c7', pct:25, amt:14000},
             {name:'Others',          color:'#374151', bg:'#f3f4f6', pct:20, amt:11230}],
  '2025':   [{name:'NexaFlow Inc.',  color:'#1e40af', bg:'#dbeafe', pct:48, amt:39540},
             {name:'DataStack',       color:'#92400e', bg:'#fef3c7', pct:22, amt:18140},
             {name:'Bloom Agency',    color:'#5b21b6', bg:'#ede9fe', pct:18, amt:14820},
             {name:'Others',          color:'#374151', bg:'#f3f4f6', pct:12, amt:9880}],
};

const STATUS_META = {
  paid:      {label:'Paid',        bg:'#dcfce7',color:'#166534'},
  pending:   {label:'Pending',     bg:'#dbeafe',color:'#1e40af'},
  review:    {label:'In Review',   bg:'#fef3c7',color:'#92400e'},
  progress:  {label:'In Progress', bg:'#e2e8f0',color:'#475569'},
  withdrawn: {label:'Withdrawn',   bg:'#ede9fe',color:'#5b21b6'},
};
const TYPE_META = {
  hourly:    {label:'Hourly',      bg:'#dcfce7',color:'#166534'},
  fixed:     {label:'Fixed/MS',    bg:'#ede9fe',color:'#5b21b6'},
  bonus:     {label:'Bonus',       bg:'#fef3c7',color:'#92400e'},
  withdrawal:{label:'Withdrawal',  bg:'#fee2e2',color:'#991b1b'},
  refund:    {label:'Refund',      bg:'#e0f2fe',color:'#0369a1'},
};

function fmt(n){
  if(n===0)return'—';
  const abs=Math.abs(n);
  const s='$'+abs.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
  return n<0?'('+s+')':s;
}

function getPeriod(){
  return document.getElementById('rpt-period')?.value || 'may2026';
}

function renderReports(){
  const key = getPeriod();
  const d   = RPT_DATA[key];
  if(!d) return;

  // Chart title
  document.getElementById('rpt-chart-title').textContent = d.chartTitle;

  // ── KPI cards ──────────────────────────────────────────
  const grossArr = d.transactions.filter(t=>t.type!=='withdrawal').map(t=>t.gross);
  const totalGross = grossArr.reduce((a,b)=>a+b,0);
  const totalFee   = d.transactions.filter(t=>t.fee>0).reduce((a,t)=>a+t.fee,0);
  const totalNet   = totalGross - totalFee;
  const totalBonus = d.transactions.filter(t=>t.type==='bonus').reduce((a,t)=>a+t.gross,0);
  const bonusCount = d.transactions.filter(t=>t.type==='bonus').length;
  const totalWith  = Math.abs(d.transactions.filter(t=>t.type==='withdrawal').reduce((a,t)=>a+t.gross,0));

  const kpis = [
    {label:'Gross Earnings', val:fmt(totalGross), sub:'Before platform fee', color:'var(--dark)', icon:'💰'},
    {label:'Platform Fees',  val:fmt(totalFee),   sub:'At 5% service fee',  color:'#dc2626',     icon:'📋'},
    {label:'Net Earnings',   val:fmt(totalNet),   sub:'After fees',         color:'var(--g)',     icon:'✅'},
    {label:'Bonus Payments', val:fmt(totalBonus), sub:`${bonusCount} bonus${bonusCount!==1?'es':''}`, color:'#7c3aed', icon:'🎁'},
    {label:'Withdrawn',      val:totalWith?fmt(totalWith):'$0.00', sub:'Transferred out', color:'#475569', icon:'🏦'},
  ];

  document.getElementById('rpt-kpi-row').innerHTML = kpis.map(k=>`
    <div style="background:white;border:1px solid var(--border);border-radius:10px;padding:14px 16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <div style="font-size:11.5px;color:var(--muted);font-weight:600">${k.label}</div>
        <span style="font-size:18px">${k.icon}</span>
      </div>
      <div style="font-size:20px;font-weight:800;color:${k.color};line-height:1.1">${k.val}</div>
      <div style="font-size:11px;color:var(--muted2);margin-top:4px">${k.sub}</div>
    </div>`).join('');

  // ── Bar chart ───────────────────────────────────────────
  const allVals = d.weeks.map((_,i)=>d.hourly[i]+d.fixed[i]+d.bonus[i]);
  const maxVal  = Math.max(...allVals) || 1;
  const barW    = Math.max(28, Math.floor(320/d.weeks.length)-8);

  document.getElementById('rpt-bar-chart').innerHTML = d.weeks.map((_,i)=>{
    const h = d.hourly[i], f = d.fixed[i], b = d.bonus[i], tot = h+f+b;
    const hp = Math.round((h/maxVal)*100);
    const fp = Math.round((f/maxVal)*100);
    const bp = Math.round((b/maxVal)*100);
    const tip = `$${tot.toLocaleString()} — Hourly: $${h.toLocaleString()}, Fixed: $${f.toLocaleString()}, Bonus: $${b.toLocaleString()}`;
    return `<div style="display:flex;flex-direction:column;align-items:center;gap:0;cursor:pointer;flex:1" title="${tip}" onclick="toast('${d.weeks[i]}','${tip}')">
      ${bp?`<div style="width:100%;background:#f59e0b;border-radius:3px 3px 0 0;height:${bp}%;min-height:3px;transition:all .3s"></div>`:''}
      ${fp?`<div style="width:100%;background:#8b5cf6;height:${fp}%;min-height:3px;transition:all .3s"></div>`:''}
      <div style="width:100%;background:var(--g);border-radius:${(!fp&&!bp)?'3px 3px':fp||bp?'0':'3px 3px'} 0 0;height:${hp}%;min-height:${tot?4:0}px;transition:all .3s"></div>
    </div>`;
  }).join('');

  document.getElementById('rpt-bar-labels').innerHTML = d.weeks.map(w=>
    `<div style="flex:1;font-size:10px;color:var(--muted);text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${w}</div>`
  ).join('');

  // ── Bonus panel ─────────────────────────────────────────
  const bonuses = d.transactions.filter(t=>t.type==='bonus');
  document.getElementById('rpt-bonus-total').textContent = bonuses.length ? fmt(totalBonus) : '';
  document.getElementById('rpt-bonus-list').innerHTML = bonuses.length
    ? bonuses.map(b=>`
        <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid var(--border)">
          <div style="width:36px;height:36px;border-radius:8px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">🎁</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600">${b.desc.replace('🎁 ','')}</div>
            <div style="font-size:11.5px;color:var(--muted)">${b.client} · ${b.date}</div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:14px;font-weight:700;color:#7c3aed">${fmt(b.gross)}</div>
            <div style="font-size:11px;color:var(--muted)">No fee charged</div>
          </div>
        </div>`).join('') +
      `<div style="padding:10px 16px;background:var(--off);border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:12.5px">
         <span style="color:var(--muted)">${bonuses.length} bonus payment${bonuses.length!==1?'s':''}</span>
         <strong style="color:#7c3aed">${fmt(totalBonus)} total</strong>
       </div>`
    : `<div style="padding:28px;text-align:center;color:var(--muted);font-size:13px">
         <div style="font-size:28px;margin-bottom:8px">🎁</div>
         No bonus payments this period
       </div>`;

  // ── Client breakdown ────────────────────────────────────
  const clients = RPT_CLIENTS[key] || [];
  document.getElementById('rpt-client-breakdown').innerHTML = clients.map(c=>`
    <div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <div style="display:flex;align-items:center;gap:7px">
          <div style="width:10px;height:10px;border-radius:50%;background:${c.color}"></div>
          <span style="font-size:13px;font-weight:600">${c.name}</span>
        </div>
        <span style="font-size:13px;font-weight:700">${fmt(c.amt)}</span>
      </div>
      <div style="background:var(--border);border-radius:4px;height:7px;overflow:hidden">
        <div style="height:100%;background:${c.color};border-radius:4px;width:${c.pct}%;transition:width .4s"></div>
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:3px">${c.pct}% of period earnings</div>
    </div>`).join('');

  // ── Type breakdown ──────────────────────────────────────
  const byType = {};
  d.transactions.filter(t=>t.type!=='withdrawal').forEach(t=>{
    if(!byType[t.type]) byType[t.type]={gross:0,count:0};
    byType[t.type].gross += t.gross;
    byType[t.type].count++;
  });
  const typeTotal = Object.values(byType).reduce((a,v)=>a+v.gross,0)||1;
  const typeColors = {hourly:'var(--g)',fixed:'#8b5cf6',bonus:'#f59e0b',refund:'#0369a1'};
  document.getElementById('rpt-type-breakdown').innerHTML = Object.entries(byType).map(([type,v])=>{
    const pct = Math.round((v.gross/typeTotal)*100);
    const m   = TYPE_META[type]||{label:type};
    return `
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <div style="display:flex;align-items:center;gap:7px">
            <div style="width:10px;height:10px;border-radius:50%;background:${typeColors[type]||'#94a3b8'}"></div>
            <span style="font-size:13px;font-weight:600">${m.label}</span>
            <span style="font-size:11px;color:var(--muted)">(${v.count})</span>
          </div>
          <span style="font-size:13px;font-weight:700">${fmt(v.gross)}</span>
        </div>
        <div style="background:var(--border);border-radius:4px;height:7px;overflow:hidden">
          <div style="height:100%;background:${typeColors[type]||'#94a3b8'};border-radius:4px;width:${pct}%;transition:width .4s"></div>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:3px">${pct}% of gross earnings</div>
      </div>`;
  }).join('');

  renderLedger();
}

function renderLedger(){
  const key    = getPeriod();
  const d      = RPT_DATA[key];
  const typeF  = document.getElementById('rpt-type-filter')?.value   || 'all';
  const statF  = document.getElementById('rpt-status-filter')?.value || 'all';

  let rows = d.transactions;
  if(typeF  !== 'all') rows = rows.filter(r=>r.type   === typeF);
  if(statF  !== 'all') rows = rows.filter(r=>r.status === statF);

  document.getElementById('rpt-ledger-count').textContent = `${rows.length} transaction${rows.length!==1?'s':''}`;

  const statusBg = {
    paid:     '#f8fafc', pending: '#eff6ff', review: '#fffbeb',
    progress: '#f8fafc', withdrawn: '#faf5ff',
  };

  document.getElementById('rpt-ledger-body').innerHTML = rows.map(r=>{
    const sm = STATUS_META[r.status]||{label:r.status,bg:'#f3f4f6',color:'#374151'};
    const tm = TYPE_META[r.type]   ||{label:r.type,  bg:'#f3f4f6',color:'#374151'};
    const isWd = r.type==='withdrawal';
    return `<tr style="background:${statusBg[r.status]||'white'}">
      <td style="color:var(--muted);font-size:12px;white-space:nowrap">${r.date}</td>
      <td><span class="badge" style="background:${tm.bg};color:${tm.color}">${tm.label}</span></td>
      <td style="font-size:13px;max-width:220px">${r.desc}</td>
      <td style="font-size:13px">${r.client}</td>
      <td style="font-weight:600;color:${isWd?'#dc2626':'var(--dark)'}">${isWd?fmt(r.gross):fmt(r.gross)}</td>
      <td style="color:var(--muted)">${r.fee?fmt(r.fee):'—'}</td>
      <td style="font-weight:700;color:${r.net>0?'var(--g)':r.net<0?'#dc2626':'var(--muted)'}">${r.net!==0?fmt(r.net):'—'}</td>
      <td><span class="badge" style="background:${sm.bg};color:${sm.color}">${sm.label}</span></td>
      <td style="font-size:11px;color:var(--muted2);font-family:monospace">${r.ref}</td>
    </tr>`;
  }).join('') || `<tr><td colspan="9" style="text-align:center;padding:24px;color:var(--muted);font-size:13px">No transactions match current filters</td></tr>`;

  // Footer totals
  const paidRows = rows.filter(r=>r.type!=='withdrawal');
  const fGross = paidRows.reduce((a,r)=>a+r.gross,0);
  const fFee   = paidRows.reduce((a,r)=>a+r.fee,0);
  const fNet   = fGross - fFee;
  const fBonus = rows.filter(r=>r.type==='bonus').reduce((a,r)=>a+r.gross,0);
  document.getElementById('rpt-ledger-footer').innerHTML = `
    <div><span style="color:var(--muted)">Gross: </span><strong>${fmt(fGross)}</strong></div>
    <div><span style="color:var(--muted)">Fees: </span><strong style="color:#dc2626">${fmt(fFee)}</strong></div>
    <div><span style="color:var(--muted)">Net: </span><strong style="color:var(--g)">${fmt(fNet)}</strong></div>
    ${fBonus?`<div><span style="color:var(--muted)">Bonuses: </span><strong style="color:#7c3aed">${fmt(fBonus)}</strong></div>`:''}
    <div style="margin-left:auto;font-size:11.5px;color:var(--muted)">${rows.length} rows shown</div>`;
}

function exportReportCSV(){
  const key = getPeriod();
  const d   = RPT_DATA[key];
  const header = 'Date,Type,Description,Client,Gross,Fee,Net,Status,Reference\n';
  const rows   = d.transactions.map(r=>
    [r.date,r.type,'"'+r.desc.replace(/"/g,'""')+'"',r.client,r.gross,r.fee,r.net,r.status,r.ref].join(',')
  ).join('\n');
  const blob = new Blob([header+rows],{type:'text/csv'});
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = `Remoworkers_PaymentReport_${d.label.replace(/\s/g,'_')}.csv`;
  a.click();
  toast('Exported ✓', `${d.label} report downloaded as CSV`);
}

// ── INLINE EDIT (profile subtitle fields) ────────────────────────
const INLINE_PLACEHOLDERS = {
  title:    'e.g. Senior UI/UX Designer',
  rate:     'e.g. $90/hr',
  location: 'e.g. Berlin, Germany',
};

function startInlineEdit(field) {
  const span = document.getElementById('field-' + field);
  if (!span || span.querySelector('input')) return; // already editing

  // Hide any other open editors first
  ['title','rate','location'].forEach(f => {
    if (f !== field) cancelInlineEdit(f);
  });

  const current = span.innerText.trim();
  const input = document.createElement('input');
  input.type = 'text';
  input.value = current;
  input.className = 'inline-edit-input';
  input.placeholder = INLINE_PLACEHOLDERS[field] || '';
  input.style.width = Math.max(current.length * 8, 100) + 'px';

  // Save button
  const saveBtn = document.createElement('button');
  saveBtn.textContent = 'Save';
  saveBtn.className = 'inline-save-btn';
  saveBtn.setAttribute('data-field', field);

  // Wrap input + button together
  const wrapper = document.createElement('span');
  wrapper.className = 'inline-edit-wrapper';
  wrapper.setAttribute('data-field', field);
  wrapper.setAttribute('data-original', current);
  wrapper.appendChild(input);
  wrapper.appendChild(saveBtn);

  span.innerHTML = '';
  span.appendChild(wrapper);
  input.focus();
  input.select();

  function commit() {
    const val = input.value.trim() || current;
    span.innerHTML = val;
    toast('Saved ✓', field.charAt(0).toUpperCase() + field.slice(1) + ' updated');
  }

  saveBtn.addEventListener('mousedown', e => e.preventDefault()); // prevent blur before click
  saveBtn.addEventListener('click', commit);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); commit(); }
    if (e.key === 'Escape') { span.innerHTML = current; }
  });
}

function cancelInlineEdit(field) {
  const span = document.getElementById('field-' + field);
  if (!span) return;
  const wrapper = span.querySelector('.inline-edit-wrapper');
  if (wrapper) {
    span.innerHTML = wrapper.getAttribute('data-original');
  }
}

// ── MOBILE SIDEBAR & NAV ─────────────────────────────────────────
function toggleMobSidebar() {
  const sb = document.querySelector('.sidebar');
  const ov = document.getElementById('mob-overlay');
  const isOpen = sb.classList.contains('mob-open');
  if (isOpen) { closeMobSidebar(); }
  else {
    sb.classList.add('mob-open');
    ov.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function closeMobSidebar() {
  document.querySelector('.sidebar').classList.remove('mob-open');
  document.getElementById('mob-overlay').classList.remove('open');
  document.body.style.overflow = '';
}

function setMobNav(id) {
  // Active state is now managed inside showPage()
  document.querySelectorAll('.mob-nav-item').forEach(el => el.classList.remove('active'));
  const el = document.getElementById('mn-' + id);
  if (el) el.classList.add('active');
}

// Close sidebar when a nav item is clicked on mobile
document.querySelectorAll('.sb-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth <= 900) closeMobSidebar();
  });
});


// ── MILESTONE SUBMISSION ──────────────────────────────────────────

function showM2Form() {
  const form = document.getElementById('m2-submit-form');
  const actions = document.getElementById('m2-actions');
  if (form) { form.style.display = 'block'; }
  if (actions) { actions.style.display = 'none'; }
  const modal = document.querySelector('.modal');
  if (modal) setTimeout(() => modal.scrollTo({ top: modal.scrollHeight, behavior: 'smooth' }), 60);
}

function cancelM2Submit() {
  const form = document.getElementById('m2-submit-form');
  const actions = document.getElementById('m2-actions');
  if (form) { form.style.display = 'none'; }
  if (actions) { actions.style.display = 'flex'; }
}

let m2Files = [];

function handleM2Files(files) {
  Array.from(files).forEach(f => {
    if (!m2Files.find(x => x.name === f.name)) m2Files.push(f);
  });
  renderM2Files();
}

function handleM2Drop(e) {
  e.preventDefault();
  const dz = document.getElementById('m2-dropzone');
  if (dz) { dz.style.borderColor = 'var(--border)'; dz.style.background = 'var(--off)'; }
  handleM2Files(e.dataTransfer.files);
}

function renderM2Files() {
  const list = document.getElementById('m2-file-list');
  if (!list) return;
  list.innerHTML = m2Files.map((f, i) => `
    <div style="display:flex;align-items:center;gap:8px;background:var(--off);border:1px solid var(--border);border-radius:7px;padding:8px 11px;font-size:12.5px">
      <span style="font-size:16px">${f.name.endsWith('.pdf') ? '📄' : f.name.endsWith('.zip') ? '🗜️' : '🖼️'}</span>
      <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${f.name}</span>
      <span style="color:var(--muted);font-size:11.5px">${(f.size/1024/1024).toFixed(1)} MB</span>
      <span style="cursor:pointer;color:var(--muted);font-size:16px;line-height:1" onclick="removeM2File(${i})">×</span>
    </div>
  `).join('');
}

function removeM2File(idx) {
  m2Files.splice(idx, 1);
  renderM2Files();
}

// ════════════════════════════════════════════════════════════
//  ID VERIFICATION
// ════════════════════════════════════════════════════════════

let vDocType = null;
let vFiles = { front: null, back: null };

function selectDocType(type, elId) {
  vDocType = type;
  // Reset all cards
  document.querySelectorAll('.doc-type-card').forEach(c => {
    c.style.border = '2px solid var(--border)';
    c.style.background = 'white';
  });
  const chosen = document.getElementById(elId);
  if (chosen) { chosen.style.border = '2px solid var(--g)'; chosen.style.background = 'var(--gl)'; }

  const labels = { passport:'Passport', 'national-id':'National ID', drivers:"Driver's Licence", residence:'Residence Permit' };
  const bar = document.getElementById('dtype-selected-bar');
  bar.style.display = 'flex';
  document.getElementById('dtype-selected-text').textContent = (labels[type] || type) + ' selected';

  // Passport = only front; others = front + back
  const backSection = document.getElementById('vback-section');
  const frontLabel = document.getElementById('front-label');
  if (type === 'passport') {
    backSection.style.display = 'none';
    frontLabel.textContent = '📄 Photo / data page';
  } else {
    backSection.style.display = 'block';
    frontLabel.textContent = '📄 Front side';
  }

  // Update upload step badge
  const lbl = document.getElementById('upload-doc-label');
  if (lbl) lbl.textContent = labels[type] || type;
}

function switchVStep(n) {
  if (n === 2 && !vDocType) { toast('Select a document','Please choose an ID type first'); return; }
  if (n === 3) { buildVReview(); }
  [1,2,3].forEach(i => {
    const panel = document.getElementById('vpanel-' + i);
    const step  = document.getElementById('vstep-' + i);
    const ico   = document.getElementById('vstep-' + i + '-ico');
    if (!panel) return;
    panel.style.display = (i === n) ? 'block' : 'none';
    if (step) step.style.background = (i === n) ? 'var(--gl)' : 'white';
    if (ico) {
      if (i < n) {
        ico.style.background = 'var(--g)'; ico.style.color = 'white'; ico.textContent = '✓';
      } else if (i === n) {
        ico.style.background = 'var(--g)'; ico.style.color = 'white'; ico.textContent = String(i);
      } else {
        ico.style.background = 'var(--border)'; ico.style.color = 'var(--muted)'; ico.textContent = String(i);
      }
    }
  });
}

function handleVDrop(e, side) {
  e.preventDefault();
  const zone = document.getElementById('vdrop-' + side);
  if (zone) { zone.style.borderColor = 'var(--border)'; zone.style.background = 'var(--off)'; }
  if (e.dataTransfer.files && e.dataTransfer.files[0]) handleVFileInput(e.dataTransfer.files, side);
}

function handleVFileInput(files, side) {
  if (!files || !files[0]) return;
  const f = files[0];
  const maxMB = 10;
  if (f.size > maxMB * 1024 * 1024) { toast('File too large', 'Maximum file size is 10 MB'); return; }
  vFiles[side] = f;

  const placeholder = document.getElementById('v' + side + '-placeholder');
  const preview = document.getElementById('v' + side + '-preview');
  if (placeholder) placeholder.style.display = 'none';
  if (preview) {
    preview.style.display = 'flex';
    const isImg = f.type.startsWith('image/');
    preview.innerHTML = isImg
      ? `<img src="${URL.createObjectURL(f)}" style="max-height:110px;max-width:100%;border-radius:7px;object-fit:contain;box-shadow:0 2px 8px rgba(0,0,0,.12)">`
      : `<div style="font-size:36px">📄</div>`;
    preview.innerHTML += `
      <div style="font-size:12.5px;font-weight:600;color:var(--dark)">${f.name}</div>
      <div style="font-size:11.5px;color:var(--muted)">${(f.size/1024/1024).toFixed(2)} MB</div>
      <button class="btn btn-w btn-sm" onclick="clearVFile('${side}')">✕ Remove</button>`;
  }
  checkVStep2Ready();
}

function clearVFile(side) {
  vFiles[side] = null;
  document.getElementById('v' + side + '-placeholder').style.display = 'block';
  const preview = document.getElementById('v' + side + '-preview');
  if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
  document.getElementById('vinput-' + side).value = '';
  checkVStep2Ready();
}

function checkVStep2Ready() {
  const needBack = (vDocType !== 'passport');
  const ready = vFiles.front && (!needBack || vFiles.back);
  const btn = document.getElementById('vnext-2');
  if (btn) { btn.disabled = !ready; btn.style.opacity = ready ? '1' : '.45'; }
}

function validateAndGoStep3() {
  const needBack = (vDocType !== 'passport');
  if (!vFiles.front) { toast('Front side missing','Please upload the front of your document'); return; }
  if (needBack && !vFiles.back) { toast('Back side missing','Please upload the back of your document'); return; }
  switchVStep(3);
}

function buildVReview() {
  const labels = { passport:'Passport', 'national-id':'National ID', drivers:"Driver's Licence", residence:'Residence Permit' };
  const needBack = vDocType !== 'passport';
  let html = `<div style="display:grid;grid-template-columns:${needBack?'1fr 1fr':'1fr'};gap:12px;margin-bottom:4px">`;
  ['front', needBack ? 'back' : null].filter(Boolean).forEach(side => {
    const f = vFiles[side];
    const isImg = f && f.type.startsWith('image/');
    html += `<div style="border:1px solid var(--border);border-radius:8px;overflow:hidden">
      <div style="background:var(--off);padding:8px 12px;font-size:11.5px;font-weight:700;border-bottom:1px solid var(--border)">${side==='front'? (vDocType==='passport'?'Photo page':'Front side') : 'Back side'}</div>
      <div style="padding:12px;text-align:center;background:white">
        ${f ? (isImg ? `<img src="${URL.createObjectURL(f)}" style="max-height:90px;max-width:100%;border-radius:5px;object-fit:contain">` : `<div style="font-size:28px;margin:8px 0">📄</div>`) : '<div style="color:var(--muted);font-size:12px">No file</div>'}
        ${f ? `<div style="font-size:11.5px;color:var(--muted);margin-top:6px">${f.name}</div>` : ''}
      </div>
    </div>`;
  });
  html += `</div>
  <div style="font-size:12px;font-weight:700;color:var(--g);text-align:center;margin-bottom:4px">Document type: ${labels[vDocType]||vDocType}</div>`;
  const rc = document.getElementById('vreview-content');
  if (rc) rc.innerHTML = html;
}

function toggleVSubmit() {
  const consent = document.getElementById('vconsent');
  const btn = document.getElementById('vsubmit-btn');
  const ready = consent && consent.checked;
  if (btn) { btn.disabled = !ready; btn.style.opacity = ready ? '1' : '.45'; }
}

function submitVerification() {
  const name = document.getElementById('vlegal-name');
  const dob  = document.getElementById('vdob');
  const docNum = document.getElementById('vdoc-number');
  if (!name || name.value.trim() === '') { toast('Name required','Enter your full legal name'); return; }
  if (!dob  || dob.value === '')  { toast('DOB required','Enter your date of birth'); return; }
  if (!docNum || docNum.value.trim() === '') { toast('Document number required','Enter your document number'); return; }

  // Show submitted state
  [1,2,3].forEach(i => { const p = document.getElementById('vpanel-' + i); if(p) p.style.display = 'none'; });
  document.getElementById('vpanel-done').style.display = 'block';

  // Update sidebar badge — remove the "!" badge now
  const navV = document.querySelector('[onclick*=verification]');
  if (navV) {
    const badge = navV.querySelector('.sb-badge');
    if (badge) badge.remove();
  }

  // Update profile badge to "Under Review"
  toast('Submitted! 🎉', 'Verification under review — expect 1–3 business days');
}

function submitM2() {
  const summary = document.getElementById('m2-summary');
  const link1 = document.getElementById('m2-link1');
  if (!summary || summary.value.trim().length < 20) {
    summary.style.borderColor = '#dc2626';
    summary.focus();
    toast('Required field', 'Please add a work summary (at least 20 characters)');
    setTimeout(() => { if (summary) summary.style.borderColor = 'var(--border)'; }, 2000);
    return;
  }
  if (!link1 || link1.value.trim() === '') {
    link1.style.borderColor = '#dc2626';
    link1.focus();
    toast('Required field', 'Please include at least one deliverable link');
    setTimeout(() => { if (link1) link1.style.borderColor = 'var(--border)'; }, 2000);
    return;
  }

  const card = document.getElementById('m2-card');
  if (card) {
    card.style.border = '2px solid #3b82f6';
    card.style.background = '#eff6ff';
    card.style.boxShadow = 'none';
    card.innerHTML = `
      <div style="display:flex;align-items:flex-start;gap:12px">
        <div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;flex-shrink:0">🕐</div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:13.5px;margin-bottom:3px">Milestone 2 — Submitted for Review</div>
          <div style="font-size:12px;color:#1e40af;margin-bottom:10px">Submitted just now · Client has 14 days to respond</div>
          <div style="background:white;border:1px solid #bfdbfe;border-radius:8px;padding:11px;font-size:12.5px;color:#1e3a8a;line-height:1.65">
            FinTech Co. has been notified and will receive your deliverables for review. You will get a notification when they approve or request changes.
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-weight:700;font-size:15px">,250</div>
          <span class="badge b-blue" style="font-size:10px">In Review</span>
        </div>
      </div>`;
  }

  toast('Milestone submitted! 🎉', 'FinTech Co. notified — awaiting approval');
  m2Files = [];
  setTimeout(() => closeModal(), 2800);
}
</script>
</body>
</html>
