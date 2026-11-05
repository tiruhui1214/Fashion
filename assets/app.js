'use strict';
const motionPreference = matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = matchMedia('(hover: hover) and (pointer: fine)');
const decorativeAnimations = new Set();
const header=document.querySelector('.site-header.overlay');
if(header){const updateHeader=()=>header.classList.toggle('scrolled',window.scrollY>60);updateHeader();window.addEventListener('scroll',updateHeader,{passive:true});}
const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('.main-nav');
menu?.addEventListener('click',()=>{const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',String(open));menu.setAttribute('aria-label',open?'Close navigation':'Open navigation');});
document.querySelector('[data-dismiss]')?.addEventListener('click',e=>e.target.closest('.notice').remove());
document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{if(!window.confirm(form.dataset.confirm))e.preventDefault();}));
document.querySelectorAll('[data-card-image]').forEach(button=>button.addEventListener('click',()=>{const card=button.closest('.product-card');softImageSwap(card.querySelector('.product-photo img'),button.dataset.cardImage);card.querySelectorAll('.swatch').forEach(b=>{b.classList.remove('selected');b.setAttribute('aria-pressed','false');});button.classList.add('selected');button.setAttribute('aria-pressed','true');}));
// Manual browsing keeps one shopping CTA and avoids an extra autoplay/pause control.
const slides = [...document.querySelectorAll('.hero-slide')];
const hero = document.querySelector('.hero');
if (hero && slides.length > 1) {
  let index = 0;
  let pointerStart = null;
  const paint = (announce = false) => {
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === index);
      slide.setAttribute('aria-hidden', String(i !== index));
      slide.inert = i !== index;
    });
    const number = hero.querySelector('[data-hero-number]');
    if (number) number.textContent = String(index + 1).padStart(2, '0');
    const status = hero.querySelector('[data-hero-status]');
    if (status && announce) status.textContent = `Collection ${index + 1} of ${slides.length}`;
  };
  const move = step => { index = (index + step + slides.length) % slides.length; paint(true); };
  hero.addEventListener('keydown', event => {
    if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    // Keep focus in the carousel when a previously focused slide becomes inert.
    hero.focus({ preventScroll: true });
    move(event.key === 'ArrowRight' ? 1 : -1);
  });
  hero.addEventListener('pointerdown', event => {
    if (event.button !== 0 || event.target.closest('a,button')) return;
    pointerStart = { x: event.clientX, y: event.clientY };
  }, { passive: true });
  hero.addEventListener('pointerup', event => {
    if (!pointerStart) return;
    const dx = event.clientX - pointerStart.x;
    const dy = event.clientY - pointerStart.y;
    pointerStart = null;
    if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.2) move(dx < 0 ? 1 : -1);
  }, { passive: true });
  hero.addEventListener('pointercancel', () => { pointerStart = null; });
  hero.addEventListener('pointerleave', () => { pointerStart = null; });
  hero.addEventListener('dragstart', event => { if (!event.target.closest('a')) event.preventDefault(); });
  paint();
}
const product=document.querySelector('[data-product]');
if(product){const select=product.querySelector('[data-variant-select]'),buttons=[...product.querySelectorAll('[data-product-color]')],quantity=product.querySelector('[name=quantity]'),add=product.querySelector('[data-add-button]'),note=product.querySelector('[data-stock-note]');
 const stock=()=>{const option=select?.selectedOptions[0],available=Number(option?.dataset.stock||0);if(quantity)quantity.max=String(Math.min(99,available||1));if(add)add.disabled=!available;if(note)note.textContent=available?`${available} available in this size`:'This colour / size is currently sold out.';};
 const choose=button=>{const color=button.dataset.productColor;buttons.forEach(b=>{b.classList.toggle('selected',b===button);b.setAttribute('aria-pressed',String(b===button));});product.querySelector('[data-color-name]').textContent=color;const main=product.querySelector('[data-main-product-image]');softImageSwap(main,button.dataset.image);main.alt=`${document.querySelector('.product-info h1').textContent} — ${color}`;product.querySelectorAll('[data-gallery-color]').forEach(img=>img.hidden=img.dataset.galleryColor!==color);[...select.options].forEach(option=>{option.hidden=option.dataset.color!==color;option.disabled=option.hidden||Number(option.dataset.stock)===0;});const first=[...select.options].find(o=>!o.disabled);select.value=first?.value||'';if(quantity)quantity.value='1';stock();};
 buttons.forEach(button=>button.addEventListener('click',()=>choose(button)));select?.addEventListener('change',stock);if(buttons[0])choose(buttons[0]);}
const bannerForm=document.querySelector('[data-banner-form]');
if(bannerForm){const preview=bannerForm.querySelector('[data-banner-preview]');let objectURL;bannerForm.addEventListener('input',e=>{preview.style.color=bannerForm.elements.text_color.value;preview.querySelector('[data-preview-title]').textContent=bannerForm.elements.title.value||'Your heading';preview.querySelector('[data-preview-subtitle]').textContent=bannerForm.elements.subtitle.value||'Supporting text';preview.querySelector('div').style.textAlign=bannerForm.elements.alignment.value;});bannerForm.elements.image.addEventListener('change',e=>{const file=e.target.files[0];if(file&&['image/jpeg','image/png','image/webp'].includes(file.type)){if(objectURL)URL.revokeObjectURL(objectURL);objectURL=URL.createObjectURL(file);preview.querySelector('img').src=objectURL;}});}

// Keyboard navigation and motion are progressive enhancements; all content remains visible without JS.
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && nav?.classList.contains('open')) {
    nav.classList.remove('open');
    menu?.setAttribute('aria-expanded', 'false');
    menu?.setAttribute('aria-label', 'Open navigation');
    menu?.focus();
  }
});
// Motion never controls visibility or form submission. All features still work without it.
function decorate(element, keyframes, options) {
  if (motionPreference.matches || !element.animate) return;
  const animation = element.animate(keyframes, options);
  decorativeAnimations.add(animation);
  const release = () => decorativeAnimations.delete(animation);
  animation.finished.then(release, release);
  return animation;
}
function softImageSwap(image, source) {
  if (image.getAttribute('src') === source) return;
  image.src = source;
  if (motionPreference.matches) return;
  image.decode().then(() => {
    if (image.getAttribute('src') !== source) return;
    decorate(image, [{ opacity: .35 }, { opacity: 1 }], { duration: 380, easing: 'ease-out' });
  }).catch(() => {});
}

let reveal;
if ('IntersectionObserver' in window) {
  document.querySelectorAll('.department-grid, .product-grid').forEach(grid => {
    [...grid.children].forEach((card, index) => card.dataset.revealDelay = String((index % 4) * 75));
  });
  reveal = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      decorate(entry.target,
        [{ opacity: .55, transform: 'translateY(22px)' }, { opacity: 1, transform: 'translateY(0)' }],
        { duration: 680, fill: 'backwards', delay: Number(entry.target.dataset.revealDelay || 0), easing: 'cubic-bezier(.22,.61,.36,1)' }
      );
      reveal.unobserve(entry.target);
    });
  }, { threshold: .1 });
  document.querySelectorAll('.section-heading, .department-card, .product-card').forEach(element => reveal.observe(element));
}

// A small image drift follows the pointer only on desktop collection cards.
document.querySelectorAll('.department-card').forEach(card => {
  let frame = 0;
  let pointerX = 0;
  const reset = () => {
    cancelAnimationFrame(frame);
    frame = 0;
    card.style.removeProperty('--image-pan');
  };
  card.addEventListener('pointermove', event => {
    if (!finePointer.matches || motionPreference.matches || event.pointerType === 'touch') return;
    pointerX = event.clientX;
    if (frame) return;
    frame = requestAnimationFrame(() => {
      const bounds = card.getBoundingClientRect();
      const offset = Math.max(-1, Math.min(1, ((pointerX - bounds.left) / bounds.width - .5) * 2));
      card.style.setProperty('--image-pan', `${(offset * 5).toFixed(2)}px`);
      frame = 0;
    });
  });
  card.addEventListener('pointerleave', reset);
  card.addEventListener('pointercancel', reset);
  motionPreference.addEventListener('change', reset);
});

// A clipped, low-opacity ripple confirms a press without delaying navigation or submission.
document.querySelectorAll('.btn').forEach(button => {
  const ripple = event => {
    if (motionPreference.matches || button.disabled) return;
    if (event.type === 'pointerdown' && event.button !== 0) return;
    const bounds = button.getBoundingClientRect();
    const dot = document.createElement('span');
    dot.className = 'button-ripple';
    dot.setAttribute('aria-hidden', 'true');
    const keyboard = event.type === 'click';
    dot.style.left = `${keyboard ? bounds.width / 2 : event.clientX - bounds.left}px`;
    dot.style.top = `${keyboard ? bounds.height / 2 : event.clientY - bounds.top}px`;
    button.append(dot);
    const animation = decorate(dot,
      [{ transform: 'translate(-50%, -50%) scale(0)', opacity: .18 },
       { transform: `translate(-50%, -50%) scale(${Math.max(bounds.width, bounds.height) / 8})`, opacity: 0 }],
      { duration: 600, easing: 'ease-out' }
    );
    if (animation) animation.finished.then(() => dot.remove(), () => dot.remove());
    else dot.remove();
  };
  button.addEventListener('pointerdown', ripple);
  button.addEventListener('click', event => { if (event.detail === 0) ripple(event); });
});
motionPreference.addEventListener('change', () => {
  if (motionPreference.matches) decorativeAnimations.forEach(animation => animation.cancel());
});
