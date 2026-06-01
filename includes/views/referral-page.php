<?php
/** @var array $user */
$userName = htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$prefillCode = htmlspecialchars((string)($user['referral_code'] ?? ''), ENT_QUOTES, 'UTF-8');
$getReferralUrl = baseUrl('get-referral');
?>
<div id="page-referral" class="page">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap">
    <div>
      <div style="font-size:22px;font-weight:800;margin-bottom:6px">Refer &amp; Share</div>
      <div style="font-size:13.5px;color:#6b7280;line-height:1.6;max-width:560px">
        Invite friends and colleagues to RemoWorkers. Share your unique referral code or link — when they sign up using it, they will be linked to your account.
      </div>
    </div>
    <span style="font-size:36px;line-height:1">🎁</span>
  </div>

  <div id="referral-loading" style="display:none;padding:40px;text-align:center;color:#6b7280;font-size:14px">Loading your referral code…</div>
  <div id="referral-error" style="display:none;padding:16px 20px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:13px;margin-bottom:20px"></div>

  <div id="referral-content" style="<?php echo $prefillCode !== '' ? '' : 'display:none;'; ?>">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:24px">
      <div class="card" style="padding:24px;border-radius:14px;background:white;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,.06)">
        <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">Your Referral Code</div>
        <div style="font-size:32px;font-weight:900;letter-spacing:.12em;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;color:#111827;margin-bottom:16px" id="referral-code-value"><?php echo $prefillCode ?: '--------'; ?></div>
        <button type="button" class="btn btn-g" style="width:100%;justify-content:center" onclick="copyReferralText('referral-code-value','Referral code copied!')">
          📋 Copy Code
        </button>
      </div>

      <div class="card" style="padding:24px;border-radius:14px;background:white;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,.06)">
        <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px">Share Link</div>
        <div style="display:flex;gap:8px;margin-bottom:16px">
          <input type="text" readonly id="referral-link-value" value="" style="flex:1;padding:12px;font-size:13px;border:1.5px solid #e5e7eb;border-radius:10px;background:#f9fafb;color:#374151;outline:none;font-family:inherit" onclick="this.select()">
          <button type="button" class="btn btn-w" style="padding:12px 14px;border-radius:10px;border:1.5px solid #e5e7eb;flex-shrink:0;cursor:pointer" onclick="copyReferralText('referral-link-value','Referral link copied!')" title="Copy link">📋</button>
        </div>
        <button type="button" class="btn btn-g" style="width:100%;justify-content:center" onclick="shareReferralNative()">
          🔗 Share Link
        </button>
      </div>
    </div>

    <div class="card" style="padding:24px;border-radius:14px;background:white;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,.06)">
      <div style="font-size:15px;font-weight:800;margin-bottom:6px">Share via</div>
      <div style="font-size:13px;color:#6b7280;margin-bottom:18px">Send your invite through your favourite channel.</div>
      <div id="referral-share-buttons" style="display:flex;flex-wrap:wrap;gap:10px"></div>
    </div>

    <div class="card" style="margin-top:20px;padding:20px 24px;border-radius:14px;background:#f0fdf4;border:1px solid #bbf7d0">
      <div style="font-size:14px;font-weight:700;color:#166534;margin-bottom:6px">How it works</div>
      <ol style="margin:0;padding-left:18px;font-size:13px;color:#15803d;line-height:1.8">
        <li>Copy your referral code or share link above.</li>
        <li>Send it to friends who want to join RemoWorkers.</li>
        <li>They sign up using your link or enter code <strong id="referral-code-inline">--------</strong> during registration.</li>
        <li>When <strong id="referral-threshold-text">10</strong> referred users verify their email, complete account verification, and upload a profile photo, you earn <strong id="referral-reward-text">$1</strong> in wallet balance.</li>
      </ol>
    </div>

    <div class="card" style="margin-top:20px;padding:24px;border-radius:14px;background:white;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,.06)">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap">
        <div>
          <div style="font-size:15px;font-weight:800;margin-bottom:4px">Your Referrals</div>
          <div style="font-size:13px;color:#6b7280">Track signups and whether they completed verification steps.</div>
        </div>
        <div id="referral-stats-summary" style="display:none;font-size:13px;color:#374151;text-align:right;line-height:1.6">
          <div><strong id="referral-qualified-count">0</strong> qualified · <strong id="referral-total-count">0</strong> total</div>
          <div style="color:#166534;font-weight:600">Earned: <span id="referral-total-earned">$0.00</span></div>
        </div>
      </div>

      <div id="referral-progress-wrap" style="display:none;margin-bottom:22px;padding:16px 18px;border-radius:12px;background:#f9fafb;border:1px solid #e5e7eb">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px;flex-wrap:wrap">
          <div style="font-size:13px;font-weight:700;color:#111827">Progress to next reward</div>
          <div style="font-size:12px;color:#6b7280"><span id="referral-progress-count">0</span> / <span id="referral-progress-threshold">10</span> qualified</div>
        </div>
        <div style="height:10px;border-radius:999px;background:#e5e7eb;overflow:hidden">
          <div id="referral-progress-bar" style="height:100%;width:0;background:linear-gradient(90deg,#14a800,#22c55e);border-radius:999px;transition:width .3s ease"></div>
        </div>
      </div>

      <div id="referral-list-loading" style="display:none;padding:24px;text-align:center;color:#6b7280;font-size:13px">Loading referrals…</div>
      <div id="referral-list-empty" style="display:none;padding:28px 16px;text-align:center;color:#6b7280;font-size:13px;border:1px dashed #d1d5db;border-radius:12px">
        No one has signed up with your code yet. Share your link to get started.
      </div>

      <div id="referral-list-wrap" style="display:none;overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="border-bottom:2px solid #e5e7eb;text-align:left">
              <th style="padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em">User</th>
              <th style="padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em">Joined</th>
              <th style="padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em">Email Verified</th>
              <th style="padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em">Account Verified</th>
              <th style="padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em">Profile Photo</th>
              <th style="padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em">Qualified</th>
            </tr>
          </thead>
          <tbody id="referral-list-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const GET_REFERRAL_URL = <?php echo json_encode($getReferralUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  let referralLoaded = <?php echo $prefillCode !== '' ? 'true' : 'false'; ?>;
  let referralLoading = false;
  let REFERRAL_LINK = '';
  let REFERRAL_CODE = <?php echo json_encode($prefillCode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

  function notify(title, msg) {
    if (typeof window.toast === 'function') {
      window.toast(title, msg);
      return;
    }
    alert(msg);
  }

  function renderShareButtons() {
    const wrap = document.getElementById('referral-share-buttons');
    if (!wrap || !REFERRAL_LINK) return;
    const shareText = encodeURIComponent('Join me on RemoWorkers! Use my referral code ' + REFERRAL_CODE + ' when you sign up: ' + REFERRAL_LINK);
    const btnStyle = 'text-decoration:none;border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 16px;font-size:13px;font-weight:600;color:#374151';
    wrap.innerHTML = [
      '<a href="https://wa.me/?text=' + shareText + '" target="_blank" rel="noopener noreferrer" class="btn btn-w" style="' + btnStyle + '">WhatsApp</a>',
      '<a href="mailto:?subject=' + encodeURIComponent('Join me on RemoWorkers') + '&body=' + shareText + '" class="btn btn-w" style="' + btnStyle + '">Email</a>',
      '<a href="https://twitter.com/intent/tweet?text=' + shareText + '" target="_blank" rel="noopener noreferrer" class="btn btn-w" style="' + btnStyle + '">X / Twitter</a>',
      '<a href="https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(REFERRAL_LINK) + '" target="_blank" rel="noopener noreferrer" class="btn btn-w" style="' + btnStyle + '">Facebook</a>',
      '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(REFERRAL_LINK) + '" target="_blank" rel="noopener noreferrer" class="btn btn-w" style="' + btnStyle + '">LinkedIn</a>'
    ].join('');
  }

  function statusBadge(done, doneLabel, pendingLabel) {
    if (done) {
      return '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:12px;font-weight:600">✓ ' + doneLabel + '</span>';
    }
    return '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:600">○ ' + pendingLabel + '</span>';
  }

  function formatJoinedDate(value) {
    if (!value) return '—';
    const d = new Date(value.replace(' ', 'T'));
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function renderReferralStats(stats) {
    if (!stats) return;

    const threshold = stats.threshold || 10;
    const rewardAmount = stats.reward_amount || 1;
    const progress = stats.progress_to_next || 0;
    const qualified = stats.qualified_count || 0;
    const total = stats.total || 0;
    const earned = stats.total_earned || 0;

    const thresholdText = document.getElementById('referral-threshold-text');
    const rewardText = document.getElementById('referral-reward-text');
    if (thresholdText) thresholdText.textContent = String(threshold);
    if (rewardText) rewardText.textContent = '$' + Number(rewardAmount).toFixed(2).replace(/\.00$/, '');

    const summary = document.getElementById('referral-stats-summary');
    if (summary) summary.style.display = 'block';
    const qualifiedEl = document.getElementById('referral-qualified-count');
    const totalEl = document.getElementById('referral-total-count');
    const earnedEl = document.getElementById('referral-total-earned');
    if (qualifiedEl) qualifiedEl.textContent = String(qualified);
    if (totalEl) totalEl.textContent = String(total);
    if (earnedEl) earnedEl.textContent = '$' + Number(earned).toFixed(2);

    const progressWrap = document.getElementById('referral-progress-wrap');
    if (progressWrap) progressWrap.style.display = 'block';
    const progressCount = document.getElementById('referral-progress-count');
    const progressThreshold = document.getElementById('referral-progress-threshold');
    const progressBar = document.getElementById('referral-progress-bar');
    if (progressCount) progressCount.textContent = String(progress);
    if (progressThreshold) progressThreshold.textContent = String(threshold);
    if (progressBar) {
      const pct = threshold > 0 ? Math.min(100, Math.round((progress / threshold) * 100)) : 0;
      progressBar.style.width = pct + '%';
    }
  }

  function renderReferralList(referrals) {
    const loading = document.getElementById('referral-list-loading');
    const empty = document.getElementById('referral-list-empty');
    const wrap = document.getElementById('referral-list-wrap');
    const body = document.getElementById('referral-list-body');

    if (loading) loading.style.display = 'none';

    if (!referrals || referrals.length === 0) {
      if (empty) empty.style.display = 'block';
      if (wrap) wrap.style.display = 'none';
      return;
    }

    if (empty) empty.style.display = 'none';
    if (wrap) wrap.style.display = 'block';
    if (!body) return;

    body.innerHTML = referrals.map(function (r) {
      const name = String(r.name || 'User').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      return '<tr style="border-bottom:1px solid #f3f4f6">'
        + '<td style="padding:12px;font-weight:600;color:#111827">' + name + '</td>'
        + '<td style="padding:12px;color:#6b7280">' + formatJoinedDate(r.joined_at) + '</td>'
        + '<td style="padding:12px">' + statusBadge(!!r.email_verified, 'Verified', 'Pending') + '</td>'
        + '<td style="padding:12px">' + statusBadge(!!r.account_verified, 'Verified', 'Pending') + '</td>'
        + '<td style="padding:12px">' + statusBadge(!!r.profile_photo, 'Uploaded', 'Missing') + '</td>'
        + '<td style="padding:12px">' + statusBadge(!!r.qualified, 'Yes', 'Not yet') + '</td>'
        + '</tr>';
    }).join('');
  }

  function applyReferralData(code, link, stats, referrals) {
    REFERRAL_CODE = code;
    REFERRAL_LINK = link;
    referralLoaded = true;

    const codeEl = document.getElementById('referral-code-value');
    const linkEl = document.getElementById('referral-link-value');
    const inlineEl = document.getElementById('referral-code-inline');
    if (codeEl) codeEl.textContent = code;
    if (linkEl) linkEl.value = link;
    if (inlineEl) inlineEl.textContent = code;

    renderShareButtons();
    renderReferralStats(stats);
    renderReferralList(referrals);

    const content = document.getElementById('referral-content');
    const loading = document.getElementById('referral-loading');
    const error = document.getElementById('referral-error');
    if (content) content.style.display = '';
    if (loading) loading.style.display = 'none';
    if (error) error.style.display = 'none';
  }

  window.loadReferralPage = function () {
    if (referralLoading) {
      return Promise.resolve();
    }

    referralLoading = true;
    const loading = document.getElementById('referral-loading');
    const listLoading = document.getElementById('referral-list-loading');
    const error = document.getElementById('referral-error');
    if (loading) loading.style.display = referralLoaded ? 'none' : 'block';
    if (listLoading) listLoading.style.display = 'block';
    if (error) error.style.display = 'none';

    return fetch(GET_REFERRAL_URL, { credentials: 'same-origin' })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        referralLoading = false;
        if (!data || !data.success) {
          throw new Error((data && data.message) || 'Unable to load referral code.');
        }
        applyReferralData(data.code, data.link, data.stats, data.referrals || []);

        if (data.reward_credited && data.reward_credited.amount > 0) {
          const amount = Number(data.reward_credited.amount).toFixed(2);
          notify('Referral reward!', '$' + amount + ' added to your wallet balance.');
        }
      })
      .catch(function (err) {
        referralLoading = false;
        if (loading) loading.style.display = 'none';
        const listLoadingEl = document.getElementById('referral-list-loading');
        if (listLoadingEl) listLoadingEl.style.display = 'none';
        if (error) {
          error.textContent = err.message || 'Unable to load referral code.';
          error.style.display = 'block';
        }
      });
  };

  window.copyReferralText = function (elementId, successMsg) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = (el.value !== undefined ? el.value : el.textContent || '').trim();
    if (!text || text === '--------') return;

    const done = function () {
      notify('Copied', successMsg || 'Copied to clipboard.');
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () {
        if (el.select) el.select();
        document.execCommand('copy');
        done();
      });
      return;
    }

    if (el.select) {
      el.select();
      document.execCommand('copy');
    }
    done();
  };

  window.shareReferralNative = function () {
    if (!REFERRAL_LINK) {
      loadReferralPage();
      return;
    }

    const shareData = {
      title: 'Join RemoWorkers',
      text: 'Use my referral code ' + REFERRAL_CODE + ' to sign up on RemoWorkers.',
      url: REFERRAL_LINK
    };

    if (navigator.share) {
      navigator.share(shareData).catch(function () {
        copyReferralText('referral-link-value', 'Referral link copied!');
      });
      return;
    }

    copyReferralText('referral-link-value', 'Referral link copied!');
  };

  if (REFERRAL_CODE) {
    REFERRAL_LINK = <?php echo json_encode($prefillCode !== '' ? baseUrl('?ref=' . urlencode($prefillCode)) : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    renderShareButtons();
  }
})();
</script>
