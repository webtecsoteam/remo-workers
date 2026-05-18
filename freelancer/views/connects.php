<div id="page-connects" class="page">
  <!-- Status Grid -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:24px;margin-bottom:30px">
    
    <!-- Connects Status Card -->
    <div class="card" style="padding:24px;border-radius:14px;background:white;border:1px solid var(--border);box-shadow:var(--sh)">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h3 style="font-size:16px;font-weight:800;color:var(--dark);margin:0">Available Balance</h3>
        <span style="font-size:24px">💼</span>
      </div>
      <div style="font-size:32px;font-weight:900;color:var(--g);margin-bottom:8px" id="connects-page-balance">$<?php echo number_format((float)($user['balance'] ?? 0), 2); ?></div>
      <div style="font-size:13px;color:var(--muted);margin-bottom:25px">Your available balance can be used to purchase connects instantly.</div>
      
      <hr style="border:0;border-top:1px solid var(--border);margin-bottom:20px">

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
        <h3 style="font-size:16px;font-weight:800;color:var(--dark);margin:0">Available Connects</h3>
        <span style="font-size:24px">🔗</span>
      </div>
      <div style="font-size:32px;font-weight:900;color:var(--dark);margin-bottom:8px" id="connects-page-count"><?php echo (int)($user['connects'] ?? 0); ?> Connects</div>
      <div style="background:var(--border);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div id="connects-page-progress" style="height:100%;background:linear-gradient(90deg, var(--g), #10b981);width:<?php echo min(((int)($user['connects'] ?? 0) / 200) * 100, 100); ?>%"></div>
      </div>
      <div style="font-size:13.5px;color:var(--muted);display:flex;justify-content:space-between" id="connects-page-max-info">
        <span><?php echo (int)($user['connects'] ?? 0); ?> of 200 max connects</span>
        <span>Monthly Refresh: +10</span>
      </div>
    </div>

    <!-- Buy Connects Card -->
    <div class="card" style="padding:24px;border-radius:14px;background:white;border:1px solid var(--border);box-shadow:var(--sh)">
      <h3 style="font-size:16px;font-weight:800;color:var(--dark);margin:0 0 20px 0;display:flex;align-items:center;gap:8px">
        <span>🛒</span> Buy Connects
      </h3>

      <!-- Pack Grid -->
      <div style="margin-bottom:20px">
        <label style="display:block;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:10px">Select Connects Package</label>
        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;margin-bottom:12px">
          <div class="connect-pack-btn" onclick="selectConnectPack(10, 1.50, this)" style="border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s">
            <div style="font-weight:800;font-size:14px">10</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">$1.50</div>
          </div>
          <div class="connect-pack-btn" onclick="selectConnectPack(20, 3.00, this)" style="border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s">
            <div style="font-weight:800;font-size:14px">20</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">$3.00</div>
          </div>
          <div class="connect-pack-btn" onclick="selectConnectPack(30, 4.50, this)" style="border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s">
            <div style="font-weight:800;font-size:14px">30</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">$4.50</div>
          </div>
          <div class="connect-pack-btn" onclick="selectConnectPack(50, 7.50, this)" style="border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s">
            <div style="font-weight:800;font-size:14px">50</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">$7.50</div>
          </div>
          <div class="connect-pack-btn" onclick="selectConnectPack(60, 9.00, this)" style="border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s">
            <div style="font-weight:800;font-size:14px">60</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">$9.00</div>
          </div>
          <div class="connect-pack-btn" onclick="selectConnectPack(100, 15.00, this)" style="border:1px solid var(--border);border-radius:10px;padding:12px 6px;text-align:center;cursor:pointer;background:var(--off);transition:all 0.15s">
            <div style="font-weight:800;font-size:14px">100</div>
            <div style="font-size:11px;color:var(--muted);margin-top:2px">$15.00</div>
          </div>
        </div>

        <!-- Custom Input -->
        <div style="background:var(--off);border:1px solid var(--border);border-radius:10px;padding:12px;margin-top:12px">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:13px;font-weight:700;color:var(--dark)">Custom Connects Pack:</span>
            <input type="number" id="custom-connects-qty" placeholder="e.g. 45" oninput="calculateCustomConnects(this.value)" style="width:100px;padding:6px 10px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;text-align:right">
          </div>
        </div>
      </div>

      <!-- Pricing Info -->
      <div style="display:flex;justify-content:space-between;align-items:center;background:var(--off2);border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:20px">
        <span style="font-size:13px;color:var(--muted)">Purchase Summary:</span>
        <strong style="font-size:16px;color:var(--g)" id="connects-purchase-summary">0 Connects = $0.00</strong>
      </div>

      <!-- Payment Method Selection -->
      <div style="margin-bottom:20px">
        <label style="display:block;font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:8px">Select Payment Method</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div id="connect-method-wallet" onclick="selectConnectPaymentMethod('wallet')" style="border:1px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;text-align:center;background:white;transition:all 0.15s">
            <div style="font-size:16px;margin-bottom:4px">💼</div>
            <div style="font-size:12.5px;font-weight:700;color:var(--dark)">Wallet Balance</div>
          </div>
          <div id="connect-method-card" onclick="selectConnectPaymentMethod('card')" style="border:1px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;text-align:center;background:white;transition:all 0.15s">
            <div style="font-size:16px;margin-bottom:4px">💳</div>
            <div style="font-size:12.5px;font-weight:700;color:var(--dark)">Debit/Credit Card</div>
          </div>
        </div>
      </div>

      <!-- Card Information Form (Hidden initially) -->
      <div id="connects-card-form" style="display:none;background:var(--off);border:1px solid var(--border);border-radius:10px;padding:15px;margin-bottom:20px">
        <div style="margin-bottom:12px">
          <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:5px">Cardholder Name</label>
          <input type="text" id="connects-card-name" placeholder="John Doe" style="width:100%;padding:9px;border:1.5px solid var(--border);border-radius:6px;font-size:13px">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:5px">Card Number</label>
          <input type="text" id="connects-card-number" placeholder="4111 2222 3333 4444" maxlength="19" style="width:100%;padding:9px;border:1.5px solid var(--border);border-radius:6px;font-size:13px">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:5px">Expiry Date</label>
            <input type="text" id="connects-card-expiry" placeholder="MM/YY" maxlength="5" style="width:100%;padding:9px;border:1.5px solid var(--border);border-radius:6px;font-size:13px">
          </div>
          <div>
            <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:5px">CVV</label>
            <input type="password" id="connects-card-cvv" placeholder="•••" maxlength="4" style="width:100%;padding:9px;border:1.5px solid var(--border);border-radius:6px;font-size:13px">
          </div>
        </div>
      </div>

      <button id="btn-buy-connects-submit" class="btn btn-g" onclick="submitConnectsPurchase()" style="width:100%;justify-content:center;padding:14px;font-weight:800;font-size:14px" disabled>Buy Connects Pack</button>
    </div>

  </div>

  <!-- Connects History Table -->
  <div class="card" style="padding:24px;border-radius:14px;background:white;border:1px solid var(--border);box-shadow:var(--sh)">
    <h3 style="font-size:16px;font-weight:800;color:var(--dark);margin:0 0 20px 0;display:flex;align-items:center;gap:8px">
      <span>📜</span> Connects Activity History
    </h3>
    
    <div class="table-container" style="overflow-x:auto">
      <table class="tbl" style="width:100%;border-collapse:collapse;text-align:left">
        <thead>
          <tr style="border-bottom:1.5px solid var(--border);background:var(--off)">
            <th style="padding:14px 16px;font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase">Date</th>
            <th style="padding:14px 16px;font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase">Description</th>
            <th style="padding:14px 16px;font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase">Action</th>
            <th style="padding:14px 16px;font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;text-align:right">Change</th>
          </tr>
        </thead>
        <tbody id="connects-history-tbody">
          <!-- Dynamically Rendered -->
        </tbody>
      </table>
    </div>
  </div>
</div>
