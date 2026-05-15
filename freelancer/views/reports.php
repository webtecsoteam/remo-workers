<!-- REPORTS -->
<div class="page" id="page-reports">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <div style="font-size:20px;font-weight:700">Weekly Summary</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w btn-sm" onclick="toast('Export','PDF downloading...')">Download PDF</button>
      <button class="btn btn-w btn-sm" onclick="toast('Date','Select range')">May 9 – May 15, 2026 ▾</button>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:25px;text-align:center">
      <div style="font-size:13px;color:var(--muted);margin-bottom:5px">Total hours this week</div>
      <div style="font-size:36px;font-weight:800;color:var(--g)">34.5 hrs</div>
      <div style="font-size:13px;color:var(--muted);margin-top:5px">Across 3 active contracts</div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Daily Breakdown</h3></div>
    <div class="card-body">
      <div id="reports-chart" style="height:200px;display:flex;align-items:flex-end;gap:15px;padding:20px 10px 10px">
        <!-- Bars will be added via JS -->
      </div>
      <div style="display:flex;justify-content:space-between;padding:0 10px;margin-top:10px">
        <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
          <div style="font-size:12px;color:var(--muted);width:30px;text-align:center"><?php echo $day; ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
