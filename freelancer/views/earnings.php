<!-- EARNINGS -->
<div class="page" id="page-earnings">
  <div style="font-size:20px;font-weight:700;margin-bottom:14px">Earnings Overview</div>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:20px">
    <div class="stat-c">
      <div style="font-size:12px;color:var(--muted);margin-bottom:6px">Available Now</div>
      <div style="font-size:24px;font-weight:800;color:var(--g)">$<?php echo number_format((float)($user['balance'] ?? 0), 2); ?></div>
      <button class="btn btn-g btn-sm" style="margin-top:10px;width:100%;justify-content:center" onclick="openModal('withdraw')">Get Paid</button>
    </div>
    <div class="stat-c">
      <div style="font-size:12px;color:var(--muted);margin-bottom:6px">Pending</div>
      <div style="font-size:24px;font-weight:800">$<?php echo number_format((float)($fStats['pending_earnings'] ?? 0), 2); ?></div>
      <div style="font-size:11px;color:var(--muted);margin-top:4px">In security period</div>
    </div>
    <div class="stat-c">
      <div style="font-size:12px;color:var(--muted);margin-bottom:6px">In Review</div>
      <div style="font-size:24px;font-weight:800">$0.00</div>
      <div style="font-size:11px;color:var(--muted);margin-top:4px">Client reviewing work</div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Recent Transactions</h3></div>
    <div class="card-body" style="padding:0">
      <table style="width:100%;border-collapse:collapse;font-size:13.5px">
        <thead>
          <tr style="text-align:left;color:var(--muted);border-bottom:1px solid var(--border)">
            <th style="padding:12px 20px">Date</th>
            <th style="padding:12px 20px">Type</th>
            <th style="padding:12px 20px">Description</th>
            <th style="padding:12px 20px;text-align:right">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($transactions as $t): ?>
            <tr style="border-bottom:1px solid var(--border)">
              <td style="padding:14px 20px"><?php echo date('M j, Y', strtotime($t['created_at'])); ?></td>
              <td style="padding:14px 20px"><span class="badge <?php echo $t['type']=='credit'?'b-green':'b-gray'; ?>"><?php echo ucfirst($t['type']); ?></span></td>
              <td style="padding:14px 20px"><?php echo htmlspecialchars($t['description']); ?></td>
              <td style="padding:14px 20px;text-align:right;font-weight:700;color:<?php echo $t['type']=='credit'?'var(--g)':'var(--dark)'; ?>"><?php echo $t['type']=='credit'?'+':'-'; ?>$<?php echo number_format($t['amount']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
