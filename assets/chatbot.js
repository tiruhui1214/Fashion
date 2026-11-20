'use strict';
(() => {
  const root = document.querySelector('[data-fashion-chat]');
  if (!root) return;
  const launcher = root.querySelector('[data-chat-toggle]');
  const panel = root.querySelector('.chat-panel');
  const close = root.querySelector('[data-chat-close]');
  const thread = root.querySelector('[data-chat-thread]');
  const form = root.querySelector('[data-chat-form]');
  const input = form.elements.message;
  const send = form.querySelector('[type=submit]');
  const reset = root.querySelector('[data-chat-reset]');
  const status = root.querySelector('[data-chat-status]');
  const feedback = root.querySelector('.chat-feedback');
  const retry = root.querySelector('[data-chat-retry]');
  let busy = false;
  let pending = null;
  let cooldownUntil = 0;
  let cooldownTimer;
  const updateControls = () => {
    const remaining = Math.max(0, cooldownUntil - Date.now());
    send.disabled = busy || remaining > 0;
    reset.disabled = busy;
    clearTimeout(cooldownTimer);
    if (remaining > 0) cooldownTimer = setTimeout(updateControls, remaining + 10);
  };
  const storageKey = 'fashion_chat_open';
  const store = value => { try { sessionStorage.setItem(storageKey, value ? '1' : '0'); } catch (_) {} };
  const feedbackText = (text = '', error = false) => {
    status.textContent = text;
    feedback.classList.toggle('chat-error', error);
    retry.hidden = !error;
  };
  const setBusy = (value, text = '') => {
    busy = value;
    updateControls();
    thread.setAttribute('aria-busy', String(value));
    if (value) feedbackText(text);
  };
  const update = data => {
    if (typeof data.html === 'string') thread.innerHTML = data.html;
    if (typeof data.csrf === 'string') form.elements.csrf.value = data.csrf;
    cooldownUntil = Date.now() + Math.min(2000, Math.max(0, Number(data.retry_after_ms) || 0));
    updateControls();
    form.hidden = !data.authenticated;
    reset.hidden = !data.authenticated;
    requestAnimationFrame(() => { thread.scrollTop = thread.scrollHeight; });
  };
  const request = async (fields = null) => {
    if (busy) return false;
    setBusy(true, fields?.action === 'message' ? 'Finding pieces for you…' : 'Loading conversation…');
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 35000);
    try {
      const options = { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { Accept: 'application/json' } };
      if (fields) {
        options.method = 'POST';
        options.body = new URLSearchParams({ csrf: form.elements.csrf.value, ...fields });
      }
      const response = await fetch(root.dataset.endpoint, options);
      const data = await response.json();
      if (!data || typeof data.ok !== 'boolean') throw new Error('invalid response');
      update(data);
      if (!response.ok || !data.ok) {
        feedbackText(data.error || 'Could not send your request. Please try again.', true);
        return false;
      }
      feedbackText();
      return true;
    } catch (_) {
      feedbackText('Connection interrupted. Your request is kept below. Refresh chat before retrying.', true);
      return false;
    } finally {
      clearTimeout(timeout);
      setBusy(false);
    }
  };
  const openPanel = () => {
    panel.hidden = false;
    launcher.setAttribute('aria-expanded', 'true');
    launcher.setAttribute('aria-label', 'Close style assistant');
    store(true);
    if (form.hidden) close.focus(); else input.focus({ preventScroll: true });
    request();
  };
  const closePanel = () => {
    panel.hidden = true;
    launcher.setAttribute('aria-expanded', 'false');
    launcher.setAttribute('aria-label', 'Open style assistant');
    store(false);
    launcher.focus({ preventScroll: true });
  };
  launcher.addEventListener('click', () => panel.hidden ? openPanel() : closePanel());
  close.addEventListener('click', closePanel);
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); closePanel(); }
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy || Date.now() < cooldownUntil || !form.reportValidity()) return;
    const message = input.value.trim();
    if (!message) return;
    if (!pending || pending.message !== message) {
      pending = { message, request_id: globalThis.crypto?.randomUUID?.() || `chat-${Date.now()}-${Math.random().toString(36).slice(2)}` };
    }
    const submitted = message;
    if (await request({ action: 'message', ...pending })) {
      if (input.value.trim() === submitted) input.value = '';
      pending = null;
      if (!panel.hidden) input.focus({ preventScroll: true });
    }
  });
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); if (!busy && Date.now() >= cooldownUntil) form.requestSubmit(); }
  });
  reset.addEventListener('click', async () => {
    if (busy) return;
    const previousDraft = input.value;
    if (await request({ action: 'reset' })) {
      if (input.value === previousDraft) input.value = '';
      pending = null;
      input.focus({ preventScroll: true });
    }
  });
  retry.addEventListener('click', () => request());
  thread.addEventListener('click', event => {
    const example = event.target.closest('[data-chat-example]');
    if (!example || busy) return;
    input.value = example.dataset.chatExample;
    input.focus({ preventScroll: true });
  });
  const fitViewport = () => {
    const viewport = window.visualViewport;
    root.style.setProperty('--chat-vh', `${viewport?.height || innerHeight}px`);
    root.style.setProperty('--chat-keyboard', `${viewport ? Math.max(0, innerHeight - viewport.height - viewport.offsetTop) : 0}px`);
  };
  window.visualViewport?.addEventListener('resize', fitViewport);
  window.visualViewport?.addEventListener('scroll', fitViewport);
  window.addEventListener('resize', fitViewport);
  fitViewport();
  let restored = false;
  try { restored = sessionStorage.getItem(storageKey) === '1'; } catch (_) {}
  const url = new URL(location.href);
  if (url.searchParams.get('chat') === 'open') {
    restored = true; url.searchParams.delete('chat'); history.replaceState(null, '', url);
  }
  if (restored) openPanel();
})();
