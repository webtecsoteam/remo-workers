<!-- HOME -->
<div class="page active" id="page-home">
  <!-- Mobile greeting -->
  <div style="padding:16px 16px 0;display:none" class="mob-greeting">
    <div style="font-size:18px;font-weight:700;margin-bottom:2px">Good morning, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?> 👋</div>
    <div style="font-size:13px;color:var(--muted)">Here's your work overview</div>
  </div>

  <?php if (!($user['is_verified'] ?? false)): ?>
    <!-- Verification Banner -->
    <div style="background:#fff8e6;border:1px solid #ffeeba;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:15px">
        <div style="font-size:24px">🪪</div>
        <div>
          <div style="font-weight:700;font-size:14.5px;color:#854d0e">Verify your identity</div>
          <div style="font-size:12.5px;color:#92400e">Unlock full platform features and build trust with clients.</div>
        </div>
      </div>
      <button class="btn" style="background:#f59e0b;color:white;border:none;flex-shrink:0" onclick="showPage('verification')">Verify Now →</button>
    </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stat-row">
    <div class="stat-c" onclick="showPage('earnings')">
      <div class="stat-label">Total Earnings <span>💰</span></div>
      <div class="stat-val">$<?php echo number_format((float)($fStats['total_earned'] ?? 0)); ?></div>
      <div class="stat-sub up">Lifetime earnings</div>
    </div>
    <div class="stat-c" onclick="showPage('contracts')">
      <div class="stat-label">Active Contracts <span>🤝</span></div>
      <div class="stat-val"><?php echo $fStats['active_contracts']; ?></div>
      <div class="stat-sub">Working now</div>
    </div>
    <div class="stat-c" onclick="showPage('proposals')">
      <div class="stat-label">Pending Proposals <span>✉️</span></div>
      <div class="stat-val"><?php echo $fStats['pending_proposals']; ?></div>
      <div class="stat-sub">Waiting for response</div>
    </div>
  </div>

  <div class="g2">
    <!-- Active Contracts Column -->
    <div class="card">
      <div class="card-head">
        <h3>My Active Contracts</h3>
        <a onclick="showPage('contracts')">View All</a>
      </div>
      <div class="card-body">
        <?php if(empty($activeContracts)): ?>
          <div style="text-align:center;padding:20px;color:var(--muted);font-size:13px">You don't have any active contracts.</div>
        <?php else: ?>
          <?php foreach($activeContracts as $c): ?>
            <div class="contract-row" onclick="openModal('contract-detail')">
              <div>
                <div style="font-weight:700;font-size:13.5px"><?php echo htmlspecialchars($c['job_title']); ?></div>
                <div style="font-size:11.5px;color:var(--muted);margin-top:2px"><?php echo htmlspecialchars($c['client_name']); ?> · <?php echo ucfirst($c['contract_type']); ?></div>
              </div>
              <div style="text-align:right">
                <div style="font-weight:700;color:var(--g)">$<?php echo number_format($c['amount']); ?></div>
                <div style="font-size:10px;color:var(--muted)">Budget</div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recommended Jobs Column -->
    <div class="card">
      <div class="card-head">
        <h3>Best Matches for You</h3>
        <a onclick="showPage('find-work')">Search Jobs</a>
      </div>
      <div class="card-body" id="home-job-list">
        <div style="text-align:center;padding:20px;">Loading matches...</div>
      </div>
    </div>
  </div>

  <!-- Earnings Chart -->
  <div class="card" style="margin-top:20px">
    <div class="card-head">
      <h3>Earnings Overview</h3>
      <div style="font-size:13px;color:var(--muted)">May 2026</div>
    </div>
    <div class="card-body">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <div>
          <div style="font-size:22px;font-weight:700;margin-bottom:4px">$<?php echo number_format((float)($fStats['monthly_earnings'] ?? 0)); ?> <span style="font-size:13px;font-weight:400;color:var(--muted)"><?php echo date('F Y'); ?></span></div>
          <div style="font-size:12.5px;color:var(--g);font-weight:600">↑ 12% from last month</div>
        </div>
        <button class="btn btn-w btn-sm" onclick="showPage('earnings')">Full Report</button>
      </div>
      <div class="chart-area" style="height:140px;background:linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);border-radius:8px;padding:15px;display:flex;flex-direction:column;justify-content:flex-end">
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
