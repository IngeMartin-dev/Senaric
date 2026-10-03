/**
 * Validación ligera de formularios en cliente.
 * Complementa — NO reemplaza — la del backend.
 */
(function () {
  'use strict';

  const rules = {
    required: (v) => v != null && String(v).trim() !== '',
    email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v)),
    minLength: (v, n) => String(v || '').length >= n,
    maxLength: (v, n) => String(v || '').length <= n,
    pattern: (v, p) => new RegExp(p).test(String(v)),
  };

  function validate(form) {
    const fields = form.querySelectorAll('[data-validate]');
    const errors = {};
    fields.forEach(el => {
      const specs = (el.dataset.validate || '').split('|');
      let msg = null;
      for (const spec of specs) {
        const [rule, arg] = spec.split(':');
        const fn = rules[rule];
        if (!fn) continue;
        const ok = arg ? fn(el.value, arg) : fn(el.value);
        if (!ok) {
          msg = el.dataset.errorMessage || defaultMessage(rule);
          break;
        }
      }
      if (msg) {
        errors[el.name] = msg;
        const small = form.querySelector(`[data-error-for="${el.name}"]`);
        if (small) small.textContent = msg;
        el.closest('.field')?.classList.add('has-error');
      } else {
        const small = form.querySelector(`[data-error-for="${el.name}"]`);
        if (small) small.textContent = '';
        el.closest('.field')?.classList.remove('has-error');
      }
    });
    return errors;
  }

  function defaultMessage(rule) {
    return {
      required: 'Este campo es obligatorio.',
      email: 'Correo no válido.',
      minLength: 'Texto demasiado corto.',
      maxLength: 'Texto demasiado largo.',
      pattern: 'Formato no válido.',
    }[rule] || 'Valor no válido.';
  }

  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.hasAttribute('data-validate-form') && !form.classList.contains('validate')) return;
    const errors = validate(form);
    if (Object.keys(errors).length) {
      e.preventDefault();
      window.UI.toast('Por favor revisa los campos marcados.', 'error');
    }
  });

  window.VALIDATOR = { validate };
})();