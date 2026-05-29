/**
 * Theme-aligned toast, confirm, and prompt dialogs (replaces alert/confirm/prompt).
 */
(function () {
  let confirmResolve = null;
  let promptResolve = null;
  let linkPromptResolve = null;
  let toastTimer = null;
  let remoToastRunning = false;

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

  function ensureLinkPromptModal() {
    let el = document.getElementById('remoLinkPromptModal');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'remoLinkPromptModal';
    el.className = 'remo-dialog-overlay';
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
      if (e.target === el) finishLinkPrompt(null);
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
      el.style.display = 'flex';
      setTimeout(() => (defaults.text ? urlInput : textInput).focus(), 50);
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
