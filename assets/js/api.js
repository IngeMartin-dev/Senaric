/**
 * Cliente HTTP para la API del backend.
 *
 * Características:
 *   - Envía CSRF token en cada request (excepto GET).
 *   - Adjunta Authorization Bearer si hay sesión iniciada.
 *   - Devuelve siempre un objeto { ok, data, error }.
 *   - Lanza ApiError con estado HTTP y datos.
 */
(function () {
  'use strict';

  const cfg = window.APP_CONFIG || {};
  const baseUrl = (cfg.apiBase || '/api').replace(/\/$/, '');
  const csrfToken = cfg.csrfToken || '';

  class ApiError extends Error {
    constructor(message, status, payload) {
      super(message);
      this.name = 'ApiError';
      this.status = status;
      this.payload = payload;
    }
  }

  function getToken() {
    try { return localStorage.getItem('supabase_access_token') || ''; }
    catch (_) { return ''; }
  }

  async function request(method, path, body) {
    const headers = { 'Accept': 'application/json' };
    if (method !== 'GET' && method !== 'HEAD') {
      headers['Content-Type'] = 'application/json';
      headers['X-CSRF-Token'] = csrfToken;
    }
    const token = getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;

    const init = { method, headers, credentials: 'same-origin' };
    if (body !== undefined && body !== null) {
      init.body = typeof body === 'string' ? body : JSON.stringify(body);
    }

    const response = await fetch(baseUrl + path, init);
    const text = await response.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (_) { data = text; }

    if (!response.ok) {
      const message = (data && (data.error || data.message)) || ('Error ' + response.status);
      throw new ApiError(message, response.status, data);
    }
    return data;
  }

  const api = {
    get: (path) => request('GET', path),
    post: (path, body) => request('POST', path, body),
    put: (path, body) => request('PUT', path, body),
    patch: (path, body) => request('PATCH', path, body),
    del: (path) => request('DELETE', path),
    ApiError,
  };
  window.API = api;
})();