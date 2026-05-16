<script>
(function() {
  const JOBS = <?php echo json_encode($allJobs ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const SAVED_IDS = <?php echo json_encode(array_column($savedJobs ?? [], 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const PROPOSALS = <?php echo json_encode($submittedProposals ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const CONTRACTS = <?php echo json_encode($allContracts ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const USER_CONNECTS = <?php echo $user['connects'] ?? 0; ?>;

  function showPage(id) {
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(p => p.classList.remove('active'));
    
    // Show target page
    const pg = document.getElementById('page-' + id);
    if (pg) {
      pg.classList.add('active');
      window.scrollTo(0, 0);
    }
    
    // Update sidebar active state
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
    const navEl = document.getElementById('nav-' + id);
    if (navEl) navEl.classList.add('active');
    
    // Update title
    const titles = {
      'home': 'Dashboard', 'find-work': 'Find Work', 'proposals': 'My Proposals',
      'contracts': 'My Contracts', 'messages': 'Messages', 'earnings': 'Earnings',
      'catalog': 'My Services', 'profile': 'My Profile', 'reports': 'Payment Reports',
      'verification': 'ID Verification'
    };
    const titleEl = document.getElementById('page-title');
    if (titleEl) titleEl.textContent = titles[id] || id;

    // Mobile sidebar close
    const sb = document.getElementById('main-sidebar');
    const ov = document.getElementById('mob-overlay');
    if (sb) sb.classList.remove('mob-open');
    if (ov) ov.classList.remove('open');

    // Re-render
    if (id === 'find-work' || id === 'home') renderJobs();
    if (id === 'proposals') renderProposals();
    if (id === 'contracts') renderContracts();
    if (id === 'reports') renderReports();
  }

  function renderContracts(filter = 'Active') {
    const list = document.getElementById('contracts-list');
    if (!list) return;

    let filtered = [...CONTRACTS];
    const cleanFilter = filter.split('(')[0].trim();
    
    if (cleanFilter === 'Active') {
      filtered = CONTRACTS.filter(c => c.status === 'active');
    } else if (cleanFilter === 'Completed') {
      filtered = CONTRACTS.filter(c => c.status === 'completed');
    } else if (cleanFilter === 'Paused') {
      filtered = CONTRACTS.filter(c => c.status === 'paused');
    }

    if (filtered.length === 0) {
      list.innerHTML = `<div style="text-align:center;padding:60px;color:var(--muted)">No ${cleanFilter.toLowerCase()} contracts found.</div>`;
      return;
    }

    list.innerHTML = filtered.map(c => {
      const typeLabel = c.contract_type === 'hourly' ? 'Hourly' : 'Fixed';
      const typeColor = c.contract_type === 'hourly' ? '#e0f2fe' : '#f3e8ff';
      const typeText = c.contract_type === 'hourly' ? '#0369a1' : '#7e22ce';
      const progressPercent = c.status === 'completed' ? 100 : (c.contract_type === 'hourly' ? 65 : 45);
      
      return `
        <div style="display:grid;grid-template-columns:1.2fr 1.2fr 0.6fr 0.8fr 1fr 0.6fr 0.8fr;padding:18px 20px;border-bottom:1px solid #eee;align-items:center;font-size:13px;cursor:pointer" onclick="openModal('contract-detail', ${c.id})">
          <!-- Client -->
          <div style="display:flex;align-items:center;gap:12px">
            <div style="width:36px;height:36px;border-radius:50%;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px">${(c.client_name || 'C').charAt(0)}</div>
            <div>
              <div style="font-weight:700;color:#333">${c.client_name}</div>
              <div style="font-size:11px;color:#999">★ 5.0 · Verified</div>
            </div>
          </div>

          <!-- Project -->
          <div style="color:#555;font-weight:500">${c.job_title}</div>

          <!-- Type -->
          <div>
            <span style="background:${typeColor};color:${typeText};padding:4px 10px;border-radius:4px;font-size:11px;font-weight:700">${typeLabel}</span>
          </div>

          <!-- Earnings -->
          <div>
            <div style="font-weight:700;color:#333">$${parseFloat(c.total_earned || 0).toLocaleString()}</div>
            <div style="font-size:11px;color:#999">
              ${parseFloat(c.pending_earned || 0) > 0 
                ? `<span style="color:#f59e0b">$${parseFloat(c.pending_earned).toLocaleString()} in review</span>` 
                : (c.contract_type === 'hourly' ? 'Hourly basis' : `$${parseFloat(c.amount).toLocaleString()} total`)
              }
            </div>
          </div>

          <!-- Progress -->
          <div style="padding-right:20px">
            <div style="font-size:11px;color:#999;margin-bottom:6px">${c.status === 'active' ? (c.contract_type === 'hourly' ? 'Active - No end date' : 'Milestone 1 of 2 done') : 'Completed'}</div>
            <div style="width:100%;height:6px;background:#f3f4f6;border-radius:3px">
              <div style="width:${progressPercent}%;height:100%;background:#14a800;border-radius:3px"></div>
            </div>
          </div>

          <!-- Started -->
          <div style="color:#666">${new Date(c.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric'})}</div>

          <!-- Action -->
          <div>
            ${c.status === 'active' 
              ? `<button class="btn" style="padding:6px 15px;font-size:12px;border:1px solid #ddd;background:white;color:#333;font-weight:600;border-radius:15px">Details</button>`
              : `<button class="btn" style="padding:6px 15px;font-size:12px;border:1px solid #ddd;background:white;color:#333;font-weight:600;border-radius:15px">View Feed</button>`
            }
          </div>
        </div>
      `;
    }).join('');
  }

  function renderProposals(filter = 'Active') {
    const list = document.getElementById('proposals-list');
    if (!list) return;

    let filtered = [...PROPOSALS];
    if (filter === 'Active') {
      filtered = PROPOSALS.filter(p => p.status === 'accepted' || p.status === 'interviewing');
    } else if (filter === 'Submitted') {
      filtered = PROPOSALS.filter(p => p.status === 'pending');
    } else if (filter === 'Archived') {
      filtered = PROPOSALS.filter(p => p.status === 'rejected' || p.status === 'withdrawn');
    }

    if (filtered.length === 0) {
      list.innerHTML = `<div style="text-align:center;padding:60px;color:var(--muted)">
        <div style="font-size:40px;margin-bottom:15px">📄</div>
        <div>No ${filter.toLowerCase()} proposals found.</div>
      </div>`;
      return;
    }

    list.innerHTML = filtered.map(p => {
      const statusClass = p.status === 'accepted' ? 'b-green' : (p.status === 'rejected' ? 'b-red' : 'b-purple');
      return `
        <div class="contract-row" style="padding:22px;border-bottom:1px solid var(--border);display:grid;grid-template-columns:1fr 150px 120px;align-items:center">
          <div style="padding-right:20px">
            <div style="font-weight:700;font-size:16px;margin-bottom:6px;color:var(--dark)">${p.job_title}</div>
            <div style="display:flex;gap:15px;font-size:12px;color:var(--muted)">
              <span>Submitted ${new Date(p.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'})}</span>
              <span>${p.estimated_days || '7'} days delivery</span>
            </div>
          </div>
          <div style="text-align:center">
            <div style="font-weight:700;font-size:15px;color:var(--dark)">$${parseFloat(p.bid_amount).toLocaleString()}</div>
            <div style="font-size:11px;color:var(--muted2)">Proposed Bid</div>
          </div>
          <div style="text-align:right">
            <span class="badge ${statusClass}" style="padding:6px 12px;border-radius:6px;text-transform:capitalize">${p.status}</span>
          </div>
        </div>`;
    }).join('');
  }

  function renderJobs(filter = 'Best Matches') {
    const flist = document.getElementById('findwork-job-list');
    const hlist = document.getElementById('home-job-list');
    
    if (hlist) {
      hlist.innerHTML = JOBS.slice(0, 3).map(j => `
        <div class="job-row" onclick="openJobDetail(${j.id})">
          <div style="font-weight:700;margin-bottom:4px">${j.title}</div>
          <div style="font-size:12px;color:var(--muted)">${j.client_name} · ${timeAgo(j.created_at)}</div>
        </div>`).join('');
    }

    if (flist) {
      let jobs = [...JOBS];
      if (filter === 'Most Recent') jobs.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
      if (filter.includes('Saved Jobs')) {
        jobs = JOBS.filter(j => SAVED_IDS.includes(parseInt(j.id)));
      }
      
      flist.innerHTML = jobs.length ? jobs.map(j => {
        const isSaved = SAVED_IDS.includes(parseInt(j.id));
        const matchPercent = 90 + Math.floor(Math.random() * 9); // Mock match %
        return `
        <div class="job-row" onclick="openJobDetail(${j.id})" style="display:grid;grid-template-columns:1fr 180px;padding:20px;border-radius:12px;margin-bottom:15px;align-items:start">
          <div style="padding-right:20px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
              <div style="font-weight:700;font-size:16px;color:var(--dark)">${j.title}</div>
              <span style="background:#e8f5e3;color:#14a800;font-size:11px;padding:2px 8px;border-radius:12px;font-weight:600">${matchPercent}% match</span>
            </div>
            <div style="font-size:13.5px;color:var(--muted);margin-bottom:12px">${(j.description || '').substring(0, 160)}...</div>
            
            <div style="display:flex;align-items:center;gap:15px;font-size:12px;color:var(--muted2);margin-bottom:12px">
              <span style="display:flex;align-items:center;gap:4px">Payment verified</span>
              <span style="display:flex;align-items:center;gap:4px">★ 4.9 client · 18 hires</span>
              <span>${j.location || 'London, UK'}</span>
              <span style="background:#fefce8;color:#854d0e;padding:2px 8px;border-radius:6px;font-weight:600">💰 $89K+ spent</span>
            </div>

            <div style="display:flex;gap:10px">
              <span style="background:#ede9fe;color:#5b21b6;font-size:11.5px;padding:3px 10px;border-radius:6px;font-weight:600">${j.budget_type === 'fixed' ? 'Fixed Price' : 'Hourly'}: $${j.budget}</span>
              <span style="background:#f3f4f6;color:var(--dark3);font-size:11.5px;padding:3px 10px;border-radius:6px;font-weight:600">14 days</span>
              <span style="background:#f3f4f6;color:var(--dark3);font-size:11.5px;padding:3px 10px;border-radius:6px;font-weight:600">3 proposals</span>
            </div>
          </div>

          <div style="text-align:right;border-left:1px solid var(--border);padding-left:20px">
            <button class="btn btn-g" style="width:100%;margin-bottom:12px" onclick="event.stopPropagation();openApplyModal(${j.id})">Apply Now</button>
            <div style="color:var(--muted2);font-size:12px;margin-bottom:4px;display:flex;align-items:center;justify-content:flex-end;gap:4px">
              <span style="color:#f59e0b">⚡</span> 4 Connects
            </div>
            <div style="color:var(--muted2);font-size:11px">Posted ${timeAgo(j.created_at)}</div>
            <button class="save-btn ${isSaved?'active':''}" style="margin-top:15px;background:none;border:none;font-size:20px;color:var(--muted2);cursor:pointer" onclick="event.stopPropagation();toggleSaveJob(${j.id}, this)">
              ${isSaved?'★':'☆'}
            </button>
          </div>
        </div>`;
      }).join('') : `<div style="padding:60px;text-align:center;color:var(--muted)">No jobs found.</div>`;
    }
  }

  function timeAgo(date) {
    if(!date) return "Just now";
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 3600) return Math.floor(seconds / 60) + "m ago";
    if (seconds < 86400) return Math.floor(seconds / 3600) + "h ago";
    return Math.floor(seconds / 86400) + "d ago";
  }

  window.openJobDetail = function(id) {
    const job = JOBS.find(j => j.id == id);
    if (!job) return;
    const matchPercent = 90 + Math.floor(Math.random() * 9);
    
    document.getElementById('mh-title').textContent = 'Job Details';
    document.getElementById('mc-body').innerHTML = `
      <div style="background:#f9fafb;min-height:400px">
        <div style="background:white;padding:30px;border-bottom:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
            <h2 style="font-size:24px;color:var(--dark);font-weight:700;line-height:1.3;flex:1">${job.title}</h2>
            <span style="background:#e8f5e3;color:#14a800;font-size:12px;padding:4px 12px;border-radius:12px;font-weight:600;margin-left:20px;white-space:nowrap">${matchPercent}% match</span>
          </div>
          
          <div style="display:flex;flex-wrap:wrap;gap:20px;color:var(--muted2);font-size:13.5px">
            <span style="color:var(--g);font-weight:600">Posted ${timeAgo(job.created_at)}</span>
            <span>📍 ${job.location || 'London, UK'}</span>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 280px;gap:0">
          <div style="background:white;padding:30px;border-right:1px solid var(--border)">
            <div style="font-size:15px;line-height:1.8;color:var(--dark3);margin-bottom:35px;white-space:pre-line">
              ${job.description || 'No description provided.'}
            </div>

            <div style="border-top:1px solid var(--border);padding-top:25px;margin-bottom:30px">
              <h4 style="margin-bottom:15px;font-size:15px;font-weight:700">Skills and Expertise</h4>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                ${(job.skills_required || 'Web Design, PHP, MySQL').split(',').map(s => `<span class="badge b-gray" style="padding:6px 14px;font-size:12.5px;background:#f3f4f6;border:none">${s.trim()}</span>`).join('')}
              </div>
            </div>

            <div style="background:#f8fafc;padding:20px;border-radius:12px;border:1px solid #e2e8f0">
              <div style="display:flex;justify-content:space-between;margin-bottom:12px">
                <span style="font-weight:600;color:var(--dark)">Connects Required</span>
                <span style="font-weight:700">4 Connects</span>
              </div>
              <div style="display:flex;justify-content:space-between">
                <span style="font-weight:600;color:var(--dark)">Your Connects</span>
                <span style="color:var(--g);font-weight:700">50 Connects</span>
              </div>
            </div>
          </div>

          <div style="padding:30px;background:#f9fafb">
            <button class="btn btn-g" style="width:100%;padding:14px;font-size:15px;font-weight:700;margin-bottom:12px;border-radius:8px" onclick="event.stopPropagation();openApplyModal(${job.id})">Apply Now</button>
            <button class="btn btn-w" style="width:100%;padding:12px;font-size:14px;margin-bottom:25px;border:1px solid var(--border)" onclick="toggleSaveJob(${job.id}, null)">Save Job</button>

            <div style="margin-bottom:25px">
              <h4 style="font-size:14px;margin-bottom:15px;font-weight:700">About the Client</h4>
              <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">Payment verified ✅</div>
              <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">★ 4.9 of 18 reviews</div>
              <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">United Kingdom</div>
              <div style="font-size:13px;color:var(--muted2)">$89K+ total spent</div>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:20px">
              <div style="font-size:13px;font-weight:700;margin-bottom:8px">Job Link</div>
              <input type="text" value="remoworkers.com/j/${job.id}" readonly style="width:100%;font-size:11px;padding:8px;border:1px solid var(--border);border-radius:4px;background:#fff">
            </div>
          </div>
        </div>
      </div>
    `;
    document.getElementById('overlay').classList.add('open');
  }

  window.openApplyModal = function(id) {
    const job = JOBS.find(j => j.id == id);
    if (!job) return;
    
    document.getElementById('mh-title').textContent = 'Submit Proposal — ' + job.title;
    document.getElementById('mc-body').innerHTML = `
      <div style="padding:25px">
        <div style="background:#f0f7ef;color:#14a800;padding:12px 18px;border-radius:8px;font-size:13.5px;margin-bottom:25px;border:1px solid #d4e8d4">
          Fixed Price · $${job.budget || 0} · 6 Connects required · You have ${USER_CONNECTS}
        </div>

        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Your Proposed Rate ($)</label>
          <input type="number" id="prop-rate" value="${job.budget || 0}" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px">
        </div>

        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Estimated Delivery (days)</label>
          <input type="number" id="prop-days" value="7" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px">
        </div>

        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Cover Letter *</label>
          <textarea id="prop-letter" style="width:100%;height:150px;padding:12px;border:1px solid var(--border);border-radius:8px;font-size:14px;line-height:1.6" placeholder="Write your proposal here..."></textarea>
        </div>

        <div style="margin-bottom:30px">
          <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">Attachments (portfolio, case study)</label>
          <input type="text" id="prop-attach" placeholder="Paste Figma or portfolio link..." style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px">
        </div>

        <div style="display:flex;gap:12px">
          <button class="btn btn-w" style="flex:1;padding:12px;font-size:14px" onclick="closeModal()">Cancel</button>
          <button class="btn btn-g" style="flex:1.5;padding:12px;font-size:14px;font-weight:700" onclick="submitProposalForm(${job.id})">Submit Proposal →</button>
        </div>
      </div>
    `;
    document.getElementById('overlay').classList.add('open');
  }

  window.submitProposalForm = function(jobId) {
    const rate = document.getElementById('prop-rate').value;
    const days = document.getElementById('prop-days').value;
    const letter = document.getElementById('prop-letter').value;
    const attach = document.getElementById('prop-attach').value;

    if (!letter) return toast('Error', 'Please write a cover letter');

    const payload = {
        job_id: jobId,
        bid_amount: rate,
        estimated_days: days,
        cover_letter: letter,
        attachments: attach
    };
    console.log('Submitting proposal:', payload);

    fetch(BASE_URL + 'freelancer/api/submit-proposal.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(res => {
      console.log('Response status:', res.status);
      return res.json();
    })
    .then(data => {
      console.log('Response data:', data);
      if (data.success) {
        toast('Success', data.message);
        closeModal();
        showPage('proposals');
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => {
      console.error('Submission error:', err);
      toast('Error', 'Submission failed. Check console.');
    });
  }

  window.toggleSaveJob = function(id, btn) {
    fetch(BASE_URL + 'freelancer/api/toggle-save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ job_id: id })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const idx = SAVED_IDS.indexOf(parseInt(id));
        if (data.action === 'unsaved') {
          if (idx > -1) SAVED_IDS.splice(idx, 1);
          if(btn) { btn.textContent = '☆'; btn.classList.remove('active'); }
          toast('Saved', 'Job removed from favorites');
        } else {
          if (idx === -1) SAVED_IDS.push(parseInt(id));
          if(btn) { btn.textContent = '★'; btn.classList.add('active'); }
          toast('Saved', 'Job saved to favorites');
        }
        renderJobs(document.querySelector('.tab.on')?.textContent.trim());
      }
    })
    .catch(err => console.error('Save error:', err));
  }

  // Global Toast
  window.toast = function(title, msg) {
    const t = document.getElementById('toast');
    document.getElementById('t-title').textContent = title;
    document.getElementById('t-msg').textContent = msg ? ' — ' + msg : '';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  // Global showPage for legacy onclicks
  window.showPage = showPage;
  window.setTab = function(el) {
    const p = el.parentElement;
    p.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
    el.classList.add('on');
    
    const filter = el.textContent.trim();
    if (document.getElementById('page-find-work').classList.contains('active')) {
      renderJobs(filter);
    } else if (document.getElementById('page-proposals').classList.contains('active')) {
      renderProposals(filter);
    } else if (document.getElementById('page-contracts').classList.contains('active')) {
      renderContracts(filter);
    }
  }
  window.toggleSidebar = function() {
    document.getElementById('main-sidebar').classList.toggle('mob-open');
    document.getElementById('mob-overlay').classList.toggle('open');
  }
  window.closeModal = function() {
    document.getElementById('overlay').classList.remove('open');
    document.body.style.overflow = '';
  }
  window.openModal = function(type, id) {
    const modal = document.getElementById('modal');
    const mc = document.getElementById('mc-body');
    const overlay = document.getElementById('overlay');
    
    if (type === 'contract-detail') {
      const c = CONTRACTS.find(x => x.id == id);
      if (!c) return;
      document.getElementById('mh-title').innerText = `Contract — ${c.client_name}`;
      modal.style.maxWidth = '750px';
      mc.innerHTML = `
        <div style="padding:25px">
          <!-- Stats Grid -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:30px">
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Type</div>
              <div style="font-weight:700;font-size:16px">${c.contract_type === 'hourly' ? 'Hourly · $90/hr' : 'Fixed Price'}</div>
            </div>
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Hours This Week</div>
              <div style="font-weight:700;font-size:16px">8.5 hrs</div>
            </div>
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Total Earned</div>
              <div style="font-weight:700;font-size:16px">$${parseFloat(c.total_earned || 0).toLocaleString()}</div>
            </div>
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Weekly Limit</div>
              <div style="font-weight:700;font-size:16px">No limit</div>
            </div>
          </div>

          <!-- Log Time Section -->
          <div style="margin-bottom:30px">
            <h3 style="font-size:16px;margin-bottom:15px">Log Time / Submit Work</h3>
            <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;margin-bottom:15px">
              <div>
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:8px">${c.contract_type === 'hourly' ? 'Hours to Log' : 'Amount to Request ($)'}</label>
                <input type="number" id="work-amount" value="${c.contract_type === 'hourly' ? '' : c.amount}" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px">
              </div>
              <div>
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:8px">Work Description / Details</label>
                <input type="text" id="work-desc" placeholder="e.g. Mobile responsive screens" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px">
              </div>
            </div>
            <div style="margin-bottom:15px">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:8px">Attachments (Links or details)</label>
                <textarea id="work-attach" placeholder="Paste links to files or project details here..." style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;height:80px"></textarea>
            </div>
            <button class="btn btn-g" id="btn-log-work" style="padding:12px 30px;border-radius:10px;font-weight:700;width:100%" onclick="logWork(${c.id})">${c.contract_type === 'hourly' ? 'Log Hours' : 'Submit Work for Payment'}</button>
          </div>

          <!-- Footer Buttons -->
          <div style="display:flex;gap:15px;border-top:1px solid #eee;padding-top:25px">
            <button class="btn" style="flex:1;justify-content:center;border:1px solid #ddd;background:white;color:#333;font-weight:600;padding:12px;border-radius:8px" onclick="showPage('messages');closeModal()">Message Client</button>
            <button class="btn" style="flex:1;justify-content:center;border:1px solid #14a800;background:white;color:#14a800;font-weight:600;padding:12px;border-radius:8px" onclick="toast('Video Call', 'Starting call...')">📹 Video Call</button>
            <button class="btn" style="flex:1;justify-content:center;background:#f3f4f6;color:#333;font-weight:600;padding:12px;border-radius:8px" onclick="toast('Paused', 'Contract paused')">Pause Contract</button>
          </div>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else if (type === 'create-service') {
      document.getElementById('mh-title').innerText = 'Create New Service';
      modal.style.maxWidth = '600px';
      mc.innerHTML = `
        <div style="padding:25px">
          <div class="fg"><label>Service Title</label><input type="text" id="svc-title" placeholder="e.g. I will design a modern logo for your brand"></div>
          <div class="fg"><label>Description</label><textarea id="svc-desc" placeholder="Describe what is included in this service..."></textarea></div>
          <div class="g2">
            <div class="fg"><label>Price ($)</label><input type="number" id="svc-price" value="50"></div>
            <div class="fg"><label>Delivery Days</label><input type="number" id="svc-days" value="3"></div>
          </div>
          <div class="fg"><label>Image URL (Optional)</label><input type="text" id="svc-image" placeholder="https://example.com/image.jpg"></div>
          <button class="btn btn-g" style="width:100%;padding:14px;border-radius:10px;font-weight:700;margin-top:10px" onclick="submitNewService()">Create Project →</button>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else {
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  window.submitNewService = function() {
    const title = document.getElementById('svc-title').value;
    const desc = document.getElementById('svc-desc').value;
    const price = document.getElementById('svc-price').value;
    const days = document.getElementById('svc-days').value;
    const image = document.getElementById('svc-image').value;

    if (!title || !desc || !price) return toast('Required', 'Please fill all fields');

    const fd = new FormData();
    fd.append('title', title);
    fd.append('description', desc);
    fd.append('price', price);
    fd.append('delivery_days', days);
    fd.append('image_url', image);

    fetch(BASE_URL + 'actions/create_service.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Project created successfully!');
        closeModal();
        location.reload();
      } else {
        toast('Error', data.error);
      }
    })
    .catch(err => toast('Error', 'Failed to create service'));
  }

  window.logWork = function(contractId) {
    const amount = document.getElementById('work-amount').value;
    const desc = document.getElementById('work-desc').value;
    const attach = document.getElementById('work-attach').value;
    const btn = document.getElementById('btn-log-work');

    if (!desc) return toast('Error', 'Please provide work description');

    btn.disabled = true;
    btn.innerText = 'Submitting...';

    fetch(BASE_URL + 'freelancer/api/log-work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contract_id: contractId,
        amount: amount,
        description: desc,
        attachments: attach
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Work submitted successfully!');
        closeModal();
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => toast('Error', 'Submission failed'))
    .finally(() => {
      btn.disabled = false;
      btn.innerText = 'Log Hours';
    });
  }

  function renderReports() {
    const chart = document.getElementById('reports-chart');
    if (chart) {
      const data = [6.5, 8, 4.5, 7, 8.5, 0, 0];
      chart.innerHTML = data.map((h, i) => `<div class="chart-bar" style="height:${(h/10)*100}%;background:${h>0?'var(--g)':'var(--border)'};flex:1;border-radius:4px" onclick="toast('Time','${h} hours on ${['Mon','Tue','Wed','Thu','Fri','Sat','Sun'][i]}')"></div>`).join('');
    }
  }

  // Initialize
  document.addEventListener('DOMContentLoaded', () => {
    showPage('home');
  });
})();
</script>
</body>
</html>
