/**
 * Theme-aligned toast, confirm, and prompt dialogs (replaces alert/confirm/prompt).
 */
(function () {
  const REMO_DIALOG_Z_INDEX = 15000;
  let confirmResolve = null;
  let promptResolve = null;
  let linkPromptResolve = null;
  let cryptoWithdrawResolve = null;
  let toastTimer = null;
  let remoToastRunning = false;

  function applyDialogOverlayShell(el) {
    const prevDisplay = el.style.display;
    el.className = 'remo-dialog-overlay';
    Object.assign(el.style, {
      position: 'fixed',
      inset: '0',
      zIndex: String(REMO_DIALOG_Z_INDEX),
      background: 'rgba(0, 0, 0, 0.5)',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '20px',
    });
    if (prevDisplay) el.style.display = prevDisplay;
  }

  function overlayBackdropShouldClose(el, e) {
    return e.target === el && el.dataset.ignoreBackdrop !== '1';
  }

  function showDialogOverlay(el, focusEl) {
    el.dataset.ignoreBackdrop = '1';
    requestAnimationFrame(() => {
      el.style.display = 'flex';
      el.style.zIndex = String(REMO_DIALOG_Z_INDEX);
      setTimeout(() => {
        delete el.dataset.ignoreBackdrop;
        if (focusEl) focusEl.focus();
      }, 0);
    });
  }

  function ensureToast() {
    let el = document.getElementById('toast');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'toast';
    el.className = 'toast';
    el.innerHTML = '<strong id="t-title"></strong><span id="t-msg"></span>';
    document.body.appendChild(el);
    return el;
  }

  function ensureConfirmModal() {
    let el = document.getElementById('remoConfirmModal');
    if (el) {
      applyDialogOverlayShell(el);
      return el;
    }
    el = document.createElement('div');
    el.id = 'remoConfirmModal';
    applyDialogOverlayShell(el);
    el.style.display = 'none';
    el.innerHTML = `
      <div class="remo-dialog-card" role="dialog" aria-modal="true">
        <div class="remo-dialog-header">
          <span class="remo-dialog-title" id="remoConfirmTitle">Confirm</span>
          <button type="button" class="remo-dialog-close" id="remoConfirmClose" aria-label="Close">&times;</button>
        </div>
        <div class="remo-dialog-body">
          <p id="remoConfirmMessage" class="remo-dialog-message"></p>
        </div>
        <div class="remo-dialog-footer">
          <button type="button" class="remo-dialog-btn remo-dialog-btn-cancel" id="remoConfirmCancel">Cancel</button>
          <button type="button" class="remo-dialog-btn remo-dialog-btn-primary" id="remoConfirmOk">Confirm</button>
        </div>
      </div>`;
    document.body.appendChild(el);
    el.querySelector('#remoConfirmCancel').addEventListener('click', () => finishConfirm(false));
    el.querySelector('#remoConfirmClose').addEventListener('click', () => finishConfirm(false));
    el.querySelector('#remoConfirmOk').addEventListener('click', () => finishConfirm(true));
    el.addEventListener('click', (e) => {
      if (overlayBackdropShouldClose(el, e)) finishConfirm(false);
    });
    return el;
  }

  function ensurePromptModal() {
    let el = document.getElementById('remoPromptModal');
    if (el) {
      applyDialogOverlayShell(el);
      return el;
    }
    el = document.createElement('div');
    el.id = 'remoPromptModal';
    applyDialogOverlayShell(el);
    el.style.display = 'none';
    el.innerHTML = `
      <div class="remo-dialog-card" role="dialog" aria-modal="true">
        <div class="remo-dialog-header">
          <span class="remo-dialog-title" id="remoPromptTitle">Input</span>
          <button type="button" class="remo-dialog-close" id="remoPromptClose" aria-label="Close">&times;</button>
        </div>
        <div class="remo-dialog-body">
          <p id="remoPromptMessage" class="remo-dialog-message"></p>
          <input type="text" id="remoPromptInput" class="remo-dialog-input" />
        </div>
        <div class="remo-dialog-footer">
          <button type="button" class="remo-dialog-btn remo-dialog-btn-cancel" id="remoPromptCancel">Cancel</button>
          <button type="button" class="remo-dialog-btn remo-dialog-btn-primary" id="remoPromptOk">OK</button>
        </div>
      </div>`;
    document.body.appendChild(el);
    el.querySelector('#remoPromptCancel').addEventListener('click', () => finishPrompt(null));
    el.querySelector('#remoPromptClose').addEventListener('click', () => finishPrompt(null));
    el.querySelector('#remoPromptOk').addEventListener('click', () => {
      const val = el.querySelector('#remoPromptInput').value;
      finishPrompt(val);
    });
    el.addEventListener('click', (e) => {
      if (overlayBackdropShouldClose(el, e)) finishPrompt(null);
    });
    return el;
  }

  function finishConfirm(result) {
    const el = document.getElementById('remoConfirmModal');
    if (el) el.style.display = 'none';
    if (confirmResolve) {
      const fn = confirmResolve;
      confirmResolve = null;
      fn(result);
    }
  }

  function finishPrompt(result) {
    const el = document.getElementById('remoPromptModal');
    if (el) el.style.display = 'none';
    if (promptResolve) {
      const fn = promptResolve;
      promptResolve = null;
      fn(result);
    }
  }

  function ensureLinkPromptModal() {
    let el = document.getElementById('remoLinkPromptModal');
    if (el) {
      applyDialogOverlayShell(el);
      return el;
    }
    el = document.createElement('div');
    el.id = 'remoLinkPromptModal';
    applyDialogOverlayShell(el);
    el.style.display = 'none';
    el.innerHTML = `
      <div class="remo-dialog-card" role="dialog" aria-modal="true">
        <div class="remo-dialog-header">
          <span class="remo-dialog-title" id="remoLinkPromptTitle">Insert link</span>
          <button type="button" class="remo-dialog-close" id="remoLinkPromptClose" aria-label="Close">&times;</button>
        </div>
        <div class="remo-dialog-body">
          <div class="remo-dialog-field">
            <label class="remo-dialog-label" for="remoLinkPromptText">Link text</label>
            <input type="text" id="remoLinkPromptText" class="remo-dialog-input" placeholder="Text shown in the email" />
          </div>
          <div class="remo-dialog-field">
            <label class="remo-dialog-label" for="remoLinkPromptUrl">URL</label>
            <input type="text" id="remoLinkPromptUrl" class="remo-dialog-input" placeholder="https://example.com" />
          </div>
        </div>
        <div class="remo-dialog-footer">
          <button type="button" class="remo-dialog-btn remo-dialog-btn-cancel" id="remoLinkPromptCancel">Cancel</button>
          <button type="button" class="remo-dialog-btn remo-dialog-btn-primary" id="remoLinkPromptOk">Insert</button>
        </div>
      </div>`;
    document.body.appendChild(el);
    el.querySelector('#remoLinkPromptCancel').addEventListener('click', () => finishLinkPrompt(null));
    el.querySelector('#remoLinkPromptClose').addEventListener('click', () => finishLinkPrompt(null));
    el.querySelector('#remoLinkPromptOk').addEventListener('click', () => {
      const text = el.querySelector('#remoLinkPromptText').value.trim();
      const url = el.querySelector('#remoLinkPromptUrl').value.trim();
      if (!text || !url) {
        window.remoAlert('Please enter both link text and URL.', 'Insert link');
        return;
      }
      finishLinkPrompt({ text, url });
    });
    el.addEventListener('click', (e) => {
      if (overlayBackdropShouldClose(el, e)) finishLinkPrompt(null);
    });
    return el;
  }

  function finishLinkPrompt(result) {
    const el = document.getElementById('remoLinkPromptModal');
    if (el) el.style.display = 'none';
    if (linkPromptResolve) {
      const fn = linkPromptResolve;
      linkPromptResolve = null;
      fn(result);
    }
  }

  function ensureCryptoWithdrawModal() {
    let el = document.getElementById('remoCryptoWithdrawModal');
    if (el) {
      applyDialogOverlayShell(el);
      return el;
    }
    el = document.createElement('div');
    el.id = 'remoCryptoWithdrawModal';
    applyDialogOverlayShell(el);
    el.style.display = 'none';
    el.innerHTML = `
      <div class="remo-dialog-card" role="dialog" aria-modal="true">
        <div class="remo-dialog-header">
          <span class="remo-dialog-title" id="remoCryptoWithdrawTitle">Crypto withdrawal</span>
          <button type="button" class="remo-dialog-close" id="remoCryptoWithdrawClose" aria-label="Close">&times;</button>
        </div>
        <div class="remo-dialog-body">
          <p id="remoCryptoWithdrawMessage" class="remo-dialog-message"></p>
          <div class="remo-dialog-field">
            <label class="remo-dialog-label" for="remoCryptoWithdrawAmount">Amount (USD / USDT)</label>
            <input type="number" id="remoCryptoWithdrawAmount" class="remo-dialog-input" min="0.01" step="0.01" placeholder="0.00" />
          </div>
          <div class="remo-dialog-field">
            <label class="remo-dialog-label" for="remoCryptoWithdrawAddress">Wallet address</label>
            <input type="text" id="remoCryptoWithdrawAddress" class="remo-dialog-input" placeholder="0x… or T…" autocomplete="off" />
          </div>
        </div>
        <div class="remo-dialog-footer">
          <button type="button" class="remo-dialog-btn remo-dialog-btn-cancel" id="remoCryptoWithdrawCancel">Cancel</button>
          <button type="button" class="remo-dialog-btn remo-dialog-btn-primary" id="remoCryptoWithdrawOk">Continue</button>
        </div>
      </div>`;
    document.body.appendChild(el);
    el.querySelector('#remoCryptoWithdrawCancel').addEventListener('click', () => finishCryptoWithdraw(null));
    el.querySelector('#remoCryptoWithdrawClose').addEventListener('click', () => finishCryptoWithdraw(null));
    el.querySelector('#remoCryptoWithdrawOk').addEventListener('click', () => {
      const maxBalance = parseFloat(el.dataset.maxBalance || '0') || 0;
      const amountRaw = el.querySelector('#remoCryptoWithdrawAmount').value.trim();
      const address = el.querySelector('#remoCryptoWithdrawAddress').value.trim();
      const amount = parseFloat(amountRaw);

      if (!amountRaw || Number.isNaN(amount) || amount <= 0) {
        window.remoAlert('Enter a valid withdrawal amount greater than zero.', 'Crypto withdrawal');
        return;
      }
      if (maxBalance > 0 && amount > maxBalance + 0.0001) {
        window.remoAlert('Amount cannot exceed your available balance ($' + maxBalance.toFixed(2) + ').', 'Crypto withdrawal');
        return;
      }
      if (!address || address.length < 10) {
        window.remoAlert('Enter a valid wallet address.', 'Crypto withdrawal');
        return;
      }
      finishCryptoWithdraw({ amount, address });
    });
    el.addEventListener('click', (e) => {
      if (overlayBackdropShouldClose(el, e)) finishCryptoWithdraw(null);
    });
    return el;
  }

  function finishCryptoWithdraw(result) {
    const el = document.getElementById('remoCryptoWithdrawModal');
    if (el) el.style.display = 'none';
    if (cryptoWithdrawResolve) {
      const fn = cryptoWithdrawResolve;
      cryptoWithdrawResolve = null;
      fn(result);
    }
  }

  function escapeRteHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function normalizeRteLinkUrl(url) {
    url = String(url || '').trim();
    if (!url) return '';
    if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
    return url;
  }

  window.remoToast = function (title, msg, type) {
    if (typeof window.showToast === 'function' && document.getElementById('toast')) {
      window.showToast(title, msg || '');
      return;
    }
    if (!remoToastRunning && typeof window.toast === 'function' && document.getElementById('toast')) {
      remoToastRunning = true;
      try {
        window.toast(title, msg || '', type);
        return;
      } finally {
        remoToastRunning = false;
      }
    }
    const el = ensureToast();
    const t = document.getElementById('t-title');
    const m = document.getElementById('t-msg');
    if (t) t.textContent = title || 'Notice';
    if (m) m.textContent = msg ? ' — ' + msg : '';
    el.classList.remove('toast-error', 'toast-success');
    if (type === 'error') el.classList.add('toast-error');
    if (type === 'success') el.classList.add('toast-success');
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 3500);
  };

  window.remoAlert = function (message, title) {
    remoToast(title || 'Notice', message, 'error');
  };

  window.remoConfirm = function (message, title, options) {
    options = options || {};
    return new Promise((resolve) => {
      const el = ensureConfirmModal();
      document.getElementById('remoConfirmTitle').textContent = title || 'Confirm';
      document.getElementById('remoConfirmMessage').textContent = message || '';
      const okBtn = document.getElementById('remoConfirmOk');
      okBtn.textContent = options.confirmLabel || 'Confirm';
      okBtn.className = 'remo-dialog-btn ' + (options.danger ? 'remo-dialog-btn-danger' : 'remo-dialog-btn-primary');
      document.getElementById('remoConfirmCancel').textContent = options.cancelLabel || 'Cancel';
      confirmResolve = resolve;
      showDialogOverlay(el);
    });
  };

  window.remoPrompt = function (message, title, defaultValue, options) {
    options = options || {};
    return new Promise((resolve) => {
      const el = ensurePromptModal();
      document.getElementById('remoPromptTitle').textContent = title || 'Input required';
      document.getElementById('remoPromptMessage').textContent = message || '';
      const input = document.getElementById('remoPromptInput');
      input.value = defaultValue || '';
      input.placeholder = options.placeholder || '';
      if (options.multiline) {
        if (input.tagName !== 'TEXTAREA') {
          const ta = document.createElement('textarea');
          ta.id = 'remoPromptInput';
          ta.className = 'remo-dialog-input';
          ta.rows = 4;
          input.replaceWith(ta);
        }
      } else if (document.getElementById('remoPromptInput').tagName === 'TEXTAREA') {
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.id = 'remoPromptInput';
        inp.className = 'remo-dialog-input';
        document.getElementById('remoPromptInput').replaceWith(inp);
      }
      promptResolve = resolve;
      showDialogOverlay(el, document.getElementById('remoPromptInput'));
    });
  };

  /**
   * @param {number} maxBalance Available balance (USD)
   * @param {string} [chainLabel] Network hint for the user
   * @returns {Promise<{amount: number, address: string}|null>}
   */
  window.remoCryptoWithdrawPrompt = function (maxBalance, chainLabel) {
    const available = Math.max(0, parseFloat(maxBalance) || 0);
    return new Promise((resolve) => {
      const el = ensureCryptoWithdrawModal();
      el.dataset.maxBalance = String(available);
      document.getElementById('remoCryptoWithdrawTitle').textContent = 'Crypto withdrawal';
      document.getElementById('remoCryptoWithdrawMessage').textContent =
        'Available: $' +
        available.toFixed(2) +
        '. Enter how much USDT to send and your wallet address' +
        (chainLabel ? ' (' + chainLabel + ').' : '.');
      const amountInput = document.getElementById('remoCryptoWithdrawAmount');
      const addressInput = document.getElementById('remoCryptoWithdrawAddress');
      amountInput.value = available > 0 ? available.toFixed(2) : '';
      amountInput.max = available > 0 ? available : undefined;
      addressInput.value = '';
      cryptoWithdrawResolve = resolve;
      showDialogOverlay(el, amountInput);
    });
  };

  window.remoLinkPrompt = function (title, defaults) {
    defaults = defaults || {};
    return new Promise((resolve) => {
      const el = ensureLinkPromptModal();
      document.getElementById('remoLinkPromptTitle').textContent = title || 'Insert link';
      const textInput = document.getElementById('remoLinkPromptText');
      const urlInput = document.getElementById('remoLinkPromptUrl');
      textInput.value = defaults.text || '';
      urlInput.value = defaults.url || 'https://';
      linkPromptResolve = resolve;
      showDialogOverlay(el, defaults.text ? urlInput : textInput);
    });
  };

  window.insertRteLink = function (editor, text, url) {
    if (!editor || !text || !url) return;
    editor.focus();
    const href = normalizeRteLinkUrl(url);
    const html = '<a href="' + escapeRteHtml(href) + '">' + escapeRteHtml(text) + '</a>';
    document.execCommand('insertHTML', false, html);
  };
})();
