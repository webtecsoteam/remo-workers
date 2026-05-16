<!-- EARNINGS & PAYMENTS -->
<div class="page" id="page-earnings">
  <div style="font-size:20px;font-weight:700;margin-bottom:6px">Earnings & Payments</div>
  <div style="font-size:13px;color:var(--muted);margin-bottom:20px">All times in your local timezone · Hourly billing week ends Sunday midnight UTC</div>

  <!-- 4-column status row -->
  <div style="background:white;border:1px solid var(--border);border-radius:12px;margin-bottom:20px;overflow:hidden">
    <div style="padding:18px 22px 14px;border-bottom:1px solid var(--border)"><div style="font-size:17px;font-weight:700">Overview</div></div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid var(--border)" class="earn-overview-grid">
      <div style="padding:20px 22px;border-right:1px solid var(--border);cursor:pointer" onclick="showEarningsInfo('wip')">
        <div style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">Work in progress <span title="Hours logged this week, not yet billed" style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;border:1.5px solid var(--muted2);font-size:10px;color:var(--muted2);cursor:help;flex-shrink:0">?</span></div>
        <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--dark)">$<?php echo number_format($fStats['wip_earnings'] ?? 0, 2); ?></div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:5px">Unbilled work logs</div>
      </div>
      <div style="padding:20px 22px;border-right:1px solid var(--border);cursor:pointer" onclick="showEarningsInfo('review')">
        <div style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">In review <span title="Milestones or hours under client review" style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;border:1.5px solid var(--muted2);font-size:10px;color:var(--muted2);cursor:help;flex-shrink:0">?</span></div>
        <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--dark)">$0.00</div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:5px">Review window</div>
      </div>
      <div style="padding:20px 22px;border-right:1px solid var(--border);cursor:pointer" onclick="showEarningsInfo('pending')">
        <div style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">Pending <span title="5-day security hold" style="display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;border-radius:50%;border:1.5px solid var(--muted2);font-size:10px;color:var(--muted2);cursor:help;flex-shrink:0">?</span></div>
        <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--dark)">$<?php echo number_format($fStats['pending_earnings'] ?? 0, 2); ?></div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:5px">Security hold</div>
      </div>
      <div style="padding:20px 22px;cursor:pointer" onclick="showEarningsInfo('available')">
        <div style="font-size:12.5px;color:var(--muted);margin-bottom:8px;font-weight:500">Available</div>
        <div class="earn-val" style="font-size:26px;font-weight:700;color:var(--g)">$<?php echo number_format($user['balance'] ?? 0, 2); ?></div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:5px">Ready to withdraw</div>
      </div>
    </div>
    <div id="earnings-info-panel" style="display:none;padding:16px 22px;background:var(--off);border-top:1px solid var(--border);font-size:13px;color:var(--dark3);line-height:1.7"></div>
  </div>

  <!-- Withdraw -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-head"><h3>Withdraw Earnings</h3></div>
    <div class="card-body">
      <div class="g2">
        <div>
          <div style="font-size:13px;font-weight:600;margin-bottom:8px">Available for Withdrawal: $<?php echo number_format($user['balance'] ?? 0, 2); ?></div>
          <select id="withdraw-method" style="width:100%;padding:8px 11px;border:1px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none;margin-bottom:10px">
            <option>Direct Bank Transfer (ACH)</option><option>PayPal</option><option>Payoneer</option><option>Wire Transfer</option>
          </select>
          <button class="btn btn-g" style="width:100%;justify-content:center;padding:10px" onclick="initiateWithdrawal(<?php echo $user['balance'] ?? 0; ?>)">Withdraw $<?php echo number_format($user['balance'] ?? 0, 2); ?> →</button>
        </div>
        <div style="background:var(--off);border-radius:9px;padding:14px">
          <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Service Fees</div>
          <?php
            // Calculate dynamic fees
            $lifetimeTotal = $fStats['total_earned'] ?? 0;
            $feeTier = $lifetimeTotal > 10000 ? '5%' : '10%';
          ?>
          <div style="font-size:13px;color:var(--dark3);line-height:1.8">
            Lifetime Earnings: <strong>$<?php echo number_format($lifetimeTotal, 2); ?></strong><br>
            Current Fee tier: <strong style="color:var(--g)"><?php echo $feeTier; ?></strong><br>
            <span style="font-size:12px;color:var(--muted)">Next tier at $10,000 lifetime</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <div style="font-size:14px;font-weight:700">Transaction History</div>
    <button class="btn btn-w btn-sm" onclick="showPage('reports')">📊 Full Payment Reports →</button>
  </div>
  <div class="card">
    <div class="card-body" style="padding:0">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Description</th><th>Gross</th><th class="hide-mob">Fee</th><th>Net</th><th class="hide-mob">Status</th></tr></thead>
        <tbody>
          <?php if(empty($transactions)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">No transactions found yet.</td></tr>
          <?php else: ?>
            <?php foreach($transactions as $t): ?>
              <tr>
                <td><?php echo date('M j, Y', strtotime($t['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($t['description']); ?></td>
                <td>$<?php echo number_format($t['amount'], 2); ?></td>
                <td class="hide-mob" style="color:#ef4444">-$<?php echo number_format($t['platform_fee'], 2); ?></td>
                <td style="font-weight:700;color:var(--g)">$<?php echo number_format($t['amount'] - $t['platform_fee'], 2); ?></td>
                <td class="hide-mob">
                  <span class="badge <?php 
                    echo $t['status'] === 'completed' ? 'b-green' : ($t['status'] === 'pending' ? 'b-blue' : 'b-gray'); 
                  ?>">
                    <?php echo ucfirst($t['status']); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
