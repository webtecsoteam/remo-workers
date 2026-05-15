<script>
const BASE_URL = '<?php echo baseUrl(); ?>';
const JOBS = <?php echo json_encode($allJobs ?? []) ?: '[]'; ?>;

function toggleSidebar() {
  const sb = document.getElementById('main-sidebar');
  const ov = document.getElementById('mob-overlay');
  if(sb) sb.classList.toggle('mob-open');
  if(ov) ov.classList.toggle('open');
}

function showPage(id, navEl) {
  // Hide all pages
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  
  // Show target page
  const pg = document.getElementById('page-' + id);
  if (pg) {
    pg.classList.add('active');
    pg.scrollTop = 0;
  }
  
  // Update sidebar active state
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
  if (navEl) navEl.classList.add('active');
  
  // Update page title in top bar
  const titles = {
    'home': 'Dashboard',
    'find-work': 'Find Work',
    'proposals': 'My Proposals',
    'contracts': 'My Contracts',
    'messages': 'Messages',
    'earnings': 'Earnings',
    'catalog': 'My Services',
    'profile': 'My Profile',
    'reports': 'Payment Reports',
    'verification': 'ID Verification'
  };
  const titleEl = document.getElementById('page-title');
  if (titleEl) titleEl.textContent = titles[id] || id;

  // Close sidebar on mobile after clicking
  if (window.innerWidth <= 900) {
    const sb = document.getElementById('main-sidebar');
    const ov = document.getElementById('mob-overlay');
    if(sb) sb.classList.remove('mob-open');
    if(ov) ov.classList.remove('open');
  }
  // Re‑render job lists whenever a new page is shown (needed for Find Work)
  renderJobs();}

function openModal(id) {
  document.getElementById('overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('overlay').classList.remove('open');
  document.body.style.overflow = '';
}

function toast(title, msg) {
  const el = document.getElementById('toast');
  document.getElementById('t-title').textContent = title;
  document.getElementById('t-msg').textContent = msg ? (' — ' + msg) : '';
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 3500);
}

// Initial job rendering (simplified for now)
function renderJobs() {
  const list = document.getElementById('home-job-list');
  if (list) {
    list.innerHTML = JOBS.length > 0 
      ? JOBS.slice(0, 3).map(j => `<div class="job-row" onclick="toast('Job', '${j.title}')">
          <div style="font-weight:700;margin-bottom:4px">${j.title}</div>
          <div style="font-size:12px;color:var(--muted)">${j.client_name} · ${j.posted}</div>
        </div>`).join('')
      : '<div style="text-align:center;padding:20px;color:var(--muted)">No jobs found.</div>';
  }
  
  const flist = document.getElementById('findwork-job-list');
  if (flist) {
    flist.innerHTML = JOBS.length > 0
      ? JOBS.map(j => `<div class="job-row" onclick="toast('Job', '${j.title}')">
          <div style="font-weight:700;margin-bottom:4px">${j.title}</div>
          <div style="font-size:12.5px;margin-bottom:8px">${j.desc}</div>
          <div style="font-size:12px;color:var(--muted)">${j.client_name} · ${j.posted}</div>
        </div>`).join('')
      : '<div style="text-align:center;padding:20px;color:var(--muted)">No jobs found.</div>';
  }
}

// Initialize on load
window.onload = () => {
  renderJobs();
  showPage('home');
};
</script>
</body>
</html>
