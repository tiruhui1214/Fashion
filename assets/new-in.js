'use strict';
(() => {
  const root = document.querySelector('[data-new-in-queue]');
  if (!root) return;
  const stage = root.querySelector('[data-queue-stage]');
  const figures = [...root.querySelectorAll('[data-queue-item]')];
  const panels = [...root.querySelectorAll('[data-queue-panel]')];
  const counter = root.querySelector('[data-queue-counter]');
  const status = root.querySelector('[data-queue-status]');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let index = 0, pointer = null, ignoreClickUntil = 0;
  const count = figures.length;
  let autoEnabled = count > 1 && !reduced.matches, hovered = false, timer = null;
  const schedule = () => {
    clearTimeout(timer);
    const focused = root.contains(document.activeElement) && document.activeElement.matches(':focus-visible');
    if (!autoEnabled || count < 2 || hovered || focused || pointer || document.hidden) return;
    timer = setTimeout(() => { show(index + 1, false); schedule(); }, 3000);
  };
  stage.addEventListener('mouseenter', () => { hovered = true; schedule(); });
  stage.addEventListener('mouseleave', () => { hovered = false; schedule(); });
  root.addEventListener('focusin', schedule);
  root.addEventListener('focusout', () => setTimeout(schedule, 0));
  root.addEventListener('pointerdown', () => clearTimeout(timer), { passive: true });
  root.addEventListener('pointerup', () => setTimeout(schedule, 0), { passive: true });
  document.addEventListener('visibilitychange', schedule);
  const show = (next, announce = true) => {
    if (!count) return;
    const previous = index;
    index = (next + count) % count;
    figures.forEach((figure, i) => {
      const offset = (i - index + count) % count;
      // The just-departed main image exits to the right while queued images advance.
      const position = i === previous && previous !== index && offset > 2 ? -1 : Math.min(offset, 3);
      figure.dataset.position = String(position);
      figure.classList.toggle('is-current', i === index);
      figure.setAttribute('aria-pressed', String(i === index));
      figure.tabIndex = offset <= 2 ? 0 : -1;
      figure.setAttribute('aria-hidden', String(offset > 2));
    });
    panels.forEach((panel, i) => {
      const active = i === index;
      panel.hidden = !active;
      panel.inert = !active;
      if (active && announce && !reduced.matches && panel.animate) {
        panel.getAnimations().forEach(animation => animation.cancel());
        panel.animate([{ opacity: .25, transform: 'translateX(18px)' }, { opacity: 1, transform: 'translateX(0)' }], { duration: 420, easing: 'ease-out' });
      }
    });
    counter.textContent = `${String(index + 1).padStart(2, '0')} / ${String(count).padStart(2, '0')}`;
    schedule();
    if (announce) status.textContent = `${index + 1} of ${count}: ${panels[index].querySelector('h2').textContent}`;
  };
  root.querySelector('[data-queue-prev]').addEventListener('click', () => show(index - 1));
  root.querySelector('[data-queue-next]').addEventListener('click', () => show(index + 1));
  figures.forEach((figure, i) => figure.addEventListener('click', () => {
    if (Date.now() >= ignoreClickUntil) show(i);
  }));
  stage.addEventListener('keydown', event => {
    if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    stage.focus({ preventScroll: true });
    show(index + (event.key === 'ArrowRight' ? 1 : -1));
  });
  stage.addEventListener('pointerdown', event => {
    if (event.button !== 0 || event.target.closest('[data-queue-prev],[data-queue-next]')) return;
    pointer = { x: event.clientX, y: event.clientY, id: event.pointerId };
  }, { passive: true });
  stage.addEventListener('pointerup', event => {
    if (!pointer || event.pointerId !== pointer.id) return;
    const dx = event.clientX - pointer.x, dy = event.clientY - pointer.y;
    pointer = null;
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.3) {
      ignoreClickUntil = Date.now() + 400;
      show(index + (dx < 0 ? 1 : -1));
    }
  }, { passive: true });
  ['pointercancel', 'pointerleave'].forEach(type => stage.addEventListener(type, () => { pointer = null; schedule(); }));
  stage.addEventListener('dragstart', event => event.preventDefault());
  panels.forEach((panel, i) => {
    const form = panel.querySelector('[data-queue-purchase]');
    if (!form) return;
    const select = form.querySelector('[data-queue-variant]');
    const quantity = form.querySelector('[data-queue-quantity]');
    const add = form.querySelector('[data-queue-add]');
    const stock = form.querySelector('[data-queue-stock]');
    const refreshStock = () => {
      const available = Number(select.selectedOptions[0]?.dataset.stock || 0);
      quantity.max = String(Math.max(1, Math.min(99, available)));
      quantity.value = String(Math.max(1, Math.min(Number(quantity.value) || 1, Number(quantity.max))));
      add.disabled = available === 0;
      add.firstChild.textContent = available ? 'Add to bag ' : 'Sold out ';
      stock.textContent = available ? `${available} available in this size` : 'This colour / size is currently sold out.';
    };
    const chooseColor = button => {
      const color = button.dataset.newColor;
      form.querySelectorAll('[data-new-color]').forEach(item => {
        const active = item === button;
        item.classList.toggle('is-selected', active);
        item.setAttribute('aria-pressed', String(active));
      });
      form.querySelector('[data-queue-color-name]').textContent = color;
      [...select.options].forEach(option => {
        option.hidden = option.dataset.color !== color;
        option.disabled = option.hidden || Number(option.dataset.stock) < 1;
      });
      const first = [...select.options].find(option => !option.disabled);
      select.value = first?.value || '';
      quantity.value = '1';
      const image = figures[i].querySelector('img');
      image.src = button.dataset.image;
      image.alt = `${panel.querySelector('h2').textContent} — ${color}`;
      refreshStock();
    };
    form.querySelectorAll('[data-new-color]').forEach(button => button.addEventListener('click', () => chooseColor(button)));
    select.addEventListener('change', refreshStock);
    refreshStock();
    // Normal CSRF-protected cart_add submission performs server stock/ownership checks.
    // Do not fabricate an optimistic cart count or intercept failed server operations.
  });
  reduced.addEventListener('change', () => {
    if (reduced.matches) {
      autoEnabled = false;
      panels.forEach(panel => panel.getAnimations().forEach(animation => animation.cancel()));
      schedule();
    }
  });
  const cursor = document.createElement('span');
  cursor.className = 'queue-drag-cursor';
  cursor.setAttribute('aria-hidden', 'true');
  stage.append(cursor);
  const finePointer = matchMedia('(hover: hover) and (pointer: fine)');
  stage.addEventListener('pointermove', event => {
    const enabled = event.pointerType === 'mouse' && finePointer.matches && !reduced.matches && !event.target.closest('.queue-controls');
    stage.classList.toggle('has-drag-cursor', enabled);
    if (!enabled) return;
    const rect = stage.getBoundingClientRect();
    cursor.style.left = `${event.clientX - rect.left}px`;
    cursor.style.top = `${event.clientY - rect.top}px`;
  }, { passive: true });
  stage.addEventListener('pointerleave', () => stage.classList.remove('has-drag-cursor'));
  root.classList.add('queue-ready');
  show(0, false);
  schedule();
})();
