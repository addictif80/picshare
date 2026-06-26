/**
 * PicShare — Main JS
 */

(function () {
  'use strict';

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });

  // CSRF helper for fetch
  window.fetchPost = function (url, data = {}) {
    const csrf = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '';
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: Object.entries(data).map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&'),
    });
  };

  // Confirm buttons
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });

  // Color picker sync
  document.querySelectorAll('.color-picker').forEach(picker => {
    const text = picker.closest('.color-input-group')?.querySelector('.color-text');
    if (text) {
      picker.addEventListener('input', () => { text.value = picker.value; });
    }
  });

  // File size display
  document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function () {
      const hint = this.closest('.form-group')?.querySelector('.file-size-hint');
      if (!hint || !this.files.length) return;
      const total = [...this.files].reduce((s, f) => s + f.size, 0);
      hint.textContent = `${this.files.length} fichier(s) — ${formatBytes(total)}`;
    });
  });

  function formatBytes(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' Go';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' Mo';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' Ko';
    return bytes + ' o';
  }

  // Sticky navbar shrink on scroll
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.style.boxShadow = window.scrollY > 10 ? '0 2px 16px rgba(0,0,0,0.1)' : '';
    });
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

})();
