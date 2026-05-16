<!-- FIND WORK -->
<div class="page" id="page-find-work">
  <div style="font-size:20px;font-weight:700;margin-bottom:14px">Find Work</div>
  <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <input type="text" placeholder="Search jobs by keyword, skill, or category…" style="flex:1;min-width:240px;padding:9px 13px;border:1px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none">
    <select style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:white;outline:none" onchange="toast('Filter','Results filtered')"><option>Any Experience Level</option><option>Entry</option><option>Intermediate</option><option>Expert</option></select>
    <select style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:white;outline:none" onchange="toast('Filter','Results filtered')"><option>Any Job Type</option><option>Fixed Price</option><option>Hourly</option></select>
    <select style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:white;outline:none" onchange="toast('Filter','Results filtered')"><option>Any Budget</option><option>$1–$500</option><option>$500–$5,000</option><option>$5,000+</option></select>
    <button class="btn btn-g" onclick="toast('Search','Showing matching jobs')">Search</button>
  </div>
  
  <div class="tab-bar">
    <div class="tab on" onclick="setTab(this)">Best Matches</div>
    <div class="tab" onclick="setTab(this)">Most Recent</div>
    <div class="tab" onclick="setTab(this)">Saved Jobs (<?php echo count($savedJobs); ?>)</div>
  </div>

  <div id="findwork-job-list">
    <?php if(empty($allJobs)): ?>
      <div style="text-align:center;padding:40px;color:var(--muted)">No jobs found matching your criteria.</div>
    <?php else: ?>
      <!-- Jobs will be rendered by JS for searchability, but we can also pre-render for SEO -->
      <div style="text-align:center;padding:20px;">Loading jobs...</div>
    <?php endif; ?>
  </div>
</div>
