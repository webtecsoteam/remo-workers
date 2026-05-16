<script>
  const JOBS = <?php echo json_encode($allJobs ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const SAVED_IDS = <?php echo json_encode(array_column($savedJobs ?? [], 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  let PROPOSALS = <?php echo json_encode($submittedProposals ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const CONTRACTS = <?php echo json_encode($allContracts ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const USER_CONNECTS = <?php echo $user['connects'] ?? 0; ?>;

  function showPage(id) {
    if (!id) id = 'home';
    
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(p => p.classList.remove('active'));
    
    // Show target page
    const pg = document.getElementById('page-' + id);
    if (pg) {
      pg.classList.add('active');
      window.scrollTo(0, 0);
      history.pushState(null, null, '#' + id);
    }
    
    // Update sidebar active state
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
    const navEl = document.getElementById('nav-' + id);
    if (navEl) navEl.classList.add('active');

    // Update mobile bottom nav sync
    document.querySelectorAll('.mob-nav-item').forEach(i => i.classList.remove('active'));
    const mobItem = document.querySelector(`.mob-nav-item[onclick*="'${id}'"]`);
    if(mobItem) mobItem.classList.add('active');
    
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
    if (id === 'profile') renderSuggestedSkills();
  }

  // Define all other functions here... (I will just remove the IIFE wrappers)

  window.showEarningsInfo = function(type) {
    const panel = document.getElementById('earnings-info-panel');
    if (!panel) return;
    
    const info = {
      'wip': '<strong>Work in Progress</strong>: These are hours you have logged during the current week that have not yet been billed to the client. This amount will move to "In Review" after the week ends (Sunday midnight UTC).',
      'review': '<strong>In Review</strong>: This includes hours from the previous week or completed milestones that the client is currently reviewing. Clients have 5 days to dispute hourly work.',
      'pending': '<strong>Pending</strong>: These are funds that have been approved by the client but are held for a standard 5-day security period before becoming available for withdrawal.',
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
    if (confirm(`Buy ${amount} Connects for $${price.toFixed(2)}?`)) {
      toast('Success', `Purchased ${amount} Connects!`);
      // Update UI or hit API
    }
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
      const statusClass = p.status === 'accepted' ? 'b-green' : (p.status === 'rejected' ? 'b-red' : 'b-purple');
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
              <span class="badge ${statusClass}" style="padding:6px 12px;border-radius:6px;text-transform:capitalize">${p.status}</span>
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
              <span class="badge ${statusClass}" style="padding:6px 12px;border-radius:6px;text-transform:capitalize">${p.status}</span>
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
          <input type="number" id="prop-rate" value="${job.budget || 0}" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:14px" oninput="updateMilestoneTotal()">
        </div>

        <div id="milestones-section" style="margin-bottom:25px;border:1px solid var(--border);padding:15px;border-radius:12px;background:#fafafa">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
            <label style="font-weight:700;font-size:14px">Milestones</label>
            <button class="btn btn-w btn-sm" onclick="addMilestoneRow()" style="font-size:12px;padding:4px 10px">+ Add Milestone</button>
          </div>
          <div id="milestones-list-container">
            <div class="milestone-row" style="display:grid;grid-template-columns:1fr 100px 30px;gap:10px;margin-bottom:10px">
              <input type="text" placeholder="Description (e.g. Initial Draft)" class="ms-desc" style="padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px">
              <input type="number" placeholder="Amount" class="ms-amount" value="${job.budget || 0}" style="padding:8px;border:1px solid var(--border);border-radius:6px;font-size:13px" oninput="updateMilestoneTotal()">
              <button onclick="this.parentElement.remove();updateMilestoneTotal()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:16px">×</button>
            </div>
          </div>
          <div style="font-size:12px;color:var(--muted2);margin-top:10px;display:flex;justify-content:space-between">
            <span>Total Milestone Amount: <strong id="ms-total-display">$${job.budget || 0}</strong></span>
            <span id="ms-warning" style="color:#ef4444;display:none">Doesn't match proposed rate!</span>
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
    const amounts = document.querySelectorAll('.ms-amount');
    let total = 0;
    amounts.forEach(a => total += parseFloat(a.value || 0));
    document.getElementById('ms-total-display').textContent = '$' + total.toLocaleString();
    
    const rate = parseFloat(document.getElementById('prop-rate').value || 0);
    const warning = document.getElementById('ms-warning');
    if (Math.abs(total - rate) > 0.01) {
      warning.style.display = 'inline';
    } else {
      warning.style.display = 'none';
    }
  }


  window.submitProposalForm = function(jobId) {
    const rate = document.getElementById('prop-rate').value;
    const days = document.getElementById('prop-days').value;
    const letter = document.getElementById('prop-letter').value;
    const attach = document.getElementById('prop-attach').value;

    if (!letter) return toast('Error', 'Please write a cover letter');

    // Collect milestones
    const milestones = [];
    const rows = document.querySelectorAll('.milestone-row');
    let msTotal = 0;
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

    if (Math.abs(msTotal - parseFloat(rate)) > 0.01) {
      return toast('Error', 'Milestone total ($' + msTotal.toFixed(2) + ') must match your proposed rate ($' + parseFloat(rate).toFixed(2) + ')');
    }

    const payload = {
        job_id: jobId,
        bid_amount: rate,
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
                      <button class="btn btn-g btn-sm" onclick="requestMilestone(${ms.id}, this)">Request Completion</button>
                    ` : (ms.status === 'requested' ? `
                      <span class="badge" style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:4px; font-size:11px">Requested</span>
                    ` : `
                      <span class="badge" style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:4px; font-size:11px">Paid</span>
                    `)}
                  </div>
                </div>
              `).join('')}
              ${(!c.milestones || c.milestones.length === 0) ? '<div style="color:#999; font-size:14px">No milestones defined.</div>' : ''}
            </div>
          </div>

          <!-- Log Time Section (for Hourly) -->
          <div id="hourly-log-section" style="margin-bottom:30px; display: ${c.contract_type === 'hourly' ? 'block' : 'none'}">
            <h3 style="font-size:16px;margin-bottom:15px;font-weight:700">Log Time</h3>
            <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;margin-bottom:15px">
              <div>
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:8px">Hours to Log</label>
                <input type="number" id="work-amount" value="" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px">
              </div>
              <div>
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:8px">Work Description</label>
                <input type="text" id="work-desc" placeholder="e.g. Mobile responsive screens" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px">
              </div>
            </div>
            <button class="btn btn-g" style="width:100%;padding:12px" onclick="logWork(currentContractId)">Log Hours</button>
          </div>

          <!-- Footer Buttons -->
          <div style="display:flex;gap:15px;border-top:1px solid #eee;padding-top:25px">
            <button class="btn" style="flex:1;justify-content:center;border:1px solid #ddd;background:white;color:#333;font-weight:600;padding:12px;border-radius:8px" onclick="showPage('messages');closeModal()">Message Client</button>
            <button class="btn" id="btn-pause-contract" style="flex:1;justify-content:center;background:#f3f4f6;color:#333;font-weight:600;padding:12px;border-radius:8px" onclick="toast('Pause','Feature coming soon')">Pause Contract</button>
            <button class="btn btn-w" style="flex:1;justify-content:center;padding:12px" onclick="closeModal()">Close</button>
          </div>
        </div>
      `;
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
      const c = <?php echo $user['connects'] ?? 0; ?>;
      const max = 200; 
      const pct = Math.min((c / max) * 100, 100);
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
          <div style="border-top:1px solid var(--border)">
            <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px">
              <div>Monthly Refresh</div>
              <div style="color:var(--g);font-weight:700">+10</div>
            </div>
            <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px">
              <div>Proposal: Web Design</div>
              <div style="color:#ef4444;font-weight:700">-6</div>
            </div>
          </div>
          <button class="btn btn-g" style="width:100%;margin-top:25px;justify-content:center" onclick="closeModal()">Close</button>
        </div>
      `;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
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

    const fd = new FormData();
    fd.append('name', name);
    fd.append('title', title);
    fd.append('hourly_rate', rate);
    fd.append('country', loc);
    fd.append('bio', bio);

    fetch(BASE_URL + 'actions/update_profile.php', {
      method: 'POST',
      body: fd
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', 'Profile updated successfully!');
        closeModal();
        // Update UI manually to avoid reload
        document.querySelector('#page-profile #field-title').textContent = title || 'Professional Specialist';
        document.querySelector('#page-profile #field-rate').textContent = '$' + parseFloat(rate).toFixed(2) + '/hr';
        document.querySelector('#page-profile #field-location').textContent = loc || 'Global';
        document.querySelector('#page-profile .sb-name')?.forEach(el => el.textContent = name); // Update sidebar if visible
        // Update sidebar user section too
        const sbName = document.querySelector('.sb-name');
        if(sbName) sbName.textContent = name;
        const sbAv = document.querySelector('.sb-av');
        if(sbAv) sbAv.textContent = name.substring(0,2).toUpperCase();
        
        // Also update the bio in the overview section
        const bioEl = document.querySelector('#page-profile .card-body div[style*="line-height: 1.7"]');
        if(bioEl) bioEl.innerHTML = bio.replace(/\n/g, '<br>');

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
      <div style="padding:15px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
        <div class="av" style="width:32px;height:32px;background:var(--gl);color:var(--forest)">${initials}</div>
        <div style="font-weight:700;font-size:14px">${name}</div>
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
      <div style="padding:15px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
        <div class="av" style="width:32px;height:32px;background:var(--gl);color:var(--forest)">${initials}</div>
        <div style="font-weight:700;font-size:14px">${name}</div>
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

  document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#', '');
    showPage(hash || 'home');
  });

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
            btn.parentElement.innerHTML = '<span class="badge" style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:4px; font-size:11px">Requested</span>';
            
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
</body>
</html>
