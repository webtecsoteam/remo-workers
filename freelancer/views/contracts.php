<!-- CONTRACTS -->
<div class="page" id="page-contracts">
  <div style="font-size:20px;font-weight:700;margin-bottom:14px">My Contracts</div>
  <div class="tab-bar">
    <div class="tab on" onclick="setTab(this)">Active</div>
    <div class="tab" onclick="setTab(this)">Completed</div>
    <div class="tab" onclick="setTab(this)">Milestones</div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Active Contracts (<?php echo count($activeContracts); ?>)</h3></div>
    <div class="card-body" style="padding:0">
      <?php if(empty($activeContracts)): ?>
        <div style="text-align:center;padding:40px;color:var(--muted)">No active contracts at the moment.</div>
      <?php else: ?>
        <?php foreach($activeContracts as $c): ?>
          <div class="contract-row" style="padding:18px 22px;border-bottom:1px solid var(--border)" onclick="openModal('contract-detail')">
            <div style="display:flex;gap:15px;align-items:center">
              <div class="av" style="background:var(--gl);color:var(--g);width:40px;height:40px;font-size:13px"><?php echo strtoupper(substr($c['client_name'], 0, 1)); ?></div>
              <div>
                <div style="font-weight:700;font-size:15px;margin-bottom:3px"><?php echo htmlspecialchars($c['job_title']); ?></div>
                <div style="font-size:12.5px;color:var(--muted)"><?php echo htmlspecialchars($c['client_name']); ?> · Since <?php echo date('M j, Y', strtotime($c['created_at'])); ?></div>
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:700;font-size:15px;color:var(--dark)">$<?php echo number_format($c['amount']); ?></div>
              <div style="font-size:11px;color:var(--g);font-weight:600">Active Now</div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
