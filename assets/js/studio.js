/**
 * UX Pacific Studio page — scroll reveal, counters, FAQ accordion
 */
(function () {
  'use strict';

  const revealEls = document.querySelectorAll(
    '.studio-reveal, .studio-reveal-left, .studio-reveal-right, .studio-reveal-scale, .studio-stagger'
  );

  if (revealEls.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
    );

    revealEls.forEach((el) => observer.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('is-visible'));
  }

  function formatCount(value, suffix) {
    if (suffix === '%') return value + suffix;
    return value.toLocaleString() + suffix;
  }

  function animateCountEl(el, duration) {
    if (el.dataset.counted === 'true') return;
    el.dataset.counted = 'true';

    const target = parseFloat(el.getAttribute('data-count'));
    const suffix = el.getAttribute('data-suffix') || '';
    let startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      const progress = Math.min((timestamp - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 4);
      const current = Math.round(eased * target);

      el.textContent = formatCount(current, suffix);

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = formatCount(target, suffix);
      }
    }

    el.textContent = formatCount(0, suffix);
    requestAnimationFrame(step);
  }

  function initCountUp(selector, options) {
    const opts = options || {};
    const els = document.querySelectorAll(selector);
    if (!els.length) return;

    const duration = opts.duration || 2200;
    const trigger = opts.trigger || null;
    const threshold = opts.threshold || 0.35;

    function runAll() {
      els.forEach((el) => animateCountEl(el, duration));
    }

    if (!('IntersectionObserver' in window)) {
      runAll();
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          runAll();
          observer.disconnect();
        });
      },
      { threshold, rootMargin: '0px 0px -20px 0px' }
    );

    observer.observe(trigger || els[0]);
  }

  initCountUp('.studio-status-num[data-count]', {
    trigger: document.getElementById('studio-stats-pill'),
    threshold: 0.5
  });

  initCountUp('.studio-metric-num[data-count]', { threshold: 0.3 });

  const faqToggles = document.querySelectorAll('.studio-faq-toggle');

  faqToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      const item = toggle.closest('.studio-faq-item');

      faqToggles.forEach((other) => {
        if (other === toggle) return;
        other.setAttribute('aria-expanded', 'false');
        other.closest('.studio-faq-item')?.classList.remove('is-open');
      });

      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      item?.classList.toggle('is-open');
    });
  });
})();
