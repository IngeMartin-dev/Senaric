/**
 * Utilidades UI: toasts, helpers de DOM, formato COP.
 */
(function () {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function fmt(value) {
    const n = Number(value || 0);
    return '$' + n.toLocaleString('es-CO', { maximumFractionDigits: 0 }) + ' COP';
  }

  function escape(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function toast(message, type = 'info', duration = 3500) {
    const region = document.getElementById('toast-region');
    if (!region) return;
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.role = 'status';
    el.textContent = message;
    region.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 200);
    }, duration);
  }

  function debounce(fn, wait = 250) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  // Mobile nav toggle
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.nav-toggle');
    if (!btn) return;
    document.querySelector('.site-header')?.classList.toggle('is-open');
  });

  document.addEventListener('click', (e) => {
    const header = document.querySelector('.site-header');
    if (!header || !header.classList.contains('is-open')) return;
    if (e.target.closest('.primary-nav') || e.target.closest('.nav-toggle')) return;
    header.classList.remove('is-open');
  });

  window.UI = { $, $$, fmt, escape, toast, debounce };
})();