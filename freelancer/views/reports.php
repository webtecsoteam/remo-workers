<!-- PAYMENT REPORTS -->
<div class="page" id="page-reports">

  <!-- Page header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:20px;font-weight:700;margin-bottom:4px">Payment Reports</div>
      <div style="font-size:13px;color:var(--muted)">Full breakdown of earnings, bonuses, fees & withdrawals</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <select id="rpt-period" style="padding:7px 11px;border:1px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none;background:white;cursor:pointer">
        <option value="current">Last 7 Days</option>
        <option value="may2026">May 2026</option>
      </select>
      <button class="btn btn-w btn-sm" onclick="toast('CSV','Exporting ledger...')">⬇ Export CSV</button>
      <button class="btn btn-w btn-sm" onclick="window.print()">🖨 Print / PDF</button>
    </div>
  </div>

  <!-- KPI summary cards -->
  <div id="rpt-kpi-row" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px">
    <div class="stat-c" style="padding:15px">
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700">Net Earnings</div>
      <div style="font-size:18px;font-weight:800;color:var(--dark)">$<?php 
        $net = array_sum(array_map(fn($l) => $l['status']==='completed' ? ($l['amount'] - $l['platform_fee']) : 0, $fullLedger ?? []));
        echo number_format($net, 2);
      ?></div>
    </div>
    <div class="stat-c" style="padding:15px">
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700">Fees Paid</div>
      <div style="font-size:18px;font-weight:800;color:#ef4444">-$<?php 
        $fees = array_sum(array_map(fn($l) => $l['status']==='completed' ? $l['platform_fee'] : 0, $fullLedger ?? []));
        echo number_format($fees, 2);
      ?></div>
    </div>
    <div class="stat-c" style="padding:15px">
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700">Bonuses</div>
      <div style="font-size:18px;font-weight:800;color:#8b5cf6">$<?php 
        $bonuses = array_sum(array_map(fn($b) => $b['amount'], $bonusPayments ?? []));
        echo number_format($bonuses, 2);
      ?></div>
    </div>
    <div class="stat-c" style="padding:15px">
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700">Pending</div>
      <div style="font-size:18px;font-weight:800;color:var(--muted)">$<?php echo number_format($fStats['pending_earnings'] ?? 0, 2); ?></div>
    </div>
    <div class="stat-c" style="padding:15px">
      <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700">Available</div>
      <div style="font-size:18px;font-weight:800;color:var(--g)">$<?php echo number_format($user['balance'] ?? 0, 2); ?></div>
    </div>
  </div>

  <!-- 2-column: bar chart + bonus breakdown -->
  <div class="g2" style="margin-bottom:16px">

    <!-- Weekly earnings bar chart -->
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><h3>Weekly Earnings (Last 7 Days)</h3></div>
      <div class="card-body">
        <div style="display:flex;align-items:flex-end;gap:12px;height:120px;padding-bottom:10px;border-bottom:1px solid var(--border)">
          <?php 
            $totals = array_column($weeklyEarnings, 'total');
            $maxWeekly = !empty($totals) ? (max($totals) ?: 1) : 1;
            foreach($weeklyEarnings as $w): 
              $h = ($w['total'] / $maxWeekly) * 100;
          ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px">
              <div style="width:100%;height:<?php echo max($h, 5); ?>%;background:var(--g);border-radius:4px 4px 0 0;min-height:4px" title="$<?php echo number_format($w['total']); ?>"></div>
              <div style="font-size:10px;color:var(--muted)"><?php echo $w['label']; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Bonus payments panel -->
    <div class="card" style="margin-bottom:0">
      <div class="card-head">
        <h3>🎁 Bonus Payments</h3>
        <span style="font-size:13px;font-weight:700;color:#8b5cf6">$<?php echo number_format($bonuses, 2); ?></span>
      </div>
      <div class="card-body" style="padding:0">
        <?php if(empty($bonusPayments)): ?>
          <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">No bonus payments yet.</div>
        <?php else: ?>
          <?php foreach($bonusPayments as $b): ?>
            <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-size:13px;font-weight:700"><?php echo htmlspecialchars($b['client_name']); ?></div>
                <div style="font-size:11px;color:var(--muted)"><?php echo date('M j, Y', strtotime($b['created_at'])); ?></div>
              </div>
              <div style="font-weight:700;color:#8b5cf6">+$<?php echo number_format($b['amount'], 2); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Earnings by client + by type -->
  <div class="g2" style="margin-bottom:16px">
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><h3>Earnings by Client</h3></div>
      <div class="card-body" style="padding:0">
        <?php foreach($earningsByClient as $ec): ?>
          <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <div style="font-size:13px"><?php echo htmlspecialchars($ec['client_name']); ?></div>
            <div style="font-weight:700">$<?php echo number_format($ec['total'], 2); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card" style="margin-bottom:0">
      <div class="card-head"><h3>Earnings by Type</h3></div>
      <div class="card-body" style="padding:20px">
        <div style="display:flex;flex-direction:column;gap:15px">
          <?php 
            $hourlyTotal = array_sum(array_map(fn($l) => $l['p_type']==='hourly' ? $l['amount'] : 0, $fullLedger));
            $fixedTotal = 0; // For now
            $totalAll = $hourlyTotal + $fixedTotal + $bonuses;
          ?>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px"><span>Hourly</span><span>$<?php echo number_format($hourlyTotal); ?></span></div>
            <div style="height:6px;background:#eee;border-radius:3px;overflow:hidden"><div style="width:<?php echo $totalAll?($hourlyTotal/$totalAll)*100:0; ?>%;height:100%;background:var(--g)"></div></div>
          </div>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px"><span>Bonuses</span><span>$<?php echo number_format($bonuses); ?></span></div>
            <div style="height:6px;background:#eee;border-radius:3px;overflow:hidden"><div style="width:<?php echo $totalAll?($bonuses/$totalAll)*100:0; ?>%;height:100%;background:#8b5cf6"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Full transaction ledger -->
  <div class="card">
    <div class="card-head">
      <h3>Transaction Ledger</h3>
    </div>
    <div class="desk-only">
      <table class="tbl">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Description</th>
            <th>Client</th>
            <th>Gross</th>
            <th>Fee</th>
            <th>Net</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($fullLedger as $l): ?>
            <tr>
              <td><?php echo date('M j, Y', strtotime($l['created_at'])); ?></td>
              <td><span class="badge <?php echo $l['p_type']==='bonus'?'b-blue':'b-gray'; ?>"><?php echo ucfirst($l['p_type']); ?></span></td>
              <td><?php echo htmlspecialchars($l['job_title'] ?? $l['type'] ?? 'Transaction'); ?></td>
              <td><?php echo htmlspecialchars($l['client_name'] ?? '—'); ?></td>
              <td>$<?php echo number_format($l['amount'], 2); ?></td>
              <td style="color:#ef4444">-$<?php echo number_format($l['platform_fee'], 2); ?></td>
              <td style="font-weight:700;color:var(--g)">$<?php echo number_format($l['amount'] - $l['platform_fee'], 2); ?></td>
              <td><span class="badge <?php echo $l['status']==='completed'?'b-green':'b-blue'; ?>"><?php echo ucfirst($l['status']); ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile Ledger View -->
    <div class="mob-only">
      <?php if(empty($fullLedger)): ?>
        <div style="padding:40px;text-align:center;color:var(--muted)">No transactions found.</div>
      <?php else: ?>
        <?php foreach($fullLedger as $l): ?>
          <div style="padding:16px;border-bottom:1px solid #eee">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
              <div>
                <div style="font-weight:700;font-size:14px;color:var(--dark)"><?php echo htmlspecialchars($l['job_title'] ?? $l['type'] ?? 'Transaction'); ?></div>
                <div style="font-size:11px;color:var(--muted)"><?php echo htmlspecialchars($l['client_name'] ?? '—'); ?></div>
              </div>
              <span class="badge <?php echo $l['status']==='completed'?'b-green':'b-blue'; ?>" style="font-size:10px;align-self:start"><?php echo ucfirst($l['status']); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:flex-end">
              <div>
                <div style="font-size:12px;color:var(--muted)"><?php echo date('M j, Y', strtotime($l['created_at'])); ?></div>
                <div style="margin-top:4px"><span class="badge <?php echo $l['p_type']==='bonus'?'b-blue':'b-gray'; ?>" style="font-size:9px"><?php echo ucfirst($l['p_type']); ?></span></div>
              </div>
              <div style="text-align:right">
                <div style="font-weight:700;font-size:15px;color:var(--g)">$<?php echo number_format($l['amount'] - $l['platform_fee'], 2); ?></div>
                <div style="font-size:10px;color:var(--muted2)">Net Amount</div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
