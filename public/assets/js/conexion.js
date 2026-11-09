// =========================================================
// API: pequeño "cliente HTTP" para hablar con las rutas /api/*.
// Uso:  await API.get('/api/score/leaderboard', { game_slug: 'x' })
//        await API.post('/api/comment/add', { game_slug, contenido })
// credentials: 'include' manda las cookies de sesión (login) en cada petición.
// =========================================================
const API = {
    baseUrl: window.APP_BASE || '',

    async get(endpoint, params = {}) {
        const url = new URL(this.baseUrl + endpoint, window.location.origin);
        Object.entries(params).forEach(([key, val]) => url.searchParams.set(key, val));

        const res = await fetch(url, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) throw new Error(`Error API ${res.status}: ${res.statusText}`);
        return res.json();
    },

    async post(endpoint, data = {}) {
        const res = await fetch(this.baseUrl + endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data),
        });
        if (!res.ok) throw new Error(`Error API ${res.status}: ${res.statusText}`);
        return res.json();
    },

    async delete(endpoint, data = {}) {
        const res = await fetch(this.baseUrl + endpoint, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data),
        });
        if (!res.ok) throw new Error(`Error API ${res.status}: ${res.statusText}`);
        return res.json();
    },
};