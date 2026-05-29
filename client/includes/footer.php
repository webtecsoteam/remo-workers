<!-- TOAST -->
<div class="toast" id="toast"><strong id="t-title"></strong><span id="t-msg"></span></div>

<script>
  let availableBalance = <?php echo (float) ($user['balance'] ?? 0); ?>;
  const CONTRACTS = <?php echo json_encode($allContracts ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;
  const BLOCKED_FREELANCER_IDS = <?php echo json_encode(array_map('intval', array_column(isset($blockedFreelancers) ? $blockedFreelancers : [], 'id'))); ?>;
  const CLIENT_USER_ID = <?php echo (int) $user['id']; ?>;
  let activeChatBlocked = false;
  let activeChatBlockedByMe = false;
  let clientFeePercent = <?php echo getPlatformSetting('client_fee_fixed', 0); ?>;
  let selectedCVType = null;
  let selectedCVFile = null;

  (function setupDashboardLiveChat() {
    function hideFloatChatButton() {
      if (!window.$zoho || !$zoho.salesiq) return;
      try {
        if ($zoho.salesiq.floatbutton && typeof $zoho.salesiq.floatbutton.visible === 'function') {
          $zoho.salesiq.floatbutton.visible('hide');
        }
      } catch (e) {}
    }

    function injectZohoScript() {
      if (document.getElementById('zsiqscript')) return;
      window.$zoho = window.$zoho || {};
      $zoho.salesiq = $zoho.salesiq || {};
      $zoho.salesiq.ready = hideFloatChatButton;
      const s = document.createElement('script');
      s.id = 'zsiqscript';
      s.src = 'https://salesiq.zohopublic.com/widget?wc=siq3522b6c8efa1866fa919f61c10976b22744d3361ea908b3985ef2a2bb0af56e8';
      s.defer = true;
      document.body.appendChild(s);
    }

    window.openDashboardLiveChat = function () {
      injectZohoScript();
      if (!window.$zoho || !$zoho.salesiq) return;
      try {
        if ($zoho.salesiq.chat && typeof $zoho.salesiq.chat.start === 'function') {
          $zoho.salesiq.chat.start();
          return;
        }
        if ($zoho.salesiq.floatwindow && typeof $zoho.salesiq.floatwindow.visible === 'function') {
          $zoho.salesiq.floatwindow.visible('show');
          return;
        }
      } catch (e) {}
      let tries = 0;
      const timer = setInterval(function () {
        tries += 1;
        try {
          if (window.$zoho && $zoho.salesiq && $zoho.salesiq.chat && typeof $zoho.salesiq.chat.start === 'function') {
            $zoho.salesiq.chat.start();
            clearInterval(timer);
            return;
          }
          if (window.$zoho && $zoho.salesiq && $zoho.salesiq.floatwindow && typeof $zoho.salesiq.floatwindow.visible === 'function') {
            $zoho.salesiq.floatwindow.visible('show');
            clearInterval(timer);
            return;
          }
        } catch (err) {}
        if (tries >= 20) {
          clearInterval(timer);
          if (typeof toast === 'function') toast('Live Chat', 'Please try again in a moment.');
        }
      }, 300);
    };
  })();

  // ─── MODALS ───
  function fundMilestoneBody(cfg) {
    const bal = availableBalance;
    const canCover = bal >= cfg.amount;
    const shortage = Math.max(0, cfg.amount - bal);
    return `
  <div style="display:flex;gap:10px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:14px 16px;margin-bottom:16px">
    <div class="av" style="background:${cfg.avatarBg};color:${cfg.avatarColor};width:38px;height:38px">${cfg.initials}</div>
    <div style="flex:1">
      <div style="font-weight:700">${cfg.name}</div>
      <div style="font-size:12px;color:var(--uw-gray)">${cfg.role} · ${cfg.contract}</div>
    </div>
    <div style="text-align:right">
      <div style="font-size:18px;font-weight:700">$${cfg.amount.toLocaleString()}</div>
      <div style="font-size:11.5px;color:var(--uw-gray)">${cfg.milestone}</div>
    </div>
  </div>
  <div style="font-size:13px;font-weight:700;margin-bottom:10px">Choose Funding Source</div>
  <div class="pay-method ${canCover ? 'selected' : ''}" id="pm-balance" onclick="selectFundSource('balance','${cfg.amount}')">
    <div class="pay-method-icon" style="background:var(--uw-black);border-color:var(--uw-black)">💰</div>
    <div class="pay-method-info">
      <div class="pay-method-name">Upwork Balance</div>
      <div class="pay-method-sub">Available: <strong style="color:${canCover ? 'var(--uw-green)' : '#dc2626'}">$${bal.toFixed(2)}</strong>${canCover ? ' · Covers full amount ✓' : ` · Shortfall: $${shortage.toFixed(2)}`}</div>
    </div>
    ${canCover ? '<span class="pay-method-badge">RECOMMENDED</span>' : '<span style="font-size:10px;background:#fee2e2;color:#991b1b;padding:2px 7px;border-radius:4px;font-weight:700;white-space:nowrap">PARTIAL ONLY</span>'}
  </div>
  <div class="pay-method ${!canCover ? 'selected' : ''}" id="pm-card" onclick="selectFundSource('card','${cfg.amount}')">
    <div class="pay-method-icon">💳</div>
    <div class="pay-method-info"><div class="pay-method-name">Visa ending in 4821</div><div class="pay-method-sub">Expires 09/27 · Primary card</div></div>
    <span class="pay-method-badge">PRIMARY</span>
  </div>
  <div class="pay-method" onclick="selectFundSource('card2','${cfg.amount}')">
    <div class="pay-method-icon">🏦</div>
    <div class="pay-method-info"><div class="pay-method-name">Mastercard ending in 3392</div><div class="pay-method-sub">Expires 03/26</div></div>
  </div>
  ${!canCover ? `<div class="split-divider">or split payment</div>
  <div class="pay-method" onclick="selectFundSource('split','${cfg.amount}')">
    <div class="pay-method-icon" style="font-size:14px">⚡</div>
    <div class="pay-method-info"><div class="pay-method-name">Split: Balance + Card</div><div class="pay-method-sub">$${bal.toFixed(2)} from balance + $${shortage.toFixed(2)} from Visa ••4821</div></div>
    <span class="pay-method-badge">RECOMMENDED</span>
  </div>`: ''}
  <div class="fund-summary">
    <div class="fund-summary-row"><span style="color:var(--uw-gray)">Milestone</span><span>${cfg.milestone}</span></div>
    <div class="fund-summary-row"><span style="color:var(--uw-gray)">Upwork Service Fee</span><span>$0.00</span></div>
    <div class="fund-summary-row total"><span>Total funded to escrow</span><span style="color:var(--uw-green)">$${cfg.amount.toLocaleString()}</span></div>
  </div>
  <div style="font-size:12px;color:var(--uw-gray);margin-bottom:14px;display:flex;align-items:center;gap:6px">🔒 Funds are held in Upwork escrow and released only when you approve the work.</div>
  <button class="btn btn-g" style="width:100%;justify-content:center;padding:11px;font-size:14px" onclick="confirmFundMilestone(${cfg.amount},'${cfg.name}','${cfg.milestone}')">Confirm & Fund Milestone →</button>`;
  }

  function selectFundSource(id, amount) {
    document.querySelectorAll('.pay-method').forEach(el => el.classList.remove('selected'));
    const m = document.getElementById('pm-' + id);
    if (m) m.classList.add('selected');
  }
  function confirmFundMilestone(amount, name, milestone) {
    availableBalance = Math.max(0, availableBalance - amount);
    toast('Milestone Funded! ✓', `$${amount.toLocaleString()} held in escrow for ${name}`);
    closeModal();
  }
  function selectPayMethod(el) { document.querySelectorAll('.pay-method').forEach(x => x.classList.remove('selected')); el.classList.add('selected'); }
  window.selectedAddFundsPaymentMethod = 'card';
  window.pendingCryptoDepositReference = null;

  window.resetAddFundsCryptoPanel = function () {
    window.pendingCryptoDepositReference = null;
    const cryptoForm = document.getElementById('add-funds-crypto-form');
    const submitBtn = document.getElementById('btn-add-funds-submit');
    if (cryptoForm) cryptoForm.style.display = 'none';
    if (submitBtn) submitBtn.style.display = '';
  };

  window.selectAddFundsPaymentMethod = function (method) {
    window.selectedAddFundsPaymentMethod = method;
    const cardEl = document.getElementById('add-funds-method-card');
    const cryptoEl = document.getElementById('add-funds-method-crypto');
    const cardNotice = document.getElementById('add-funds-card-notice');
    const inactive = 'border:1.5px solid var(--uw-border);border-radius:10px;padding:12px;cursor:pointer;text-align:center;background:white;transition:all 0.15s';
    const active = 'border:2px solid var(--uw-green);border-radius:10px;padding:11px;cursor:pointer;text-align:center;background:var(--uw-green-light)';

    [cardEl, cryptoEl].forEach(function (el) {
      if (!el) return;
      el.style.cssText = inactive;
      const label = el.querySelector('.add-funds-method-label');
      if (label) label.style.color = 'var(--uw-black)';
    });

    const map = { card: cardEl, crypto: cryptoEl };
    const selected = map[method];
    if (selected) {
      selected.style.cssText = active;
      const label = selected.querySelector('.add-funds-method-label');
      if (label) label.style.color = 'var(--uw-green)';
    }

    if (cardNotice) cardNotice.style.display = method === 'card' ? 'block' : 'none';
    if (method !== 'crypto') resetAddFundsCryptoPanel();
  };

  window.showAddFundsCryptoDeposit = function (data) {
    window.pendingCryptoDepositReference = data.reference_id;
    const cryptoForm = document.getElementById('add-funds-crypto-form');
    const submitBtn = document.getElementById('btn-add-funds-submit');
    const addrEl = document.getElementById('add-funds-crypto-address');
    const amtEl = document.getElementById('add-funds-crypto-amount');
    const rateEl = document.getElementById('add-funds-crypto-rate');
    const memoWrap = document.getElementById('add-funds-crypto-memo-wrap');
    const memoEl = document.getElementById('add-funds-crypto-memo');
    const chainLabel = data.chain_label || 'Tron (TRC20)';
    const titleEl = document.getElementById('add-funds-crypto-title');
    const networkEl = document.getElementById('add-funds-crypto-network');

    if (titleEl) titleEl.textContent = 'Pay with USDT (' + chainLabel + ')';
    if (networkEl) networkEl.textContent = chainLabel;
    if (rateEl) rateEl.textContent = data.rate_label || '1 USDT = 1 USD';
    if (amtEl) amtEl.textContent = Number(data.usdt_amount || 0).toFixed(2) + ' USDT';
    if (addrEl) addrEl.textContent = data.address || '—';
    if (memoWrap && memoEl) {
      if (data.memo) {
        memoWrap.style.display = 'block';
        memoEl.textContent = data.memo;
      } else {
        memoWrap.style.display = 'none';
      }
    }
    if (cryptoForm) cryptoForm.style.display = 'block';
    if (submitBtn) submitBtn.style.display = 'none';
  };

  window.copyAddFundsCryptoAddress = function () {
    const addr = document.getElementById('add-funds-crypto-address');
    if (!addr || !addr.textContent || addr.textContent === '—') return;
    navigator.clipboard.writeText(addr.textContent.trim()).then(function () {
      toast('Copied', 'Deposit address copied to clipboard.');
    }).catch(function () {
      toast('Copy failed', 'Please copy the address manually.');
    });
  };

  window.confirmCryptoDeposit = function () {
    const ref = window.pendingCryptoDepositReference;
    if (!ref) {
      toast('Error', 'No pending crypto deposit found. Please start again.');
      return;
    }
    const btn = document.getElementById('btn-confirm-crypto-deposit');
    const originalText = btn ? btn.innerText : '';
    if (btn) {
      btn.disabled = true;
      btn.innerText = 'Checking deposit...';
    }
    fetch(BASE_URL + 'client/api/confirm-crypto-deposit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reference_id: ref })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success && data.completed) {
          toast('Deposit confirmed! ✓', data.message);
          if (typeof data.new_balance === 'number') {
            availableBalance = data.new_balance;
            const balEl = document.getElementById('client-available-balance');
            if (balEl) {
              balEl.textContent = '$' + availableBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            const pill = document.getElementById('add-funds-current-bal-pill');
            if (pill) {
              pill.innerHTML = '💰 Current Balance: $' + availableBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            const newBalEl = document.getElementById('add-new-bal');
            if (newBalEl) {
              newBalEl.textContent = '$' + availableBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
          }
          resetAddFundsCryptoPanel();
          selectAddFundsPaymentMethod('card');
          const amtInput = document.getElementById('add-funds-amount');
          if (amtInput) {
            amtInput.value = '';
            updateAddFundsSummary(amtInput);
          }
          setTimeout(function () { location.reload(); }, 1200);
        } else if (data.success && data.pending) {
          toast('Awaiting confirmation', data.message);
        } else {
          toast('Error', data.message || 'Could not confirm deposit.');
        }
      })
      .catch(function () {
        toast('Error', 'Could not check deposit status.');
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerText = originalText;
        }
      });
  };

  async function handleAddFunds(btn) {
    const input = document.getElementById('add-funds-amount');
    const val = input ? input.value : 0;
    const v = parseFloat(val || 0);
    if (v < 1) { toast('Minimum $1', 'Please enter at least $1 to add'); return; }

    const method = window.selectedAddFundsPaymentMethod || 'card';
    if (method === 'crypto' && window.pendingCryptoDepositReference) {
      toast('Deposit pending', 'Complete your USDT deposit or use the confirm button below.');
      return;
    }

    if (!btn) btn = document.getElementById('btn-add-funds-submit');
    const originalText = btn ? btn.innerText : 'Add Funds →';
    if (btn) {
      btn.disabled = true;
      const loadingLabels = { card: 'Redirecting to Paystack...', crypto: 'Generating deposit address...' };
      btn.innerText = loadingLabels[method] || 'Initializing...';
    }

    try {
      const response = await fetch(BASE_URL + 'actions/add_funds.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: v, payment_method: method })
      });

      const result = await response.json();
      if (result.success && result.authorization_url) {
        toast('Redirecting...', 'Taking you to Paystack secure payment page');
        window.location.href = result.authorization_url;
        return;
      }
      if (result.success && result.crypto && result.address) {
        showAddFundsCryptoDeposit(result);
        const net = result.chain_label || 'Tron (TRC20)';
        toast('Deposit address ready', 'Send ' + Number(result.usdt_amount).toFixed(2) + ' USDT on ' + net + ' to the address shown.');
        return;
      }
      toast('Error', result.error || result.message || 'Failed to initialize payment');
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerText = originalText;
      }
    }
  }

  // ─── DM MODAL BUILDER ───
  function buildDmModal(cfg) {
    const historyHtml = cfg.history.map(msg => {
      const isMe = msg.from === 'me';
      return `<div style="display:flex;gap:10px;flex-direction:${isMe ? 'row-reverse' : 'row'};margin-bottom:14px">
      <div class="av" style="background:${isMe ? 'var(--uw-green)' : cfg.avatarBg};color:${isMe ? '#001e00' : cfg.avatarColor};flex-shrink:0;width:32px;height:32px">${isMe ? 'NX' : cfg.initials}</div>
      <div style="max-width:75%">
        <div style="background:${isMe ? 'var(--uw-green)' : 'var(--uw-bg)'};color:${isMe ? 'white' : 'var(--uw-dark)'};border:${isMe ? 'none' : '1.5px solid var(--uw-border)'};border-radius:${isMe ? '12px 2px 12px 12px' : '2px 12px 12px 12px'};padding:10px 14px;font-size:13px;line-height:1.6">${msg.text}</div>
        <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px;text-align:${isMe ? 'right' : 'left'}">${msg.time}</div>
      </div>
    </div>`;
    }).join('');

    return `
  <div style="display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1.5px solid var(--uw-border);margin-bottom:14px">
    <div class="av" style="background:${cfg.avatarBg};color:${cfg.avatarColor};width:44px;height:44px;font-size:14px;flex-shrink:0">${cfg.initials}</div>
    <div style="flex:1;min-width:0">
      <div style="font-weight:700;font-size:14px">${cfg.name} <span class="uw-level-badge ${cfg.badgeCls}" style="font-size:10px">${cfg.badge}</span></div>
      <div style="font-size:12px;color:var(--uw-gray);margin-top:1px">${cfg.role} · ${cfg.rating} (${cfg.reviews} reviews) · ${cfg.rate} · ${cfg.location}</div>
    </div>
    <button class="btn btn-o btn-sm" onclick="openModal('${cfg.hireModal}')">Hire →</button>
  </div>

  <div style="background:var(--uw-green-light);border:1.5px solid var(--uw-green-mid);border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
    <span style="font-size:14px">📋</span>
    <div>
      <div style="font-size:11px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.05em">Proposal for</div>
      <div style="font-size:13px;font-weight:600;color:var(--uw-black)">${cfg.proposalFor}</div>
    </div>
    <div style="margin-left:auto;font-size:13px;font-weight:700;color:var(--uw-green);white-space:nowrap">${cfg.proposalAmount}</div>
  </div>

  <div id="dm-chat-${cfg.initials}" style="min-height:160px;max-height:260px;overflow-y:auto;padding:4px 2px;margin-bottom:12px">
    ${historyHtml}
    <div id="dm-sent-msgs-${cfg.initials}"></div>
  </div>

  <div style="border-top:1.5px solid var(--uw-border);padding-top:14px">
    <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap">
      <button class="btn btn-w btn-sm" onclick="insertQuickReply('${cfg.initials}','Thanks for your proposal! Could you share some relevant examples of past work?')">📎 Ask for samples</button>
      <button class="btn btn-w btn-sm" onclick="insertQuickReply('${cfg.initials}','When are you available for a quick intro call?')">📅 Ask availability</button>
      <button class="btn btn-w btn-sm" onclick="insertQuickReply('${cfg.initials}','Can you break down your approach to this project?')">💡 Ask approach</button>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end">
      <textarea id="dm-input-${cfg.initials}" style="flex:1;padding:10px 13px;border:1.5px solid var(--uw-border);border-radius:10px;font-size:13px;font-family:inherit;outline:none;resize:none;min-height:60px;line-height:1.55;transition:border-color .15s" placeholder="${cfg.placeholder}" onfocus="this.style.borderColor='var(--uw-green)'" onblur="this.style.borderColor='var(--uw-border)'" onkeydown="if(event.key==='Enter'&&(event.metaKey||event.ctrlKey)){sendDm('${cfg.initials}');return false}"></textarea>
      <button class="btn btn-g" style="padding:10px 18px;align-self:flex-end;flex-shrink:0" onclick="sendDm('${cfg.initials}')">Send</button>
    </div>
    <div style="font-size:11px;color:var(--uw-gray2);margin-top:6px">Press Ctrl+Enter or ⌘+Enter to send</div>
  </div>`;
  }

  function sendDm(initials) {
    const input = document.getElementById('dm-input-' + initials);
    const container = document.getElementById('dm-sent-msgs-' + initials);
    const chat = document.getElementById('dm-chat-' + initials);
    if (!input || !container || !chat) return;
    const text = input.value.trim();
    if (!text) return;
    const now = new Date();
    const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const msgEl = document.createElement('div');
    msgEl.style.cssText = 'display:flex;gap:10px;flex-direction:row-reverse;margin-bottom:14px';
    msgEl.innerHTML = `
    <div class="av" style="background:var(--uw-green);color:#001e00;flex-shrink:0;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px">NX</div>
    <div style="max-width:75%">
      <div style="background:var(--uw-green);color:white;border-radius:12px 2px 12px 12px;padding:10px 14px;font-size:13px;line-height:1.6">${text.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
      <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px;text-align:right">Just now · ${time}</div>
    </div>`;
    container.appendChild(msgEl);
    input.value = '';
    autoGrowChatInput(input);
    input.style.height = 'auto';
    chat.scrollTop = chat.scrollHeight;
    toast('Message sent', 'Your message was delivered');
  }

  function insertQuickReply(initials, text) {
    const input = document.getElementById('dm-input-' + initials);
    if (input) { input.value = text; input.focus(); }
  }

  const MODALS = {
    'post-job': {
      t: 'Post a New Job', b: `
    <div class="pj-modal-scroll"><div id="pj-form">
      <div class="fg"><label>Job Title</label><input type="text" id="pj-title" placeholder="e.g. Senior React Developer for Analytics Dashboard"></div>

      <div class="fg">
        <label>Category</label>
        <select id="pj-cat" onchange="updateSubcats()">
          <option value="">— Select a category —</option>
        </select>
      </div>

      <div class="fg" id="pj-subcat-wrap" style="display:none">
        <label>Subcategory</label>
        <select id="pj-subcat" onchange="updateSpecialties()">
          <option value="">— Select a subcategory —</option>
        </select>
      </div>

      <div class="fg" id="pj-spec-wrap" style="display:none">
        <label>Specialty</label>
        <select id="pj-spec">
          <option value="">— Select a specialty —</option>
        </select>
      </div>

      <div class="fg"><label>Billing Type</label><select id="pj-billing-type" onchange="updatePostJobFields()"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option><option value="monthly">Monthly Rate</option></select></div>
      
      <div id="pj-fixed-fields" class="fg"><label>Budget ($)</label><input type="number" id="pj-budget" placeholder="e.g. 5000"></div>

      <div id="pj-hourly-fields" class="fg" style="display:none">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label>Min Hourly Rate ($/hr)</label>
            <input type="number" id="pj-min-rate" placeholder="e.g. 20" min="1">
          </div>
          <div>
            <label>Max Hourly Rate ($/hr)</label>
            <input type="number" id="pj-max-rate" placeholder="e.g. 50" min="1">
          </div>
        </div>
      </div>

      <div id="pj-monthly-fields" class="fg" style="display:none"><label>Monthly Rate ($/month)</label><input type="number" id="pj-monthly-rate" placeholder="e.g. 3000"></div>

      <div class="fg"><label>Project Description</label><textarea id="pj-desc" placeholder="Describe the scope, goals, and requirements of your project…" style="min-height:100px"></textarea></div>
      <div class="fg"><label>Required Skills (comma separated)</label><input type="text" id="pj-skills" placeholder="e.g. React, Node.js, TypeScript"></div>
      
    </div></div>
    <div class="pj-modal-footer">
      <button type="button" class="btn btn-g" id="pj-submit-btn" style="width:100%;justify-content:center;padding:11px">
        <span id="pj-btn-text">Post Job →</span>
      </button>
    </div>
  `},
    'view-job': { t: '', b: '' },
    'job-1': {
      t: 'Senior React Developer — Analytics Dashboard', b: `
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
      <span class="badge b-green">Open</span><span class="badge b-blue">Fixed Price</span><span class="badge b-gray">Remote</span>
    </div>
    <div class="g2" style="margin-bottom:14px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Budget</div><div style="font-weight:700">$8,000–$12,000</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Proposals</div><div style="font-weight:700;color:var(--uw-green)">8 received</div></div>
    </div>
    <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:14px">Build a production-ready analytics dashboard with real-time WebSocket charts, interactive data visualizations, and a filterable data table. Must be responsive and integrate with our REST API.</div>
    <div class="fg"><label>Required Skills</label><div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px"><span class="badge b-gray">React</span><span class="badge b-gray">TypeScript</span><span class="badge b-gray">WebSockets</span><span class="badge b-gray">D3.js</span><span class="badge b-gray">REST APIs</span></div></div>
    <div style="display:flex;gap:8px;margin-top:16px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Job paused','Job is now paused')">Pause Job</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'));closeModal()">View Proposals (8)</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Editing','Opening job editor')">Edit Job</button>
    </div>
  `},
    'job-2': {
      t: 'Brand Designer — Full Identity Redesign', b: `
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"><span class="badge b-green">Open</span><span class="badge b-purple">Fixed Price</span><span class="badge b-gray">Remote</span></div>
    <div class="g2" style="margin-bottom:14px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Budget</div><div style="font-weight:700">$3,500–$6,000</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Proposals</div><div style="font-weight:700;color:var(--uw-green)">4 received</div></div>
    </div>
    <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:14px">Full brand identity redesign for our 2026 rebrand — including logo, color system, typography, brand guidelines, and social media templates. Looking for a senior brand designer with a strong portfolio.</div>
    <div style="display:flex;gap:8px;margin-top:16px">
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'));closeModal()">View Proposals (4)</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Editing','Opening job editor')">Edit Job</button>
    </div>
  `},
    'prop-anika': {
      t: 'Proposal — Anika Nkosi', b: `
    <div style="display:flex;gap:12px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#d1fae5;color:#065f46;width:44px;height:44px;font-size:14px">AN</div>
      <div style="flex:1">
        <div style="font-weight:700;font-size:15px">Anika Nkosi <span class="uw-level-badge lvl-top-rated-plus">✦ Top Rated Plus</span></div>
        <div style="font-size:12px;color:var(--uw-gray)">UI/UX Designer · ★ 5.0 · 127 reviews · Berlin, Germany</div>
      </div>
      <div style="text-align:right"><div style="font-size:18px;font-weight:700">$5,800</div><div style="font-size:11px;color:var(--uw-gray)">Fixed price</div></div>
    </div>
    <div style="font-size:13.5px;line-height:1.75;color:#374151;background:var(--uw-bg);border-radius:8px;padding:14px;margin-bottom:16px;border:1.5px solid var(--uw-border)">"I specialize in brand identity systems and have redesigned 40+ brands across fintech and SaaS. I'd love to bring your 2026 brand vision to life with a comprehensive system that scales across every touchpoint — including logo, typography, color system, and comprehensive brand guidelines."</div>
    <div style="margin-bottom:16px"><div style="font-size:12px;font-weight:700;color:var(--uw-gray);margin-bottom:6px">EST. TIMELINE</div><div style="font-weight:600">7 business days</div></div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Archived','Proposal archived')">Archive</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Shortlisted','Anika added to shortlist')">Shortlist</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="openModal('hire-anika')">Hire Anika →</button>
    </div>
  `},
    'prop-james': {
      t: 'Proposal — James Kowalski', b: `
    <div style="display:flex;gap:12px;align-items:center;background:#eff6ff;border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#dbeafe;color:#1e40af;width:44px;height:44px;font-size:14px">JK</div>
      <div style="flex:1">
        <div style="font-weight:700;font-size:15px">James Kowalski <span class="uw-level-badge lvl-expert-vetted">★ Expert-Vetted</span></div>
        <div style="font-size:12px;color:var(--uw-gray)">Full Stack Engineer · ★ 4.9 · 89 reviews · Toronto, Canada</div>
      </div>
      <div style="text-align:right"><div style="font-size:18px;font-weight:700">$130/hr</div><div style="font-size:11px;color:var(--uw-gray)">Hourly rate</div></div>
    </div>
    <div style="font-size:13.5px;line-height:1.75;color:#374151;background:var(--uw-bg);border-radius:8px;padding:14px;margin-bottom:16px;border:1.5px solid var(--uw-border)">"I've built 6 real-time analytics dashboards in the last 18 months, including one for a 50,000-user SaaS platform using React, WebSockets, and D3. I can start immediately and deliver milestone 1 within 5 business days."</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Archived','Proposal archived')">Archive</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Shortlisted','James added to shortlist')">Shortlist</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="openModal('hire-james')">Hire James →</button>
    </div>
  `},
    'hire-anika': {
      t: 'Hire Anika Nkosi', b: `
    <div style="display:flex;gap:10px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:12px 14px;margin-bottom:16px">
      <div class="av" style="background:#d1fae5;color:#065f46">AN</div>
      <div><div style="font-weight:700">Anika Nkosi</div><div style="font-size:12px;color:var(--uw-gray)">UI/UX Designer · ★ 5.0</div></div>
    </div>
    <div class="fg"><label>Contract Type</label><select id="hire-anika-contract-type" onchange="toggleHireFields('hire-anika')"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option><option value="monthly">Monthly Retainer</option></select></div>
    <div id="hire-anika-fixed-fields">
      <div class="fg"><label>Total Contract Amount ($)</label><input type="number" placeholder="e.g. 5800"></div>
      <div class="fg"><label>Milestone Name</label><input type="text" placeholder="e.g. Brand Identity Delivery"></div>
    </div>
    <div id="hire-anika-hourly-fields" style="display:none"><div class="fg"><label>Hourly Rate ($)</label><input type="number" value="90"></div><div class="fg"><label>Weekly Hour Limit</label><input type="number" placeholder="e.g. 20"></div></div>
    <div id="hire-anika-monthly-fields" style="display:none"><div class="fg"><label>Monthly Rate ($)</label><input type="number" placeholder="e.g. 3600"></div></div>
    <div class="fg"><label>Start Date</label><input type="date"></div>
    <div class="fg"><label>Project Description / Scope</label><textarea placeholder="Describe what you need Anika to deliver…"></textarea></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:8px;padding:11px" onclick="toast('Contract Sent! 🎉','Anika has been notified and has 48 hours to accept');closeModal()">Send Contract Offer →</button>
  `},
    'hire-james': {
      t: 'Hire James Kowalski', b: `
    <div style="display:flex;gap:10px;align-items:center;background:#eff6ff;border-radius:8px;padding:12px 14px;margin-bottom:16px">
      <div class="av" style="background:#dbeafe;color:#1e40af">JK</div>
      <div><div style="font-weight:700">James Kowalski</div><div style="font-size:12px;color:var(--uw-gray)">Full Stack Engineer · ★ 4.9</div></div>
    </div>
    <div class="fg"><label>Contract Type</label><select id="hire-james-contract-type" onchange="toggleHireFields('hire-james')"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option></select></div>
    <div id="hire-james-fixed-fields">
      <div class="fg"><label>Total Contract Amount ($)</label><input type="number" placeholder="e.g. 8000"></div>
      <div class="fg"><label>First Milestone</label><input type="text" placeholder="e.g. Milestone 1 — API Foundation"></div>
      <div class="fg"><label>Milestone Amount ($)</label><input type="number" placeholder="e.g. 2500"></div>
    </div>
    <div id="hire-james-hourly-fields" style="display:none"><div class="fg"><label>Hourly Rate ($)</label><input type="number" value="130"></div></div>
    <div class="fg"><label>Start Date</label><input type="date"></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:8px;padding:11px" onclick="toast('Contract Sent! 🎉','James has been notified and has 48 hours to accept');closeModal()">Send Contract Offer →</button>
  `},
    'hire-sofia': {
      t: 'Hire Sofia Reyes', b: `
    <div style="display:flex;gap:10px;align-items:center;background:#fef9ec;border-radius:8px;padding:12px 14px;margin-bottom:16px">
      <div class="av" style="background:#fef3c7;color:#92400e">SR</div>
      <div><div style="font-weight:700">Sofia Reyes</div><div style="font-size:12px;color:var(--uw-gray)">AI/ML Engineer · ★ 4.7</div></div>
    </div>
    <div class="fg"><label>Contract Type</label><select id="hire-sofia-contract-type" onchange="toggleHireFields('hire-sofia')"><option value="fixed">Fixed Price</option><option value="hourly">Hourly Rate</option></select></div>
    <div id="hire-sofia-fixed-fields">
      <div class="fg"><label>Total Contract Amount ($)</label><input type="number" placeholder="e.g. 10500"></div>
      <div class="fg"><label>First Milestone</label><input type="text" placeholder="e.g. Milestone 1 — AI Backend Setup"></div>
      <div class="fg"><label>Milestone Amount ($)</label><input type="number" placeholder="e.g. 3500"></div>
    </div>
    <div id="hire-sofia-hourly-fields" style="display:none"><div class="fg"><label>Hourly Rate ($)</label><input type="number" value="85"></div></div>
    <div class="fg"><label>Start Date</label><input type="date"></div>
    <button class="btn btn-g" style="width:100%;justify-content:center;margin-top:8px;padding:11px" onclick="toast('Contract Sent! 🎉','Sofia has been notified and has 48 hours to accept');closeModal()">Send Contract Offer →</button>
  `},
    'contract-anika': {
      t: 'Contract — Anika Nkosi', b: `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Type</div><div style="font-weight:700">Hourly · $90/hr</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Hours Logged</div><div style="font-weight:700">34.5 hrs</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Billed to Date</div><div style="font-weight:700">$3,105</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Started</div><div style="font-weight:700">Apr 28, 2026</div></div>
    </div>
    <div style="font-size:13.5px;font-weight:700;margin-bottom:8px">Work Diary — This Week</div>
    <div style="background:var(--uw-bg);border-radius:8px;padding:12px;font-size:13px;color:#374151;line-height:1.7;margin-bottom:16px;border:1.5px solid var(--uw-border)">
      <em>"Anika logged 8.5 hrs this week. Primary focus: mobile responsive variants of dashboard screens (~5h). Secondary: component library documentation in Figma (~3.5h). On track for end of week delivery."</em>
      <div style="font-size:11px;color:var(--uw-gray);margin-top:4px">— AI Work Summary</div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Paused','Contract paused')">Pause Contract</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Message sent','Chat opened with Anika')">Message</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Upwork room...')">📹 Video Call</button>
    </div>
  `},
    'contract-lena': {
      t: 'Contract — Lena Thornton', b: `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Type</div><div style="font-weight:700">Hourly · $65/hr</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Hours Logged</div><div style="font-weight:700">22 hrs</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Billed to Date</div><div style="font-weight:700">$1,430</div></div>
      <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)"><div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Started</div><div style="font-weight:700">Apr 15, 2026</div></div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Paused','Contract paused')">Pause Contract</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Message sent','Chat opened with Lena')">Message Lena</button>
    </div>
  `},
    'msg-anika': {
      t: 'Message from Anika Nkosi', b: `
    <div style="display:flex;gap:12px;align-items:center;background:var(--uw-green-light);border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#d1fae5;color:#065f46;width:42px;height:42px">AN</div>
      <div><div style="font-weight:700">Anika Nkosi</div><div style="font-size:12px;color:var(--uw-green);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;background:var(--uw-green);border-radius:50%;display:inline-block"></span>Online now</div></div>
    </div>
    <div style="background:var(--uw-bg);border-radius:8px;padding:14px;font-size:13.5px;color:#374151;line-height:1.75;margin-bottom:16px;border:1.5px solid var(--uw-border)">"Hi! I've completed the first set of dashboard screens — 6 screens total including the main overview, analytics detail, settings, and mobile variants. I've also set up Figma comment threads for each screen. Ready for your review whenever you have a moment!"</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="showPage('messages',document.querySelector('[onclick*=messages]'));closeModal()">Open Chat</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Video call','Opening Upwork meeting room...')">📹 Video Call</button>
    </div>
  `},
    'msg-james': {
      t: 'Message from James Kowalski', b: `
    <div style="display:flex;gap:12px;align-items:center;background:#eff6ff;border-radius:8px;padding:14px;margin-bottom:16px">
      <div class="av" style="background:#dbeafe;color:#1e40af;width:42px;height:42px">JK</div>
      <div><div style="font-weight:700">James Kowalski</div><div style="font-size:12px;color:var(--uw-gray)">Last seen 2 hours ago</div></div>
    </div>
    <div style="background:var(--uw-bg);border-radius:8px;padding:14px;font-size:13.5px;color:#374151;line-height:1.75;margin-bottom:16px;border:1.5px solid var(--uw-border)">"Milestone 2 is complete — all 47 unit tests passing, integration tests green, and I've pushed the final code to the repo. Please review and release the milestone payment when ready. I can also do a quick walkthrough call before you release."</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toast('Chat','Chat with James opened')">Reply</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toast('Video call','Opening meeting with James')">📹 Call</button>
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toast('Milestone Released ✓','$2,300 released to James Kowalski');closeModal()">Release Milestone $2,300 →</button>
    </div>
  `},
    'fund-milestone-james': { t: 'Fund Milestone — James Kowalski', b: fundMilestoneBody({ name: 'James Kowalski', initials: 'JK', avatarBg: '#dbeafe', avatarColor: '#1e40af', role: 'Full Stack Engineer', milestone: 'Milestone 3 — Final Delivery', amount: 2300, contract: 'Backend API Development' }) },
    'fund-milestone-marcus': { t: 'Fund Milestone — Marcus Patel', b: fundMilestoneBody({ name: 'Marcus Patel', initials: 'MP', avatarBg: '#ede9fe', avatarColor: '#5b21b6', role: 'AI/ML Engineer', milestone: 'Milestone 2 — AI Chatbot Build', amount: 1100, contract: 'AI Chatbot Integration' }) },
    'add-funds': {
      t: 'Add Funds to Balance', b: `
    <div class="balance-pill" id="add-funds-current-bal-pill">💰 Current Balance: $0.00</div>
    <div class="fg"><label>Amount to Add ($)</label><input type="number" placeholder="e.g. 500" min="1" id="add-funds-amount" oninput="updateAddFundsSummary(this)"></div>

    <div style="margin-bottom:16px">
      <label style="display:block;font-size:11px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Payment method</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div id="add-funds-method-card" onclick="selectAddFundsPaymentMethod('card')" style="border:2px solid var(--uw-green);border-radius:10px;padding:11px;cursor:pointer;text-align:center;background:var(--uw-green-light)">
          <div style="font-size:18px;margin-bottom:4px">💳</div>
          <div class="add-funds-method-label" style="font-size:12.5px;font-weight:700;color:var(--uw-green)">Card / Bank</div>
        </div>
        <div id="add-funds-method-crypto" onclick="selectAddFundsPaymentMethod('crypto')" style="border:1.5px solid var(--uw-border);border-radius:10px;padding:12px;cursor:pointer;text-align:center;background:white">
          <div style="font-size:18px;margin-bottom:4px">₿</div>
          <div class="add-funds-method-label" style="font-size:12.5px;font-weight:700;color:var(--uw-black)">Crypto (USDT)</div>
        </div>
      </div>
    </div>

    <div id="add-funds-card-notice" style="background:#f8fafc;border:1.5px dashed var(--uw-border);border-radius:10px;padding:16px;margin-bottom:20px;text-align:center">
      <div style="font-size:24px;margin-bottom:6px">🔒</div>
      <div style="font-size:13.5px;font-weight:700;color:var(--uw-black);margin-bottom:4px">Secure Paystack Checkout</div>
      <div style="font-size:11.5px;color:var(--uw-gray);line-height:1.5">You will be securely redirected to Paystack to complete your deposit. All major debit/credit cards, bank transfers, and mobile money options are fully supported.</div>
    </div>

    <div id="add-funds-crypto-form" style="display:none;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:16px;margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px">
        <div>
          <div id="add-funds-crypto-title" style="font-size:13.5px;font-weight:800;color:var(--uw-black);margin-bottom:4px">Pay with USDT (Tron TRC20)</div>
          <div style="font-size:12px;color:var(--uw-gray);line-height:1.5"><strong id="add-funds-crypto-rate">1 USDT = 1 USD</strong> · Send exactly <strong id="add-funds-crypto-amount">0 USDT</strong> to the address below.</div>
        </div>
        <span style="font-size:22px">₿</span>
      </div>
      <div style="font-size:11px;font-weight:700;color:#b45309;text-transform:uppercase;margin-bottom:6px">Deposit address</div>
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
        <code id="add-funds-crypto-address" style="flex:1;word-break:break-all;font-size:12px;background:white;border:1px solid var(--uw-border);border-radius:8px;padding:10px;display:block">—</code>
        <button type="button" class="btn btn-w btn-sm" onclick="copyAddFundsCryptoAddress()" style="flex-shrink:0">Copy</button>
      </div>
      <div id="add-funds-crypto-memo-wrap" style="display:none;margin-bottom:10px">
        <div style="font-size:11px;font-weight:700;color:#b45309;text-transform:uppercase;margin-bottom:6px">Memo (required)</div>
        <code id="add-funds-crypto-memo" style="word-break:break-all;font-size:12px;background:white;border:1px solid var(--uw-border);border-radius:8px;padding:10px;display:block">—</code>
      </div>
      <div style="font-size:12px;color:var(--uw-gray);line-height:1.55;margin-bottom:14px">
        Deposit <strong>USDT</strong> on the <strong id="add-funds-crypto-network">Tron (TRC20)</strong> network only. After your transfer is sent, click confirm — your balance is updated automatically once CCPayment verifies the deposit.
      </div>
      <button type="button" id="btn-confirm-crypto-deposit" class="btn btn-g" onclick="confirmCryptoDeposit()" style="width:100%;justify-content:center;padding:12px;font-weight:800;font-size:13px">I've deposited — Confirm payment</button>
    </div>

    <div class="fund-summary">
      <div class="fund-summary-row"><span style="color:var(--uw-gray)">Amount</span><span id="add-total">$0.00</span></div>
      <div class="fund-summary-row"><span style="color:var(--uw-gray)">Processing fee</span><span>$0.00</span></div>
      <div class="fund-summary-row total"><span>New balance after deposit</span><span id="add-new-bal" style="color:var(--uw-green)">$0.00</span></div>
    </div>
    <button id="btn-add-funds-submit" class="btn btn-g" style="width:100%;justify-content:center;padding:11px" onclick="handleAddFunds(this)">Add Funds →</button>
  `},
    'manage-cards': {
      t: 'Payment Methods', b: `
    <div style="margin-bottom:14px">
      <div style="font-size:13px;font-weight:700;margin-bottom:10px">Saved Cards</div>
      <div class="pay-method selected"><div class="pay-method-icon">💳</div><div class="pay-method-info"><div class="pay-method-name">Visa ending in 4821</div><div class="pay-method-sub">Expires 09/27</div></div><span class="pay-method-badge">PRIMARY</span></div>
      <div class="pay-method"><div class="pay-method-icon">🏦</div><div class="pay-method-info"><div class="pay-method-name">Mastercard ending in 3392</div><div class="pay-method-sub">Expires 03/26</div></div><button class="btn btn-w btn-sm" onclick="toast('Card removed','Mastercard ending in 3392 removed')" style="margin-left:auto">Remove</button></div>
    </div>
    <button class="btn btn-o" style="width:100%;justify-content:center" onclick="toast('Add card','Secure card entry form opening...')">+ Add a New Card</button>
  `},

    'dm-anika': {
      t: 'Message Anika Nkosi', b: buildDmModal({
        initials: 'AN', avatarBg: '#d1fae5', avatarColor: '#065f46',
        name: 'Anika Nkosi', role: 'UI/UX Designer', badge: '✦ Top Rated Plus', badgeCls: 'lvl-top-rated-plus',
        rate: '$90/hr', location: 'Berlin, Germany', rating: '★ 5.0', reviews: 127,
        hireModal: 'hire-anika',
        proposalFor: 'Brand Designer — Full Identity Redesign',
        proposalAmount: '$5,800 fixed',
        history: [
          { from: 'them', text: "Hi! I submitted my proposal for your brand redesign project. I\u2019d love to learn more about your vision for the 2026 rebrand \u2014 do you have any brand references or mood boards I could look at?", time: '1 hr ago' },
        ],
        placeholder: "Ask about their experience, timeline, availability…"
      })
    },

    'dm-james': {
      t: 'Message James Kowalski', b: buildDmModal({
        initials: 'JK', avatarBg: '#dbeafe', avatarColor: '#1e40af',
        name: 'James Kowalski', role: 'Full Stack Engineer', badge: '★ Expert-Vetted', badgeCls: 'lvl-expert-vetted',
        rate: '$130/hr', location: 'Toronto, Canada', rating: '★ 4.9', reviews: 89,
        hireModal: 'hire-james',
        proposalFor: 'Senior React Developer — Analytics Dashboard',
        proposalAmount: '$130/hr',
        history: [
          { from: 'them', text: "I just submitted my proposal \u2014 happy to hop on a quick call to walk you through my approach to real-time dashboards. I\u2019ve built 6 in the last 18 months and can share some live demos.", time: '3 hrs ago' },
        ],
        placeholder: "Ask about their tech stack, availability, or past projects…"
      })
    },

    'dm-sofia': {
      t: 'Message Sofia Reyes', b: buildDmModal({
        initials: 'SR', avatarBg: '#fef3c7', avatarColor: '#92400e',
        name: 'Sofia Reyes', role: 'AI/ML Engineer', badge: '↑ Rising Talent', badgeCls: 'lvl-rising',
        rate: '$85/hr', location: 'Mexico City', rating: '★ 4.7', reviews: 22,
        hireModal: 'hire-sofia',
        proposalFor: 'Senior React Developer — Analytics Dashboard',
        proposalAmount: '$10,500 fixed',
        history: [
          { from: 'them', text: "Thanks for posting this project! I submitted a proposal combining React on the frontend with FastAPI for real-time AI insights. I\u2019d be happy to share a short prototype I built for a similar use case \u2014 just let me know!", time: '5 hrs ago' },
        ],
        placeholder: "Ask about their AI/ML experience, approach, or availability…"
      })
    }
  };

  let currentJob = null;
  let _modalScrollY = 0;

  function lockBodyForModal() {
    _modalScrollY = window.scrollY || window.pageYOffset || 0;
    document.body.classList.add('modal-open');
    document.body.style.top = `-${_modalScrollY}px`;
  }

  function unlockBodyForModal() {
    document.body.classList.remove('modal-open');
    document.body.style.top = '';
    window.scrollTo(0, _modalScrollY);
  }

  function openModal(id, param = null) {
    if (id === 'add-milestone') {
      const freelancerId = param;
      const activeContracts = CONTRACTS.filter(c => c.freelancer_id == freelancerId && (c.status === 'active' || c.status === 'paused' || c.status === 'completed'));
      if (activeContracts.length === 0) {
        toast('Error', 'You must have an active, paused, or completed contract with this freelancer.');
        return;
      }
      
      const contract = activeContracts[0];
      
      MODALS['add-milestone'] = {
        t: 'Create Milestone',
        b: `
        <div style="padding:10px 0">
          <p style="font-size:13.5px; color:var(--uw-gray); margin-bottom:20px">
            Add a new milestone to your contract: <strong>${contract.job_title}</strong> with <strong>${contract.freelancer_name}</strong>.
          </p>
          <div style="margin-bottom:16px">
            <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px; color:var(--uw-black)">Milestone Description</label>
            <input type="text" id="ms-desc" placeholder="e.g. Phase 2: React Native app build" style="width:100%; padding:10px; border:1.5px solid var(--uw-border); border-radius:8px; outline:none; font-family:inherit; font-size:13px">
          </div>
          <div style="margin-bottom:20px">
            <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px; color:var(--uw-black)">Milestone Budget ($)</label>
            <input type="number" id="ms-amount" placeholder="e.g. 500" style="width:100%; padding:10px; border:1.5px solid var(--uw-border); border-radius:8px; outline:none; font-family:inherit; font-size:13px">
          </div>
          <div style="display:flex; gap:10px">
            <button class="btn btn-o" style="flex:1; justify-content:center" onclick="closeModal()">Cancel</button>
            <button class="btn btn-g" style="flex:2; justify-content:center" id="btn-submit-ms" onclick="submitNewMilestone(${contract.id}, ${freelancerId})">Create Milestone →</button>
          </div>
        </div>
        `
      };
    }

    const m = MODALS[id];
    if (!m) {
      toast('Unavailable', 'This action is not available yet.');
      return;
    }
    const mc = document.getElementById('mc-body');
    document.getElementById('mh-title').innerText = m.t;
    mc.innerHTML = m.b;
    mc.classList.toggle('pj-modal-mc', id === 'post-job');
    document.getElementById('overlay').classList.add('open');
    lockBodyForModal();

    if (id === 'post-job') {
      window.isEditingJobId = null;
      bindPostJobModal();
    }

    if (id === 'add-funds') {
      window.selectedAddFundsPaymentMethod = 'card';
      window.pendingCryptoDepositReference = null;
      const curBalEl = document.getElementById('add-funds-current-bal-pill');
      if (curBalEl) {
        curBalEl.innerHTML = `💰 Current Balance: $${parseFloat(availableBalance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      }
      const newBalEl = document.getElementById('add-new-bal');
      if (newBalEl) {
        newBalEl.textContent = `$${parseFloat(availableBalance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      }
      selectAddFundsPaymentMethod('card');
    }
  }

  // Update Add Funds summary dynamically
  window.updateAddFundsSummary = function (input) {
    const amt = parseFloat(input.value) || 0;
    const current = parseFloat(availableBalance || 0);
    const total = current + amt;

    const addTotalEl = document.getElementById('add-total');
    const addNewBalEl = document.getElementById('add-new-bal');

    if (addTotalEl) addTotalEl.textContent = '$' + amt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (addNewBalEl) addNewBalEl.textContent = '$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  function bindPostJobModal() {
    loadActiveJobCategories();
    const btn = document.getElementById('pj-submit-btn');
    const btnText = document.getElementById('pj-btn-text');
    if (btnText) btnText.innerText = 'Post Job →';
    if (!btn) return;
    btn.disabled = false;
    btn.type = 'button';

    // Use a cleaner event listener approach
    const onSubmit = (e) => {
      if (btn.disabled) return;

      // Only prevent default if it's a real event
      if (e && e.preventDefault) e.preventDefault();

      if (window.isEditingJobId) {
        updateJob(window.isEditingJobId);
      } else {
        submitPostJob();
      }
    };

    // Use a more universal approach for mobile
    btn.onclick = null; // Clear any old onclick
    btn.removeEventListener('click', btn._postJobHandler);
    btn.removeEventListener('touchstart', btn._postJobHandler);
    btn.removeEventListener('pointerdown', btn._postJobHandler);

    btn._postJobHandler = onSubmit;

    // Use pointerdown for fast response if supported, else touchstart
    if (window.PointerEvent) {
      btn.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'touch' || e.pointerType === 'mouse') {
          onSubmit(e);
        }
      });
    } else {
      btn.addEventListener('touchstart', onSubmit, { passive: false });
    }
    btn.addEventListener('click', onSubmit);

    // Also bind to enter key on inputs
    const formInputs = document.querySelectorAll('#pj-form input, #pj-form select, #pj-form textarea');
    formInputs.forEach(input => {
      input.onkeydown = (e) => {
        if (e.key === 'Enter' && e.ctrlKey) {
          onSubmit(e);
        }
      };
    });
  }
  window.__openModalImpl = openModal;
  window.openModal = openModal;

  function encodeJobId(id) {
    const xor = parseInt(id, 10) ^ 958273;
    const str = String(xor);
    const encoded = btoa(str);
    return encoded.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  function getPublicJobUrl(jobId) {
    const base = (typeof BASE_URL === 'string' ? BASE_URL : '/').replace(/\/?$/, '/');
    return base + 'j/' + encodeJobId(jobId);
  }

  function copyJobShareLink(jobId) {
    const url = getPublicJobUrl(jobId);
    const done = function() {
      toast('Link copied', 'Job link copied to clipboard. Share it with freelancers or anyone.');
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(done).catch(function() {
        fallbackCopyJobLink(url, done);
      });
    } else {
      fallbackCopyJobLink(url, done);
    }
  }

  function fallbackCopyJobLink(text, onSuccess) {
    const input = document.createElement('input');
    input.value = text;
    input.setAttribute('readonly', '');
    input.style.position = 'fixed';
    input.style.left = '-9999px';
    document.body.appendChild(input);
    input.select();
    try {
      document.execCommand('copy');
      if (onSuccess) onSuccess();
    } catch (e) {
      toast('Copy link', text);
    }
    document.body.removeChild(input);
  }

  function viewJobDetails(job) {
    currentJob = job;
    const shareUrl = getPublicJobUrl(job.id);

    let actionButtons = '';
    if (job.status === 'open') {
      actionButtons = `
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="editJob()">Edit Job</button>
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="showPage('proposals',document.querySelector('[onclick*=proposals]'));closeModal()">View Proposals (${job.proposal_count})</button>
      <button class="btn btn-w" style="flex:1;justify-content:center;color:#ef4444;border-color:#fecaca" onclick="toggleJobStatus(${job.id}, 'cancelled')">Cancel Job</button>
    `;
    } else if (job.status === 'in_progress') {
      actionButtons = `
      <button class="btn btn-o" style="flex:1;justify-content:center" onclick="toggleJobStatus(${job.id}, 'closed')">Mark Complete</button>
      <button class="btn btn-w" style="flex:1;justify-content:center" onclick="toggleJobStatus(${job.id}, 'paused')">Pause</button>
      <button class="btn btn-w" style="flex:1;justify-content:center;color:#ef4444;border-color:#fecaca" onclick="toggleJobStatus(${job.id}, 'cancelled')">Cancel Job</button>
    `;
    } else if (job.status === 'paused') {
      actionButtons = `
      <button class="btn btn-g" style="flex:1;justify-content:center" onclick="toggleJobStatus(${job.id}, 'open')">Resume</button>
      <button class="btn btn-w" style="flex:1;justify-content:center;color:#ef4444;border-color:#fecaca" onclick="toggleJobStatus(${job.id}, 'cancelled')">Cancel</button>
    `;
    }

    let deleteButton = `<button class="btn btn-w" style="flex:1;justify-content:center;color:#ef4444;border-color:#fecaca" onclick="deleteJob(${job.id})">🗑️ Delete</button>`;

    MODALS['view-job'] = {
      t: job.title,
      b: `
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <span class="badge b-${job.status === 'open' ? 'green' : (job.status === 'paused' ? 'yellow' : 'gray')}">${job.status.charAt(0).toUpperCase() + job.status.slice(1)}</span>
        <span class="badge b-blue">${job.budget_type === 'fixed' ? 'Fixed-price' : 'Hourly'}</span>
        <span class="badge b-gray">${job.category}</span>
        <span class="badge b-purple" style="font-size:11px">${job.subcategory || 'General'}</span>
      </div>
      <div class="g2" style="margin-bottom:14px">
        <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)">
          <div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Budget</div>
          <div style="font-weight:700">$${new Intl.NumberFormat().format(job.budget)}</div>
        </div>
        <div style="background:var(--uw-bg);padding:12px;border-radius:8px;border:1.5px solid var(--uw-border)">
          <div style="font-size:11px;color:var(--uw-gray);margin-bottom:2px">Proposals</div>
          <div style="font-weight:700;color:var(--uw-green)">${job.proposal_count} received</div>
        </div>
      </div>
      <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:14px">${job.description}</div>
      <div style="border-top:1.5px solid var(--uw-border);padding-top:16px;margin-bottom:20px">
        <div style="font-size:13px;font-weight:700;margin-bottom:6px">Share job link</div>
        <p style="font-size:12px;color:var(--uw-gray);margin:0 0 10px;line-height:1.5">Copy this link to share the public job page. Anyone can view the posting; freelancers can sign in to apply.</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <input type="text" readonly value="${shareUrl}" id="job-share-url-${job.id}" style="flex:1;min-width:180px;padding:9px 12px;font-size:12px;border:1.5px solid var(--uw-border);border-radius:8px;background:var(--uw-bg);color:var(--uw-black);font-family:inherit" onclick="this.select()">
          <button type="button" class="btn btn-g btn-sm" onclick="copyJobShareLink(${job.id})">Copy link</button>
          <a href="${shareUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-w btn-sm" style="text-decoration:none">View page</a>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:16px;margin-bottom:24px;flex-wrap:wrap">
        ${actionButtons}
        ${deleteButton}
      </div>
    `
    };
    openModal('view-job');
  }
  function viewProposalDetails(p) {
    let milestoneHtml = '';
    if (p.milestones && p.milestones.length > 0) {
      milestoneHtml = `
      <div style="margin-top:20px; background:#f9fafb; padding:16px; border-radius:12px; border:1.5px solid var(--uw-border)">
        <h4 style="margin:0 0 12px 0; font-size:14px; text-transform:uppercase; color:var(--uw-gray); letter-spacing:0.05em">Proposed Milestones</h4>
        <div style="display:grid; gap:10px">
          ${p.milestones.map((ms, i) => `
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:13.5px">
              <div style="display:flex; gap:10px; align-items:center">
                <span style="width:20px; height:20px; background:var(--uw-green-light); color:var(--uw-green); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700">${i + 1}</span>
                <span style="color:var(--uw-black)">${ms.description}</span>
              </div>
              <span style="font-weight:700">$${new Intl.NumberFormat().format(ms.amount)}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `;
    }

    let actionButtons = '';
    if (p.status === 'accepted') {
      actionButtons = `
      <button class="btn btn-w" style="flex:1;justify-content:center;padding:12px" onclick="closeModal();showChatWithFreelancer(${p.freelancer_id}, '${p.freelancer_name.replace(/'/g, "\\'")}', '${p.freelancer_avatar || ''}')">💬 Message</button>
    `;

    } else {
      actionButtons = `
      <button class="btn btn-w" style="flex:1;justify-content:center;padding:12px" onclick="closeModal();showChatWithFreelancer(${p.freelancer_id}, '${p.freelancer_name.replace(/'/g, "\\'")}', '${p.freelancer_avatar || ''}')">💬 Message</button>
      <button class="btn btn-o" style="flex:1;justify-content:center;padding:12px" onclick="closeModal();updateProposalStatus(${p.id}, '${p.status === 'shortlisted' ? 'pending' : 'shortlisted'}')">${p.status === 'shortlisted' ? 'Unshortlist' : 'Shortlist'}</button>
      <button class="btn btn-g" style="flex:1.5;justify-content:center;padding:12px" onclick="closeModal();hireFreelancer(${p.id}, ${p.bid_amount}, '${(p.budget_type || 'fixed').replace(/'/g, "\\'")}')">Hire Freelancer →</button>
    `;
    }

    MODALS['view-proposal'] = {
      t: 'Proposal: ' + p.job_title,
      b: `
      <div style="display:flex; gap:16px; align-items:center; margin-bottom:20px">
        <div class="av" style="width:56px;height:56px">
          ${p.freelancer_avatar ? `<img src="${BASE_URL}${p.freelancer_avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">` :
          `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:18px">${p.freelancer_name.substring(0, 2).toUpperCase()}</div>`}
        </div>
        <div style="flex:1">
          <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
            <h3 style="margin:0; font-size:18px">${p.freelancer_name}</h3>
            <span class="badge b-${p.status === 'shortlisted' ? 'blue' : (p.status === 'archived' ? 'gray' : 'green')}" style="font-size:10px">${p.status.charAt(0).toUpperCase() + p.status.slice(1)}</span>
          </div>
          <div style="font-size:13px; color:var(--uw-gray)">${p.freelancer_title || 'Freelancer'} · ★ ${p.freelancer_rating || '0.0'} (${p.freelancer_reviews_count || 0} reviews) · JSS: ${p.freelancer_jss || 'N/A'} ${p.freelancer_badge ? `· <span style="color:var(--uw-green); font-weight:700">${p.freelancer_badge}</span>` : ''} · $${parseFloat(p.freelancer_hourly_rate || 0).toFixed(2)}/hr</div>
        </div>
        <div style="text-align:right">
          <div style="font-size:24px; font-weight:800; color:var(--uw-black)">$${new Intl.NumberFormat().format(p.bid_amount)}</div>
          <div style="font-size:12px; color:var(--uw-gray)">Proposed Budget</div>
        </div>
      </div>

      <div style="margin-bottom:20px">
        <h4 style="font-size:14px; margin:0 0 8px 0; color:var(--uw-black)">Cover Letter</h4>
        <div style="font-size:14.5px; color:#374151; line-height:1.7; white-space:pre-wrap">${p.cover_letter}</div>
      </div>

      ${p.attachments ? `
        <div style="margin-bottom:20px">
          <h4 style="font-size:14px; margin:0 0 8px 0; color:var(--uw-black)">Attachments</h4>
          <div style="padding:10px; background:var(--uw-bg); border-radius:8px; border:1px solid var(--uw-border); display:inline-flex; align-items:center; gap:8px">
            <span style="font-size:16px">🔗</span>
            <a href="${p.attachments}" target="_blank" style="font-size:13px; color:var(--uw-green); font-weight:600; text-decoration:none">${p.attachments}</a>
          </div>
        </div>
      ` : ''}

      ${milestoneHtml}

      <div style="display:flex; gap:12px; margin-top:25px; border-top:1px solid var(--uw-border); padding-top:20px">
        ${actionButtons}
      </div>
    `
    };
    openModal('view-proposal');
  }


  async function toggleJobStatus(jobId, newStatus) {
    toast('Updating...', 'Changing job status');
    const formData = new FormData();
    formData.append('job_id', jobId);
    formData.append('status', newStatus);

    try {
      const res = await fetch(BASE_URL + 'actions/update_job_status.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Updated! 🎉', result.message);
        setTimeout(() => location.reload(), 1000);
      } else {
        toast('Error', result.error || 'Failed to update status');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  async function deleteJob(jobId) {
    if (!(await remoConfirm('This will also remove any received proposals and cannot be undone.', 'Delete this job post?', { danger: true, confirmLabel: 'Delete' }))) {
      return;
    }
    toast('Deleting...', 'Removing job post...');
    const formData = new FormData();
    formData.append('job_id', jobId);

    try {
      const res = await fetch(BASE_URL + 'actions/delete_job.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Deleted! 🗑️', result.message);
        closeModal();
        setTimeout(() => location.reload(), 1000);
      } else {
        toast('Error', result.error || 'Failed to delete job post');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  async function editJob() {
    const job = currentJob;
    if (!job) return;
    openModal('post-job');
    await loadActiveJobCategories();
    window.isEditingJobId = job.id;
    document.getElementById('mh-title').innerText = 'Edit Job Post';

    // Fill fields
    document.getElementById('pj-title').value = job.title;
    document.getElementById('pj-cat').value = job.category;
    updateSubcats();
    document.getElementById('pj-subcat').value = job.subcategory || '';
    updateSpecialties();
    document.getElementById('pj-spec').value = job.specialty || '';
    document.getElementById('pj-billing-type').value = job.budget_type;
    if (job.budget_type === 'hourly') {
      document.getElementById('pj-min-rate').value = job.min_hourly_rate || '';
      document.getElementById('pj-max-rate').value = job.max_hourly_rate || '';
    } else if (job.budget_type === 'monthly') {
      document.getElementById('pj-monthly-rate').value = job.budget || '';
    } else {
      document.getElementById('pj-budget').value = job.budget || '';
    }
    updatePostJobFields();
    document.getElementById('pj-desc').value = job.description;

    // Parse skills from JSON if it's a string
    let skillsArr = job.skills_required;
    if (typeof skillsArr === 'string') {
      try { skillsArr = JSON.parse(skillsArr); } catch (e) { skillsArr = []; }
    }
    document.getElementById('pj-skills').value = Array.isArray(skillsArr) ? skillsArr.join(', ') : '';

    // Change button text (onSubmit listener handles invocation dynamically via window.isEditingJobId)
    const btnText = document.getElementById('pj-btn-text');
    if (btnText) btnText.innerText = 'Save Changes';
  }

  async function updateJob(jobId) {
    const title = document.getElementById('pj-title').value.trim();
    const cat = document.getElementById('pj-cat').value;
    const subcat = document.getElementById('pj-subcat').value;
    const spec = document.getElementById('pj-spec').value;
    const type = document.getElementById('pj-billing-type').value;

    let budget = '';
    let minRate = '';
    let maxRate = '';

    if (type === 'hourly') {
      const minEl = document.getElementById('pj-min-rate');
      const maxEl = document.getElementById('pj-max-rate');
      minRate = minEl ? minEl.value : '';
      maxRate = maxEl ? maxEl.value : '';
      if (!minRate || !maxRate) {
        return toast('Error', 'Please enter minimum and maximum hourly rates.');
      }
      if (parseFloat(minRate) > parseFloat(maxRate)) {
        return toast('Error', 'Minimum hourly rate cannot be greater than maximum hourly rate.');
      }
    } else if (type === 'monthly') {
      const monthlyEl = document.getElementById('pj-monthly-rate');
      budget = monthlyEl ? monthlyEl.value : '';
      if (!budget) return toast('Error', 'Please enter monthly rate.');
    } else {
      const budgetEl = document.getElementById('pj-budget');
      budget = budgetEl ? budgetEl.value : '';
      if (!budget) return toast('Error', 'Please enter budget.');
    }

    const desc = document.getElementById('pj-desc').value.trim();
    const skills = document.getElementById('pj-skills').value.trim();

    if (!title || !cat || !desc) {
      return toast('Error', 'Please fill in job title, category, and description');
    }

    const btn = document.getElementById('pj-submit-btn');
    const btnText = document.getElementById('pj-btn-text');
    if (btn && btn.disabled) return;
    if (btn) btn.disabled = true;
    if (btnText) btnText.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px"></span>Saving...';

    toast('Saving...', 'Updating your job post');
    const formData = new FormData();
    formData.append('job_id', jobId);
    formData.append('title', title);
    formData.append('category', cat);
    formData.append('subcategory', subcat || 'General');
    formData.append('specialty', spec);
    formData.append('budget_type', type);
    formData.append('budget', budget || '0');
    formData.append('min_hourly_rate', minRate);
    formData.append('max_hourly_rate', maxRate);
    formData.append('description', desc);
    formData.append('skills', skills);

    try {
      const res = await fetch(BASE_URL + 'actions/edit_job.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Success! 🎉', 'Job updated successfully.');
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to update job');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  function closeModal() {
    document.getElementById('overlay').classList.remove('open');
    const mc = document.getElementById('mc-body');
    if (mc) mc.classList.remove('pj-modal-mc');
    unlockBodyForModal();
  }
  window.__closeModalImpl = closeModal;
  window.closeModal = closeModal;
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal() });
  (function initModalOverlay() {
    const overlay = document.getElementById('overlay');
    const backdrop = document.getElementById('overlay-backdrop');
    const panel = document.getElementById('modal-panel');
    if (!overlay) return;

    if (backdrop) {
      backdrop.addEventListener('click', closeModal);
    }

    if (panel) {
      panel.addEventListener('click', (e) => {
        if (e.target.closest('.mclose')) {
          e.preventDefault();
          closeModal();
        }
      });
    }
  })();

  function initMobFab() {
    const fab = document.getElementById('mob-fab');
    if (!fab || fab.dataset.bound === '1') return;
    fab.dataset.bound = '1';
    fab.addEventListener('click', (e) => {
      e.preventDefault();
      openModal('post-job');
    });
  }

  function filterTalent(query) {
    const q = query.toLowerCase();
    const cards = document.querySelectorAll('.talent-card');
    cards.forEach(card => {
      const text = card.innerText.toLowerCase();
      card.style.display = text.includes(q) ? 'block' : 'none';
    });
  }

  let tt;
  function toast(title, msg) {
    const el = document.getElementById('toast');
    const titleEl = document.getElementById('t-title');
    const msgEl = document.getElementById('t-msg');
    if (!el || !titleEl || !msgEl) {
      console.warn('Toast elements missing from DOM:', { el, titleEl, msgEl });
      return;
    }
    titleEl.textContent = title;
    msgEl.textContent = msg ? (' — ' + msg) : '';
    el.classList.add('show');
    clearTimeout(tt);
    tt = setTimeout(() => el.classList.remove('show'), 3500);
  }

  const getAvatarUrl = (avatar) => {
    if (!avatar) return '';
    if (avatar.startsWith('http://') || avatar.startsWith('https://')) return avatar;
    const cleanBase = BASE_URL.replace(/\/+$/, '');
    const cleanPath = avatar.replace(/^\/+/, '');
    return cleanBase + '/' + cleanPath;
  };

  let activeChatId = null;
  let activeChatName = '';
  let activeChatInitials = '';
  let activeChatAvatar = '';
  let pendingChatAttachment = null;

  function escapeChatHtml(text) {
    const el = document.createElement('div');
    el.textContent = text == null ? '' : String(text);
    return el.innerHTML;
  }

  function messageAttachmentUrl(messageId) {
    const base = (typeof BASE_URL === 'string' ? BASE_URL : '/').replace(/\/?$/, '/');
    return base + 'actions/download_message_attachment.php?id=' + messageId;
  }

  function renderMessageAttachmentHtml(m, isMe) {
    if (!m.attachment_path && !m.attachment_name) return '';
    const name = escapeChatHtml(m.attachment_name || 'Attachment');
    const url = messageAttachmentUrl(m.id);
    const style = isMe
      ? 'display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:8px 12px;background:rgba(255,255,255,.18);border-radius:8px;color:#fff;font-size:12px;font-weight:600;text-decoration:none'
      : 'display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#15803d;font-size:12px;font-weight:600;text-decoration:none';
    return `<a href="${url}" class="msg-attachment-link" style="${style}" target="_blank" rel="noopener noreferrer">📎 ${name}</a>`;
  }

  function onChatAttachmentSelected(input) {
    const file = input.files && input.files[0];
    if (!file) return;
    pendingChatAttachment = file;
    updateChatAttachmentPreview();
  }

  function updateChatAttachmentPreview() {
    const el = document.getElementById('chat-attachment-preview');
    if (!el) return;
    if (!pendingChatAttachment) {
      el.style.display = 'none';
      el.innerHTML = '';
      return;
    }
    el.style.display = 'flex';
    el.innerHTML = `
      <span style="font-size:12px;color:var(--uw-gray2);flex:1">📎 ${escapeChatHtml(pendingChatAttachment.name)}</span>
      <button type="button" class="btn btn-sm" style="padding:2px 8px;font-size:11px" onclick="clearChatAttachment()">Remove</button>
    `;
  }

  function clearChatAttachment() {
    pendingChatAttachment = null;
    const input = document.getElementById('chat-attachment-input');
    if (input) input.value = '';
    updateChatAttachmentPreview();
  }

  function autoGrowChatInput(el) {
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
  }

  async function loadChat(otherId, name, initials, el, avatar = '') {
    activeChatId = otherId;
    activeChatName = name;
    activeChatInitials = initials;
    activeChatAvatar = avatar;

    // Highlight sidebar
    if (el) {
      document.querySelectorAll('.msg-item').forEach(i => i.classList.remove('active'));
      el.classList.add('active');
      el.classList.remove('unread');
      const dot = el.querySelector('.msg-dot');
      if (dot) dot.remove();
    }

    const chatWindow = document.getElementById('chat-window');
    chatWindow.innerHTML = `<div style="flex:1;display:flex;align-items:center;justify-content:center"><span class="spinner"></span></div>`;

    try {
      const response = await fetch(`${BASE_URL}/actions/get_messages.php?with=${otherId}`);
      const result = await response.json();

      if (result.success) {
        activeChatBlocked = !!result.blocked;
        activeChatBlockedByMe = !!result.blocked_by_me;
        renderChatWindow(name, initials, result.messages, avatar);
        scheduleConversationsRefresh(300);
        if (!activeChatBlocked) {
          startChatPolling(otherId, name, initials, avatar);
        }
      } else {
        chatWindow.innerHTML = `<div style="padding:20px;text-align:center;color:red">${result.error}</div>`;
      }
    } catch (err) {
      chatWindow.innerHTML = `<div style="padding:20px;text-align:center;color:red">Failed to load messages</div>`;
    }
  }

  function renderChatWindow(name, initials, messages, avatar = '') {
    const chatWindow = document.getElementById('chat-window');
    const msgHtml = messages.map(m => {
      const isMe = (m.sender_id != activeChatId);

      // Inline rich card for proposed milestones
      let bubbleContent = m.message || '';
      if (!isMe && bubbleContent.startsWith('PROPOSED MILESTONE:')) {
        bubbleContent = `
        <div style="margin-bottom:10px">${m.message}</div>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; margin-top:8px; display:flex; flex-direction:column; gap:8px; align-items:flex-start">
          <div style="display:flex; align-items:center; gap:6px; color:#1e40af; font-weight:700; font-size:12px">
            <span>💼</span> Proposed Milestone Request
          </div>
          <div style="font-size:11.5px; color:#1e3a8a; line-height:1.4">
            The freelancer has added this milestone to your contract. You can review and fund it now.
          </div>
          <button class="btn btn-g btn-sm" onclick="showPage('contracts')" style="padding:4px 10px; font-size:11px; margin-top:4px">Review & Fund Milestone</button>
        </div>
      `;
      } else if (isMe && bubbleContent.startsWith('CREATED MILESTONE:')) {
        bubbleContent = `
        <div style="margin-bottom:10px">${m.message}</div>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; margin-top:8px; display:flex; flex-direction:column; gap:8px; align-items:flex-start">
          <div style="display:flex; align-items:center; gap:6px; color:#1e40af; font-weight:700; font-size:12px">
            <span>💼</span> Created Milestone
          </div>
          <div style="font-size:11.5px; color:#1e3a8a; line-height:1.4">
            You have added this milestone to the contract. You can fund it now.
          </div>
          <button class="btn btn-g btn-sm" onclick="showPage('contracts')" style="padding:4px 10px; font-size:11px; margin-top:4px">Fund Milestone</button>
        </div>
      `;
      }

      return `
      <div style="display:flex;gap:10px;${isMe ? 'flex-direction:row-reverse' : ''}">
        <div class="av" style="width:30px;height:30px;font-size:10px">
          ${isMe ? '<div style="background:var(--uw-green);color:white;width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%">Me</div>' :
          (avatar ? `<div class="av" style="position:relative;width:100%;height:100%"><img src="${getAvatarUrl(avatar)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:700">${initials}</div></div>` :
            `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%">${initials}</div>`)}
        </div>
        <div style="max-width:75%;${isMe ? 'text-align:right' : ''}">
          <div style="background:${isMe ? 'var(--uw-green)' : 'var(--uw-bg)'};color:${isMe ? 'white' : 'var(--uw-dark)'};border:${isMe ? 'none' : '1.5px solid var(--uw-border)'};border-radius:${isMe ? '12px 2px 12px 12px' : '2px 12px 12px 12px'};padding:10px 14px;font-size:13px;line-height:1.6;text-align:left">${bubbleContent}${renderMessageAttachmentHtml(m, isMe)}</div>
          <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px">${new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
        </div>
      </div>
    `;
    }).join('');

    const hasContract = (typeof CONTRACTS !== 'undefined' && Array.isArray(CONTRACTS)) 
      ? CONTRACTS.some(c => c.freelancer_id == activeChatId && (c.status === 'active' || c.status === 'paused' || c.status === 'completed'))
      : false;
      
    const milestoneBtn = hasContract && !activeChatBlocked ? `
      <button class="btn btn-g btn-sm" onclick="openModal('add-milestone', ${activeChatId})" style="padding:6px 12px;font-size:12.5px;display:flex;align-items:center;gap:6px;margin-left:auto">
        <span>➕</span> Add Milestone
      </button>
    ` : '';

    const blockBtn = !activeChatBlockedByMe ? `
      <button type="button" id="chat-block-btn" class="btn btn-sm" title="Block freelancer" style="padding:6px 12px;font-size:12px;color:#991b1b;border-color:#fecaca">Block</button>
    ` : `
      <button type="button" id="chat-unblock-btn" class="btn btn-sm" style="padding:6px 12px;font-size:12px">Unblock</button>
    `;

    const composerHtml = activeChatBlocked ? `
    <div class="chat-pane-composer chat-pane-composer--blocked">
      <div style="font-size:13px;color:var(--uw-gray2);text-align:center;line-height:1.5">
        ${activeChatBlockedByMe
          ? 'You blocked this freelancer. They cannot message you until you unblock them.'
          : 'Messaging is unavailable for this conversation.'}
      </div>
      ${activeChatBlockedByMe ? `<div style="text-align:center;margin-top:10px"><button type="button" id="chat-unblock-composer-btn" class="btn btn-g btn-sm">Unblock &amp; chat again</button></div>` : ''}
    </div>
    ` : `
    <div class="chat-pane-composer">
      <div id="chat-attachment-preview" style="display:none;align-items:center;gap:8px;margin-bottom:8px;padding:8px 10px;background:var(--uw-bg);border:1px solid var(--uw-border);border-radius:8px"></div>
      <div style="display:flex;gap:10px;align-items:center">
        <input type="file" id="chat-attachment-input" style="display:none" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" onchange="onChatAttachmentSelected(this)">
        <button type="button" class="btn" title="Attach file" style="padding:9px 12px;font-size:16px;line-height:1" onclick="document.getElementById('chat-attachment-input').click()">📎</button>
        <textarea id="chat-input" rows="1" style="flex:1;padding:9px 14px;border:1.5px solid var(--uw-border);border-radius:16px;font-size:13px;font-family:inherit;outline:none;line-height:1.4;resize:none;overflow-y:auto;min-height:40px;max-height:120px" placeholder="Type a message…" oninput="autoGrowChatInput(this)" onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendMsg();}"></textarea>
        <button class="btn btn-g" onclick="sendMsg()">Send</button>
      </div>
    </div>
    `;

    chatWindow.innerHTML = `
    <div class="chat-pane-header">
      <div style="display:flex;align-items:center;gap:12px">
        <div class="av" style="width:36px;height:36px">
          ${avatar ? `<div class="av" style="position:relative;width:100%;height:100%"><img src="${getAvatarUrl(avatar)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:700">${initials}</div></div>` :
          `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%">${initials}</div>`}
        </div>
        <div><div style="font-weight:700;font-size:14px">${name}</div><div style="font-size:12px;color:${activeChatBlockedByMe ? 'var(--uw-gray)' : 'var(--uw-green)'}">${activeChatBlockedByMe ? 'Blocked' : 'Online'}</div></div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">${blockBtn}${milestoneBtn}</div>
    </div>
    <div id="chat-messages-scroll">${msgHtml}</div>
    ${composerHtml}
  `;
    const scroll = document.getElementById('chat-messages-scroll');
    scroll.scrollTop = scroll.scrollHeight;
    autoGrowChatInput(document.getElementById('chat-input'));
    updateChatAttachmentPreview();
    bindChatBlockButtons(activeChatId, name);
  }

  function bindChatBlockButtons(freelancerId, name) {
    const blockBtnEl = document.getElementById('chat-block-btn');
    if (blockBtnEl) {
      blockBtnEl.onclick = () => blockFreelancer(freelancerId, name);
    }
    const unblockHeaderBtn = document.getElementById('chat-unblock-btn');
    if (unblockHeaderBtn) {
      unblockHeaderBtn.onclick = () => unblockFreelancer(freelancerId, name);
    }
    const unblockComposerBtn = document.getElementById('chat-unblock-composer-btn');
    if (unblockComposerBtn) {
      unblockComposerBtn.onclick = () => unblockFreelancer(freelancerId, name);
    }
  }

  async function blockFreelancer(freelancerId, name) {
    if (!freelancerId) return;
    if (!(await remoConfirm(`They will not be able to message you.`, `Block ${name}?`))) return;

    try {
      const res = await fetch(BASE_URL + 'client/api/block-freelancer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ freelancer_id: freelancerId })
      });
      const data = await res.json();
      if (!data.success) {
        toast('Error', data.message || 'Could not block freelancer.');
        return;
      }
      toast('Blocked', data.message || 'Freelancer blocked.');
      if (!BLOCKED_FREELANCER_IDS.includes(freelancerId)) {
        BLOCKED_FREELANCER_IDS.push(freelancerId);
      }
      removeConversationFromList(freelancerId);
      appendBlockedListItem(freelancerId, name);
      activeChatBlocked = true;
      activeChatBlockedByMe = true;
      if (activeChatId === freelancerId) {
        if (chatPollInterval) clearInterval(chatPollInterval);
        const res = await fetch(`${BASE_URL}/actions/get_messages.php?with=${freelancerId}`);
        const payload = await res.json();
        if (payload.success) {
          renderChatWindow(activeChatName, activeChatInitials, payload.messages, activeChatAvatar);
        }
      }
    } catch (e) {
      toast('Error', 'Could not block freelancer.');
    }
  }

  async function unblockFreelancer(freelancerId, name, btnEl) {
    if (!freelancerId) return;

    try {
      const res = await fetch(BASE_URL + 'client/api/unblock-freelancer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ freelancer_id: freelancerId })
      });
      const data = await res.json();
      if (!data.success) {
        toast('Error', data.message || 'Could not unblock freelancer.');
        return;
      }
      toast('Unblocked', data.message || 'You can chat again.');
      const idx = BLOCKED_FREELANCER_IDS.indexOf(freelancerId);
      if (idx >= 0) BLOCKED_FREELANCER_IDS.splice(idx, 1);
      removeBlockedListItem(freelancerId, btnEl);
      activeChatBlocked = false;
      activeChatBlockedByMe = false;
      if (activeChatId === freelancerId) {
        loadChat(freelancerId, activeChatName || name, activeChatInitials, null, activeChatAvatar);
      }
    } catch (e) {
      toast('Error', 'Could not unblock freelancer.');
    }
  }

  window.blockFreelancer = blockFreelancer;
  window.unblockFreelancer = unblockFreelancer;

  function removeConversationFromList(freelancerId) {
    const list = document.getElementById('conversations-list');
    if (!list) return;
    const targetIdStr = String(freelancerId);
    list.querySelectorAll('.msg-item').forEach(item => {
      const onclick = item.getAttribute('onclick') || '';
      const dsId = String(item.dataset?.otherId || '');
      const onclickFn = item.onclick ? item.onclick.toString() : '';
      if (
        dsId === targetIdStr ||
        onclick.includes(`loadChat(${freelancerId}`) ||
        onclickFn.includes(targetIdStr)
      ) {
        item.remove();
      }
    });
    if (!list.querySelector('.msg-item') && !list.querySelector('.blocked-freelancer-item')) {
      const empty = document.createElement('div');
      empty.style.cssText = 'padding:20px;text-align:center;color:var(--uw-gray);font-size:13px';
      empty.textContent = 'No conversations yet.';
      list.prepend(empty);
    }
  }

  function removeBlockedListItem(freelancerId, btnEl) {
    const row = btnEl?.closest?.('.blocked-freelancer-item')
      || document.querySelector(`.blocked-freelancer-item[data-freelancer-id="${freelancerId}"]`);
    if (row) row.remove();
    const section = document.getElementById('blocked-freelancers-list');
    if (section && !section.children.length) {
      section.closest('div')?.remove();
    }
  }

  function appendBlockedListItem(freelancerId, name) {
    let section = document.getElementById('blocked-freelancers-list');
    if (!section) {
      const sidebar = document.getElementById('messages-sidebar');
      if (!sidebar) return;
      const wrap = document.createElement('div');
      wrap.className = 'messages-blocked';
      wrap.innerHTML = '<div style="font-size:11px;font-weight:700;color:var(--uw-gray);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Blocked</div><div id="blocked-freelancers-list"></div>';
      sidebar.appendChild(wrap);
      section = document.getElementById('blocked-freelancers-list');
    }
    const list = document.getElementById('conversations-list');
    list?.querySelector('div[style*="No conversations"]')?.remove();
    const initials = (name || '?').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
    const row = document.createElement('div');
    row.className = 'blocked-freelancer-item';
    row.dataset.freelancerId = String(freelancerId);
    row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 0';
    row.innerHTML = `
      <div class="av" style="width:28px;height:28px;flex-shrink:0"><div style="background:#f3f4f6;color:var(--uw-gray);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:11px">${initials}</div></div>
      <div style="flex:1;min-width:0"><div style="font-size:12.5px;font-weight:600;color:var(--uw-gray2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeChatHtml(name)}</div></div>
      <button type="button" class="btn btn-sm" style="font-size:11px;padding:4px 10px;flex-shrink:0">Unblock</button>
    `;
    row.querySelector('button').onclick = () => unblockFreelancer(freelancerId, name, row.querySelector('button'));
    section.appendChild(row);
  }

  async function sendMsg() {
    if (activeChatBlocked) {
      toast('Blocked', 'Unblock this freelancer to send messages.');
      return;
    }
    const input = document.getElementById('chat-input');
    const msg = input?.value?.trim() ?? '';
    const file = pendingChatAttachment;
    if ((!msg && !file) || !activeChatId) return;

    const chatMessagesScroll = document.getElementById('chat-messages-scroll');
    const tempId = 'temp-' + Date.now();
    const attachPreview = file
      ? `<a style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:8px 12px;background:rgba(255,255,255,.18);border-radius:8px;color:#fff;font-size:12px">📎 ${escapeChatHtml(file.name)}</a>`
      : '';

    // Append immediately for snappy feel
    const myMsgHtml = `
    <div style="display:flex;gap:10px;flex-direction:row-reverse" id="${tempId}">
      <div class="av" style="width:30px;height:30px;font-size:10px;background:var(--uw-green);color:white;flex-shrink:0">Me</div>
      <div style="max-width:75%;text-align:right">
        <div style="background:var(--uw-green);color:white;border-radius:12px 2px 12px 12px;padding:10px 14px;font-size:13px;line-height:1.6;text-align:left">${msg ? escapeChatHtml(msg) : ''}${attachPreview}</div>
        <div style="font-size:11px;color:var(--uw-gray2);margin-top:4px">Sending...</div>
      </div>
    </div>
  `;
    chatMessagesScroll.insertAdjacentHTML('beforeend', myMsgHtml);
    chatMessagesScroll.scrollTop = chatMessagesScroll.scrollHeight;

    input.value = '';
    const sentFile = file;
    clearChatAttachment();
    try {
      const formData = new FormData();
      formData.append('receiver_id', activeChatId);
      formData.append('message', msg);
      if (sentFile) formData.append('attachment', sentFile);

      const response = await fetch(`${BASE_URL}/actions/send_message.php`, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        const tempMsg = document.getElementById(tempId);
        if (tempMsg) {
          const timeEl = tempMsg.querySelector('div[style*="margin-top:4px"]');
          if (timeEl) timeEl.innerText = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          if (result.attachment && result.message_id) {
            const bubble = tempMsg.querySelector('div[style*="border-radius:12px 2px"]');
            if (bubble) {
              const link = document.createElement('a');
              link.href = messageAttachmentUrl(result.message_id);
              link.target = '_blank';
              link.rel = 'noopener noreferrer';
              link.style.cssText = 'display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:8px 12px;background:rgba(255,255,255,.18);border-radius:8px;color:#fff;font-size:12px;font-weight:600;text-decoration:none';
              link.textContent = '📎 ' + (result.attachment.name || 'Attachment');
              if (!msg) bubble.innerHTML = '';
              bubble.appendChild(link);
            }
          }
        }
        scheduleConversationsRefresh(300);
      } else {
        toast('Error', result.error || 'Failed to send message');
        document.getElementById(tempId)?.remove();
      }
    } catch (err) {
      toast('Error', 'Failed to send message');
      document.getElementById(tempId)?.remove();
    }
  }

  // Polling for new messages
  let chatPollInterval = null;
  function startChatPolling(otherId, name, initials, avatar = '') {
    if (chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(async () => {
      if (activeChatId !== otherId || document.getElementById('page-messages').style.display === 'none') {
        clearInterval(chatPollInterval);
        return;
      }
      try {
        const response = await fetch(`${BASE_URL}/actions/get_messages.php?with=${otherId}`);
        const result = await response.json();
        if (result.success) {
          activeChatBlocked = !!result.blocked;
          activeChatBlockedByMe = !!result.blocked_by_me;
          if (activeChatBlocked) {
            clearInterval(chatPollInterval);
            return;
          }
          const currentCount = document.querySelectorAll('#chat-messages-scroll > div').length;
          if (result.messages.length > currentCount) {
            renderChatWindow(name, initials, result.messages, avatar);
          }
        }
      } catch (e) { }
    }, 5000);
  }

  // Live unread badge (Message menu) across all pages.
  let unreadBadgePollInterval = null;
  const UNREAD_BADGE_POLL_MS = 3000;

  function setUnreadBadgeDisplay(badgeEl, count) {
    if (!badgeEl) return;
    if (count > 0) {
      badgeEl.textContent = String(count);
      badgeEl.style.display = '';
    } else {
      badgeEl.style.display = 'none';
    }
  }

  function updateUnreadMessagesBadgesClient(unreadCount) {
    // Desktop: sidebar badge inside the Messages nav item.
    const messagesItem = Array.from(document.querySelectorAll('.sb-item')).find(el => {
      const onclick = el.getAttribute('onclick') || '';
      return onclick.includes("showPage('messages'");
    });
    if (messagesItem) {
      let badge = messagesItem.querySelector('.sb-badge');
      if (!badge && unreadCount > 0) {
        badge = document.createElement('span');
        badge.className = 'sb-badge';
        messagesItem.appendChild(badge);
      }
      setUnreadBadgeDisplay(badge, unreadCount);
    }

    // Mobile: bottom nav badge next to the Messages icon.
    const mobMessagesBtn = document.getElementById('mbn-messages');
    if (mobMessagesBtn) {
      let badge = mobMessagesBtn.querySelector('.mob-nav-badge');
      if (!badge && unreadCount > 0) {
        badge = document.createElement('div');
        badge.className = 'mob-nav-badge';
        mobMessagesBtn.appendChild(badge);
      }
      setUnreadBadgeDisplay(badge, unreadCount);
    }
  }

  async function pollUnreadMessagesBadgeClient() {
    try {
      const response = await fetch(`${BASE_URL}actions/get_unread_messages_count.php`, { method: 'GET' });
      const result = await response.json();
      if (result && result.success) {
        updateUnreadMessagesBadgesClient(result.unread_count || 0);
      }
    } catch (e) {
      // Ignore polling errors; next tick may succeed.
    }
  }

  function startUnreadMessagesBadgePolling() {
    if (unreadBadgePollInterval) clearInterval(unreadBadgePollInterval);
    pollUnreadMessagesBadgeClient();
    unreadBadgePollInterval = setInterval(pollUnreadMessagesBadgeClient, UNREAD_BADGE_POLL_MS);
  }

  window.startUnreadMessagesBadgePolling = startUnreadMessagesBadgePolling;

  function filterConversations(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('.msg-item').forEach(item => {
      const text = item.innerText.toLowerCase();
      item.style.display = text.includes(q) ? 'flex' : 'none';
    });
  }

  // ── MESSAGES LEFT-PANEL REFRESH (AJAX, no full reload) ──
  let conversationsPollInterval = null;
  let conversationsRefreshTimeout = null;
  let isRefreshingConversations = false;
  const CONVERSATIONS_POLL_MS = 10000;

  function getConversationsSearchValue() {
    const list = document.getElementById('conversations-list');
    const searchEl = list?.previousElementSibling?.querySelector('input');
    return (searchEl?.value || '').toString();
  }

  function scheduleConversationsRefresh(delayMs = 250) {
    clearTimeout(conversationsRefreshTimeout);
    conversationsRefreshTimeout = setTimeout(() => refreshConversationsList(), delayMs);
  }

  async function refreshConversationsList() {
    const list = document.getElementById('conversations-list');
    if (!list) return;
    if (!document.getElementById('page-messages')?.classList.contains('active')) return;
    if (isRefreshingConversations) return;

    isRefreshingConversations = true;
    const searchValue = getConversationsSearchValue();

    try {
      const res = await fetch(`${BASE_URL}actions/get_conversations.php`, { method: 'GET' });
      const result = await res.json();
      if (!result.success) return;

      const conversations = Array.isArray(result.conversations) ? result.conversations : [];
      list.innerHTML = '';

      if (!conversations.length) {
        list.innerHTML = `<div style="padding:20px;text-align:center;color:var(--uw-gray);font-size:13px">No conversations yet.</div>`;
      } else {
        conversations.forEach(c => {
          const otherId = Number(c.other_id);
          const otherName = c.other_name || '';
          const initials = (otherName || '?')
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(p => p[0])
            .join('')
            .toUpperCase();

          const otherAvatar = c.other_avatar || '';
          const otherNameEsc = escapeChatHtml(otherName);
          const lastMsgEsc = escapeChatHtml(c.last_message || '');

          const timeLabel = c.last_time
            ? new Date(c.last_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : '';

          const isActive = activeChatId != null && Number(activeChatId) === otherId;
          const isUnread =
            !isActive &&
            Number(c.is_read) === 0 &&
            Number(c.sender_id) !== Number(CLIENT_USER_ID);

          const item = document.createElement('div');
          item.dataset.otherId = String(otherId);
          item.className = `msg-item${isUnread ? ' unread' : ''}${isActive ? ' active' : ''}`;
          item.style.cssText = 'border-radius:0;margin:0;padding:12px 14px';
          item.addEventListener('click', () => loadChat(otherId, otherName, initials, item, otherAvatar));

          const avatarHtml = otherAvatar
            ? `
                <img src="${getAvatarUrl(otherAvatar)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:13px">${initials}</div>
              `
            : `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:13px">${initials}</div>`;

          item.innerHTML = `
            <div class="av">${avatarHtml}</div>
            <div class="msg-meta">
              <div class="msg-name">${otherNameEsc}<span class="msg-time">${timeLabel}</span></div>
              <div class="msg-text">${lastMsgEsc}</div>
            </div>
            ${isUnread ? '<div class="msg-dot"></div>' : ''}
          `;

          list.appendChild(item);
        });
      }

      if (searchValue) filterConversations(searchValue);
    } catch (e) {
      // Ignore refresh errors (polling will retry).
    } finally {
      isRefreshingConversations = false;
    }
  }

  function startConversationsPolling() {
    if (conversationsPollInterval) clearInterval(conversationsPollInterval);
    conversationsPollInterval = setInterval(() => {
      const page = document.getElementById('page-messages');
      if (!page || !page.classList.contains('active')) return;
      refreshConversationsList();
    }, CONVERSATIONS_POLL_MS);
  }

  function stopConversationsPolling() {
    if (conversationsPollInterval) clearInterval(conversationsPollInterval);
    conversationsPollInterval = null;
    clearTimeout(conversationsRefreshTimeout);
  }

  // ── MOBILE SIDEBAR ──
  function openMobSidebar() {
    document.querySelector('.sidebar').classList.add('mob-open');
    document.getElementById('sidebar-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeMobSidebar() {
    document.querySelector('.sidebar').classList.remove('mob-open');
    document.getElementById('sidebar-overlay').classList.remove('open');
    document.body.style.overflow = '';
  }

  function setMobNav(id) {
    document.querySelectorAll('.mob-nav-item').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('mbn-' + id);
    if (btn) btn.classList.add('active');
  }

  function openChatWith(id, name, initials, avatar = '') {
    showPage('messages', document.querySelector('.sb-item[onclick*="messages"]'));

    // Wait for page to switch
    setTimeout(() => {
      if (BLOCKED_FREELANCER_IDS.includes(id)) {
        loadChat(id, name, initials, null, avatar);
        return;
      }
      const list = document.getElementById('conversations-list');
      const items = list.querySelectorAll('.msg-item');
      let foundEl = null;
      const targetIdStr = String(id);
      items.forEach(item => {
        const dsId = String(item.dataset?.otherId || '');
        const onclickAttr = item.getAttribute('onclick') || '';
        const onclickFn = item.onclick ? item.onclick.toString() : '';
        if (
          dsId === targetIdStr ||
          onclickAttr.includes(`loadChat(${id}`) ||
          onclickFn.includes(targetIdStr)
        ) {
          foundEl = item;
        }
      });

      if (foundEl) {
        foundEl.click();
      } else {
        // Create temporary sidebar item
        const newItem = document.createElement('div');
        newItem.className = 'msg-item active';
        newItem.style.cssText = 'border-radius:0;margin:0;padding:12px 14px';
        newItem.dataset.otherId = String(id);
        newItem.onclick = function () { loadChat(id, name, initials, this, avatar); };
        newItem.innerHTML = `
        <div class="av">
          ${avatar ? `<div class="av" style="position:relative;width:100%;height:100%"><img src="${getAvatarUrl(avatar)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:700">${initials}</div></div>` :
            `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%">${initials}</div>`}
        </div>
        <div class="msg-meta">
          <div class="msg-name">${name}<span class="msg-time">Now</span></div>
          <div class="msg-text">Starting conversation...</div>
        </div>
      `;
        list.prepend(newItem);
        loadChat(id, name, initials, newItem, avatar);
      }
    }, 150);
  }

  function showPage(id, navEl) {
    if (!id) id = 'home';

    // 1. Switch pages
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const targetPage = document.getElementById('page-' + id);
    if (targetPage) targetPage.classList.add('active');

    // 2. Sync Sidebar
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
    const sideNav = document.querySelector(`.sb-item[onclick*="'${id}'"]`);
    if (sideNav) sideNav.classList.add('active');

    // 3. Sync Bottom Nav (Mobile)
    document.querySelectorAll('.mob-nav-item').forEach(b => b.classList.remove('active'));
    const mbn = document.getElementById('mbn-' + id);
    if (mbn) mbn.classList.add('active');

    // 4. Update Header Title
    const titles = {
      home: 'Home',
      jobs: 'My Jobs',
      proposals: 'Proposals',
      contracts: 'Contracts',
      talent: 'Talent',
      messages: 'Messages',
      payments: 'Payments',
      reports: 'Reports',
      verification: 'Identity Verification'
    };
    const titleEl = document.getElementById('page-title');
    if (titleEl) titleEl.textContent = titles[id] || id;

    // 5. Cleanup
    // 5a. Messages: keep left panel updated without reload
    if (id === 'messages') {
      refreshConversationsList();
      startConversationsPolling();
    } else {
      stopConversationsPolling();
    }
    document.body.classList.toggle('page-messages-active', id === 'messages');
    window.location.hash = id;
    closeMobSidebar();
    if (id !== 'messages') {
      window.scrollTo(0, 0);
    }
  }

  function setTab(el, targetId, page = 1) {
    const tabBar = el.closest('.tab-bar');
    if (!el.classList.contains('on')) {
      page = 1;
    }
    tabBar.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
    el.classList.add('on');

    const limit = 10;
    const isMob = window.innerWidth <= 900;

    if (targetId && targetId.includes('talent')) {
      document.querySelectorAll('.talent-list').forEach(l => l.style.display = 'none');
      const target = document.getElementById(targetId);
      if (target) {
        target.style.display = 'table';
        const rows = Array.from(target.querySelectorAll('tbody tr')).filter(tr => !tr.querySelector('td[colspan]'));
        paginateArray(rows, page, limit, target.parentElement, el, targetId, '');
      }
    } else {
      const status = el.dataset.tabStatus || el.innerText.toLowerCase();
      const nextEl = tabBar.nextElementSibling;
      const container = nextEl.classList.contains('card') ? nextEl : tabBar.parentElement;

      const items = Array.from(container.querySelectorAll('[data-status]'));
      const visibleItems = items.filter(item => {
        const insideDesk = item.closest('.desk-only');
        const insideMob = item.closest('.mob-only');
        if (insideDesk && isMob) return false;
        if (insideMob && !isMob) return false;
        
        return status === 'all' || item.dataset.status === status;
      });
      
      items.forEach(item => item.style.display = 'none');
      paginateArray(visibleItems, page, limit, container, el, targetId, status);
    }
  }

  function paginateArray(items, page, limit, container, el, targetId, status) {
    const start = (page - 1) * limit;
    const end = start + limit;
    
    items.forEach((item, index) => {
      if (index >= start && index < end) {
        if (item.tagName === 'TR') {
          item.style.display = 'table-row';
        } else {
          item.style.display = (item.classList.contains('job-card')) ? 'flex' : 'block';
        }
      } else {
        item.style.display = 'none';
      }
    });

    let noRes = container.querySelector('.no-results-msg');
    if (items.length === 0) {
      if (!noRes) {
        noRes = document.createElement('div');
        noRes.className = 'no-results-msg';
        noRes.style.cssText = 'text-align:center;padding:40px;color:var(--uw-gray);background:white;border-radius:8px;border:1.5px dashed var(--uw-border);margin-top:16px';
        container.appendChild(noRes);
      }
      noRes.style.display = 'block';
      noRes.innerText = `No items found.`;
    } else if (noRes) {
      noRes.style.display = 'none';
    }

    let controls = container.querySelector('.paginator-controls');
    if (!controls) {
      controls = document.createElement('div');
      controls.className = 'paginator-controls';
      controls.style.cssText = 'display:flex;justify-content:center;gap:8px;margin-top:16px;padding:10px 0;';
      container.appendChild(controls);
    }

    const totalPages = Math.ceil(items.length / limit);
    if (totalPages <= 1) {
      controls.style.display = 'none';
    } else {
      controls.style.display = 'flex';
      controls.innerHTML = `
        <button class="btn btn-outline btn-sm" ${page === 1 ? 'disabled style="opacity:0.5"' : ''} onclick="window.setTabPg(this, ${page - 1}, '${targetId || ''}')">Prev</button>
        <span style="font-size:13px;align-self:center;color:var(--uw-gray)">Page ${page} of ${totalPages}</span>
        <button class="btn btn-outline btn-sm" ${page === totalPages ? 'disabled style="opacity:0.5"' : ''} onclick="window.setTabPg(this, ${page + 1}, '${targetId || ''}')">Next</button>
      `;
    }
  }

  window.setTabPg = function(btn, page, targetId) {
     const card = btn.closest('.card') || btn.closest('.page');
     const tabBar = card ? card.querySelector('.tab-bar') : null;
     if (tabBar) {
        const activeTab = tabBar.querySelector('.tab.on');
        if (activeTab) {
           setTab(activeTab, targetId, page);
        }
     }
  };

  async function updateProposalStatus(propId, newStatus) {
    toast('Updating...', 'Changing proposal status');
    const formData = new FormData();
    formData.append('proposal_id', propId);
    formData.append('status', newStatus);

    try {
      const res = await fetch(BASE_URL + 'actions/update_proposal_status.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Success! 🎉', result.message);
        setTimeout(() => location.reload(), 1000);
      } else {
        toast('Error', result.error || 'Failed to update proposal');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  function completeJob(propId) {
    MODALS['complete-job-form'] = {
      t: '✅ Complete Contract & Leave Feedback',
      b: `
      <form id="complete-job-form-el" onsubmit="submitCompleteJob(event, ${propId})" style="display:flex;flex-direction:column;gap:16px">
        <p style="color:var(--uw-gray);font-size:13.5px;line-height:1.6">
          Congratulations on completing this project! Please take a moment to rate the freelancer's performance and provide feedback. This will help calculate their JSS badge dynamically.
        </p>
        
        <div class="fg">
          <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">Performance Rating</label>
          <div style="display:flex;gap:12px;align-items:center;margin-bottom:4px">
            <select name="rating" style="width:100%;padding:10px;border:1.5px solid var(--uw-border);border-radius:8px;font-size:14px;outline:none" required>
              <option value="5.0">⭐⭐⭐⭐⭐ Excellent (5.0 / 5.0)</option>
              <option value="4.0">⭐⭐⭐⭐ Good (4.0 / 5.0)</option>
              <option value="3.0">⭐⭐⭐ Average (3.0 / 5.0)</option>
              <option value="2.0">⭐⭐ Fair (2.0 / 5.0)</option>
              <option value="1.0">⭐ Poor (1.0 / 5.0)</option>
            </select>
          </div>
        </div>
        
        <div class="fg">
          <label style="font-weight:700;font-size:13px;display:block;margin-bottom:6px">Public Review Feedback</label>
          <textarea name="feedback" placeholder="Share your experience working with this freelancer. What did they do well? What could be improved?" style="width:100%;min-height:100px;padding:12px;border:1.5px solid var(--uw-border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none;resize:vertical" required></textarea>
        </div>
        
        <div style="display:flex;gap:12px;margin-top:10px">
          <button type="submit" class="btn btn-g" style="flex:1;justify-content:center;padding:12px">
            Submit Feedback & Complete Job
          </button>
          <button type="button" class="btn btn-w" onclick="closeModal()" style="justify-content:center;padding:12px">
            Cancel
          </button>
        </div>
      </form>
    `
    };
    openModal('complete-job-form');
  }

  async function submitCompleteJob(event, propId) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerText;

    submitBtn.disabled = true;
    submitBtn.innerText = 'Processing...';

    const formData = new FormData(form);
    formData.append('proposal_id', propId);

    try {
      const res = await fetch(BASE_URL + 'actions/complete_job.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Success! 🎉', result.message);
        closeModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to complete job');
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
      }
    } catch (err) {
      toast('Error', 'Communication failed');
      submitBtn.disabled = false;
      submitBtn.innerText = originalText;
    }
  }

  async function cancelHiring(propId) {
    const reason = await remoPrompt('Please enter the reason for cancellation:', 'Cancel hiring', '', { multiline: true });
    if (reason === null) return;

    toast('Processing...', 'Cancelling hiring');
    const formData = new FormData();
    formData.append('proposal_id', propId);
    formData.append('reason', reason);

    try {
      const res = await fetch(BASE_URL + 'actions/cancel_hiring.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Cancelled', result.message);
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to cancel hiring');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  window.openDisputeModal = function(contractId) {
    MODALS['file-dispute'] = {
      t: 'File a Dispute',
      b: `
      <div style="padding:10px 0">
        <p style="font-size:13.5px;color:var(--uw-gray);line-height:1.5;margin-bottom:15px">
          If you and the freelancer cannot agree on milestone delivery, quality of work, or payment terms, you can file a dispute.
          This will temporarily freeze all funds, and our support team will mediate.
        </p>
        <div style="margin-bottom:16px">
          <label style="font-weight:700;font-size:12.5px;color:var(--uw-black);display:block;margin-bottom:8px">Reason for Dispute</label>
          <textarea id="dispute-reason" style="width:100%;height:120px;padding:10px;border:1.5px solid var(--uw-border);border-radius:10px;font-family:inherit;font-size:13px;outline:none;resize:none" placeholder="Please describe the disagreement in detail..."></textarea>
        </div>
      </div>
      <div style="display:flex;gap:12px">
        <button class="btn btn-w" style="flex:1;padding:12px;font-size:14px" onclick="closeModal()">Cancel</button>
        <button class="btn btn-o" style="flex:1.5;padding:12px;font-size:14px;font-weight:700" onclick="submitDispute(${contractId})">Raise Dispute ⚠️</button>
      </div>
      `
    };
    openModal('file-dispute');
  };

  window.submitDispute = async function(contractId) {
    const reason = document.getElementById('dispute-reason').value.trim();
    if (!reason) {
      remoAlert('Please enter a reason for the dispute.', 'Dispute');
      return;
    }

    const btn = document.querySelector('[onclick*="submitDispute"]');
    if (btn) {
      btn.disabled = true;
      btn.innerText = 'Filing Dispute...';
    }

    const formData = new FormData();
    formData.append('contract_id', contractId);
    formData.append('reason', reason);

    fetch(BASE_URL + 'actions/file_dispute.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        toast('Success', data.message);
        closeModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        remoAlert(data.error || 'Failed to file dispute', 'Error');
        if (btn) {
          btn.disabled = false;
          btn.innerText = 'Raise Dispute ⚠️';
        }
      }
    })
    .catch(err => {
      console.error(err);
      remoAlert('An error occurred. Please try again.', 'Error');
      if (btn) {
        btn.disabled = false;
        btn.innerText = 'Raise Dispute ⚠️';
      }
    });
  };

  window.manageContract = async function (contract) {
    let contractId = null;
    if (typeof contract === 'object' && contract !== null) {
      contractId = contract.id;
    } else {
      contractId = contract;
    }

    if (contractId) {
      toast('Loading...', 'Fetching contract details');
      try {
        const res = await fetch(BASE_URL + 'client/api/get-contract.php?id=' + contractId);
        const data = await res.json();
        if (!data.success) {
          toast('Error', data.message);
          return;
        }
        contract = data.contract;
      } catch (err) {
        toast('Error', 'Failed to fetch contract details');
        return;
      }
    }

    MODALS['manage-contract'] = {
      t: 'Manage Contract',
      b: `
      <div style="display:flex;gap:12px;align-items:center;background:var(--uw-bg);border-radius:12px;padding:16px;margin-bottom:20px;border:1.5px solid var(--uw-border)">
        <div class="av" style="width:48px;height:48px">
          ${contract.freelancer_avatar ? `<img src="${BASE_URL}${contract.freelancer_avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover">` :
          `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:16px">${contract.freelancer_name.substring(0, 2).toUpperCase()}</div>`}
        </div>
        <div style="flex:1">
          <div style="font-weight:700;font-size:15px;margin-bottom:2px">${contract.freelancer_name}</div>
          <div style="font-size:12px;color:var(--uw-gray)">${contract.job_title}</div>
        </div>
      </div>

      <!-- Milestones Section -->
      <div style="margin-bottom:25px">
        <h4 style="margin:0 0 12px 0; font-size:14px; color:var(--uw-black)">Milestones</h4>
        <div style="display:grid; gap:10px">
          ${(contract.milestones || []).map((ms, i) => `
            <div style="padding:12px; border:1px solid var(--uw-border); border-radius:10px; display:flex; justify-content:space-between; align-items:center; background:white">
              <div>
                <div style="font-size:13px; font-weight:600">${ms.description}</div>
                <div style="font-size:11px; color:var(--uw-gray)">
                  $${parseFloat(ms.amount).toLocaleString()} · ${ms.status.charAt(0).toUpperCase() + ms.status.slice(1)}
                  ${ms.refund_request_status ? ` · Refund ${ms.refund_request_status.charAt(0).toUpperCase() + ms.refund_request_status.slice(1)}` : ''}
                </div>
              </div>
              <div>
                ${ms.status === 'pending' ? (
              (contract.status === 'completed' || contract.status === 'cancelled') ? `
                    <span class="badge" style="background:#f3f4f6; color:#9ca3af; padding:4px 8px; border-radius:4px; font-size:11px">Awaiting Funding</span>
                  ` : `
                    <button class="btn btn-g btn-sm" onclick="openFundMilestoneModal(${ms.id}, ${ms.amount}, '${ms.description.replace(/'/g, "\\'")}', ${contract.id})">Fund Milestone</button>
                  `
            ) : (ms.status === 'funded' ? `
                ${ms.refund_request_status === 'pending' ? `
                  <span class="badge" style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600">Refund Requested</span>
                ` : `
                  <button class="btn btn-w btn-sm" style="color:#b91c1c;border-color:#fecaca" onclick="requestMilestoneRefund(${ms.id}, this, ${contract.id})">
                    ${ms.refund_request_status === 'rejected' ? 'Request Refund Again' : 'Request Refund'}
                  </button>
                `}
                ` : (ms.status === 'requested' ? (
              (contract.status === 'completed' || contract.status === 'cancelled') ? `
                    <span class="badge" style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:4px; font-size:11px">Requested</span>
                  ` : `
                    <div style="display:flex;gap:6px">
                      <button class="btn btn-g btn-sm" onclick="releaseMilestone(${ms.id}, this, ${contract.id})">Approve</button>
                      <button class="btn btn-w btn-sm" style="color:#ef4444;border-color:#fecaca" onclick="rejectMilestone(${ms.id}, this, ${contract.id})">Reject</button>
                    </div>
                  `
            ) : `
                  <span class="badge b-green" style="font-size:10px">Paid</span>
                `))}
              </div>
            </div>
          `).join('')}
          ${(!contract.milestones || contract.milestones.length === 0) ? '<div style="color:var(--uw-gray); font-size:13px; text-align:center; padding:10px; border:1.5px dashed var(--uw-border); border-radius:10px">No milestones defined.</div>' : ''}
        </div>
      </div>

      <!-- Logged Timesheet Section -->
      <div style="margin-bottom:25px">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
          <h4 style="margin:0; font-size:14px; color:var(--uw-black)">Logged Timesheet</h4>
          <span style="font-size:12px; font-weight:700; color:var(--uw-green)">
            Total: ${(contract.work_logs || []).reduce((acc, curr) => acc + parseFloat(curr.hours), 0).toFixed(2)} hrs
          </span>
        </div>
        <div style="max-height:220px; overflow-y:auto; border:1px solid var(--uw-border); border-radius:10px; background:white">
          <table style="width:100%; border-collapse:collapse; font-size:12.5px; text-align:left">
            <thead>
              <tr style="background:var(--uw-bg); border-bottom:1.5px solid var(--uw-border)">
                <th style="padding:10px; font-weight:700; color:var(--uw-black)">Date</th>
                <th style="padding:10px; font-weight:700; color:var(--uw-black)">Hours</th>
                <th style="padding:10px; font-weight:700; color:var(--uw-black)">Description</th>
                <th style="padding:10px; font-weight:700; color:var(--uw-black)">Amount</th>
              </tr>
            </thead>
            <tbody>
              ${(contract.work_logs || []).map(wl => {
              const dateStr = new Date(wl.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
              const timeRange = (wl.start_time && wl.end_time) ? `<div style="font-size:10px; color:var(--uw-gray)">${wl.start_time} - ${wl.end_time}</div>` : '';
              const typeLabel = wl.log_type === 'manual' ?
                `<div style="font-size:9.5px; font-weight:700; color:#0369a1; background:#e0f2fe; display:inline-block; padding:2px 5px; border-radius:3px; margin-top:3px">✍️ Manual</div>` :
                `<div style="font-size:9.5px; font-weight:700; color:#15803d; background:#dcfce7; display:inline-block; padding:2px 5px; border-radius:3px; margin-top:3px">⏱️ Auto</div>`;
              return `
                  <tr style="border-bottom:1px solid var(--uw-border)">
                    <td style="padding:10px; white-space:nowrap">
                      <div>${dateStr}</div>
                      ${timeRange}
                      ${typeLabel}
                    </td>
                    <td style="padding:10px; font-weight:600">${parseFloat(wl.hours).toFixed(2)} hrs</td>
                    <td style="padding:10px; max-width:180px; word-break:break-word; color:var(--uw-gray)">${wl.description || 'No description'}</td>
                    <td style="padding:10px; font-weight:600; color:var(--uw-green)">$${parseFloat(wl.amount).toFixed(2)}</td>
                  </tr>
                `;
            }).join('')}
              ${(!contract.work_logs || contract.work_logs.length === 0) ? `
                <tr>
                  <td colspan="4" style="text-align:center; padding:15px; color:var(--uw-gray)">No logged hours recorded yet.</td>
                </tr>
              ` : ''}
            </tbody>
          </table>
        </div>
      </div>
      
      <div style="display:grid;gap:12px">
        <button class="btn btn-w" style="justify-content:center;padding:12px" onclick="closeModal();showChatWithFreelancer(${contract.freelancer_id}, '${contract.freelancer_name.replace(/'/g, "\\'")}', '${contract.freelancer_avatar || ''}')">
          💬 Message Freelancer
        </button>
        
        ${contract.status === 'active' ? `
          <button class="btn btn-w" style="justify-content:center;padding:12px;color:#b45309;border-color:#fde68a" onclick="closeModal();updateContractStatus(${contract.id}, 'paused')">
            ⏸ Pause Contract
          </button>
        ` : ''}
        
        ${contract.status === 'paused' ? `
          <button class="btn btn-g" style="justify-content:center;padding:12px" onclick="closeModal();updateContractStatus(${contract.id}, 'active')">
            ▶ Resume Contract
          </button>
        ` : ''}
        
        ${contract.status === 'disputed' ? `
          <div style="background:#fef2f2;border:1px solid #fee2e2;border-radius:10px;padding:15px;color:#991b1b;font-size:13px;line-height:1.5;margin-bottom:12px;display:flex;align-items:flex-start;gap:8px">
            <span style="font-size:16px">⚠️</span>
            <div>
              <strong>This contract is currently Disputed.</strong><br>
              The escrow and milestones have been frozen. Our arbitration team is reviewing the case logs. We will contact both parties via email/chat soon.
            </div>
          </div>
        ` : ''}

        ${(contract.status === 'active' || contract.status === 'paused') ? `
          <button class="btn btn-o" style="justify-content:center;padding:12px" onclick="closeModal();completeJob(${contract.proposal_id})">
            ✅ Mark Job as Completed
          </button>
          
          <button class="btn btn-w" style="justify-content:center;padding:12px;color:#ef4444;border-color:#fecaca" onclick="closeModal();cancelHiring(${contract.proposal_id})">
            ❌ Cancel Hiring
          </button>
        ` : ''}

        ${(contract.status === 'active' || contract.status === 'paused' || contract.status === 'completed') ? `
          <button class="btn btn-w" style="justify-content:center;padding:12px;color:#ef4444;border-color:#fecaca;margin-top:4px" onclick="closeModal();openDisputeModal(${contract.id})">
            ⚠️ File a Dispute
          </button>
        ` : ''}
      </div>
    `
    };
    openModal('manage-contract');
  }

  async function releaseMilestone(milestoneId, btn, contractId) {
    if (!(await remoConfirm('Payment will be released to the freelancer.', 'Approve this milestone?'))) return;

    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Processing...';

    try {
      const res = await fetch(BASE_URL + 'client/api/release-milestone.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ milestone_id: milestoneId })
      });
      const data = await res.json();
      if (data.success) {
        toast('Success! 🎉', data.message || 'Payment approved and released.');

        // Real-time update of parent contract details modal
        setTimeout(() => {
          if (typeof contractId !== 'undefined') {
            manageContract(contractId);
          } else {
            location.reload();
          }
        }, 1000);
      } else {
        toast('Error', data.message);
        btn.disabled = false;
        btn.innerText = originalText;
      }
    } catch (err) {
      toast('Error', 'Communication failed');
      btn.disabled = false;
      btn.innerText = originalText;
    }
  }

  async function rejectMilestone(milestoneId, btn, contractId) {
    if (!(await remoConfirm('The milestone will return to Active status.', 'Reject this submission?'))) return;

    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Rejecting...';

    try {
      const res = await fetch(BASE_URL + 'client/api/reject-submission.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ milestone_id: milestoneId })
      });
      const data = await res.json();
      if (data.success) {
        toast('Submission Rejected', data.message || 'Work submission has been rejected.');

        setTimeout(() => {
          if (typeof contractId !== 'undefined') {
            manageContract(contractId);
          } else {
            location.reload();
          }
        }, 1000);
      } else {
        toast('Error', data.message);
        btn.disabled = false;
        btn.innerText = originalText;
      }
    } catch (err) {
      toast('Error', 'Communication failed');
      btn.disabled = false;
      btn.innerText = originalText;
    }
  }

  async function requestMilestoneRefund(milestoneId, btn, contractId) {
    if (!(await remoConfirm('The freelancer will be asked to accept or reject your refund request.', 'Request refund for this funded milestone?'))) return;

    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Requesting...';

    try {
      const res = await fetch(BASE_URL + 'client/api/request-refund-milestone.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ milestone_id: milestoneId })
      });
      const data = await res.json();
      if (data.success) {
        toast('Refund Requested', data.message || 'Refund request has been sent to the freelancer.');
        setTimeout(() => {
          if (typeof contractId !== 'undefined') {
            manageContract(contractId);
          } else {
            location.reload();
          }
        }, 1000);
      } else {
        toast('Error', data.message || 'Could not request refund.');
        btn.disabled = false;
        btn.innerText = originalText;
      }
    } catch (err) {
      toast('Error', 'Communication failed');
      btn.disabled = false;
      btn.innerText = originalText;
    }
  }


  async function updateContractStatus(contractId, newStatus) {
    const actionText = newStatus === 'paused' ? 'pausing' : 'resuming';
    toast('Processing...', `Contract is ${actionText}`);

    const formData = new FormData();
    formData.append('contract_id', contractId);
    formData.append('status', newStatus);

    try {
      const res = await fetch(BASE_URL + 'actions/update_contract_status.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Success! 🎉', result.message);
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to update contract status');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  // ─── UPWORK CATEGORY DATA ───
  let ACTIVE_JOB_CATEGORIES = [];
  const UW_CATS = {
    "Accounting & Consulting": {
      "Personal & Professional Coaching": ["Career Coaching", "Personal Coaching"],
      "Accounting & Bookkeeping": ["Accounting", "Bookkeeping"],
      "Financial Planning": ["Financial Analysis & Modeling", "Financial Management/CFO"],
      "Recruiting & Human Resources": ["HR Administration", "Recruiting & Talent Sourcing", "Training & Development"],
      "Management Consulting & Analysis": ["Business Analysis & Strategy", "Instructional Design", "Management Consulting"],
      "Other - Accounting & Consulting": ["Tax Preparation"]
    },
    "Admin Support": {
      "Data Entry & Transcription Services": ["Data Entry", "Manual Transcription"],
      "Virtual Assistance": ["Executive Virtual Assistance", "Legal Virtual Assistance", "Medical Virtual Assistance", "Ecommerce Management", "Personal Virtual Assistance", "General Virtual Assistance"],
      "Project Management": ["Business Project Management", "Supply Chain & Logistics Project Management", "Construction & Engineering Project Management", "Development & IT Project Management", "Healthcare Project Management", "Digital Project Management"],
      "Market Research & Product Reviews": ["Web & Software Product Research", "Market Research", "General Research Services", "Product Reviews", "Qualitative Research", "Quantitative Research"]
    },
    "Customer Service": {
      "Community Management & Tagging": ["Community Management", "Content Moderation", "Visual Tagging & Processing"],
      "Customer Service & Tech Support": ["Customer Onboarding", "Email, Phone & Chat Support", "Customer Success", "IT Support", "Tech Support"]
    },
    "Data Science & Analytics": {
      "Data Analysis & Testing": ["Data Analytics", "Data Visualization", "Experimentation & Testing"],
      "Data Extraction/ETL": ["Data Extraction", "Data Processing"],
      "Data Mining & Management": ["Data Engineering", "Data Mining"],
      "AI & Machine Learning": ["Generative AI Modeling", "AI Data Annotation & Labeling", "Deep Learning", "Knowledge Representation", "Machine Learning"]
    },
    "Design & Creative": {
      "Art & Illustration": ["Portraits & Caricatures", "Cartoons & Comics", "Fine Art", "Illustration", "Pattern Design"],
      "Audio & Music Production": ["AI Speech & Audio Generation", "Audio Editing", "Audio Production", "Songwriting & Music Composition", "Music Production"],
      "Branding & Logo Design": ["Brand Identity Design", "Logo Design"],
      "NFT, AR/VR & Game Art": ["NFT Art", "Game Art", "AR/VR Design"],
      "Graphic, Editorial & Presentation Design": ["AI Image Generation & Editing", "Art Direction", "Creative Direction", "Editorial Design", "Graphic Design", "Image Editing", "Packaging Design", "Presentation Design"],
      "Performing Arts": ["Acting", "Music Performance", "Singing", "Voice Talent"],
      "Photography": ["Local Photography", "Product Photography"],
      "Product Design": ["Fashion Design", "Jewelry Design", "Product & Industrial Design"],
      "Video & Animation": ["AI Video Generation & Editing", "Motion Graphics", "3D Animation", "2D Animation", "Video Editing", "Videography", "Video Production", "Visual Effects"]
    },
    "Engineering & Architecture": {
      "Building & Landscape Architecture": ["Architectural Design", "Landscape Architecture"],
      "Chemical Engineering": ["Chemical & Process Engineering"],
      "Civil & Structural Engineering": ["Building Information Modeling", "Civil Engineering", "Structural Engineering"],
      "Electrical & Electronic Engineering": ["Electrical Engineering", "Electronic Engineering"],
      "Interior & Trade Show Design": ["Trade Show Design", "Interior Design"],
      "Energy & Mechanical Engineering": ["Energy Engineering", "Mechanical Engineering"],
      "Physical Sciences": ["Biology", "Chemistry", "Mathematics", "Physics", "STEM Tutoring"],
      "3D Modeling & CAD": ["CAD", "3D Modeling & Rendering"],
      "Contract Manufacturing": ["Logistics & Supply Chain Management", "Sourcing & Procurement"]
    },
    "IT & Networking": {
      "Database Management & Administration": ["Database Administration"],
      "ERP/CRM Software": ["Business Applications Development", "Systems Engineering"],
      "Information Security & Compliance": ["IT Compliance", "Information Security", "Network Security"],
      "Network & System Administration": ["Network Administration", "Systems Administration"],
      "DevOps & Solution Architecture": ["Cloud Engineering", "DevOps Engineering", "Solution Architecture"]
    },
    "Legal": {
      "Corporate & Contract Law": ["Business & Corporate Law", "Intellectual Property Law", "Paralegal Services"],
      "International & Immigration Law": ["Immigration Law", "International Law"],
      "Finance & Tax Law": ["Securities & Finance Law", "Tax Law"],
      "Public Law": ["Labor & Employment Law", "Regulatory Law"]
    },
    "Sales & Marketing": {
      "Digital Marketing": ["Display Advertising", "Campaign Management", "Email Marketing", "Marketing Automation", "Search Engine Marketing", "SEO", "Social Media Marketing"],
      "Lead Generation & Telemarketing": ["Sales & Business Development", "Lead Generation", "Telemarketing"],
      "Marketing, PR & Brand Strategy": ["Brand Strategy", "Content Strategy", "Marketing Strategy", "Public Relations", "Social Media Strategy"]
    },
    "Translation": {
      "Language Tutoring & Interpretation": ["Live Interpretation", "Sign Language Interpretation", "Language Tutoring"],
      "Translation & Localization Services": ["Language Localization", "Legal Document Translation", "Medical Document Translation", "Technical Document Translation", "General Translation Services"]
    },
    "Web, Mobile & Software Dev": {
      "Blockchain, NFT & Cryptocurrency": ["Blockchain & NFT Development", "Crypto Coins & Tokens", "Crypto Wallet Development"],
      "AI Apps & Integration": ["AI Chatbot Development", "AI Integration"],
      "Desktop Application Development": ["Desktop Software Development"],
      "Ecommerce Development": ["Ecommerce Website Development"],
      "Game Design & Development": ["Video Game Development"],
      "Mobile Development": ["Mobile App Development", "Mobile Game Development"],
      "Other - Software Development": ["AR/VR Development", "Database Development", "Emerging Tech", "Firmware Development", "Coding Tutoring"],
      "Product Management & Scrum": ["Product Management", "Scrum Leadership"],
      "QA Testing": ["Automation Testing", "Manual Testing"],
      "Scripts & Utilities": ["Scripting & Automation"],
      "Web & Mobile Design": ["Mobile Design", "Prototyping", "UX/UI Design", "Web Design"],
      "Web Development": ["Back-End Development", "CMS Development", "Front-End Development", "Full Stack Development"]
    },
    "Writing": {
      "Sales & Marketing Copywriting": ["Ad & Email Copywriting", "Marketing Copywriting", "Sales Copywriting"],
      "Content Writing": ["Web & UX Writing", "Article & Blog Writing", "AI Content Writing", "Creative Writing", "Ghostwriting", "Scriptwriting", "Writing Tutoring"],
      "Editing & Proofreading Services": ["Proofreading", "Copy Editing"],
      "Professional & Business Writing": ["Academic & Research Writing", "Legal Writing", "Medical Writing", "Resume & Cover Letter Writing", "Business & Proposal Writing", "Grant Writing", "Technical Writing"]
    }
  };

  function escapeJobCategoryHtml(s) {
    return String(s || '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[m]));
  }

  function renderJobCategoryOptions() {
    const catSel = document.getElementById('pj-cat');
    if (!catSel) return;
    const options = ACTIVE_JOB_CATEGORIES
      .map(c => `<option value="${escapeJobCategoryHtml(c)}">${escapeJobCategoryHtml(c)}</option>`)
      .join('');
    catSel.innerHTML = '<option value="">— Select a category —</option>' + options;
  }

  async function loadActiveJobCategories() {
    try {
      const response = await fetch(BASE_URL + 'actions/get_job_categories.php?active_only=1');
      const result = await response.json();
      if (result && result.success && Array.isArray(result.data) && result.data.length) {
        ACTIVE_JOB_CATEGORIES = result.data.map(row => row.name).filter(Boolean);
      } else {
        ACTIVE_JOB_CATEGORIES = Object.keys(UW_CATS);
      }
    } catch (e) {
      ACTIVE_JOB_CATEGORIES = Object.keys(UW_CATS);
    }
    renderJobCategoryOptions();
  }

  function updateSubcats() {
    const cat = (document.getElementById('pj-cat') || {}).value;
    const subcatSel = document.getElementById('pj-subcat');
    const specSel = document.getElementById('pj-spec');
    const subcatWrap = document.getElementById('pj-subcat-wrap');
    const specWrap = document.getElementById('pj-spec-wrap');
    if (!cat) { subcatWrap.style.display = 'none'; specWrap.style.display = 'none'; return; }
    const subcats = Object.keys(UW_CATS[cat] || {});
    if (!subcats.length) {
      subcatSel.innerHTML = '<option value="">— Select a subcategory —</option>';
      specSel.innerHTML = '<option value="">— Select a specialty —</option>';
      subcatWrap.style.display = 'none';
      specWrap.style.display = 'none';
      return;
    }
    subcatSel.innerHTML = '<option value="">— Select a subcategory —</option>' + subcats.map(s => `<option value="${s}">${s}</option>`).join('');
    subcatWrap.style.display = 'block';
    specSel.innerHTML = '<option value="">— Select a specialty —</option>';
    specWrap.style.display = 'none';
  }

  function updateSpecialties() {
    const cat = (document.getElementById('pj-cat') || {}).value;
    const subcat = (document.getElementById('pj-subcat') || {}).value;
    const specSel = document.getElementById('pj-spec');
    const specWrap = document.getElementById('pj-spec-wrap');
    if (!cat || !subcat) { specWrap.style.display = 'none'; return; }
    const specs = (UW_CATS[cat] || {})[subcat] || [];
    specSel.innerHTML = '<option value="">— Select a specialty —</option>' + specs.map(s => `<option value="${s}">${s}</option>`).join('');
    specWrap.style.display = 'block';
  }

  function updatePostJobFields() {
    const v = (document.getElementById('pj-billing-type') || {}).value;
    ['fixed', 'hourly', 'monthly'].forEach(k => {
      const el = document.getElementById('pj-' + k + '-fields');
      if (el) el.style.display = (k === v) ? 'block' : 'none';
    });
  }
  function showChatWithFreelancer(id, name, avatar = '') {
    const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    showPage('messages', document.querySelector('[onclick*=messages]'));

    // Wait for page to switch
    setTimeout(() => {
      const list = document.getElementById('conversations-list');
      if (!list) return;
      const items = list.querySelectorAll('.msg-item');
      let foundEl = null;
      items.forEach(item => {
        const oc = item.getAttribute('onclick') || '';
        if (oc.includes('loadChat(' + id + ',')) {
          foundEl = item;
        }
      });

      if (foundEl) {
        foundEl.click();
      } else {
        // Create temporary sidebar item
        const newItem = document.createElement('div');
        newItem.className = 'msg-item active';
        newItem.style.cssText = 'border-radius:0;margin:0;padding:12px 14px';
        newItem.onclick = function () { loadChat(id, name, initials, this, avatar); };
        newItem.innerHTML = `
        <div class="av">
          ${avatar ? `<div class="av" style="position:relative;width:100%;height:100%"><img src="${getAvatarUrl(avatar)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div style="display:none;background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:700">${initials}</div></div>` :
            `<div style="background:var(--uw-green-light);color:var(--uw-green);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%">${initials}</div>`}
        </div>
        <div class="msg-meta">
          <div class="msg-name">${name}<span class="msg-time">Now</span></div>
          <div class="msg-text">Starting conversation...</div>
        </div>
      `;
        list.prepend(newItem);
        loadChat(id, name, initials, newItem, avatar);
      }
    }, 150);
  }

  async function submitPostJob() {
    const titleEl = document.getElementById('pj-title');
    const title = (titleEl && titleEl.value) ? titleEl.value.trim() : '';

    const catEl = document.getElementById('pj-cat');
    const cat = (catEl && catEl.value) ? catEl.value : '';

    const subcatEl = document.getElementById('pj-subcat');
    const subcat = (subcatEl && subcatEl.value) ? subcatEl.value : '';

    const specEl = document.getElementById('pj-spec');
    const spec = (specEl && specEl.value) ? specEl.value : '';

    const typeEl = document.getElementById('pj-billing-type');
    const type = (typeEl && typeEl.value) ? typeEl.value : 'fixed';

    let budget = '';
    let minRate = '';
    let maxRate = '';

    if (type === 'hourly') {
      const minEl = document.getElementById('pj-min-rate');
      const maxEl = document.getElementById('pj-max-rate');
      minRate = minEl ? minEl.value : '';
      maxRate = maxEl ? maxEl.value : '';
      if (!minRate || !maxRate) {
        return toast('Error', 'Please enter minimum and maximum hourly rates.');
      }
      if (parseFloat(minRate) > parseFloat(maxRate)) {
        return toast('Error', 'Minimum hourly rate cannot be greater than maximum hourly rate.');
      }
    } else if (type === 'monthly') {
      const monthlyEl = document.getElementById('pj-monthly-rate');
      budget = monthlyEl ? monthlyEl.value : '';
      if (!budget) return toast('Error', 'Please enter monthly rate.');
    } else {
      const budgetEl = document.getElementById('pj-budget');
      budget = budgetEl ? budgetEl.value : '';
      if (!budget) return toast('Error', 'Please enter budget.');
    }

    const descEl = document.getElementById('pj-desc');
    const desc = (descEl && descEl.value) ? descEl.value.trim() : '';

    const skillsEl = document.getElementById('pj-skills');
    const skills = (skillsEl && skillsEl.value) ? skillsEl.value.trim() : '';
    const subcatWrap = document.getElementById('pj-subcat-wrap');
    const subcatRequired = subcatWrap && subcatWrap.style.display !== 'none';

    if (!title || !cat || !desc || (subcatRequired && !subcat)) {
      return toast('Error', 'Please fill in job title, category, subcategory, and description.');
    }

    const btn = document.getElementById('pj-submit-btn');
    const btnText = document.getElementById('pj-btn-text');
    if (btn && btn.disabled) return;
    if (btn) btn.disabled = true;
    if (btnText) btnText.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px"></span>Posting...';

    const formData = new FormData();
    formData.append('title', title);
    formData.append('category', cat);
    formData.append('subcategory', subcat || 'General');
    formData.append('specialty', spec);
    formData.append('budget_type', type);
    formData.append('budget', budget || '0');
    formData.append('min_hourly_rate', minRate);
    formData.append('max_hourly_rate', maxRate);
    formData.append('description', desc);
    formData.append('skills', skills);

    try {
      const res = await fetch(BASE_URL + 'actions/post_job.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Success! 🎉', 'Your job has been posted.');
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to post job');
        if (btn) btn.disabled = false;
        if (btnText) btnText.innerText = 'Post Job →';
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
      if (btn) btn.disabled = false;
      if (btnText) btnText.innerText = 'Post Job →';
    }
  }
  window.updateSubcats = updateSubcats;
  window.updateSpecialties = updateSpecialties;
  window.updatePostJobFields = updatePostJobFields;
  window.submitPostJob = submitPostJob;
  window.bindPostJobModal = bindPostJobModal;
  async function hireFreelancer(proposalId, amount, budgetType) {
    if (budgetType === 'hourly' && availableBalance < 1) {
      toast('Balance required', 'Add at least $1.00 to your wallet before starting an hourly contract.');
      return;
    }

    if (!(await remoConfirm('A contract will be created for this freelancer.', 'Hire this freelancer?'))) return;

    toast('Processing...', 'Setting up your contract');

    const formData = new FormData();
    formData.append('proposal_id', proposalId);

    try {
      const res = await fetch(BASE_URL + 'actions/hire_freelancer.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        toast('Hired! 🎉', 'Contract created successfully.');
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to hire freelancer');
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
    }
  }

  function toggleHireFields(prefix) {
    const sel = document.getElementById(prefix + '-contract-type');
    if (!sel) return;
    const v = sel.value;
    ['fixed', 'hourly', 'monthly'].forEach(k => {
      const el = document.getElementById(prefix + '-' + k + '-fields');
      if (el) el.style.display = (k === v) ? 'block' : 'none';
    });
  }

  function switchCVStep(n) {
    document.querySelectorAll('[id^="cvpanel-"]').forEach(p => p.style.display = 'none');
    const target = document.getElementById('cvpanel-' + n);
    if (target) target.style.display = 'block';

    // Update progress UI
    for (let i = 1; i <= 3; i++) {
      const s = document.getElementById('cvstep-' + i);
      const ico = document.getElementById('cvstep-' + i + '-ico');
      if (!s || !ico) continue;
      if (i === n) {
        s.style.background = 'var(--uw-green-light)';
        ico.style.background = 'var(--uw-green)';
        ico.style.color = 'white';
      } else if (i < n) {
        s.style.background = 'white';
        ico.style.background = '#e6f5e6';
        ico.style.color = 'var(--uw-green)';
        ico.innerHTML = '✓';
      } else {
        s.style.background = 'white';
        ico.style.background = 'var(--uw-border)';
        ico.style.color = 'var(--uw-gray)';
        ico.innerHTML = i;
      }
    }
  }

  function selectCDocType(type, id) {
    selectedCVType = type;
    document.querySelectorAll('.doc-type-card').forEach(c => {
      c.style.borderColor = 'var(--uw-border)';
      c.style.background = 'white';
      c.classList.remove('selected');
    });
    const el = document.getElementById(id);
    if (el) {
      el.style.borderColor = 'var(--uw-green)';
      el.style.background = 'var(--uw-green-light)';
      el.classList.add('selected');
    }

    const bar = document.getElementById('cdtype-selected-bar');
    if (bar) {
      bar.style.display = 'flex';
      document.getElementById('cdtype-selected-text').textContent = type + ' selected';
      document.getElementById('cvreview-type').textContent = type;
    }
  }

  function handleCVFile(input) {
    if (input.files && input.files[0]) {
      selectedCVFile = input.files[0];
      document.getElementById('cv-text').textContent = selectedCVFile.name;
      document.getElementById('cvreview-file').textContent = selectedCVFile.name;
      document.getElementById('cvnext-2').disabled = false;
    }
  }

  async function submitClientFinalVerification() {
    if (!selectedCVType || !selectedCVFile) {
      return toast('Error', 'Please complete all steps');
    }

    const btn = document.getElementById('v-submit-btn');
    const btnText = document.getElementById('v-btn-text');

    if (btn) btn.disabled = true;
    if (btnText) btnText.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:8px"></span>Submitting...';

    const formData = new FormData();
    formData.append('doc_type', selectedCVType);
    formData.append('document', selectedCVFile);

    try {
      const response = await fetch(BASE_URL + 'upload-doc', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        toast('Success!', 'Your documents have been submitted for verification.');
        setTimeout(() => location.reload(), 1500);
      } else {
        toast('Error', result.error || 'Failed to submit verification');
        if (btn) btn.disabled = false;
        if (btnText) btnText.innerText = 'Submit for Review';
      }
    } catch (err) {
      toast('Error', 'An unexpected error occurred.');
      if (btn) btn.disabled = false;
      if (btnText) btnText.innerText = 'Submit for Review';
    }
  }

  window.addEventListener('DOMContentLoaded', () => {
    initMobFab();
    const hash = window.location.hash.replace('#', '');
    if (hash === 'post-job') {
      openModal('post-job');
      showPage('jobs');
    } else {
      showPage(hash || 'home');
    }
    if (window.__pendingModalId) {
      openModal(window.__pendingModalId);
      window.__pendingModalId = null;
    }

    if (typeof startUnreadMessagesBadgePolling === 'function') startUnreadMessagesBadgePolling();
    
    // Paginate client transactions
    applyPagination('#page-payments .desk-only table', 'tbody tr', 10);
    applyPagination('#page-payments .mob-only > div', '.tx-item', 10);

    // Auto-initialize pagination on active tabs
    document.querySelectorAll('.tab-bar').forEach(bar => {
      const activeTab = bar.querySelector('.tab.on');
      if (activeTab) {
        const onClickAttr = activeTab.getAttribute('onclick');
        let targetId = '';
        if (onClickAttr && onClickAttr.includes("'")) {
          const match = onClickAttr.match(/'([^']+)'/);
          if (match) targetId = match[1];
        }
        setTab(activeTab, targetId);
      }
    });
  });

  setTimeout(() => toast('Welcome back, <?php echo addslashes(htmlspecialchars($user['name'])); ?>!', 'You have <?php echo (int) $unreadMessagesCount; ?> unread messages and <?php echo (int) $stats['open_proposals']; ?> new proposals'), 1000);
  async function processWorkLog(logId, action) {
    if (action === 'approved' && !(await remoConfirm('Payment will be released to the freelancer.', 'Approve this work?'))) return;
    if (action === 'rejected' && !(await remoConfirm('The freelancer will be notified of the rejection.', 'Reject this work?'))) return;

    fetch(BASE_URL + 'client/api/process-work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ log_id: logId, action: action })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          toast('Success', data.message);
          location.reload();
        } else {
          toast('Error', data.message);
        }
      })
      .catch(err => toast('Error', 'Communication failed'));
  }
  function saveClientProfile(btn) {
    const name = document.getElementById('client-name').value;
    const company = document.getElementById('client-company').value;
    const country = document.getElementById('client-country').value;
    const bio = document.getElementById('client-bio').value;

    if (!name) return toast('Error', 'Name is required');

    if (!btn) btn = document.querySelector('#page-settings button.btn-g');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Saving...';

    fetch(BASE_URL + 'client/api/update-profile.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: name,
        company: company,
        country: country,
        bio: bio
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          toast('Success', 'Profile updated successfully!');
          setTimeout(() => location.reload(), 1000);
        } else {
          toast('Error', data.message);
        }
      })
      .catch(err => toast('Error', 'Update failed'))
      .finally(() => {
        btn.disabled = false;
        btn.innerText = originalText;
      });
  }

  window.openFundMilestoneModal = function (milestoneId, amount, description, contractId) {
    const modal = document.getElementById('modal-panel');
    const mc = document.getElementById('mc-body');

    document.getElementById('mh-title').innerText = 'Fund Milestone';
    modal.style.maxWidth = '500px';

    const clientFee = amount * (clientFeePercent / 100);
    const totalAmount = amount + clientFee;
    const isWalletDisabled = availableBalance < totalAmount;

    mc.innerHTML = `
    <div style="padding:20px">
      <div style="background:var(--uw-bg); border:1px solid var(--uw-border); border-radius:10px; padding:15px; margin-bottom:20px">
        <div style="font-size:12px; color:var(--uw-gray); margin-bottom:4px; font-weight:600; text-transform:uppercase">Milestone Description</div>
        <div style="font-size:14px; font-weight:700; color:var(--uw-black); margin-bottom:12px">${description}</div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
          <span style="font-size:13px; color:var(--uw-gray)">Funding Amount:</span>
          <span style="font-size:14px; font-weight:700; color:var(--uw-black)">$${parseFloat(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
          <span style="font-size:13px; color:var(--uw-gray)">Service Fee (${clientFeePercent}%):</span>
          <span style="font-size:14px; font-weight:700; color:var(--uw-black)">$${clientFee.toFixed(2)}</span>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; border-top: 1px dashed var(--uw-border); padding-top: 8px;">
          <span style="font-size:13px; color:var(--uw-gray); font-weight:700">Total Charge:</span>
          <span style="font-size:18px; font-weight:800; color:var(--uw-green)">$${totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
        </div>
      </div>
      
      <div style="margin-bottom:20px">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:8px; color:var(--uw-black)">Select Payment Method</label>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div id="method-wallet" onclick="selectFundingMethod('wallet', ${isWalletDisabled})" style="border:1.5px solid ${isWalletDisabled ? '#ef4444' : 'var(--uw-green)'}; background:${isWalletDisabled ? '#fef2f2' : 'var(--uw-green-light)'}; border-radius:10px; padding:15px; cursor:pointer; text-align:center; position:relative">
            <div style="font-size:20px; margin-bottom:5px">💼</div>
            <div style="font-size:13px; font-weight:700; color:${isWalletDisabled ? '#b91c1c' : 'var(--uw-green)'}">Wallet Balance</div>
            <div style="font-size:11px; color:var(--uw-gray); margin-top:4px">Bal: $${availableBalance.toFixed(2)}</div>
            ${isWalletDisabled ? '<span style="font-size:9px; color:#ef4444; font-weight:700; display:block; margin-top:4px">Insufficient Balance</span>' : ''}
          </div>
          <div id="method-card" onclick="selectFundingMethod('card', false)" style="border:1.5px solid var(--uw-border); border-radius:10px; padding:15px; cursor:pointer; text-align:center">
            <div style="font-size:20px; margin-bottom:5px">💳</div>
            <div style="font-size:13px; font-weight:700; color:var(--uw-black)">Credit/Debit Card</div>
            <div style="font-size:11px; color:var(--uw-gray); margin-top:4px">Instant checkout</div>
          </div>
        </div>
      </div>
      
      <!-- Card Information Section (Hidden by default) -->
      <div id="card-fields-section" style="display:none; background:#f9fafb; border:1px solid var(--uw-border); border-radius:10px; padding:15px; margin-bottom:20px">
        <div style="margin-bottom:12px">
          <label style="display:block; font-size:11px; font-weight:700; color:var(--uw-gray); text-transform:uppercase; margin-bottom:5px">Cardholder Name</label>
          <input type="text" id="funding-card-name" placeholder="John Doe" style="width:100%; padding:10px; border:1.5px solid var(--uw-border); border-radius:6px; font-size:13px">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block; font-size:11px; font-weight:700; color:var(--uw-gray); text-transform:uppercase; margin-bottom:5px">Card Number</label>
          <input type="text" id="funding-card-number" placeholder="4111 2222 3333 4444" maxlength="19" style="width:100%; padding:10px; border:1.5px solid var(--uw-border); border-radius:6px; font-size:13px">
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div>
            <label style="display:block; font-size:11px; font-weight:700; color:var(--uw-gray); text-transform:uppercase; margin-bottom:5px">Expiry Date</label>
            <input type="text" id="funding-card-expiry" placeholder="MM/YY" maxlength="5" style="width:100%; padding:10px; border:1.5px solid var(--uw-border); border-radius:6px; font-size:13px">
          </div>
          <div>
            <label style="display:block; font-size:11px; font-weight:700; color:var(--uw-gray); text-transform:uppercase; margin-bottom:5px">CVV</label>
            <input type="password" id="funding-card-cvv" placeholder="•••" maxlength="4" style="width:100%; padding:10px; border:1.5px solid var(--uw-border); border-radius:6px; font-size:13px">
          </div>
        </div>
      </div>
      
      <div style="display:flex; gap:12px">
        <button class="btn btn-w" style="flex:1; justify-content:center; padding:12px" onclick="openContractDetailsAfterEscrow(${contractId})">Back</button>
        <button id="btn-submit-funding" class="btn btn-g" style="flex:2; justify-content:center; padding:12px" onclick="submitFunding(${milestoneId}, ${amount}, ${contractId})" ${isWalletDisabled ? 'disabled' : ''}>Fund Milestone →</button>
      </div>
    </div>
  `;

    // Track selected method
    window.selectedFundingMethod = isWalletDisabled ? 'card' : 'wallet';
    if (isWalletDisabled) {
      selectFundingMethod('card', false);
    }
  }

  window.selectFundingMethod = function (method, isDisabled) {
    if (isDisabled) return;
    window.selectedFundingMethod = method;

    const w = document.getElementById('method-wallet');
    const c = document.getElementById('method-card');
    const cardSection = document.getElementById('card-fields-section');
    const btn = document.getElementById('btn-submit-funding');

    if (method === 'wallet') {
      w.style.border = '1.5px solid var(--uw-green)';
      w.style.background = 'var(--uw-green-light)';
      w.querySelector('div:nth-child(2)').style.color = 'var(--uw-green)';

      c.style.border = '1.5px solid var(--uw-border)';
      c.style.background = 'white';
      c.querySelector('div:nth-child(2)').style.color = 'var(--uw-black)';

      cardSection.style.display = 'none';
      btn.disabled = false;
    } else {
      c.style.border = '1.5px solid var(--uw-green)';
      c.style.background = 'var(--uw-green-light)';
      c.querySelector('div:nth-child(2)').style.color = 'var(--uw-green)';

      w.style.border = '1.5px solid var(--uw-border)';
      w.style.background = 'white';
      w.querySelector('div:nth-child(2)').style.color = 'var(--uw-black)';

      cardSection.style.display = 'block';
      btn.disabled = false;
    }
  }

  window.openContractDetailsAfterEscrow = function (contractId) {
    toast('Loading...', 'Returning to contract');
    setTimeout(() => {
      manageContract(contractId);
    }, 100);
  }

  window.submitFunding = async function (milestoneId, amount, contractId) {
    const btn = document.getElementById('btn-submit-funding');
    const originalText = btn.innerText;

    if (window.selectedFundingMethod === 'card') {
      const name = document.getElementById('funding-card-name').value.trim();
      const num = document.getElementById('funding-card-number').value.trim();
      const exp = document.getElementById('funding-card-expiry').value.trim();
      const cvv = document.getElementById('funding-card-cvv').value.trim();

      if (!name || !num || !exp || !cvv) {
        toast('Required', 'Please complete all credit card fields.');
        return;
      }
    }

    btn.disabled = true;
    btn.innerText = 'Processing Payment...';

    try {
      const res = await fetch(BASE_URL + 'client/api/fund-milestone.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          milestone_id: milestoneId,
          payment_method: window.selectedFundingMethod
        })
      });

      const data = await res.json();
      if (data.success) {
        toast('Funded! 🎉', 'Milestone accepted & funded in escrow!');

        // Update balance globally if wallet was used
        if (window.selectedFundingMethod === 'wallet' && typeof data.new_balance === 'number') {
          availableBalance = data.new_balance;
          const balEl = document.getElementById('client-available-balance');
          if (balEl) balEl.textContent = '$' + availableBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Close the funding modal
        closeModal();

        // Return to contract details modal to reflect updated funded state in real-time
        setTimeout(() => {
          manageContract(contractId);
        }, 1000);
      } else {
        toast('Error', data.message);
        btn.disabled = false;
        btn.innerText = originalText;
      }
    } catch (err) {
      toast('Error', 'Payment processing failed.');
      btn.disabled = false;
      btn.innerText = originalText;
    }
  }
  window.submitNewMilestone = async function(contractId, freelancerId) {
    const descEl = document.getElementById('ms-desc');
    const amtEl = document.getElementById('ms-amount');
    const desc = descEl ? descEl.value.trim() : '';
    const amount = amtEl ? parseFloat(amtEl.value) : 0;
    const btn = document.getElementById('btn-submit-ms');

    if (!desc) {
      toast('Error', 'Please enter a description for the milestone.');
      return;
    }
    if (amount <= 0 || isNaN(amount)) {
      toast('Error', 'Please enter a valid amount greater than $0.');
      return;
    }

    btn.disabled = true;
    btn.innerText = 'Creating...';

    try {
      const response = await fetch(BASE_URL + 'client/api/add-milestone.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          contract_id: contractId,
          description: desc,
          amount: amount
        })
      });

      const result = await response.json();
      if (result.success) {
        toast('Success 🎉', result.message || 'Milestone added successfully.');
        closeModal();

        // Dynamically add the milestone to the local CONTRACTS list in memory
        CONTRACTS.forEach(c => {
          if (c.id == contractId) {
            if (!c.milestones) c.milestones = [];
            c.milestones.push(result.milestone);
          }
        });

        // Refresh chat window if active to show the backend-inserted milestone message
        if (activeChatId) {
          loadChat(activeChatId, activeChatName, activeChatInitials, null, activeChatAvatar);
        }
      } else {
        toast('Error', result.message || 'Failed to create milestone.');
        btn.disabled = false;
        btn.innerText = 'Create Milestone →';
      }
    } catch (err) {
      btn.disabled = false;
      btn.innerText = 'Create Milestone →';
      toast('Error', 'Failed to create milestone.');
    }
  }

  // Handle Paystack callback status
  window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('payment') === 'success') {
      const amount = urlParams.get('amount');
      toast('Payment Successful! ✓', `$${amount} has been added to your balance.`);
      // Clean up URL
      window.history.replaceState({}, document.title, window.location.pathname);
    } else if (urlParams.get('payment') === 'failed') {
      toast('Payment Failed', 'There was an issue processing your transaction.');
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  });
</script>
</body>

</html>