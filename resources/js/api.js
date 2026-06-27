// Lee la base URL desde el meta tag inyectado por Laravel en el blade
function getBase() {
    const meta = document.querySelector('meta[name="api-base"]');
    if (meta) return meta.content;
    if (import.meta.env.VITE_API_BASE) return import.meta.env.VITE_API_BASE;
    return window.location.origin + '/api';
}

const BASE = getBase();

function getToken() {
    return localStorage.getItem('token');
}

function headers(extra = {}) {
    const h = { 'Content-Type': 'application/json', ...extra };
    const token = getToken();
    if (token) h['Authorization'] = `Bearer ${token}`;
    return h;
}

async function request(method, path, body = null) {
    const opts = { method, headers: headers() };
    if (body) opts.body = JSON.stringify(body);

    const res = await fetch(`${BASE}${path}`, opts);

    if (res.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.reload();
        return;
    }

    if (res.status === 204) return null;
    return res.json();
}

export const api = {
    get(path, params = {}) {
        const qs = new URLSearchParams(
            Object.fromEntries(Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''))
        ).toString();
        return request('GET', qs ? `${path}?${qs}` : path);
    },
    post: (path, body) => request('POST', path, body),
    put: (path, body) => request('PUT', path, body),
    patch: (path, body) => request('PATCH', path, body),
    delete: (path) => request('DELETE', path),

    async download(path, params = {}) {
        const qs = new URLSearchParams(
            Object.fromEntries(Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''))
        ).toString();
        const url = `${BASE}${path}${qs ? '?' + qs : ''}`;
        const res = await fetch(url, { headers: headers() });
        if (!res.ok) throw new Error('Export failed');
        const blob = await res.blob();
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        const disposition = res.headers.get('content-disposition') || '';
        const match = disposition.match(/filename="(.+?)"/);
        a.download = match ? match[1] : 'export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    },

    async login(email, password) {
        const res = await fetch(`${BASE}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Error al iniciar sesión');
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        return data;
    },

    async logout() {
        await request('POST', '/logout');
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    },

    isAuthenticated() {
        return !!getToken();
    },

    getUser() {
        const u = localStorage.getItem('user');
        return u ? JSON.parse(u) : null;
    },
};
