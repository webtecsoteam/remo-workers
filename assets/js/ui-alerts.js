/**
 * Theme-aligned toast, confirm, and prompt dialogs (replaces alert/confirm/prompt).
 */
(function () {
  let confirmResolve = null;
  let promptResolve = null;
  let toastTimer = null;

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
    if (el) return el;
    el = document.createElement('div');
    el.id = 'remoConfirmModal';
    el.className = 'remo-dialog-overlay';
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
      if (e.target === el) finishConfirm(false);
    });
    return el;
  }

  function ensurePromptModal() {
    let el = document.getElementById('remoPromptModal');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'remoPromptModal';
    el.className = 'remo-dialog-overlay';
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
      if (e.target === el) finishPrompt(null);
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

  window.remoToast = function (title, msg, type) {
    if (typeof window.showToast === 'function' && document.getElementById('toast')) {
      window.showToast(title, msg || '');
      return;
    }
    if (typeof window.toast === 'function' && document.getElementById('toast')) {
      window.toast(title, msg || '');
      const el = document.getElementById('toast');
      if (el) {
        el.classList.remove('toast-error', 'toast-success');
        if (type === 'error') el.classList.add('toast-error');
        if (type === 'success') el.classList.add('toast-success');
      }
      return;
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
      el.style.display = 'flex';
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
      el.style.display = 'flex';
      setTimeout(() => document.getElementById('remoPromptInput').focus(), 50);
    });
  };
})();
