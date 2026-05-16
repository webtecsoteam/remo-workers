<!-- CONTRACTS -->
<div class="page" id="page-contracts">
  <?php
    $allCount = count($allContracts);
    $activeCount = count(array_filter($allContracts, fn($c) => ($c['status'] ?? '') === 'active'));
    $completedCount = count(array_filter($allContracts, fn($c) => ($c['status'] ?? '') === 'completed'));
    $pausedCount = count(array_filter($allContracts, fn($c) => ($c['status'] ?? '') === 'paused'));
    $cancelledCount = count(array_filter($allContracts, fn($c) => ($c['status'] ?? '') === 'cancelled'));
  ?>
  <div style="font-size:22px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    My Contracts <span style="font-size:14px;color:var(--muted);font-weight:400">(<?php echo $activeCount; ?> active)</span>
  </div>

  <div class="tab-bar" style="border-bottom:1px solid #eee;margin-bottom:20px;gap:15px">
    <div class="tab" onclick="setTab(this)" style="padding-bottom:10px;font-weight:600">All (<?php echo $allCount; ?>)</div>
    <div class="tab on" onclick="setTab(this)" style="padding-bottom:10px;font-weight:600">Active (<?php echo $activeCount; ?>)</div>
    <div class="tab" onclick="setTab(this)" style="padding-bottom:10px;font-weight:600">Paused (<?php echo $pausedCount; ?>)</div>
    <div class="tab" onclick="setTab(this)" style="padding-bottom:10px;font-weight:600">Completed (<?php echo $completedCount; ?>)</div>
    <div class="tab" onclick="setTab(this)" style="padding-bottom:10px;font-weight:600">Cancelled (<?php echo $cancelledCount; ?>)</div>
  </div>


  <div class="card" style="border:1px solid #eee;border-radius:8px;overflow:hidden">
    <div class="desk-only" style="display:grid;grid-template-columns:1.2fr 1.2fr 0.6fr 0.8fr 1fr 0.6fr 0.8fr;padding:12px 20px;background:#f9fafb;border-bottom:1px solid #eee;font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:0.5px">
      <div>Client</div>
      <div>Project</div>
      <div>Type</div>
      <div>Earnings</div>
      <div>Progress</div>
      <div>Started</div>
      <div>Action</div>
    </div>
    <div id="contracts-list">
      <!-- Dynamic list -->
    </div>
  </div>
</div>
