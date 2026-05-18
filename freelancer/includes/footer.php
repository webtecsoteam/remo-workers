<script>
  // Core Navigation (replaces header stub with full version including deferred renders)
  window.showPage = function(id) {
    if (!id) id = 'home';
    
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(p => p.classList.remove('active'));
    
    // Show target page
    const pg = document.getElementById('page-' + id);
    if (pg) {
      pg.classList.add('active');
      window.scrollTo(0, 0);
      if (window.history.pushState) {
        history.pushState(null, null, '#' + id);
      }
    }
    
    // Update sidebar active state
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
    const navEl = document.getElementById('nav-' + id);
    if (navEl) navEl.classList.add('active');

    // Update mobile bottom nav sync
    document.querySelectorAll('.mob-nav-item').forEach(i => i.classList.remove('active'));
    try {
      const mobItem = document.querySelector(`.mob-nav-item[onclick*="'${id}'"]`);
      if(mobItem) mobItem.classList.add('active');
    } catch(e) {
      console.warn("Mobile nav selector error:", e);
    }
    
    // Update title
    const titles = {
      'home': 'Dashboard', 'find-work': 'Find Work', 'proposals': 'My Proposals',
      'contracts': 'My Contracts', 'messages': 'Messages', 'earnings': 'Earnings',
      'catalog': 'My Services', 'profile': 'My Profile', 'reports': 'Payment Reports',
      'verification': 'ID Verification', 'connects': 'Connects Management'
    };
    const titleEl = document.getElementById('page-title');
    if (titleEl) titleEl.textContent = titles[id] || id;

    // Mobile sidebar close
    const sb = document.getElementById('main-sidebar');
    const ov = document.getElementById('mob-overlay');
    if (sb) sb.classList.remove('mob-open');
    if (ov) ov.classList.remove('open');

    // Re-render (guarded in case functions not yet defined)
    try {
      if ((id === 'find-work' || id === 'home') && typeof renderJobs === 'function') renderJobs();
      if (id === 'proposals' && typeof renderProposals === 'function') renderProposals();
      if (id === 'contracts' && typeof renderContracts === 'function') renderContracts();
      if (id === 'reports' && typeof renderReports === 'function') renderReports();
      if (id === 'profile' && typeof renderSuggestedSkills === 'function') renderSuggestedSkills();
      if (id === 'connects' && typeof loadConnectsPageData === 'function') loadConnectsPageData();
    } catch (e) { console.warn("Deferred render error:", e); }
  };

  const JOBS = <?php echo json_encode($allJobs ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const SERVER_TIME = "<?php echo date('Y-m-d H:i:s'); ?>";
  const SAVED_IDS = <?php echo json_encode(array_column($savedJobs ?? [], 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  let PROPOSALS = <?php echo json_encode($submittedProposals ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const CONTRACTS = <?php echo json_encode($allContracts ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const USER_CONNECTS = <?php echo (int)($user['connects'] ?? 0); ?>;
  const CONNECTS_PER_APPLICATION = <?php echo Auth::CONNECTS_PER_APPLICATION; ?>;
  const USER_EMAIL_VERIFIED = <?php echo Auth::isEmailVerified($user) ? 'true' : 'false'; ?>;
  const USER_ID_VERIFIED = <?php echo Auth::isIdentityVerified($user) ? 'true' : 'false'; ?>;
  const USER_HAS_PHOTO = <?php echo !empty($user['avatar_url']) ? 'true' : 'false'; ?>;
  let userConnectsBalance = USER_CONNECTS;
  // Global showPage for legacy onclicks (redundant now but keeping it for safety at end of script if needed)
  window.showPage = window.showPage;

  // Define all other functions here... (I will just remove the IIFE wrappers)

  window.showEarningsInfo = function(type) {
    const panel = document.getElementById('earnings-info-panel');
    if (!panel) return;
    
    const info = {
      'wip': '<strong>Work in Progress</strong>: These are hours you have logged during the current week that have not yet been billed to the client. This amount will move to "In Review" after the week ends (Sunday midnight UTC).',
      'review': '<strong>In Review</strong>: This includes hours from the previous week or completed milestones that the client is currently reviewing. Clients have 5 days to dispute hourly work.',
      'pending': '<strong>Processing</strong>: These are funds that have been approved by the client but are held for a standard 5-day security period before becoming available for withdrawal.',
      'available': '<strong>Available</strong>: This is your balance ready to be withdrawn to your preferred payment method.'
    };
    
    panel.style.display = 'block';
    panel.innerHTML = info[type] || '';
  }

  window.initiateWithdrawal = function(amount) {
    if (amount <= 0) return toast('Error', 'No funds available to withdraw');
    const method = document.getElementById('withdraw-method').value;
    
    if (confirm(`Are you sure you want to withdraw $${parseFloat(amount).toFixed(2)} to ${method}?`)) {
      toast('Success', 'Withdrawal initiated successfully!');
    }
  }

  window.buyConnects = function(amount, price) {
    if (!confirm(`Buy ${amount} Connects for $${price.toFixed(2)} using your available balance?`)) return;
    
    toast('Processing...', 'Purchasing connects');
    
    fetch(BASE_URL + 'freelancer/api/buy-connects.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ amount: amount, price: price })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success! 🎉', data.message);
        
        // Dynamically update connects displays
        const sbVal = document.getElementById('sb-connects-val');
        if (sbVal) sbVal.textContent = data.new_connects;
        
        const navConn = document.getElementById('nav-connects');
        if (navConn) navConn.innerHTML = `<span class="sb-ico">🔗</span>Connects (${data.new_connects})`;
        
        // Re-open the connects modal to refresh connects data and recent activity list in real-time
        openModal('connects');
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => {
      toast('Error', 'Purchase request failed.');
    });
  }

  window.togglePwVis = function(id, el) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
      input.type = 'text';
      el.innerText = '👓';
    } else {
      input.type = 'password';
      el.innerText = '👁';
    }
  }

  window.submitChangePassword = function() {
    const current = document.getElementById('pw-current').value;
    const newPw = document.getElementById('pw-new').value;
    const confirmPw = document.getElementById('pw-confirm').value;

    if (!current || !newPw || !confirmPw) return toast('Required', 'Please fill all fields');
    if (newPw !== confirmPw) return toast('Error', 'New passwords do not match');
    if (newPw.length < 8) return toast('Short', 'Password must be at least 8 characters');

    const fd = new FormData();
    fd.append('current_password', current);
    fd.append('new_password', newPw);

    fetch(BASE_URL + 'actions/change_password.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Password changed successfully!');
        closeModal();
      } else {
        toast('Error', data.error);
      }
    })
    .catch(err => toast('Error', 'Failed to update password'));
  }

  window.toggleAvailability = function() {
    const textEl = document.getElementById('avail-text');
    const dotEl = document.getElementById('avail-dot');
    const current = textEl.innerText;
    
    let next = 'available';
    let label = 'Available for Work';
    let dot = '🟢';
    
    if (current === 'Available for Work') {
      next = 'limited';
      label = 'Limited Availability';
      dot = '🟡';
    } else if (current === 'Limited Availability') {
      next = 'unavailable';
      label = 'Not Available';
      dot = '🔴';
    }
    
    const fd = new FormData();
    fd.append('availability', next);
    
    fetch(BASE_URL + 'actions/update_availability.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        textEl.innerText = label;
        dotEl.innerText = dot;
        toast('Availability', 'Set to ' + label);
      }
    })
    .catch(err => toast('Error', 'Failed to update status'));
  }

  function renderContracts(filter = 'Active') {
    const list = document.getElementById('contracts-list');
    if (!list) return;

    let filtered = [...CONTRACTS];
    const cleanFilter = filter.split('(')[0].trim();
    
    if (cleanFilter === 'Active') {
      filtered = CONTRACTS.filter(c => (c.status || '').toLowerCase() === 'active');
    } else if (cleanFilter === 'Completed') {
      filtered = CONTRACTS.filter(c => (c.status || '').toLowerCase() === 'completed');
    } else if (cleanFilter === 'Paused') {
      filtered = CONTRACTS.filter(c => (c.status || '').toLowerCase() === 'paused');
    } else if (cleanFilter === 'Cancelled') {
      filtered = CONTRACTS.filter(c => (c.status || '').toLowerCase() === 'cancelled');
    } else if (cleanFilter === 'All') {
      filtered = [...CONTRACTS];
    }

    const isMob = window.innerWidth <= 900;
    if (filtered.length === 0) {
      list.innerHTML = `<div style="text-align:center;padding:60px;color:var(--muted)">No ${cleanFilter.toLowerCase()} contracts found.</div>`;
      return;
    }

    list.innerHTML = filtered.map(c => {
      const typeLabel = c.contract_type === 'hourly' ? 'Hourly' : 'Fixed';
      const typeColor = c.contract_type === 'hourly' ? '#e0f2fe' : '#f3e8ff';
      const typeText = c.contract_type === 'hourly' ? '#0369a1' : '#7e22ce';
      
      let progressPercent = 0;
      if (c.status === 'completed') {
        progressPercent = 100;
      } else {
        const budget = parseFloat(c.amount || 0);
        const earned = parseFloat(c.total_earned || 0);
        if (budget > 0) {
          progressPercent = Math.min(100, Math.round((earned / budget) * 100));
        } else {
          progressPercent = c.contract_type === 'hourly' ? 65 : 10;
        }
      }
      
      const contractId = c.id || c.contract_id;
      if (isMob) {
        return `
          <div class="card" style="padding:16px;margin:10px;border-radius:12px;margin-bottom:12px" onclick="openModal('contract-detail', ${contractId})">
            <div style="display:flex;justify-content:space-between;margin-bottom:12px">
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:50%;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px">${(c.client_name || 'C').charAt(0)}</div>
                <div>
                  <div style="font-weight:700;font-size:14px">${c.client_name}</div>
                  <div style="font-size:11px;color:var(--muted2)">Started ${new Date(c.created_at).toLocaleDateString()}</div>
                </div>
              </div>
              <span style="background:${typeColor};color:${typeText};padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;align-self:start">${typeLabel}</span>
            </div>
            <div style="font-weight:700;font-size:15px;margin-bottom:12px;color:var(--dark)">${c.job_title}</div>
            <div style="display:flex;justify-content:space-between;align-items:flex-end">
              <div style="flex:1;padding-right:20px">
                <div style="font-size:10px;color:#999;margin-bottom:4px">Progress: ${progressPercent}%</div>
                <div style="width:100%;height:5px;background:#f3f4f6;border-radius:3px">
                  <div style="width:${progressPercent}%;height:100%;background:#14a800;border-radius:3px"></div>
                </div>
              </div>
              <div style="text-align:right">
                <div style="font-weight:700;font-size:16px">$${parseFloat(c.total_earned || 0).toLocaleString()}</div>
                <div style="font-size:10px;color:var(--muted2)">Earned</div>
              </div>
            </div>
          </div>
        `;
      }
      return `
        <div class="contract-list-item" style="display:grid;grid-template-columns:1.2fr 1.2fr 0.6fr 0.8fr 1fr 0.6fr 0.8fr;padding:16px 20px;border-bottom:1px solid #eee;align-items:center;font-size:13.5px;cursor:pointer" onclick="openModal('contract-detail', ${contractId})">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:30px;height:30px;border-radius:50%;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px">${(c.client_name || 'C').charAt(0)}</div>
            <div style="font-weight:600;color:#333">${c.client_name}</div>
          </div>
          <div style="font-weight:600;color:#333;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:10px">${c.job_title}</div>
          <div><span style="background:${typeColor};color:${typeText};padding:3px 8px;border-radius:4px;font-size:11px;font-weight:700">${typeLabel}</span></div>
          <div style="font-weight:700;color:#333">$${parseFloat(c.total_earned || 0).toLocaleString()}</div>
          <div style="padding-right:20px">
            <div style="font-size:10px;color:#999;margin-bottom:4px">${c.status === 'completed' ? '100%' : progressPercent + '%'}</div>
            <div style="width:100%;height:5px;background:#f3f4f6;border-radius:3px"><div style="width:${progressPercent}%;height:100%;background:#14a800;border-radius:3px"></div></div>
          </div>
          <div style="color:#666;font-size:12px">${new Date(c.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric'})}</div>
          <div><button class="btn btn-w btn-sm" style="padding:4px 10px;font-size:11px">Manage</button></div>
        </div>`;
    }).join('');
  }

  function renderProposals(filter = 'Active') {
    const list = document.getElementById('proposals-list');
    if (!list) return;
    
    const title = document.getElementById('proposals-list-title');
    if (title) title.textContent = filter + ' Proposals';

    let filtered = [...PROPOSALS];
    if (filter === 'Active') {
      filtered = PROPOSALS.filter(p => p.status === 'pending' || p.status === 'accepted' || p.status === 'interviewing');
    } else if (filter === 'Submitted') {
      filtered = PROPOSALS.filter(p => p.status === 'pending');
    } else if (filter === 'Archived') {
      filtered = PROPOSALS.filter(p => p.status === 'rejected' || p.status === 'withdrawn');
    }

    const isMob = window.innerWidth <= 900;
    if (filtered.length === 0) {
      list.innerHTML = `<div style="text-align:center;padding:60px;color:var(--muted)">
        <div style="font-size:40px;margin-bottom:15px">📄</div>
        <div>No ${filter.toLowerCase()} proposals found.</div>
      </div>`;
      return;
    }

    list.innerHTML = filtered.map(p => {
      let displayStatus = p.status;
      let statusClass = p.status === 'accepted' ? 'b-green' : (p.status === 'rejected' ? 'b-red' : 'b-purple');
      
      if (p.job_status === 'closed' && p.status !== 'accepted') {
        displayStatus = 'Job Closed';
        statusClass = 'b-red';
      }

      if (isMob) {
        return `
          <div class="card" style="padding:16px;margin:10px;border-radius:12px;margin-bottom:10px" onclick="toast('Details','Viewing ${p.job_title}')">
            <div style="font-weight:700;font-size:15px;margin-bottom:6px;color:var(--dark)">${p.job_title}</div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:12px">Submitted ${new Date(p.created_at).toLocaleDateString()}</div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-weight:700;font-size:15px;color:var(--dark)">$${parseFloat(p.bid_amount).toLocaleString()}</div>
                <div style="font-size:10px;color:var(--muted2)">Proposed Bid</div>
              </div>
              <span class="badge ${statusClass}" style="padding:6px 12px;border-radius:6px;text-transform:capitalize">${displayStatus}</span>
            </div>
          </div>`;
      }
      return `
        <div class="contract-row" style="padding:22px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;align-items:center;gap:15px;justify-content:space-between">
          <div style="flex:1;min-width:260px">
            <div style="font-weight:700;font-size:16px;margin-bottom:6px;color:var(--dark)">${p.job_title}</div>
            <div style="display:flex;gap:15px;font-size:12px;color:var(--muted)">
              <span>Submitted ${new Date(p.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'})}</span>
              <span class="hide-mob">${p.estimated_days || '7'} days delivery</span>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:20px;flex-shrink:0">
            <div style="text-align:right">
              <div style="font-weight:700;font-size:15px;color:var(--dark)">$${parseFloat(p.bid_amount).toLocaleString()}</div>
              <div style="font-size:11px;color:var(--muted2)">Proposed Bid</div>
            </div>
            <div style="text-align:right;min-width:100px">
              <span class="badge ${statusClass}" style="padding:6px 12px;border-radius:6px;text-transform:capitalize">${displayStatus}</span>
            </div>
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
        const matchPercent = getMatchPercentage(j);
        return `
        <div class="job-row" onclick="openJobDetail(${j.id})" style="display:grid;grid-template-columns:1fr 180px;padding:20px;border-radius:12px;margin-bottom:15px;align-items:start">
          <div style="padding-right:20px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
              <div style="font-weight:700;font-size:16px;color:var(--dark)">${j.title}</div>
              <span style="background:#e8f5e3;color:#14a800;font-size:11px;padding:2px 8px;border-radius:12px;font-weight:600">${matchPercent}% match</span>
            </div>
            <div style="font-size:13.5px;color:var(--muted);margin-bottom:12px">${(j.description || '').substring(0, 160)}...</div>
            
            <div style="display:flex;align-items:center;gap:15px;font-size:12px;color:var(--muted2);margin-bottom:12px;flex-wrap:wrap">
              ${isClientVerified(j) ? '<span style="display:flex;align-items:center;gap:4px">Payment verified</span>' : ''}
              <span style="display:flex;align-items:center;gap:4px">★ ${clientHiresLabel(j)}</span>
              <span>${clientLocation(j)}</span>
              <span style="background:#fefce8;color:#854d0e;padding:2px 8px;border-radius:6px;font-weight:600">💰 ${formatClientSpent(j.client_total_spent)} spent</span>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <span style="background:#ede9fe;color:#5b21b6;font-size:11.5px;padding:3px 10px;border-radius:6px;font-weight:600">
                ${j.budget_type === 'hourly' 
                  ? (j.min_hourly_rate && j.max_hourly_rate 
                      ? `Hourly: $${parseFloat(j.min_hourly_rate).toLocaleString()} - $${parseFloat(j.max_hourly_rate).toLocaleString()}`
                      : `Hourly: $${parseFloat(j.budget || 0).toLocaleString()}`
                    )
                  : (j.budget_type === 'monthly'
                      ? `Monthly: $${parseFloat(j.budget || 0).toLocaleString()}`
                      : `Fixed Price: $${parseFloat(j.budget || 0).toLocaleString()}`
                    )
                }
              </span>
              <span style="background:#f3f4f6;color:var(--dark3);font-size:11.5px;padding:3px 10px;border-radius:6px;font-weight:600">${parseInt(j.proposal_count, 10) || 0} proposal${(parseInt(j.proposal_count, 10) || 0) === 1 ? '' : 's'}</span>
            </div>
          </div>

          <div style="text-align:right;border-left:1px solid var(--border);padding-left:20px">
            <button class="btn btn-g" style="width:100%;margin-bottom:12px" onclick="event.stopPropagation();openApplyModal(${j.id})">Apply Now</button>
            <div style="color:var(--muted2);font-size:12px;margin-bottom:4px;display:flex;align-items:center;justify-content:flex-end;gap:4px">
              <span style="color:#f59e0b">⚡</span> ${CONNECTS_PER_APPLICATION} Connects
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

  function getMatchPercentage(job) {
    if (!job || !job.skills_required) return 100;
    let skills = [];
    try {
      if (typeof job.skills_required === 'string') {
        if (job.skills_required.startsWith('[')) {
          skills = JSON.parse(job.skills_required);
        } else {
          skills = job.skills_required.split(',').map(s => s.trim());
        }
      } else if (Array.isArray(job.skills_required)) {
        skills = job.skills_required;
      }
    } catch(e) {
      skills = String(job.skills_required).split(',').map(s => s.trim());
    }
    
    if (skills.length === 0) return 100;
    
    let matchCount = 0;
    skills.forEach(skill => {
      const normalized = skill.toLowerCase();
      for (let uSkill of selectedSkills) {
        if (String(uSkill).toLowerCase() === normalized) {
          matchCount++;
          break;
        }
      }
    });
    
    if (matchCount > 0) {
      return Math.min(100, 75 + Math.round((matchCount / skills.length) * 25));
    }
    
    const seededRandom = (job.id * 17) % 25;
    return 70 + seededRandom;
  }

  function timeAgo(date) {
    if(!date) return "Just now";
    
    // Use server time as reference to avoid timezone mismatches
    const now = new Date(SERVER_TIME.replace(' ', 'T'));
    const d = new Date(date.replace(' ', 'T'));
    const seconds = Math.floor((now - d) / 1000);
    
    if (seconds < 60) return "Just now";
    if (seconds < 3600) return Math.floor(seconds / 60) + "m ago";
    if (seconds < 86400) return Math.floor(seconds / 3600) + "h ago";
    return Math.floor(seconds / 86400) + "d ago";
  }

  function formatClientSpent(amount) {
    const n = parseFloat(amount) || 0;
    return '$' + n.toLocaleString('en-US', { maximumFractionDigits: 0, minimumFractionDigits: 0 });
  }

  function clientLocation(job) {
    return job.client_country || job.location || 'Remote';
  }

  function clientHiresLabel(job) {
    const rating = parseFloat(job.client_rating) || 0.0;
    const hires = parseInt(job.client_hires, 10) || 0;
    const ratingText = rating > 0 ? rating.toFixed(1) : 'No reviews';
    return `${ratingText} (${hires} ${hires === 1 ? 'hire' : 'hires'})`;
  }

  function isClientVerified(job) {
    return job.client_verified == 1 || job.client_verified === true || job.client_verified === '1';
  }

  function updateConnectsDisplay(balance) {
    userConnectsBalance = balance;
    const el = document.getElementById('sb-connects-val');
    if (el) el.textContent = balance;
    document.querySelectorAll('#nav-connects').forEach(function(nav) {
      nav.innerHTML = '<span class="sb-ico">🔗</span>Connects (' + balance + ')';
    });
  }

  function getApplyBlockReason() {
    if (!USER_EMAIL_VERIFIED) {
      return {
        title: 'Verify your email',
        message: 'You must verify your email address before applying to jobs.',
        action: function() { requestEmailVerification(); }
      };
    }
    if (!USER_ID_VERIFIED) {
      return {
        title: 'Verify your identity',
        message: 'Complete identity verification before applying to jobs.',
        action: function() { showPage('verification'); }
      };
    }
    if (!USER_HAS_PHOTO) {
      return {
        title: 'Profile photo required',
        message: 'Please upload a profile photo before applying to jobs.',
        action: function() { showPage('profile'); }
      };
    }
    if (userConnectsBalance < CONNECTS_PER_APPLICATION) {
      return {
        title: 'Not enough Connects',
        message: 'Redirecting you to the Connects page. You need ' + CONNECTS_PER_APPLICATION + ' Connects but only have ' + userConnectsBalance + '.',
        action: function() { showPage('connects'); }
      };
    }
    return null;
  }

  window.requestEmailVerification = function() {
    fetch(BASE_URL + 'verify-email', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.success) {
        toast('Email Sent! 📧', data.message || 'Please check your inbox to verify.');
      } else if (data.already) {
        toast('Email verified', data.message);
        location.reload();
      } else {
        toast('Error', data.error || data.message || 'Could not send verification link.');
      }
    })
    .catch(function() {
      toast('Error', 'Could not request verification link.');
    });
  };

  window.openJobDetail = function(id) {
    const job = JOBS.find(j => j.id == id);
    if (!job) return;
    const matchPercent = getMatchPercentage(job);
    const isSaved = SAVED_IDS.includes(parseInt(job.id));
    const saveBtnText = isSaved ? 'Saved' : 'Save Job';
    const saveBtnStyle = isSaved ? 'background-color:#10b981;color:#fff;border-color:#10b981;' : '';
    
    document.getElementById('mh-title').textContent = 'Job Details';
    document.getElementById('mc-body').innerHTML = `
      <div style="background:#f9fafb;min-height:400px;padding-bottom:100px">
        <div style="background:white;padding:30px;border-bottom:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
            <h2 style="font-size:24px;color:var(--dark);font-weight:700;line-height:1.3;flex:1">${job.title}</h2>
            <span style="background:#e8f5e3;color:#14a800;font-size:12px;padding:4px 12px;border-radius:12px;font-weight:600;margin-left:20px;white-space:nowrap">${matchPercent}% match</span>
          </div>
          
          <div style="display:flex;flex-wrap:wrap;gap:20px;color:var(--muted2);font-size:13.5px">
            <span style="color:var(--g);font-weight:600">Posted ${timeAgo(job.created_at)}</span>
            <span>📍 ${clientLocation(job)}</span>
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
                <span style="font-weight:700">${CONNECTS_PER_APPLICATION} Connects</span>
              </div>
              <div style="display:flex;justify-content:space-between">
                <span style="font-weight:600;color:var(--dark)">Your Connects</span>
                <span style="color:var(--g);font-weight:700">${userConnectsBalance} Connects</span>
              </div>
            </div>
          </div>

          <div style="padding:30px;background:#f9fafb">
            <button class="btn btn-g" style="width:100%;padding:14px;font-size:15px;font-weight:700;margin-bottom:12px;border-radius:8px" onclick="event.stopPropagation();openApplyModal(${job.id})">Apply Now</button>
            <button class="btn btn-w" style="width:100%;padding:12px;font-size:14px;margin-bottom:25px;border:1px solid var(--border);${saveBtnStyle}" onclick="toggleSaveJob(${job.id}, this)">${saveBtnText}</button>

            <div style="margin-bottom:25px">
              <h4 style="font-size:14px;margin-bottom:15px;font-weight:700">About the Client</h4>
              ${isClientVerified(job) ? '<div style="font-size:13px;color:var(--muted2);margin-bottom:10px">Payment verified ✅</div>' : ''}
              <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">★ ${clientHiresLabel(job)}</div>
              <div style="font-size:13px;color:var(--muted2);margin-bottom:10px">${formatClientSpent(job.client_total_spent)} total spent</div>
              <div style="font-size:13px;color:var(--muted2)">📍 ${clientLocation(job)}</div>
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

    const block = getApplyBlockReason();
    if (block) {
      toast(block.title, block.message);
      if (block.action) block.action();
      return;
    }
    
    const isHourly = job.budget_type === 'hourly';
    const isMonthly = job.budget_type === 'monthly';
    
    let budgetDisplay = '';
    let defaultRate = 0;
    if (isHourly) {
      if (job.min_hourly_rate && job.max_hourly_rate) {
        budgetDisplay = `Hourly · $${parseFloat(job.min_hourly_rate).toLocaleString()}/hr - $${parseFloat(job.max_hourly_rate).toLocaleString()}/hr`;
        defaultRate = parseFloat(job.min_hourly_rate);
      } else {
        budgetDisplay = `Hourly · $${parseFloat(job.budget || 0).toLocaleString()}/hr`;
        defaultRate = parseFloat(job.budget || 0);
      }
    } else if (isMonthly) {
      budgetDisplay = `Monthly · $${parseFloat(job.budget || 0).toLocaleString()}/month`;
      defaultRate = parseFloat(job.budget || 0);
    } else {
      budgetDisplay = `Fixed Price · $${parseFloat(job.budget || 0).toLocaleString()}`;
      defaultRate = parseFloat(job.budget || 0);
    }

    let labelText = 'Your Proposed Rate ($)';
    if (isHourly) {
      labelText = 'Your Proposed Hourly Rate ($/hr)';
    } else if (isMonthly) {
      labelText = 'Your Proposed Monthly Rate ($/mo)';
    }

    const showMilestones = !isHourly && !isMonthly;
    
    document.getElementById('mh-title').textContent = 'Submit Proposal — ' + job.title;
    document.getElementById('mc-body').innerHTML = `
      <div style="padding:25px 25px 100px 25px">
        <div style="background:#f0f7ef;color:#14a800;padding:12px 18px;border-radius:8px;font-size:13.5px;margin-bottom:25px;border:1px solid #d4e8d4">
          ${budgetDisplay} · ${CONNECTS_PER_APPLICATION} Connects required · You have ${userConnectsBalance}
        </div>

        <div style="margin-bottom:20px">
          <label style="display:block;font-weight:700;margin-bottom:8px;font-size:14px">${labelText}</label>
          <input type="number" id="prop-rate" value="${defaultRate || 0}" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px" oninput="updateMilestoneTotal()">
        </div>

        <div id="milestones-section" style="margin-bottom:25px;border:1px solid var(--border);padding:15px;border-radius:12px;background:#fafafa; display: ${showMilestones ? 'block' : 'none'}">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <label style="font-weight:700;font-size:14px">Milestones</label>
            <button class="btn btn-w btn-sm" onclick="addMilestoneRow()" style="font-size:12px;padding:4px 10px">+ Add Milestone</button>
          </div>
          <div id="milestones-list-container">
            <div class="milestone-row" style="display:grid;grid-template-columns:1fr 100px 30px;gap:10px;margin-bottom:10px">
              <input type="text" placeholder="Description (e.g. Initial Draft)" class="ms-desc" style="padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px">
              <input type="number" placeholder="Amount" class="ms-amount" value="${defaultRate || 0}" style="padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px" oninput="updateMilestoneTotal()">
              <button onclick="this.parentElement.remove();updateMilestoneTotal()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:16px">×</button>
            </div>
          </div>
          <div style="font-size:12px;color:var(--muted2);margin-top:10px;display:flex;justify-content:space-between">
            <span>Total Milestone Amount: <strong id="ms-total-display">$${defaultRate || 0}</strong></span>
          </div>
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

  window.addMilestoneRow = function() {
    const container = document.getElementById('milestones-list-container');
    const row = document.createElement('div');
    row.className = 'milestone-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr 100px 30px;gap:10px;margin-bottom:10px';
    row.innerHTML = `
      <input type="text" placeholder="Description" class="ms-desc" style="padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px">
      <input type="number" placeholder="Amount" class="ms-amount" style="padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px" oninput="updateMilestoneTotal()">
      <button onclick="this.parentElement.remove();updateMilestoneTotal()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:16px">×</button>
    `;
    container.appendChild(row);
  }

  window.updateMilestoneTotal = function() {
    const propRateInput = document.getElementById('prop-rate');
    const milestonesSection = document.getElementById('milestones-section');
    const isHourly = milestonesSection && milestonesSection.style.display === 'none';
    
    if (isHourly) {
      return;
    }
    
    const amounts = document.querySelectorAll('.ms-amount');
    let total = 0;
    amounts.forEach(a => total += parseFloat(a.value || 0));
    document.getElementById('ms-total-display').textContent = '$' + total.toLocaleString();
    
    if (propRateInput) {
      propRateInput.value = total;
    }
  }


  window.submitProposalForm = function(jobId) {
    const rate = document.getElementById('prop-rate').value;
    const days = document.getElementById('prop-days').value;
    const letter = document.getElementById('prop-letter').value;
    const attach = document.getElementById('prop-attach').value;

    if (!letter) return toast('Error', 'Please write a cover letter');

    const job = JOBS.find(j => j.id == jobId);
    const isHourly = job && job.budget_type === 'hourly';

    // Collect milestones (only if not hourly)
    const milestones = [];
    let msTotal = 0;
    
    if (!isHourly) {
      const rows = document.querySelectorAll('.milestone-row');
      rows.forEach(row => {
        const desc = row.querySelector('.ms-desc').value.trim();
        const amt = parseFloat(row.querySelector('.ms-amount').value || 0);
        if (desc && amt > 0) {
          milestones.push({ description: desc, amount: amt });
          msTotal += amt;
        }
      });

      if (milestones.length === 0) {
        return toast('Error', 'Please add at least one milestone');
      }
    }

    const payload = {
        job_id: jobId,
        bid_amount: isHourly ? parseFloat(rate || 0) : msTotal,
        estimated_days: days,
        cover_letter: letter,
        attachments: attach,
        milestones: milestones
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
        if (typeof data.new_connects === 'number') {
          updateConnectsDisplay(data.new_connects);
        }
        // Add new proposal to local list so it shows up immediately
        const job = JOBS.find(j => j.id == jobId);
        PROPOSALS.unshift({
          id: data.id,
          job_id: jobId,
          job_title: job ? job.title : 'Job Post',
          bid_amount: rate,
          estimated_days: days,
          status: 'pending',
          created_at: new Date().toISOString()
        });
        
        toast('Success', data.message);
        closeModal();
        showPage('proposals');
      } else {
        toast('Error', data.message || data.error);
        if (data.code === 'email_unverified') requestEmailVerification();
        if (data.code === 'identity_unverified') showPage('verification');
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
        
        // Find both card save button and modal save button to update both in sync!
        const cardBtn = document.querySelector(`.save-btn[onclick*="toggleSaveJob(${id},"]`);
        const modalBtn = document.querySelector(`button[onclick*="toggleSaveJob(${id},"][class*="btn-w"]`);

        if (data.action === 'unsaved') {
          if (idx > -1) SAVED_IDS.splice(idx, 1);
          
          if (cardBtn) {
            cardBtn.textContent = '☆';
            cardBtn.classList.remove('active');
          }
          if (modalBtn) {
            modalBtn.textContent = 'Save Job';
            modalBtn.style.backgroundColor = '#fff';
            modalBtn.style.color = '#374151';
            modalBtn.style.borderColor = 'var(--border)';
          }
          if (btn && btn !== cardBtn && btn !== modalBtn) {
            if (btn.classList.contains('btn-w')) {
              btn.textContent = 'Save Job';
              btn.style.backgroundColor = '#fff';
              btn.style.color = '#374151';
              btn.style.borderColor = 'var(--border)';
            } else {
              btn.textContent = '☆';
              btn.classList.remove('active');
            }
          }
          
          toast('Saved', 'Job removed from favorites');
        } else {
          if (idx === -1) SAVED_IDS.push(parseInt(id));
          
          if (cardBtn) {
            cardBtn.textContent = '★';
            cardBtn.classList.add('active');
          }
          if (modalBtn) {
            modalBtn.textContent = 'Saved';
            modalBtn.style.backgroundColor = '#10b981';
            modalBtn.style.color = '#fff';
            modalBtn.style.borderColor = '#10b981';
          }
          if (btn && btn !== cardBtn && btn !== modalBtn) {
            if (btn.classList.contains('btn-w')) {
              btn.textContent = 'Saved';
              btn.style.backgroundColor = '#10b981';
              btn.style.color = '#fff';
              btn.style.borderColor = '#10b981';
            } else {
              btn.textContent = '★';
              btn.classList.add('active');
            }
          }
          
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

  // window.showPage already defined at top
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
  window.openModal = function(type, data) {
    const modal = document.getElementById('modal');
    const mc = document.getElementById('mc-body');
    const overlay = document.getElementById('overlay');
    
    if (type === 'contract-detail') {
      const c = CONTRACTS.find(ct => ct.id == data);
      if (!c) return;
      window.currentContractId = c.id;

      document.getElementById('mh-title').innerText = c.job_title;
      modal.style.maxWidth = '750px';
      mc.innerHTML = `
        <div style="padding:25px">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px">
            <div>
              <div style="font-size:13px;color:#999;margin-bottom:4px">Client</div>
              <div style="font-weight:700;font-size:18px">${c.client_name}</div>
            </div>
            <div style="text-align:right">
              <div style="font-size:13px;color:#999;margin-bottom:4px">Total Budget</div>
              <div style="font-weight:700;font-size:18px">$${parseFloat(c.amount).toLocaleString()}</div>
            </div>
          </div>

          <!-- Stats Grid -->
          <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:15px;margin-bottom:30px">
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Type</div>
              <div style="font-weight:700;font-size:14px">${c.contract_type === 'hourly' ? 'Hourly' : 'Fixed Price'}</div>
            </div>
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Total Earned</div>
              <div style="font-weight:700;font-size:14px;color:#14a800">$${parseFloat(c.total_earned || 0).toLocaleString()}</div>
            </div>
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Pending</div>
              <div style="font-weight:700;font-size:14px">$${parseFloat(c.pending_earned || 0).toLocaleString()}</div>
            </div>
            <div style="border:1px solid #eee;padding:15px;border-radius:12px">
              <div style="font-size:12px;color:#999;margin-bottom:4px">Status</div>
              <div style="font-weight:700;font-size:14px">${c.status.charAt(0).toUpperCase() + c.status.slice(1)}</div>
            </div>
          </div>

          ${c.status === 'completed' ? `
            <div id="contract-feedback-section" style="border:1.5px solid #bfdbfe;background:#f0f7ff;padding:20px;border-radius:12px;margin-bottom:30px">
              <h3 style="font-size:15px;margin:0 0 10px 0;font-weight:700;color:#1e40af;display:flex;align-items:center;gap:6px">
                📝 Double-Sided Reviews & Feedback
              </h3>
              <div id="freelancer-review-status-box">
                <div style="text-align:center;padding:10px;font-size:13px;color:#666">Loading feedback status...</div>
              </div>
            </div>
          ` : ''}

          <!-- Milestones Section (for Fixed Price) -->
          <div id="milestones-section-detail" style="margin-bottom:30px; display: ${c.contract_type === 'fixed' ? 'block' : 'none'}">
            <h3 style="font-size:16px;margin-bottom:15px;font-weight:700">Contract Milestones</h3>
            <div id="milestone-list-detail">
              ${(c.milestones || []).map((ms, i) => `
                <div style="padding:15px; border:1px solid #eee; border-radius:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center">
                  <div>
                    <div style="font-size:14px; font-weight:600">${ms.description}</div>
                    <div style="font-size:12px; color:#999">$${parseFloat(ms.amount).toLocaleString()} · ${ms.status.charAt(0).toUpperCase() + ms.status.slice(1)}</div>
                  </div>
                  <div>
                    ${ms.status === 'pending' ? `
                      <span class="badge" style="background:#f3f4f6; color:#4b5563; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600">Awaiting Funding</span>
                    ` : (ms.status === 'funded' ? (
                      (c.status === 'completed' || c.status === 'cancelled') ? `
                        <span class="badge" style="background:#e0f2fe; color:#0369a1; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600">Funded</span>
                      ` : `
                        <button class="btn btn-g btn-sm" onclick="requestMilestone(${ms.id}, this)">Submit Work</button>
                      `
                    ) : (ms.status === 'requested' ? `
                      <span class="badge" style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:4px; font-size:11px">Under Review</span>
                    ` : `
                      <span class="badge" style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:4px; font-size:11px">Paid</span>
                    `))}
                  </div>
                </div>
              `).join('')}
              ${(!c.milestones || c.milestones.length === 0) ? `
                <div style="color:#666; font-size:14px; text-align:center; padding:25px; border:1.5px dashed #ccc; border-radius:12px; background:#fff">
                  <div style="margin-bottom:12px; font-weight:600; color:#374151">No milestones defined for this contract.</div>
                  ${(c.status === 'completed' || c.status === 'cancelled') ? '' : `
                    <button class="btn btn-g" style="margin: 0 auto" onclick="openDirectSubmissionModal(${c.id}, ${parseFloat(c.amount)})">Submit Work & Request Payment</button>
                  `}
                </div>
              ` : ''}
            </div>
            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; font-size:12px; color:#1e40af; margin-top:15px; line-height:1.5">
              💡 <strong>How Escrow Milestones Work:</strong><br>
              1. The client must first fund the milestone (deposit the amount to escrow) from their Client dashboard.<br>
              2. Once funded, the "Awaiting Funding" badge will turn into a green <strong>"Submit Work"</strong> button, allowing you to submit your work and request payment.<br>
              3. After you submit, the client will approve it and release the funds to your balance!
            </div>
          </div>

          <!-- Log Time Section (for Hourly) -->
          <div id="hourly-log-section" style="margin-bottom:30px; display: ${c.contract_type === 'hourly' ? 'block' : 'none'}">
            ${(c.status === 'completed' || c.status === 'cancelled') ? `
              <div style="background:#f3f4f6; border:1px solid #e5e7eb; border-radius:8px; padding:15px; font-size:13px; color:#4b5563; text-align:center">
                🔒 This contract is ${c.status}. Logging time is disabled.
              </div>
            ` : `
              <h3 style="font-size:16px;margin-bottom:15px;font-weight:700;color:var(--dark)">Work Session Logger</h3>
            
            <div style="display:flex;border-bottom:1.5px solid var(--border);margin-bottom:20px;gap:20px">
              <div id="tab-tracker" style="font-weight:700;font-size:13px;color:var(--g);border-bottom:2.5px solid var(--g);padding-bottom:10px;cursor:pointer" onclick="switchHourlyTab('tracker')">⏱️ Start Tracker</div>
              <div id="tab-manual" style="font-weight:600;font-size:13px;color:var(--muted);padding-bottom:10px;cursor:pointer" onclick="switchHourlyTab('manual')">✍️ Manual Log</div>
            </div>

            <!-- Timer Panel -->
            <div id="panel-tracker" style="display:block;background:#f9fafb;border:1px solid #eee;border-radius:12px;padding:25px 20px;text-align:center;margin-bottom:20px">
              <div id="timer-display" style="font-family:'Courier New', monospace;font-size:48px;font-weight:800;color:var(--dark);margin-bottom:15px;letter-spacing:1px">00:00:00</div>
              <div style="max-width:400px;margin:0 auto 15px auto">
                <input type="text" id="tracker-desc" placeholder="What are you working on right now? (e.g. Designing dashboard)" style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;text-align:center">
              </div>
              <button id="btn-timer-toggle" class="btn btn-g" style="padding:12px 30px;font-weight:800;font-size:14px;border-radius:8px;margin:0 auto" onclick="toggleTimer(window.currentContractId)">Start Tracker</button>
            </div>

            <!-- Manual Panel -->
            <div id="panel-manual" style="display:none;background:#f9fafb;border:1px solid #eee;border-radius:12px;padding:20px;margin-bottom:20px">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:15px">
                <div>
                  <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--dark)">Select Date</label>
                  <input type="date" id="work-date-manual" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                </div>
                <div>
                  <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--dark)">Calculated Hours (UTC)</label>
                  <div id="calculated-hours-preview" data-hours="8.00" style="width:100%;padding:10px;background:#e5e7eb;border-radius:8px;font-size:13px;font-weight:800;color:var(--dark)">8.00 hrs</div>
                </div>
              </div>
              
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:15px">
                <div>
                  <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--dark)">Start Time (UTC)</label>
                  <input type="time" id="work-start-time" value="09:00" onchange="calculateManualHours()" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                </div>
                <div>
                  <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--dark)">End Time (UTC)</label>
                  <input type="time" id="work-end-time" value="17:00" onchange="calculateManualHours()" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:13px">
                </div>
              </div>

              <div style="margin-bottom:15px">
                <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--dark)">Work Description</label>
                <input type="text" id="work-desc-manual" placeholder="e.g. Implemented responsive screens" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:13px">
              </div>
              <button id="btn-log-work" class="btn btn-g" style="width:100%;padding:12px;font-weight:700;border-radius:8px" onclick="logWorkManual(window.currentContractId)">Log Hours</button>
            </div>

            <!-- Weekly Hour Limit Alert -->
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px;font-size:12px;color:#b45309;margin-bottom:25px;display:flex;align-items:center;gap:8px">
              <span>⚠️</span>
              <span><strong>Weekly Hour Limit: 40 hrs</strong>. Real-time logging & work activity tracking are enabled.</span>
            </div>
            `}

            <!-- Dynamic Work Diary -->
            <h3 style="font-size:15px;font-weight:700;margin-bottom:15px;color:var(--dark)">📝 Work Diary & Activity Logs</h3>
            <div id="work-diary-container" style="margin-bottom:20px">
              <div style="color:#999;font-size:13px;text-align:center;padding:15px">Loading work logs...</div>
            </div>
          </div>

          <!-- Footer Buttons -->
          <div style="display:flex;gap:15px;border-top:1px solid #eee;padding-top:25px">
            <button class="btn" style="flex:1;justify-content:center;border:1px solid #ddd;background:white;color:#333;font-weight:600;padding:12px;border-radius:8px" onclick="showPage('messages');closeModal()">Message Client</button>
            ${c.status === 'active' || c.status === 'paused' ? `
              <button class="btn btn-g" style="flex:1;justify-content:center;padding:12px;font-weight:700" onclick="event.stopPropagation();openFreelancerCompleteModal(${c.id})">Mark as Completed</button>
            ` : ''}
            <button class="btn btn-w" style="flex:1;justify-content:center;padding:12px" onclick="closeModal()">Close</button>
          </div>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';

      // Load the work logs dynamically
      setTimeout(() => {
        loadWorkDiary(c.id);
        initializeTimerUI();
        if (c.status === 'completed') {
          loadReviewStatus(c.id);
        }
        
        // Auto-configure the manual log date limits based on contract start_date
        const dateInput = document.getElementById('work-date-manual');
        if (dateInput) {
          const today = new Date().toISOString().split('T')[0];
          let startDateStr = today;
          if (c.start_date) {
            startDateStr = c.start_date.substring(0, 10);
          }
          dateInput.min = startDateStr;
          dateInput.max = today;
          dateInput.value = today;
        }
      }, 50);
    }
    else if (type === 'add-milestone') {
      const clientId = data;
      const activeContracts = CONTRACTS.filter(c => c.client_id == clientId && (c.status === 'active' || c.status === 'paused' || c.status === 'completed'));
      
      document.getElementById('mh-title').innerText = 'Propose Milestone';
      modal.style.maxWidth = '500px';

      if (activeContracts.length === 0) {
        mc.innerHTML = `
          <div style="padding:30px; text-align:center">
            <div style="font-size:40px; margin-bottom:15px">⚠️</div>
            <h3 style="font-size:16px; font-weight:700; color:var(--dark); margin-bottom:10px">No Contract Found</h3>
            <p style="font-size:13.5px; color:var(--muted); line-height:1.5; margin-bottom:20px">
              You must have a contract with this client to propose a new milestone.
            </p>
            <button class="btn btn-g" style="margin:0 auto" onclick="closeModal()">OK</button>
          </div>
        `;
      } else {
        let contractOptions = activeContracts.map(c => 
          `<option value="${c.id}">${c.job_title} (${c.status.charAt(0).toUpperCase() + c.status.slice(1)} · $${parseFloat(c.amount).toLocaleString()})</option>`
        ).join('');

        mc.innerHTML = `
          <div style="padding:25px">
            <p style="font-size:13.5px; color:var(--muted); margin-bottom:20px; line-height:1.5">
              Propose a new milestone for this contract. Once submitted, the client will see it and can fund it to start the work.
            </p>
            
            <div class="fg" style="margin-bottom:15px">
              <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px; color:var(--dark)">Select Contract</label>
              <select id="ms-contract-id" style="width:100%; padding:10px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; background:white; font-family:inherit; color:var(--dark)">
                ${contractOptions}
              </select>
            </div>

            <div class="fg" style="margin-bottom:15px">
              <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px; color:var(--dark)">Milestone Description</label>
              <input type="text" id="ms-desc" placeholder="e.g. Phase 2: React Native App Integration" style="width:100%; padding:10px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; font-family:inherit; color:var(--dark)">
            </div>

            <div class="fg" style="margin-bottom:25px">
              <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px; color:var(--dark)">Milestone Budget ($)</label>
              <input type="number" id="ms-amt" placeholder="e.g. 500" min="1" step="any" style="width:100%; padding:10px; border:1.5px solid var(--border); border-radius:8px; font-size:13.5px; font-family:inherit; color:var(--dark)">
            </div>

            <div style="display:flex; gap:12px">
              <button class="btn btn-w" style="flex:1; justify-content:center" onclick="closeModal()">Cancel</button>
              <button class="btn btn-g" style="flex:2; justify-content:center" id="btn-submit-ms" onclick="submitNewMilestone(${clientId})">Propose Milestone →</button>
            </div>
          </div>
        `;
      }
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    else if (type === 'edit-profile') {
      document.getElementById('mh-title').innerText = 'Edit Profile';
      modal.style.maxWidth = '700px';
      mc.innerHTML = `
        <div style="padding:25px">
          <div class="g2">
            <div class="fg"><label>Full Name</label><input type="text" id="edit-name" value="<?php echo addslashes($user['name']); ?>"></div>
            <div class="fg"><label>Title / Headline</label><input type="text" id="edit-title" value="<?php echo addslashes($user['title'] ?? ''); ?>" placeholder="e.g. Senior UI/UX Designer"></div>
          </div>
          <div class="g2">
            <div class="fg"><label>Hourly Rate ($/hr)</label><input type="number" id="edit-rate" value="<?php echo $user['hourly_rate'] ?? 0; ?>"></div>
            <div class="fg"><label>Country / Location</label><input type="text" id="edit-location" value="<?php echo addslashes($user['country'] ?? ''); ?>" placeholder="e.g. Berlin, Germany"></div>
          </div>
          <div class="fg"><label>Bio / Overview</label><textarea id="edit-bio" style="min-height:130px"><?php echo addslashes($user['bio'] ?? ''); ?></textarea></div>
          <div class="fg"><label>Profile Photo</label><input type="file" id="edit-avatar" accept="image/*"></div>
          <div style="display:flex;gap:12px;margin-top:10px">
            <button class="btn btn-w" style="flex:1;justify-content:center" onclick="closeModal()">Cancel</button>
            <button class="btn btn-g" style="flex:2;justify-content:center" onclick="saveProfile()">Save Changes →</button>
          </div>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else if (type === 'edit-service') {
      const s = id; // Here 'id' is the service object passed from catalog.php
      document.getElementById('mh-title').innerText = 'Edit Service';
      modal.style.maxWidth = '600px';
      mc.innerHTML = `
        <div style="padding:25px">
          <input type="hidden" id="edit-svc-id" value="${s.id}">
          <div class="fg"><label>Service Title</label><input type="text" id="edit-svc-title" value="${s.title.replace(/"/g, '&quot;')}" placeholder="e.g. I will design a modern logo"></div>
          <div class="fg"><label>Description</label><textarea id="edit-svc-desc" placeholder="Describe your service...">${s.description}</textarea></div>
          <div class="g2">
            <div class="fg"><label>Price ($)</label><input type="number" id="edit-svc-price" value="${s.price}"></div>
            <div class="fg"><label>Delivery Days</label><input type="number" id="edit-svc-days" value="${s.delivery_days}"></div>
          </div>
          <div class="fg"><label>Service Image (Optional)</label><input type="file" id="edit-svc-image" accept="image/*"></div>
          <button class="btn btn-g" style="width:100%;padding:14px;border-radius:10px;font-weight:700;margin-top:10px" onclick="saveEditedService()">Save Changes →</button>
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
          <div class="fg"><label>Service Image (Optional)</label><input type="file" id="svc-image" accept="image/*"></div>
          <button class="btn btn-g" style="width:100%;padding:14px;border-radius:10px;font-weight:700;margin-top:10px" onclick="submitNewService()">Create Project →</button>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else if (type === 'change-password') {
      document.getElementById('mh-title').innerText = 'Change Password';
      modal.style.maxWidth = '450px';
      mc.innerHTML = `
        <div style="padding:25px">
          <div style="background:var(--gl);border:1px solid #c3e6c3;border-radius:8px;padding:11px 14px;font-size:12.5px;color:#166534;line-height:1.6;margin-bottom:18px">
            🔒 Choose a strong password — at least 8 characters with a mix of letters and numbers.
          </div>
          <div class="fg">
            <label>Current Password</label>
            <div style="position:relative">
              <input type="password" id="pw-current" placeholder="Enter your current password">
              <span onclick="togglePwVis('pw-current',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:15px;user-select:none">👁</span>
            </div>
          </div>
          <div class="fg">
            <label>New Password</label>
            <div style="position:relative">
              <input type="password" id="pw-new" placeholder="At least 8 characters">
              <span onclick="togglePwVis('pw-new',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:15px;user-select:none">👁</span>
            </div>
          </div>
          <div class="fg">
            <label>Confirm New Password</label>
            <input type="password" id="pw-confirm" placeholder="Repeat new password">
          </div>
          <button class="btn btn-g" style="width:100%;padding:14px;border-radius:10px;font-weight:700;margin-top:10px" onclick="submitChangePassword()">Update Password →</button>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else if (type === 'connects') {
      document.getElementById('mh-title').innerText = 'Connects';
      modal.style.maxWidth = '500px';
      mc.innerHTML = `<div style="padding:40px;text-align:center;color:var(--muted)">Loading connects details...</div>`;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';

      fetch(BASE_URL + 'freelancer/api/get-connects-activity.php')
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const c = data.connects;
            const max = 200;
            const pct = Math.min((c / max) * 100, 100);
            
            // Build history HTML
            let historyHtml = '';
            if (data.history && data.history.length > 0) {
              historyHtml = data.history.map(item => {
                const amountText = item.amount > 0 ? `+${item.amount}` : `${item.amount}`;
                const amountColor = item.amount > 0 ? 'var(--g)' : '#ef4444';
                const formattedDate = new Date(item.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                return `
                  <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px">
                    <div>
                      <div style="font-weight:600">${item.description}</div>
                      <div style="font-size:11px;color:var(--muted);margin-top:2px">${formattedDate}</div>
                    </div>
                    <div style="color:${amountColor};font-weight:700">${amountText}</div>
                  </div>
                `;
              }).join('');
            } else {
              historyHtml = `<div style="text-align:center;padding:20px;color:var(--muted);font-size:13px">No recent connects activity found.</div>`;
            }

            mc.innerHTML = `
              <div style="padding:25px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
                  <div style="font-size:24px;font-weight:700">${c} Connects</div>
                  <div style="font-size:13px;color:var(--muted)">Available</div>
                </div>
                <div style="background:var(--border);border-radius:6px;height:10px;overflow:hidden;margin-bottom:8px">
                  <div style="height:100%;background:var(--g);width:${pct}%"></div>
                </div>
                <div style="font-size:12.5px;color:var(--muted);margin-bottom:25px">${c} of ${max} max connects</div>

                <div style="font-size:15px;font-weight:700;margin-bottom:12px">Buy Connects</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:25px">
                  <div style="border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center;cursor:pointer" onclick="buyConnects(10, 1.50)">
                    <div style="font-weight:700;font-size:14px">10 Connects</div>
                    <div style="font-size:12px;color:var(--muted)">$1.50</div>
                  </div>
                  <div style="border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center;cursor:pointer" onclick="buyConnects(20, 3.00)">
                    <div style="font-weight:700;font-size:14px">20 Connects</div>
                    <div style="font-size:12px;color:var(--muted)">$3.00</div>
                  </div>
                </div>

                <div style="font-size:15px;font-weight:700;margin-bottom:12px">Recent Activity</div>
                <div style="border-top:1px solid var(--border);max-height:220px;overflow-y:auto;padding-right:4px">
                  ${historyHtml}
                </div>
                <button class="btn btn-g" style="width:100%;margin-top:25px;justify-content:center" onclick="closeModal()">Close</button>
              </div>
            `;
          } else {
            mc.innerHTML = `<div style="padding:30px;text-align:center;color:#ef4444">${data.message}</div>`;
          }
        })
        .catch(err => {
          mc.innerHTML = `<div style="padding:30px;text-align:center;color:#ef4444">Failed to load connects details.</div>`;
        });
    } else {
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  window.editService = function(serviceObj) {
    openModal('edit-service', serviceObj);
  }

  window.saveEditedService = function() {
    const id = document.getElementById('edit-svc-id').value;
    const title = document.getElementById('edit-svc-title').value;
    const desc = document.getElementById('edit-svc-desc').value;
    const price = document.getElementById('edit-svc-price').value;
    const days = document.getElementById('edit-svc-days').value;
    const imageInput = document.getElementById('edit-svc-image');

    if (!title || !desc || !price) return toast('Required', 'Please fill all fields');

    const fd = new FormData();
    fd.append('id', id);
    fd.append('title', title);
    fd.append('description', desc);
    fd.append('price', price);
    fd.append('delivery_days', days);
    if (imageInput.files[0]) {
      fd.append('image', imageInput.files[0]);
    }

    fetch(BASE_URL + 'actions/update_service.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Project updated successfully!');
        closeModal();
        location.reload();
      } else {
        toast('Error', data.error);
      }
    })
    .catch(err => toast('Error', 'Failed to update service'));
  }

  window.deleteService = function(id) {
    if (!confirm('Are you sure you want to delete this service?')) return;

    const fd = new FormData();
    fd.append('id', id);

    fetch(BASE_URL + 'actions/delete_service.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Deleted', 'Project removed from catalog');
        location.reload();
      } else {
        toast('Error', data.error);
      }
    })
    .catch(err => toast('Error', 'Failed to delete service'));
  }

  window.submitNewService = function() {
    const title = document.getElementById('svc-title').value;
    const desc = document.getElementById('svc-desc').value;
    const price = document.getElementById('svc-price').value;
    const days = document.getElementById('svc-days').value;
    const imageInput = document.getElementById('svc-image');

    if (!title || !desc || !price) return toast('Required', 'Please fill all fields');

    const fd = new FormData();
    fd.append('title', title);
    fd.append('description', desc);
    fd.append('price', price);
    fd.append('delivery_days', days);
    if (imageInput.files[0]) {
      fd.append('image', imageInput.files[0]);
    }

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

  window.saveProfile = function() {
    const name = document.getElementById('edit-name').value;
    const title = document.getElementById('edit-title').value;
    const rate = document.getElementById('edit-rate').value;
    const loc = document.getElementById('edit-location').value;
    const bio = document.getElementById('edit-bio').value;
    const avatarInput = document.getElementById('edit-avatar');

    const fd = new FormData();
    fd.append('name', name);
    fd.append('title', title);
    fd.append('hourly_rate', rate);
    fd.append('country', loc);
    fd.append('bio', bio);
    if (avatarInput && avatarInput.files[0]) {
      fd.append('avatar', avatarInput.files[0]);
    }

    fetch(BASE_URL + 'actions/update_profile.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success! 🎉', 'Profile updated successfully.');
        closeModal();
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        toast('Error', data.error);
      }
    })
    .catch(err => toast('Error', 'Update failed'));
  }

  window.logWork = function(contractId) {
    const amount = document.getElementById('work-amount').value;
    const desc = document.getElementById('work-desc').value;
    const attach = document.getElementById('work-attach').value;
    const btn = document.getElementById('btn-log-work');

    if (!desc) return toast('Error', 'Please provide work description');
    if (!amount || amount <= 0) return toast('Error', 'Please provide a valid amount/hours');

    const c = CONTRACTS.find(x => x.id == contractId);
    if (!c) return;

    btn.disabled = true;
    const originalText = btn.innerText;
    btn.innerText = 'Submitting...';

    const payload = {
      contract_id: contractId,
      description: desc,
      attachments: attach
    };

    if (c.contract_type === 'hourly') {
      payload.hours = amount;
    } else {
      payload.amount = amount;
    }

    fetch(BASE_URL + 'freelancer/api/log-work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Work submitted successfully!');
        closeModal();
        renderContracts(); // Refresh list if needed
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => toast('Error', 'Submission failed'))
    .finally(() => {
      btn.disabled = false;
      btn.innerText = originalText;
    });
  }

  window.switchHourlyTab = function(tab) {
    const tabTracker = document.getElementById('tab-tracker');
    const tabManual = document.getElementById('tab-manual');
    const panelTracker = document.getElementById('panel-tracker');
    const panelManual = document.getElementById('panel-manual');

    if (!tabTracker || !tabManual || !panelTracker || !panelManual) return;

    if (tab === 'tracker') {
      tabTracker.style.color = 'var(--g)';
      tabTracker.style.borderBottom = '2.5px solid var(--g)';
      tabTracker.style.fontWeight = '700';

      tabManual.style.color = 'var(--muted)';
      tabManual.style.borderBottom = 'none';
      tabManual.style.fontWeight = '600';

      panelTracker.style.display = 'block';
      panelManual.style.display = 'none';
    } else {
      tabManual.style.color = 'var(--g)';
      tabManual.style.borderBottom = '2.5px solid var(--g)';
      tabManual.style.fontWeight = '700';

      tabTracker.style.color = 'var(--muted)';
      tabTracker.style.borderBottom = 'none';
      tabTracker.style.fontWeight = '600';

      panelTracker.style.display = 'none';
      panelManual.style.display = 'block';
    }
  };

  let trackerInterval = null;
  let trackerSeconds = 0;
  let trackerActiveContractId = null;

  window.restoreActiveTracker = function() {
    const savedStartTime = localStorage.getItem('active_tracker_start_time');
    const savedContractId = localStorage.getItem('active_tracker_contract_id');
    const savedDesc = localStorage.getItem('active_tracker_description');

    if (savedStartTime && savedContractId) {
      const elapsed = Math.floor((Date.now() - parseInt(savedStartTime, 10)) / 1000);
      trackerSeconds = Math.max(0, elapsed);
      trackerActiveContractId = savedContractId;
      
      if (!trackerInterval) {
        trackerInterval = setInterval(() => {
          trackerSeconds++;
          const display = document.getElementById('timer-display');
          if (display && trackerActiveContractId === window.currentContractId) {
            display.innerText = formatSeconds(trackerSeconds);
          }
        }, 1000);
      }
    }
  };

  window.initializeTimerUI = function() {
    const display = document.getElementById('timer-display');
    const btn = document.getElementById('btn-timer-toggle');
    const desc = document.getElementById('tracker-desc');

    if (!display || !btn || !desc) return;

    restoreActiveTracker();

    if (trackerActiveContractId === String(window.currentContractId) && trackerInterval) {
      btn.innerText = 'Stop Tracker';
      btn.style.background = '#ef4444';
      btn.style.borderColor = '#ef4444';
      desc.disabled = true;
      const savedDesc = localStorage.getItem('active_tracker_description');
      if (savedDesc) desc.value = savedDesc;
      display.innerText = formatSeconds(trackerSeconds);
    } else {
      btn.innerText = 'Start Tracker';
      btn.style.background = 'var(--g)';
      btn.style.borderColor = 'var(--g)';
      desc.disabled = false;
      display.innerText = formatSeconds(0);
      desc.value = '';
    }
  };

  function formatSeconds(secs) {
    const hrs = Math.floor(secs / 3600);
    const mins = Math.floor((secs % 3600) / 60);
    const s = secs % 60;
    return [hrs, mins, s].map(v => v < 10 ? "0" + v : v).join(":");
  }

  window.toggleTimer = function(contractId) {
    const btn = document.getElementById('btn-timer-toggle');
    const descInput = document.getElementById('tracker-desc');
    const display = document.getElementById('timer-display');

    if (!btn || !descInput || !display) return;

    if (trackerInterval) {
      clearInterval(trackerInterval);
      trackerInterval = null;
      
      const savedStartTime = localStorage.getItem('active_tracker_start_time');
      const desc = localStorage.getItem('active_tracker_description') || descInput.value || 'Tracked work session';
      
      let elapsedSeconds = trackerSeconds;
      if (savedStartTime) {
        elapsedSeconds = Math.floor((Date.now() - parseInt(savedStartTime, 10)) / 1000);
      }
      
      let elapsedHours = parseFloat((elapsedSeconds / 3600).toFixed(2));
      
      if (elapsedHours < 0.01 && elapsedSeconds > 0) {
        elapsedHours = 0.01;
      }
      
      if (elapsedSeconds <= 0) {
        toast('Timer Stopped', 'No elapsed time to log.');
        localStorage.removeItem('active_tracker_contract_id');
        localStorage.removeItem('active_tracker_start_time');
        localStorage.removeItem('active_tracker_description');
        trackerSeconds = 0;
        trackerActiveContractId = null;
        initializeTimerUI();
        return;
      }

      btn.disabled = true;
      btn.innerText = 'Submitting...';

      fetch(BASE_URL + 'freelancer/api/log-work.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          contract_id: contractId,
          hours: elapsedHours,
          description: desc
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          toast('Success! 🎉', `Logged ${elapsedHours} hrs successfully!`);
          descInput.value = '';
        } else {
          toast('Error', data.message);
        }
      })
      .catch(err => toast('Error', 'Failed to log tracked time.'))
      .finally(() => {
        localStorage.removeItem('active_tracker_contract_id');
        localStorage.removeItem('active_tracker_start_time');
        localStorage.removeItem('active_tracker_description');
        trackerSeconds = 0;
        trackerActiveContractId = null;
        btn.disabled = false;
        initializeTimerUI();
        loadWorkDiary(contractId);
      });

    } else {
      const descVal = descInput.value || '';
      trackerSeconds = 0;
      trackerActiveContractId = String(contractId);
      descInput.disabled = true;

      localStorage.setItem('active_tracker_contract_id', contractId);
      localStorage.setItem('active_tracker_start_time', Date.now().toString());
      localStorage.setItem('active_tracker_description', descVal);

      btn.innerText = 'Stop Tracker';
      btn.style.background = '#ef4444';
      btn.style.borderColor = '#ef4444';

      trackerInterval = setInterval(() => {
        trackerSeconds++;
        display.innerText = formatSeconds(trackerSeconds);
      }, 1000);
      
      toast('Tracker Started', 'Real-time hour tracking is now active! It will keep running even if you close the window.');
    }
  };

  window.calculateManualHours = function() {
    const start = document.getElementById('work-start-time').value;
    const end = document.getElementById('work-end-time').value;
    const preview = document.getElementById('calculated-hours-preview');
    if (!preview) return;

    if (start && end) {
      const [sH, sM] = start.split(':').map(Number);
      const [eH, eM] = end.split(':').map(Number);
      let diffMins = (eH * 60 + eM) - (sH * 60 + sM);
      if (diffMins < 0) {
        diffMins += 24 * 60; // handle cross-midnight
      }
      const hours = (diffMins / 60).toFixed(2);
      preview.innerText = hours + ' hrs';
      preview.dataset.hours = hours;
    } else {
      preview.innerText = '0.00 hrs';
      preview.dataset.hours = '0';
    }
  };

  window.logWorkManual = function(contractId) {
    const dateInput = document.getElementById('work-date-manual');
    const startTimeInput = document.getElementById('work-start-time');
    const endTimeInput = document.getElementById('work-end-time');
    const preview = document.getElementById('calculated-hours-preview');
    const descInput = document.getElementById('work-desc-manual');
    const btn = document.getElementById('btn-log-work');

    if (!dateInput || !startTimeInput || !endTimeInput || !preview || !descInput || !btn) return;

    const workDate = dateInput.value;
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    const hours = parseFloat(preview.dataset.hours || 0);
    const desc = descInput.value;

    if (!workDate) return toast('Error', 'Please select a date.');
    if (!startTime || !endTime) return toast('Error', 'Please enter Start Time and End Time.');
    if (!hours || hours <= 0) return toast('Error', 'Please enter a valid time range.');
    if (!desc) return toast('Error', 'Please enter a work description.');

    btn.disabled = true;
    const originalText = btn.innerText;
    btn.innerText = 'Logging...';

    fetch(BASE_URL + 'freelancer/api/log-work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contract_id: contractId,
        hours: hours,
        description: desc,
        work_date: workDate,
        start_time: startTime,
        end_time: endTime
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success! 🎉', 'Hours logged successfully.');
        descInput.value = '';
        loadWorkDiary(contractId);
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => toast('Error', 'Failed to log manual time.'))
    .finally(() => {
      btn.disabled = false;
      btn.innerText = originalText;
    });
  };

  window.loadWorkDiary = function(contractId) {
    const container = document.getElementById('work-diary-container');
    if (!container) return;

    fetch(BASE_URL + 'freelancer/api/get-work-logs.php?contract_id=' + contractId)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.work_logs && data.work_logs.length > 0) {
        // Group logs by date (YYYY-MM-DD from created_at)
        const groups = {};
        data.work_logs.forEach(log => {
          const dateKey = log.created_at.substring(0, 10);
          if (!groups[dateKey]) {
            groups[dateKey] = {
              logs: [],
              totalHours: 0
            };
          }
          groups[dateKey].logs.push(log);
          groups[dateKey].totalHours += parseFloat(log.hours || 0);
        });

        // Get sorted list of date keys (descending order)
        const sortedDates = Object.keys(groups).sort((a, b) => new Date(b) - new Date(a));

        let html = `
          <div style="display:grid; gap:20px; max-height:280px; overflow-y:auto; padding-right:5px">
        `;

        sortedDates.forEach(dateStr => {
          const group = groups[dateStr];
          
          // Format dateStr to nice format: e.g. "May 15, 2026"
          const displayDate = new Date(dateStr).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
          });

          html += `
            <div style="border-left: 3px solid var(--g); padding-left: 12px; margin-bottom: 5px">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
                <span style="font-size:13px; font-weight:800; color:var(--dark)">📅 ${displayDate}</span>
                <span style="font-size:12px; font-weight:800; color:var(--g); background:rgba(30, 190, 165, 0.1); padding:2px 8px; border-radius:12px">Day Total: ${group.totalHours.toFixed(2)} hrs</span>
              </div>
              <div style="display:grid; gap:8px">
          `;

          group.logs.forEach(log => {
            let statusBadge = '';
            if (log.status === 'pending') {
              statusBadge = `<span class="badge" style="background:#fef3c7; color:#d97706; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:700">Under Review</span>`;
            } else if (log.status === 'approved') {
              statusBadge = `<span class="badge" style="background:#d1fae5; color:#059669; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:700">Approved & Paid</span>`;
            } else {
              statusBadge = `<span class="badge" style="background:#fee2e2; color:#dc2626; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:700">Rejected</span>`;
            }

            let typeBadge = '';
            if (log.log_type === 'manual') {
              typeBadge = `<span class="badge" style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:700">✍️ Manual Log</span>`;
            } else {
              typeBadge = `<span class="badge" style="background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:4px; font-size:10px; font-weight:700">⏱️ Auto Tracker</span>`;
            }

            let timeRange = '';
            if (log.start_time && log.end_time) {
              const formatTime = (t) => t.substring(0, 5);
              timeRange = `<span style="color:#4b5563; font-weight:600; background:#f3f4f6; padding:2px 6px; border-radius:4px; font-size:10px; margin-right:8px">🕒 ${formatTime(log.start_time)} - ${formatTime(log.end_time)} UTC</span>`;
            }

            html += `
              <div style="background:white; border:1px solid var(--border); border-radius:8px; padding:12px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 2px rgba(0,0,0,0.01)">
                <div style="flex:1; padding-right:15px">
                  <div style="font-size:12.5px; font-weight:700; color:#374151; margin-bottom:4px">${log.description || 'Working Session'}</div>
                  <div style="font-size:11px; color:var(--muted); display:flex; align-items:center; gap:5px; flex-wrap:wrap">
                    ${timeRange}
                    ${typeBadge}
                    ${statusBadge}
                  </div>
                </div>
                <div style="text-align:right">
                  <div style="font-size:13px; font-weight:800; color:var(--dark)">${parseFloat(log.hours).toFixed(2)} hrs</div>
                  <div style="font-size:11px; font-weight:600; color:var(--muted)">$${parseFloat(log.amount).toFixed(2)}</div>
                </div>
              </div>
            `;
          });

          html += `
              </div>
            </div>
          `;
        });

        html += `</div>`;
        container.innerHTML = html;
      } else {
        container.innerHTML = `
          <div style="color:var(--muted); font-size:13px; text-align:center; padding:20px; border:1.5px dashed var(--border); border-radius:10px; background:#fafafa">
            No tracked sessions or manual logs yet.
          </div>
        `;
      }
    })
    .catch(err => {
      container.innerHTML = `<div style="color:red; font-size:13px; text-align:center">Failed to load work diary.</div>`;
    });
  };

  window.updateContractStatus = function(contractId, status) {
    const btn = document.getElementById('btn-pause-contract');
    const c = CONTRACTS.find(x => x.id == contractId);
    if (!c) return;

    // Toggle if already paused
    const newStatus = (c.status === 'paused' && status === 'paused') ? 'active' : status;

    btn.disabled = true;
    btn.innerText = 'Updating...';

    fetch(BASE_URL + 'freelancer/api/update-contract.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contract_id: contractId,
        status: newStatus
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        c.status = newStatus;
        toast('Success', 'Contract ' + newStatus);
        closeModal();
        renderContracts(); 
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => toast('Error', 'Update failed'))
    .finally(() => {
      btn.disabled = false;
    });
  }

  function renderReports() {
    const chart = document.getElementById('reports-chart');
    if (chart) {
      const data = [6.5, 8, 4.5, 7, 8.5, 0, 0];
      chart.innerHTML = data.map((h, i) => `<div class="chart-bar" style="height:${(h/10)*100}%;background:${h>0?'var(--g)':'var(--border)'};flex:1;border-radius:4px" onclick="toast('Time','${h} hours on ${['Mon','Tue','Wed','Thu','Fri','Sat','Sun'][i]}')"></div>`).join('');
    }
  }

  // ════════════════════════════════════════════════════════════
  //  SKILL SELECTOR — full Upwork 2025 categories/subcategories
  // ════════════════════════════════════════════════════════════
  const SKILL_TREE = {
    'Accounting & Consulting': { icon: '📊', subs: { 'Personal & Professional Coaching': ['Career Coaching','Personal Coaching'], 'Accounting & Bookkeeping': ['Accounting','Bookkeeping'], 'Financial Planning': ['Financial Analysis & Modeling','Financial Management/CFO'], 'Recruiting & Human Resources': ['HR Administration','Recruiting & Talent Sourcing','Training & Development'], 'Management Consulting & Analysis': ['Business Analysis & Strategy','Instructional Design','Management Consulting'], 'Other - Accounting & Consulting': ['Tax Preparation'], } },
    'Admin Support': { icon: '🗂️', subs: { 'Data Entry & Transcription Services': ['Data Entry','Manual Transcription'], 'Virtual Assistance': ['Executive Virtual Assistance','Legal Virtual Assistance','Medical Virtual Assistance','Ecommerce Management','Personal Virtual Assistance','General Virtual Assistance'], 'Project Management': ['Business Project Management','Supply Chain & Logistics Project Management','Construction & Engineering Project Management','Development & IT Project Management','Healthcare Project Management','Digital Project Management'], 'Market Research & Product Reviews': ['Web & Software Product Research','Market Research','General Research Services','Product Reviews','Qualitative Research','Quantitative Research'], } },
    'Customer Service': { icon: '🎧', subs: { 'Community Management & Tagging': ['Community Management','Content Moderation','Visual Tagging & Processing'], 'Customer Service & Tech Support': ['Customer Onboarding','Email, Phone & Chat Support','Customer Success','IT Support','Tech Support'], } },
    'Data Science & Analytics': { icon: '📈', subs: { 'Data Analysis & Testing': ['Data Analytics','Data Visualization','Experimentation & Testing'], 'Data Extraction/ETL': ['Data Extraction','Data Processing'], 'Data Mining & Management': ['Data Engineering','Data Mining'], 'AI & Machine Learning': ['Generative AI Modeling','AI Data Annotation & Labeling','Deep Learning','Knowledge Representation','Machine Learning'], } },
    'Design & Creative': { icon: '🎨', subs: { 'Art & Illustration': ['Portraits & Caricatures','Cartoons & Comics','Fine Art','Illustration','Pattern Design'], 'Audio & Music Production': ['AI Speech & Audio Generation','Audio Editing','Audio Production','Songwriting & Music Composition','Music Production'], 'Branding & Logo Design': ['Brand Identity Design','Logo Design'], 'NFT, AR/VR & Game Art': ['NFT Art','Game Art','AR/VR Design'], 'Graphic, Editorial & Presentation Design': ['AI Image Generation & Editing','Art Direction','Creative Direction','Editorial Design','Graphic Design','Image Editing','Packaging Design','Presentation Design'], 'Performing Arts': ['Acting','Music Performance','Singing','Voice Talent'], 'Photography': ['Local Photography','Product Photography'], 'Product Design': ['Fashion Design','Jewelry Design','Product & Industrial Design'], 'Video & Animation': ['AI Video Generation & Editing','Motion Graphics','3D Animation','2D Animation','Video Editing','Videography','Video Production','Visual Effects'], } },
    'Engineering & Architecture': { icon: '🏗️', subs: { 'Building & Landscape Architecture': ['Architectural Design','Landscape Architecture'], 'Chemical Engineering': ['Chemical & Process Engineering'], 'Civil & Structural Engineering': ['Building Information Modeling','Civil Engineering','Structural Engineering'], 'Electrical & Electronic Engineering': ['Electrical Engineering','Electronic Engineering'], 'Interior & Trade Show Design': ['Trade Show Design','Interior Design'], 'Energy & Mechanical Engineering': ['Energy Engineering','Mechanical Engineering'], 'Physical Sciences': ['Biology','Chemistry','Mathematics','Physics','STEM Tutoring'], '3D Modeling & CAD': ['CAD','3D Modeling & Rendering'], 'Contract Manufacturing': ['Logistics & Supply Chain Management','Sourcing & Procurement'], } },
    'IT & Networking': { icon: '🖧', subs: { 'Database Management & Administration': ['Database Administration'], 'ERP/CRM Software': ['Business Applications Development','Systems Engineering'], 'Information Security & Compliance': ['IT Compliance','Information Security','Network Security'], 'Network & System Administration': ['Network Administration','Systems Administration'], 'DevOps & Solution Architecture': ['Cloud Engineering','DevOps Engineering','Solution Architecture'], } },
    'Legal': { icon: '⚖️', subs: { 'Corporate & Contract Law': ['Business & Corporate Law','Intellectual Property Law','Paralegal Services'], 'International & Immigration Law': ['Immigration Law','International Law'], 'Finance & Tax Law': ['Securities & Finance Law','Tax Law'], 'Public Law': ['Labor & Employment Law','Regulatory Law'], } },
    'Sales & Marketing': { icon: '📣', subs: { 'Digital Marketing': ['Display Advertising','Campaign Management','Email Marketing','Marketing Automation','Search Engine Marketing','SEO','Social Media Marketing'], 'Lead Generation & Telemarketing': ['Sales & Business Development','Lead Generation','Telemarketing'], 'Marketing, PR & Brand Strategy': ['Brand Strategy','Content Strategy','Marketing Strategy','Public Relations','Social Media Strategy'], } },
    'Translation': { icon: '🌐', subs: { 'Language Tutoring & Interpretation': ['Live Interpretation','Sign Language Interpretation','Language Tutoring'], 'Translation & Localization Services': ['Language Localization','Legal Document Translation','Medical Document Translation','Technical Document Translation','General Translation Services'], } },
    'Web, Mobile & Software Dev': { icon: '💻', subs: { 'Blockchain, NFT & Cryptocurrency': ['Blockchain & NFT Development','Crypto Coins & Tokens','Crypto Wallet Development'], 'AI Apps & Integration': ['AI Chatbot Development','AI Integration'], 'Desktop Application Development': ['Desktop Software Development'], 'Ecommerce Development': ['Ecommerce Website Development'], 'Game Design & Development': ['Video Game Development'], 'Mobile Development': ['Mobile App Development','Mobile Game Development'], 'Other - Software Development': ['AR/VR Development','Database Development','Emerging Tech','Firmware Development','Coding Tutoring'], 'Product Management & Scrum': ['Product Management','Scrum Leadership'], 'QA Testing': ['Automation Testing','Manual Testing'], 'Scripts & Utilities': ['Scripting & Automation'], 'Web & Mobile Design': ['Mobile Design','Prototyping','UX/UI Design','Web Design'], 'Web Development': ['Back-End Development','CMS Development','Front-End Development','Full Stack Development'], } },
    'Writing': { icon: '✍️', subs: { 'Sales & Marketing Copywriting': ['Ad & Email Copywriting','Marketing Copywriting','Sales Copywriting'], 'Content Writing': ['Web & UX Writing','Article & Blog Writing','AI Content Writing','Creative Writing','Ghostwriting','Scriptwriting','Writing Tutoring'], 'Editing & Proofreading Services': ['Proofreading','Copy Editing'], 'Professional & Business Writing': ['Academic & Research Writing','Legal Writing','Medical Writing','Resume & Cover Letter Writing','Business & Proposal Writing','Grant Writing','Technical Writing'], } },
  };

  const MAX_SKILLS = 15;
  let selectedSkills = new Set(<?php echo json_encode($userSkills ?? []); ?>);
  let activeCat = null, activeSub = null;

  window.openSkillSelector = function() {
    document.getElementById('skill-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    document.getElementById('skill-search').value = '';
    renderCatCol();
    renderSelectedPreview();
    const firstCat = Object.keys(SKILL_TREE)[0];
    selectCat(firstCat);
  }

  window.closeSkillSelector = function() {
    document.getElementById('skill-overlay').style.display = 'none';
    document.body.style.overflow = '';
  }

  function renderCatCol(filter) {
    const col = document.getElementById('cat-col');
    col.innerHTML = Object.entries(SKILL_TREE).map(([cat, data]) => {
      if (filter && !cat.toLowerCase().includes(filter)) return '';
      const count = countSelected(cat);
      return `<div class="cat-item${activeCat===cat?' active':''}" onclick="selectCat('${cat.replace(/'/g,"\\\\'")}')">
        <span class="cat-icon">${data.icon}</span>${cat}${count?` <span style="background:var(--g);color:white;font-size:10px;padding:1px 6px;border-radius:8px;margin-left:4px">${count}</span>`:''}
      </div>`;
    }).join('');
  }

  function countSelected(cat) {
    let n = 0;
    Object.values(SKILL_TREE[cat].subs).forEach(specs => specs.forEach(s => { if(selectedSkills.has(s)) n++; }));
    return n;
  }

  window.selectCat = function(cat) {
    activeCat = cat; activeSub = null;
    renderCatCol();
    const subs = SKILL_TREE[cat].subs;
    const col = document.getElementById('subcat-col');
    col.innerHTML = Object.keys(subs).map(sub => {
      const c = subs[sub].filter(s=>selectedSkills.has(s)).length;
      return `<div class="subcat-item${activeSub===sub?' active':''}" onclick="selectSub('${sub.replace(/'/g,"\\\\'")}')">
        ${sub}${c?` <span style="background:var(--g);color:white;font-size:10px;padding:1px 5px;border-radius:8px;margin-left:4px">${c}</span>`:''}
      </div>`;
    }).join('');
    document.getElementById('skill-col').innerHTML = '<div style="padding:20px;font-size:12.5px;color:var(--muted);text-align:center">← Select a subcategory</div>';
  }

  window.selectSub = function(sub) {
    activeSub = sub;
    const subs = SKILL_TREE[activeCat].subs;
    const col = document.getElementById('subcat-col');
    col.innerHTML = Object.keys(subs).map(s => {
      const c = subs[s].filter(x=>selectedSkills.has(x)).length;
      return `<div class="subcat-item${activeSub===s?' active':''}" onclick="selectSub('${s.replace(/'/g,"\\\\'")}')">
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
        const action = disabled ? `toast('Limit','You can select up to ${MAX_SKILLS} skills')` : `toggleSkill('${spec.replace(/'/g,"\\\\'")}')`;
        return `<div class="spec-item${sel?' selected':''}" onclick="${action}" style="${disabled?'opacity:.45;cursor:not-allowed':''}">
          <span>${spec}</span>
          <span class="spec-check">${sel?'✓':''}</span>
        </div>`;
      }).join('')}`;
  }

  window.toggleSkill = function(spec) {
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
    if (activeCat) selectCat(activeCat);
    if (activeSub) renderSpecCol(activeSub);
    renderSelectedPreview();
  }

  function renderSelectedPreview() {
    const el = document.getElementById('selected-preview');
    const arr = [...selectedSkills];
    el.innerHTML = arr.length
      ? arr.map(s=>`<span style="display:inline-flex;align-items:center;gap:4px;background:#e8f5e3;color:#14a800;border:1px solid #c3e6c3;border-radius:5px;padding:2px 8px;font-size:11.5px;font-weight:500">
          ${s} <span style="cursor:pointer;color:#6b7c6b;font-size:13px" onclick="toggleSkill('${s.replace(/'/g,"\\\\'")}')">×</span>
        </span>`).join('')
      : '<span style="font-size:12.5px;color:var(--muted)">No skills selected yet</span>';
    el.innerHTML += `<span style="font-size:11.5px;color:var(--muted);white-space:nowrap;margin-left:auto;align-self:center">${arr.length}/${MAX_SKILLS}</span>`;
  }

  window.saveSkills = function() {
    const skills = [...selectedSkills];
    const fd = new FormData();
    fd.append('skills', JSON.stringify(skills));

    fetch(BASE_URL + 'actions/update_skills.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Skills updated successfully!');
        closeSkillSelector();
        
        // Update the skills display on the profile page manually
        const display = document.getElementById('profile-skills-display');
        const empty = document.getElementById('profile-skills-empty');
        const badge = document.getElementById('skill-count-badge');
        
        if (display) {
          display.innerHTML = skills.map(s => 
            `<span class="skill-tag">${s} <span class="skill-remove" onclick="removeSkill('${s.replace(/'/g,"\\\\'")}')">×</span></span>`
          ).join('');
          display.style.display = skills.length ? 'flex' : 'none';
        }
        if (empty) empty.style.display = skills.length ? 'none' : 'block';
        if (badge) badge.textContent = skills.length + ' / ' + MAX_SKILLS;

      } else {
        toast('Error', data.error);
      }
    })
    .catch(err => toast('Error', 'Update failed'));
  }

  window.removeSkill = function(skill) {
    selectedSkills.delete(skill);
    window.saveSkills();
  }

  window.quickAddSkill = function(val) {
    const skill = (val || '').trim();
    if (!skill) return;
    if (selectedSkills.has(skill)) { toast('Already added', `"${skill}" is already in your skills`); return; }
    if (selectedSkills.size >= MAX_SKILLS) { toast('Limit reached', `You can add up to ${MAX_SKILLS} skills`); return; }
    selectedSkills.add(skill);
    window.saveSkills();
  }

  window.filterSkills = function(q) {
    const query = q.toLowerCase().trim();
    if (!query) {
      renderCatCol();
      if (activeCat) selectCat(activeCat);
      return;
    }
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
          return `<div class="spec-item${sel?' selected':''}" onclick="toggleSkill('${spec.replace(/'/g,"\\\\'")}')">
            <div><div style="font-size:13px">${spec}</div><div style="font-size:11px;color:var(--muted)">${cat} › ${sub}</div></div>
            <span class="spec-check">${sel?'✓':''}</span>
          </div>`;
        }).join('')
      : '<div style="padding:20px;font-size:13px;color:var(--muted);text-align:center">No matching skills found</div>';
  }

  const SUGGESTED_FOR_DESIGNER = [
    'UX/UI Design','Wireframing','Adobe XD','Sketch','User Testing',
    'Information Architecture','Accessibility Design','Design Thinking',
    'Usability Testing','Interaction Design','Visual Design','Typography',
    'Color Theory','Responsive Design','Design Research'
  ];

  window.renderSuggestedSkills = function() {
    const el = document.getElementById('suggested-skills-row');
    if (!el) return;
    const shown = SUGGESTED_FOR_DESIGNER.filter(s => !selectedSkills.has(s)).slice(0, 8);
    el.innerHTML = shown.map(s =>
      `<button onclick="quickAddSkill('${s.replace(/'/g,"\\\\'")}')"
        style="display:inline-flex;align-items:center;gap:4px;background:white;border:1.5px dashed var(--border);color:var(--dark3);border-radius:6px;padding:3px 10px;font-size:12.5px;font-weight:500;cursor:pointer;transition:all .15s;font-family:inherit"
        onmouseover="this.style.borderColor='var(--g)';this.style.color='var(--g)'"
        onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--dark3)'">
        + ${s}
      </button>`
    ).join('');
  }

  // Initialize
  let activeChatId = null;

  async function loadChat(otherId, name, initials, el) {
    activeChatId = otherId;
    
    // Highlight sidebar
    if(el) {
      document.querySelectorAll('.msg-item').forEach(i => {
        i.style.background = 'transparent';
        i.classList.remove('active');
      });
      el.style.background = 'var(--gl)';
      el.classList.add('active');
      const dot = el.querySelector('span[style*="background:var(--g)"]');
      if(dot) dot.remove();
    }

    const chatWindow = document.getElementById('chat-window');
    chatWindow.innerHTML = `<div style="flex:1;display:flex;align-items:center;justify-content:center"><span class="spinner"></span></div>`;

    try {
      const response = await fetch(`${BASE_URL}/actions/get_messages.php?with=${otherId}`);
      const result = await response.json();
      
      if(result.success) {
        renderChatWindow(name, initials, result.messages);
      } else {
        chatWindow.innerHTML = `<div style="padding:20px;text-align:center;color:red">${result.error}</div>`;
      }
    } catch (err) {
      chatWindow.innerHTML = `<div style="padding:20px;text-align:center;color:red">Failed to load messages</div>`;
    }
  }

  function renderChatWindow(name, initials, messages) {
    const chatWindow = document.getElementById('chat-window');
    
    // Check if freelancer has contracts with this client
    const clientContracts = (typeof CONTRACTS !== 'undefined' && Array.isArray(CONTRACTS)) 
      ? CONTRACTS.filter(c => c.client_id == activeChatId && (c.status === 'active' || c.status === 'paused' || c.status === 'completed'))
      : [];

    const msgHtml = messages.map(m => {
      const isMe = (m.sender_id != activeChatId);
      return `
        <div style="display:flex;gap:10px;${isMe ? 'flex-direction:row-reverse' : ''}">
          <div class="av" style="width:30px;height:30px;font-size:10px;background:${isMe ? 'var(--g)' : 'white'};color:${isMe ? 'white' : 'var(--muted)'};border:${isMe ? 'none' : '1px solid var(--border)'};flex-shrink:0">${isMe ? 'Me' : initials}</div>
          <div style="max-width:75%;${isMe ? 'text-align:right' : ''}">
            <div style="background:${isMe ? 'var(--g)' : 'white'};color:${isMe ? 'white' : 'var(--forest)'};border-radius:12px;padding:10px 15px;font-size:13.5px;box-shadow:0 1px 2px rgba(0,0,0,.05);text-align:left">${m.message}</div>
            <div style="font-size:11px;color:var(--muted);margin-top:4px">${new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
          </div>
        </div>
      `;
    }).join('');

    chatWindow.innerHTML = `
      <div style="padding:15px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="av" style="width:32px;height:32px;background:var(--gl);color:var(--forest)">${initials}</div>
          <div style="font-weight:700;font-size:14px">${name}</div>
        </div>
        ${clientContracts.length > 0 ? `
          <button class="btn btn-g btn-sm" onclick="openModal('add-milestone', ${activeChatId})" style="padding:6px 12px;font-size:12.5px;display:flex;align-items:center;gap:6px">
            <span>➕</span> Add Milestone
          </button>
        ` : ''}
      </div>
      <div style="flex:1;padding:20px;display:flex;flex-direction:column;gap:15px;overflow-y:auto" id="chat-messages-scroll">${msgHtml}</div>
      <div style="padding:15px;background:white;border-top:1px solid var(--border);display:flex;gap:10px">
        <input id="chat-input" type="text" placeholder="Write a message..." style="flex:1;padding:10px;border:1px solid var(--border);border-radius:8px" onkeydown="if(event.key==='Enter')sendMsg()">
        <button class="btn btn-g" onclick="sendMsg()">Send</button>
      </div>
    `;
    const scroll = document.getElementById('chat-messages-scroll');
    scroll.scrollTop = scroll.scrollHeight;
  }

  async function sendMsg() {
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if(!msg || !activeChatId) return;

    const chatMessagesScroll = document.getElementById('chat-messages-scroll');
    const tempId = 'temp-' + Date.now();
    
    // Append immediately for snappy feel
    const myMsgHtml = `
      <div style="display:flex;gap:10px;flex-direction:row-reverse" id="${tempId}">
        <div class="av" style="width:30px;height:30px;font-size:10px;background:var(--g);color:white;flex-shrink:0">Me</div>
        <div style="max-width:75%;text-align:right">
          <div style="background:var(--g);color:white;border-radius:12px;padding:10px 15px;font-size:13.5px;box-shadow:0 1px 2px rgba(0,0,0,.05);text-align:left">${msg}</div>
          <div style="font-size:11px;color:var(--muted);margin-top:4px">Sending...</div>
        </div>
      </div>
    `;
    chatMessagesScroll.insertAdjacentHTML('beforeend', myMsgHtml);
    chatMessagesScroll.scrollTop = chatMessagesScroll.scrollHeight;

    input.value = '';
    try {
      const formData = new FormData();
      formData.append('receiver_id', activeChatId);
      formData.append('message', msg);
      
      const response = await fetch(`${BASE_URL}/actions/send_message.php`, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if(result.success) {
        const tempMsg = document.getElementById(tempId);
        if(tempMsg) {
          tempMsg.querySelector('div[style*="margin-top:4px"]').innerText = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        }
      }
    } catch(err) {
      toast('Error', 'Failed to send message');
    }
  }

  // Polling for new messages
  let chatPollInterval = null;
  function startChatPolling(otherId, name, initials) {
    if(chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(async () => {
      if(activeChatId !== otherId || document.getElementById('page-messages').style.display === 'none') {
        clearInterval(chatPollInterval);
        return;
      }
      try {
        const response = await fetch(`${BASE_URL}/actions/get_messages.php?with=${otherId}`);
        const result = await response.json();
        if(result.success) {
          const currentCount = document.querySelectorAll('#chat-messages-scroll > div').length;
          if(result.messages.length > currentCount) {
            renderChatWindow(name, initials, result.messages);
          }
        }
      } catch(e) {}
    }, 5000);
  }

  async function loadChat(otherId, name, initials, el) {
    activeChatId = otherId;
    
    // Highlight sidebar
    if(el) {
      document.querySelectorAll('.msg-item').forEach(i => {
        i.style.background = 'transparent';
        i.classList.remove('active');
      });
      el.style.background = 'var(--gl)';
      el.classList.add('active');
      const dot = el.querySelector('span[style*="background:var(--g)"]');
      if(dot) dot.remove();
    }

    const chatWindow = document.getElementById('chat-window');
    chatWindow.innerHTML = `<div style="flex:1;display:flex;align-items:center;justify-content:center"><span class="spinner"></span></div>`;

    try {
      const response = await fetch(`${BASE_URL}/actions/get_messages.php?with=${otherId}`);
      const result = await response.json();
      
      if(result.success) {
        renderChatWindow(name, initials, result.messages);
        startChatPolling(otherId, name, initials);
      } else {
        chatWindow.innerHTML = `<div style="padding:20px;text-align:center;color:red">${result.error}</div>`;
      }
    } catch (err) {
      chatWindow.innerHTML = `<div style="padding:20px;text-align:center;color:red">Failed to load messages</div>`;
    }
  }

  function renderChatWindow(name, initials, messages) {
    const chatWindow = document.getElementById('chat-window');
    
    // Check if freelancer has contracts with this client
    const clientContracts = (typeof CONTRACTS !== 'undefined' && Array.isArray(CONTRACTS)) 
      ? CONTRACTS.filter(c => c.client_id == activeChatId && (c.status === 'active' || c.status === 'paused' || c.status === 'completed'))
      : [];

    const msgHtml = messages.map(m => {
      const isMe = (m.sender_id != activeChatId);
      return `
        <div style="display:flex;gap:10px;${isMe ? 'flex-direction:row-reverse' : ''}">
          <div class="av" style="width:30px;height:30px;font-size:10px;background:${isMe ? 'var(--g)' : 'white'};color:${isMe ? 'white' : 'var(--muted)'};border:${isMe ? 'none' : '1px solid var(--border)'};flex-shrink:0">${isMe ? 'Me' : initials}</div>
          <div style="max-width:75%;${isMe ? 'text-align:right' : ''}">
            <div style="background:${isMe ? 'var(--g)' : 'white'};color:${isMe ? 'white' : 'var(--forest)'};border-radius:12px;padding:10px 15px;font-size:13.5px;box-shadow:0 1px 2px rgba(0,0,0,.05);text-align:left">${m.message}</div>
            <div style="font-size:11px;color:var(--muted);margin-top:4px">${new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
          </div>
        </div>
      `;
    }).join('');

    chatWindow.innerHTML = `
      <div style="padding:15px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="av" style="width:32px;height:32px;background:var(--gl);color:var(--forest)">${initials}</div>
          <div style="font-weight:700;font-size:14px">${name}</div>
        </div>
        ${clientContracts.length > 0 ? `
          <button class="btn btn-g btn-sm" onclick="openModal('add-milestone', ${activeChatId})" style="padding:6px 12px;font-size:12.5px;display:flex;align-items:center;gap:6px">
            <span>➕</span> Add Milestone
          </button>
        ` : ''}
      </div>
      <div style="flex:1;padding:20px;display:flex;flex-direction:column;gap:15px;overflow-y:auto" id="chat-messages-scroll">${msgHtml}</div>
      <div style="padding:15px;background:white;border-top:1px solid var(--border);display:flex;gap:10px">
        <input id="chat-input" type="text" placeholder="Write a message..." style="flex:1;padding:10px;border:1px solid var(--border);border-radius:8px" onkeydown="if(event.key==='Enter')sendMsg()">
        <button class="btn btn-g" onclick="sendMsg()">Send</button>
      </div>
    `;
    const scroll = document.getElementById('chat-messages-scroll');
    if(scroll) scroll.scrollTop = scroll.scrollHeight;
  }

  function filterConversations(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.msg-item').forEach(item => {
      const text = item.innerText.toLowerCase();
      item.style.display = text.includes(q) ? 'block' : 'none';
    });
  }

  function initPage() {
    if (typeof restoreActiveTracker === 'function') {
      restoreActiveTracker();
    }
    const params = new URLSearchParams(window.location.search);
    if (params.get('verified') === 'email') {
      toast('Email verified', 'You can now apply to jobs after identity verification.');
    }
    
    // Handle Paystack payment callbacks
    if (params.get('payment') === 'success') {
      const connects = params.get('connects') || 0;
      setTimeout(() => {
        toast('Payment Successful! 🎉', `${connects} connects have been successfully added to your account.`);
        loadConnectsPageData();
      }, 500);
      
      // Clear query params from URL and navigate to connects page
      const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '#connects';
      window.history.pushState({path:newurl}, '', newurl);
      showPage('connects');
    } else if (params.get('payment') === 'failed') {
      setTimeout(() => {
        toast('Payment Failed ❌', 'Your purchase was cancelled or failed.');
        loadConnectsPageData();
      }, 500);
      
      const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '#connects';
      window.history.pushState({path:newurl}, '', newurl);
      showPage('connects');
    } else {
      const hash = window.location.hash.replace('#', '');
      showPage(hash || 'home');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initPage, 0));
  } else {
    setTimeout(initPage, 0);
  }

  window.addEventListener('hashchange', () => {
    const hash = window.location.hash.replace('#', '');
    showPage(hash || 'home');
  });
async function requestMilestone(milestoneId, btn) {
    if (!confirm('Are you sure you want to request completion for this milestone?')) return;
    
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Requesting...';

    try {
        const res = await fetch(BASE_URL + 'freelancer/api/request-milestone.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ milestone_id: milestoneId })
        });
        const data = await res.json();
        if (data.success) {
            toast('Success', 'Request sent to client');
            // Update UI locally
            btn.parentElement.innerHTML = '<span class="badge" style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:4px; font-size:11px">Under Review</span>';
            
            // Also update the global CONTRACTS object so if they reopen modal it stays
            CONTRACTS.forEach(c => {
                if (c.milestones) {
                    c.milestones.forEach(m => {
                        if (m.id == milestoneId) m.status = 'requested';
                    });
                }
            });
        } else {
            toast('Error', data.message);
            btn.disabled = false;
            btn.innerText = originalText;
        }
    } catch (err) {
        toast('Error', 'Request failed');
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

async function releasePendingPayment(paymentId, btn) {
    if (!confirm('Are you sure you want to clear this hold and move funds to your available balance?')) return;
    
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Clearing...';

    try {
        const res = await fetch(BASE_URL + 'freelancer/api/clear-hold.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ payment_id: paymentId })
        });
        const data = await res.json();
        if (data.success) {
            toast('Success! 🎉', 'Hold cleared successfully.');
            // Reload page to reflect stats and available balance
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            toast('Error', data.message);
            btn.disabled = false;
            btn.innerText = originalText;
        }
    } catch (err) {
        toast('Error', 'Action failed');
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

// Dedicated Connects Page Helpers
window.loadConnectsPageData = function() {
  const tbody = document.getElementById('connects-history-tbody');
  if (tbody) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted)">Loading connects history...</td></tr>`;
  }
  
  fetch(BASE_URL + 'freelancer/api/get-connects-activity.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Update stats
        const countEl = document.getElementById('connects-page-count');
        if (countEl) countEl.textContent = data.connects + ' Connects';
        
        const progEl = document.getElementById('connects-page-progress');
        if (progEl) progEl.style.width = Math.min((data.connects / 200) * 100, 100) + '%';
        
        const infoEl = document.getElementById('connects-page-max-info');
        if (infoEl) infoEl.innerHTML = `<span>${data.connects} of 200 max connects</span><span>Monthly Refresh: +10</span>`;
        
        const balEl = document.getElementById('connects-page-balance');
        if (balEl) balEl.textContent = '$' + data.balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        window.freelancerAvailableBalance = data.balance;
        
        // Render history table
        if (tbody) {
          if (data.history && data.history.length > 0) {
            tbody.innerHTML = data.history.map(item => {
              const isPositive = item.amount > 0;
              const amtText = isPositive ? `+${item.amount}` : `${item.amount}`;
              const actionText = item.action.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
              const dateStr = new Date(item.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute:'2-digit'});
              
              let sourceBadge = '';
              if (item.action === 'purchase' && item.payment_method) {
                const methodLower = item.payment_method.toLowerCase();
                if (methodLower.includes('paystack')) {
                  sourceBadge = `
                    <div style="display:inline-flex;align-items:center;gap:4px;background:#e0f2fe;color:#0369a1;padding:3px 6px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;margin-top:4px">
                      <span>💳</span> Paystack
                    </div>
                  `;
                } else {
                  sourceBadge = `
                    <div style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#15803d;padding:3px 6px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;margin-top:4px">
                      <span>💼</span> Wallet
                    </div>
                  `;
                }
              }

              return `
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:14px 16px;font-size:13px;color:var(--muted)">${dateStr}</td>
                  <td style="padding:14px 16px;font-size:13.5px;font-weight:700;color:var(--dark)">
                    <div>${item.description}</div>
                    ${sourceBadge}
                  </td>
                  <td style="padding:14px 16px;font-size:13px;color:var(--muted)">${actionText}</td>
                  <td style="padding:14px 16px;font-size:13.5px;font-weight:800;text-align:right;color:${isPositive ? 'var(--g)' : '#ef4444'}">${amtText}</td>
                </tr>
              `;
            }).join('');
          } else {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--muted);font-size:13.5px">No connects history recorded yet.</td></tr>`;
          }
        }
      } else {
        toast('Error', data.message);
      }
    })
    .catch(err => {
      console.error(err);
      toast('Error', 'Failed to fetch connects details.');
    });
}

window.selectConnectPack = function(qty, price, element) {
  document.querySelectorAll('.connect-pack-btn').forEach(b => {
    b.style.cssText = 'border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s';
  });
  element.style.cssText = 'border:2px solid var(--g);border-radius:10px;padding:11px 5px;text-align:center;cursor:pointer;background:var(--gl);color:var(--forest)';
  
  document.getElementById('custom-connects-qty').value = '';
  
  window.selectedConnectsQty = qty;
  window.selectedConnectsPrice = price;
  
  document.getElementById('connects-purchase-summary').textContent = qty + ' Connects = $' + price.toFixed(2);
  document.getElementById('btn-buy-connects-submit').disabled = false;
}

window.calculateCustomConnects = function(val) {
  document.querySelectorAll('.connect-pack-btn').forEach(b => {
    b.style.cssText = 'border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s';
  });
  
  const qty = parseInt(val || 0);
  if (qty <= 0) {
    window.selectedConnectsQty = 0;
    window.selectedConnectsPrice = 0;
    document.getElementById('connects-purchase-summary').textContent = '0 Connects = $0.00';
    document.getElementById('btn-buy-connects-submit').disabled = true;
    return;
  }
  
  const price = qty * 0.15; // $0.15 per Connect
  window.selectedConnectsQty = qty;
  window.selectedConnectsPrice = price;
  
  document.getElementById('connects-purchase-summary').textContent = qty + ' Connects = $' + price.toFixed(2);
  document.getElementById('btn-buy-connects-submit').disabled = false;
}

window.selectConnectPaymentMethod = function(method) {
  window.selectedConnectPaymentMethod = method;
  const w = document.getElementById('connect-method-wallet');
  const c = document.getElementById('connect-method-card');
  const cardForm = document.getElementById('connects-card-form');
  
  if (method === 'wallet') {
    w.style.cssText = 'border:2px solid var(--g);border-radius:10px;padding:11px;cursor:pointer;text-align:center;background:var(--gl)';
    w.querySelector('div:nth-child(2)').style.color = 'var(--g)';
    c.style.cssText = 'border:1px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;text-align:center;background:white';
    c.querySelector('div:nth-child(2)').style.color = 'var(--dark)';
    if (cardForm) cardForm.style.display = 'none';
  } else {
    c.style.cssText = 'border:2px solid var(--g);border-radius:10px;padding:11px;cursor:pointer;text-align:center;background:var(--gl)';
    c.querySelector('div:nth-child(2)').style.color = 'var(--g)';
    w.style.cssText = 'border:1px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;text-align:center;background:white';
    w.querySelector('div:nth-child(2)').style.color = 'var(--dark)';
    if (cardForm) cardForm.style.display = 'block';
  }
}

window.submitConnectsPurchase = function() {
  const qty = window.selectedConnectsQty || 0;
  const price = window.selectedConnectsPrice || 0;
  const method = window.selectedConnectPaymentMethod || 'wallet';
  
  if (qty <= 0 || price <= 0) {
    window.toast('Error', 'Please select a package first.');
    return;
  }
  
  if (method === 'wallet' && window.freelancerAvailableBalance < price) {
    window.toast('Insufficient Balance', `Your wallet balance ($${window.freelancerAvailableBalance.toFixed(2)}) is less than the package price ($${price.toFixed(2)}). Please select Credit Card instead!`);
    return;
  }
  
  const btn = document.getElementById('btn-buy-connects-submit');
  const originalText = btn.innerText;
  btn.disabled = true;
  btn.innerText = method === 'card' ? 'Redirecting to Paystack...' : 'Processing Payment...';
  
  fetch(BASE_URL + 'freelancer/api/buy-connects.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      amount: qty,
      price: price,
      payment_method: method
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (data.redirect && data.authorization_url) {
        window.toast('Redirecting...', 'Taking you to Paystack secure payment page');
        window.location.href = data.authorization_url;
        return;
      }
      
      window.toast('Success! 🎉', data.message);
      
      // Update sidebar connects counters instantly
      const sbVal = document.getElementById('sb-connects-val');
      if (sbVal) sbVal.textContent = data.new_connects;
      
      const navConn = document.getElementById('nav-connects');
      if (navConn) navConn.innerHTML = `<span class="sb-ico">🔗</span>Connects (${data.new_connects})`;
      
      // Reset inputs & fields
      document.getElementById('custom-connects-qty').value = '';
      document.getElementById('connects-purchase-summary').textContent = '0 Connects = $0.00';
      
      // Reload stats and history
      loadConnectsPageData();
      
      // Select default wallet method
      selectConnectPaymentMethod('wallet');
    } else {
      window.toast('Error', data.message);
    }
  })
  .catch(err => {
    console.error(err);
    window.toast('Error', 'Payment processing failed.');
  })
  .finally(() => {
    btn.disabled = false;
    btn.innerText = originalText;
  });
}

// Setup input formatting listeners
window.setupConnectsCardFormatters = function() {
  const nameInput = document.getElementById('connects-card-name');
  const numInput = document.getElementById('connects-card-number');
  const expInput = document.getElementById('connects-card-expiry');
  const cvvInput = document.getElementById('connects-card-cvv');
  
  if (nameInput) {
    nameInput.addEventListener('input', function(e) {
      // Allow only letters and spaces
      e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '');
    });
  }
  
  if (numInput) {
    numInput.addEventListener('input', function(e) {
      let val = e.target.value.replace(/\D/g, '');
      let formatted = '';
      for (let i = 0; i < val.length; i++) {
        if (i > 0 && i % 4 === 0) formatted += ' ';
        formatted += val[i];
      }
      e.target.value = formatted.substring(0, 19); // Max 16 digits + 3 spaces
    });
  }
  
  if (expInput) {
    expInput.addEventListener('input', function(e) {
      let val = e.target.value.replace(/\D/g, '');
      if (val.length >= 2) {
        e.target.value = val.substring(0, 2) + '/' + val.substring(2, 4);
      } else {
        e.target.value = val;
      }
    });
    
    expInput.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && e.target.value.endsWith('/')) {
        e.preventDefault();
        e.target.value = e.target.value.slice(0, -1);
      }
    });
  }
  
  if (cvvInput) {
    cvvInput.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
    });
  }
};

// Select default payment method and setup listeners on init
setTimeout(() => {
  if (document.getElementById('connect-method-wallet')) {
    selectConnectPaymentMethod('wallet');
  }
  setupConnectsCardFormatters();
}, 100);
</script>


<!-- Skill Selector Overlay -->
<div id="skill-overlay" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
  <div style="background:white;border-radius:14px;width:min(820px,96vw);max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden">
    <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <div>
        <div style="font-size:16px;font-weight:700">Add Skills</div>
        <div style="font-size:12.5px;color:var(--muted);margin-top:2px">Browse by category · up to 15 skills</div>
      </div>
      <button onclick="closeSkillSelector()" style="background:none;border:none;font-size:22px;color:var(--muted);cursor:pointer;line-height:1;padding:4px">×</button>
    </div>
    <div style="padding:12px 22px;border-bottom:1px solid var(--border);flex-shrink:0">
      <input id="skill-search" type="text" placeholder="Search skills (e.g. Python, Logo Design, SEO…)"
        style="width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none"
        oninput="filterSkills(this.value)">
    </div>
    <div style="display:grid;grid-template-columns:190px 210px 1fr;flex:1;overflow:hidden;min-height:0">
      <div id="cat-col" style="border-right:1px solid var(--border);overflow-y:auto;padding:8px 0"></div>
      <div id="subcat-col" style="border-right:1px solid var(--border);overflow-y:auto;padding:8px 0"></div>
      <div id="skill-col" style="overflow-y:auto;padding:12px 16px"></div>
    </div>
    <div style="padding:14px 22px;border-top:1px solid var(--border);flex-shrink:0;background:var(--off)">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <div style="font-size:12px;color:var(--muted);font-weight:600;white-space:nowrap">Selected:</div>
        <div id="selected-preview" style="display:flex;flex-wrap:wrap;gap:5px;flex:1"></div>
        <button onclick="saveSkills()" class="btn btn-g" style="padding:9px 20px;white-space:nowrap">Save Skills</button>
      </div>
    </div>
  </div>
</div>
<!-- ══ DYNAMIC DIRECT WORK SUBMISSION ══ -->
<script>
window.openDirectSubmissionModal = function(contractId, totalAmount) {
  const container = document.getElementById('milestone-list-detail');
  container.innerHTML = `
    <div style="background:#fff; border:1.5px dashed var(--g); border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.04); text-align:left">
      <h4 style="font-weight:700; font-size:14px; margin-bottom:12px; color:var(--dark)">Submit Work for Contract</h4>
      <form id="direct-submit-form" onsubmit="submitDirectWork(event, ${contractId})">
        <div class="fg" style="margin-bottom:12px">
          <label style="display:block; font-size:12px; font-weight:700; margin-bottom:6px; color:var(--dark)">Work Description / Note</label>
          <textarea id="direct-submit-note" placeholder="Describe the work you've completed for this contract..." required style="width:100%; padding:10px; border:1.5px solid var(--border); border-radius:8px; font-size:13px; font-family:inherit; min-height:80px; outline:none; resize:vertical"></textarea>
        </div>
        
        <div class="fg" style="margin-bottom:15px">
          <label style="display:block; font-size:12px; font-weight:700; margin-bottom:6px; color:var(--dark)">Requested Amount ($)</label>
          <input type="number" id="direct-submit-amount" value="${totalAmount}" min="1" max="${totalAmount}" required style="width:100%; padding:10px; border:1.5px solid var(--border); border-radius:8px; font-size:13px; font-family:inherit; outline:none">
          <span style="font-size:11px; color:#999; margin-top:4px; display:block">Maximum request: $${totalAmount.toLocaleString()}</span>
        </div>

        <div style="display:flex; gap:10px; margin-top:15px">
          <button type="submit" class="btn btn-g" style="padding:10px 18px; font-size:13px">Submit to Client</button>
          <button type="button" class="btn btn-w" style="padding:10px 18px; font-size:13px; color:#ef4444; border-color:#fecaca" onclick="cancelDirectSubmission(${contractId}, ${totalAmount})">Cancel</button>
        </div>
      </form>
    </div>
  `;
}

window.cancelDirectSubmission = function(contractId, totalAmount) {
  const container = document.getElementById('milestone-list-detail');
  container.innerHTML = `
    <div style="color:#666; font-size:14px; text-align:center; padding:25px; border:1.5px dashed #ccc; border-radius:12px; background:#fff">
      <div style="margin-bottom:12px; font-weight:600; color:#374151">No milestones defined for this contract.</div>
      <button class="btn btn-g" style="margin: 0 auto" onclick="openDirectSubmissionModal(${contractId}, ${totalAmount})">Submit Work & Request Payment</button>
    </div>
  `;
}

window.submitDirectWork = function(event, contractId) {
  event.preventDefault();
  const noteVal = document.getElementById('direct-submit-note').value;
  const amountVal = document.getElementById('direct-submit-amount').value;

  fetch(BASE_URL + 'freelancer/api/submit-direct-work.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      contract_id: contractId,
      description: noteVal,
      amount: amountVal
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      toast('Success', data.message || 'Work submitted successfully!');
      closeModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      toast('Error', data.error || 'Failed to submit work.');
    }
  })
  .catch(err => {
    console.error(err);
    toast('Error', 'Failed to submit work.');
  });
}

window.submitNewMilestone = async function(clientId) {
  const contractSelect = document.getElementById('ms-contract-id');
  if (!contractSelect) return;
  const contractId = contractSelect.value;
  const desc = document.getElementById('ms-desc').value.trim();
  const amountStr = document.getElementById('ms-amt').value;
  const amount = parseFloat(amountStr);

  if (!desc) {
    toast('Error', 'Please enter a description for the milestone.');
    return;
  }

  if (isNaN(amount) || amount <= 0) {
    toast('Error', 'Please enter a valid amount greater than $0.');
    return;
  }

  const btn = document.getElementById('btn-submit-ms');
  btn.disabled = true;
  btn.innerText = 'Submitting...';

  try {
    const response = await fetch(BASE_URL + 'freelancer/api/add-milestone.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        contract_id: contractId,
        description: desc,
        amount: amount
      })
    });
    const result = await response.json();

    if (result.success) {
      toast('Success 🎉', result.message);
      closeModal();

      // Dynamically add the milestone to the local CONTRACTS list in memory
      CONTRACTS.forEach(c => {
        if (c.id == contractId) {
          if (!c.milestones) c.milestones = [];
          c.milestones.push(result.milestone);
        }
      });

      // Proactively send a system/chat message about the newly added milestone
      const msg = `PROPOSED MILESTONE: I have proposed a new milestone of $${amount.toLocaleString()} for: "${desc}". Please approve and fund this milestone from your dashboard.`;
      
      // Use sendMsg logic but post it directly
      const formData = new FormData();
      formData.append('receiver_id', clientId);
      formData.append('message', msg);
      
      await fetch(BASE_URL + 'actions/send_message.php', {
        method: 'POST',
        body: formData
      });

      // If chat is open, reload messages
      if (activeChatId == clientId) {
        const chatName = document.querySelector('.msg-item.active div[style*="font-weight:700"]')?.innerText || 'Client';
        const chatInitials = document.querySelector('.msg-item.active .av')?.innerText || 'CL';
        loadChat(clientId, chatName, chatInitials);
      }
    } else {
      toast('Error', result.message);
      btn.disabled = false;
      btn.innerText = 'Propose Milestone →';
    }
  } catch(err) {
    toast('Error', 'Failed to propose milestone.');
    btn.disabled = false;
    btn.innerText = 'Propose Milestone →';
  }
}

async function loadReviewStatus(contractId) {
  const container = document.getElementById('freelancer-review-status-box');
  if (!container) return;

  try {
    const res = await fetch(BASE_URL + 'freelancer/api/get-review-status.php?contract_id=' + contractId);
    const data = await res.json();
    
    if (data.success) {
      let html = '';
      
      // Client review to Freelancer
      if (data.client_review) {
        const ratingStars = '⭐'.repeat(Math.round(data.client_review.rating));
        html += `
          <div style="background:white; border:1px solid #ddd; padding:15px; border-radius:10px; margin-bottom:15px">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
              <span style="font-weight:700; font-size:13px; color:var(--dark)">Feedback Given By Client</span>
              <span style="font-size:12px; font-weight:700; color:#b45309">${ratingStars} ${data.client_review.rating} / 5.0</span>
            </div>
            <p style="font-size:12.5px; color:#4b5563; margin:0; line-height:1.5; font-style:italic">
              "${data.client_review.feedback || 'No written feedback left.'}"
            </p>
          </div>
        `;
      } else {
        html += `
          <div style="background:#fffbeb; border:1px solid #fde68a; padding:12px; border-radius:8px; margin-bottom:15px; font-size:12px; color:#b45309; text-align:center">
            ⏳ Client hasn't left feedback for you yet.
          </div>
        `;
      }

      // Freelancer review to Client
      if (data.freelancer_review) {
        const ratingStars = '⭐'.repeat(Math.round(data.freelancer_review.rating));
        html += `
          <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:10px">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
              <span style="font-weight:700; font-size:13px; color:#166534">Your Feedback for Client</span>
              <span style="font-size:12px; font-weight:700; color:#166534">${ratingStars} ${data.freelancer_review.rating} / 5.0</span>
            </div>
            <p style="font-size:12.5px; color:#15803d; margin:0; line-height:1.5; font-style:italic">
              "${data.freelancer_review.feedback}"
            </p>
          </div>
        `;
      } else {
        html += `
          <form id="freelancer-review-form" onsubmit="submitFreelancerReview(event, ${contractId})" style="display:flex; flex-direction:column; gap:12px; background:white; border:1px solid #ddd; padding:15px; border-radius:10px">
            <div style="font-weight:700; font-size:13px; color:var(--dark); margin-bottom:4px">Rate Your Experience with ${data.client_name}</div>
            
            <div style="display:flex; flex-direction:column; gap:10px">
              <div>
                <select name="rating" style="width:100%; padding:10px; border:1.5px solid #ccc; border-radius:8px; font-size:13px; outline:none" required>
                  <option value="5.0">⭐⭐⭐⭐⭐ 5.0 - Great experience</option>
                  <option value="4.0">⭐⭐⭐⭐ 4.0 - Good</option>
                  <option value="3.0">⭐⭐⭐ 3.0 - Satisfactory</option>
                  <option value="2.0">⭐⭐ 2.0 - Difficult</option>
                  <option value="1.0">⭐ 1.0 - Unacceptable</option>
                </select>
              </div>
              <div>
                <textarea name="feedback" placeholder="Write public feedback about this client..." style="width:100%; min-height:80px; padding:12px; border:1.5px solid #ccc; border-radius:8px; font-size:13px; font-family:inherit; outline:none; resize:vertical" required></textarea>
              </div>
            </div>
            
            <button type="submit" class="btn btn-g" style="align-self:flex-start; padding:10px 20px; font-size:13px; font-weight:700; border-radius:8px">
              Submit Review Feedback
            </button>
          </form>
        `;
      }

      container.innerHTML = html;
    } else {
      container.innerHTML = `<div style="color:red; font-size:12px; text-align:center">${data.error || 'Failed to load status'}</div>`;
    }
  } catch(err) {
    container.innerHTML = `<div style="color:red; font-size:12px; text-align:center">Error loading reviews</div>`;
  }
}

async function submitFreelancerReview(event, contractId) {
  event.preventDefault();
  const form = event.target;
  const btn = form.querySelector('button[type="submit"]');
  const originalText = btn.innerText;
  
  btn.disabled = true;
  btn.innerText = 'Submitting...';
  
  const formData = new FormData(form);
  formData.append('contract_id', contractId);
  
  try {
    const res = await fetch(BASE_URL + 'freelancer/api/submit-review.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      toast('Success! 🎉', data.message);
      loadReviewStatus(contractId);
    } else {
      toast('Error', data.error || 'Submission failed');
      btn.disabled = false;
      btn.innerText = originalText;
    }
  } catch(err) {
    toast('Error', 'Communication failed');
    btn.disabled = false;
    btn.innerText = originalText;
  }
}

window.openFreelancerCompleteModal = function(contractId) {
  const c = CONTRACTS.find(ct => ct.id == contractId);
  if (!c) return;

  const modal = document.getElementById('modal');
  const mc = document.getElementById('mc-body');
  
  document.getElementById('mh-title').innerText = "End Contract & Rate Client";
  modal.style.maxWidth = '550px';

  mc.innerHTML = `
    <div style="padding:25px">
      <div style="text-align:center;margin-bottom:20px">
        <div style="font-size:36px;margin-bottom:8px">🎉</div>
        <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin-bottom:6px">Congratulations on finishing your project!</h3>
        <p style="font-size:13px;color:var(--muted2);line-height:1.5">Completing this contract changes its status to "completed" and archives it. Please rate your experience with <strong>${c.client_name}</strong> to finalize.</p>
      </div>

      <form id="freelancer-complete-form" onsubmit="submitFreelancerComplete(event, ${contractId})">
        <!-- Star Selection -->
        <div style="margin-bottom:20px;text-align:center">
          <label style="display:block;font-size:12.5px;font-weight:700;color:var(--dark);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Your Rating for Client</label>
          <div style="display:inline-flex;flex-direction:row-reverse;justify-content:center;gap:8px" class="star-rating-selector">
            <input type="radio" id="star5" name="rating" value="5" required style="display:none" checked><label for="star5" onclick="setSelectStars(5)" style="font-size:32px;color:#fbbf24;cursor:pointer;transition:transform .15s">★</label>
            <input type="radio" id="star4" name="rating" value="4" style="display:none"><label for="star4" onclick="setSelectStars(4)" style="font-size:32px;color:#fbbf24;cursor:pointer;transition:transform .15s">★</label>
            <input type="radio" id="star3" name="rating" value="3" style="display:none"><label for="star3" onclick="setSelectStars(3)" style="font-size:32px;color:#fbbf24;cursor:pointer;transition:transform .15s">★</label>
            <input type="radio" id="star2" name="rating" value="2" style="display:none"><label for="star2" onclick="setSelectStars(2)" style="font-size:32px;color:#fbbf24;cursor:pointer;transition:transform .15s">★</label>
            <input type="radio" id="star1" name="rating" value="1" style="display:none"><label for="star1" onclick="setSelectStars(1)" style="font-size:32px;color:#fbbf24;cursor:pointer;transition:transform .15s">★</label>
          </div>
          <div id="star-desc" style="font-size:12.5px;font-weight:600;color:#b45309;margin-top:6px">Excellent (5.0 / 5.0)</div>
        </div>

        <!-- Feedback comment -->
        <div class="fg" style="margin-bottom:20px">
          <label style="display:block;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--dark)">Write a Public Review for Client</label>
          <textarea name="feedback" required placeholder="Share your experience working with this client. Was communication clear? Were requirements defined and milestones funded promptly?" style="width:100%;padding:12px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:13.5px;outline:none;min-height:100px;resize:vertical" onfocus="this.style.borderColor='var(--g)'" onblur="this.style.borderColor='var(--border)'"></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:25px">
          <button type="submit" id="btn-submit-complete" class="btn btn-g" style="flex:1.5;justify-content:center;padding:12px;font-size:14.5px;font-weight:700">Submit Review & End Contract</button>
          <button type="button" class="btn btn-w" onclick="closeModal()" style="flex:1;justify-content:center;padding:12px;font-size:14.5px">Cancel</button>
        </div>
      </form>
    </div>
  `;

  if (!document.getElementById('star-selector-style')) {
    const s = document.createElement('style');
    s.id = 'star-selector-style';
    s.innerHTML = `
      .star-rating-selector label:hover {
        transform: scale(1.25);
      }
    `;
    document.head.appendChild(s);
  }
};

window.setSelectStars = function(stars) {
  const labels = ['Waste of time', 'Poor experience', 'Average experience', 'Great to work with!', 'Excellent'];
  const desc = document.getElementById('star-desc');
  if (desc) {
    desc.innerText = `${labels[stars - 1]} (${stars}.0 / 5.0)`;
  }
};

window.submitFreelancerComplete = async function(event, contractId) {
  event.preventDefault();
  const btn = document.getElementById('btn-submit-complete');
  const form = document.getElementById('freelancer-complete-form');
  if (!btn || !form) return;

  const originalText = btn.innerText;
  btn.disabled = true;
  btn.innerText = 'Closing contract...';

  const formData = new FormData(form);
  formData.append('contract_id', contractId);

  try {
    const res = await fetch(BASE_URL + 'freelancer/api/complete-contract.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      toast('Contract Ended! 🎓', data.message);
      closeModal();
      setTimeout(() => {
        window.location.reload();
      }, 1200);
    } else {
      toast('Error', data.error || 'Failed to complete contract');
      btn.disabled = false;
      btn.innerText = originalText;
    }
  } catch(err) {
    toast('Error', 'Communication failed');
    btn.disabled = false;
    btn.innerText = originalText;
  }
};
</script>
</body>
</html>
