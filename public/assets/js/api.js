/* Minimal client for the JSON API: JSON/FormData bodies, CSRF header, error objects.
   Usage: await api.session(); const { product } = await api.get('/api/products/te-22bk'); */
const api = (() => {
    let csrf = null;

    async function request(method, url, body) {
        const headers = { Accept: "application/json" };
        if (csrf) headers["X-CSRF-Token"] = csrf;
        if (body && !(body instanceof FormData)) {
            headers["Content-Type"] = "application/json";
            body = JSON.stringify(body);
        }

        const res = await fetch(url, { method, headers, body, credentials: "same-origin" });
        const data = res.status === 204 ? null : await res.json().catch(() => ({}));

        if (!res.ok) {
            const err = new Error((data && data.error) || `HTTP ${res.status}`);
            err.status = res.status;
            err.errors = (data && data.errors) || {};
            throw err;
        }
        return data;
    }

    return {
        get: (url) => request("GET", url),
        post: (url, body) => request("POST", url, body),
        put: (url, body) => request("PUT", url, body),
        del: (url) => request("DELETE", url),

        /* GET /api/session - caches the CSRF token; returns { csrf, user|null }. */
        async session() {
            const s = await request("GET", "/api/session");
            csrf = s.csrf;
            return s;
        },

        /* Admin pages: bounce to the login page unless an admin is logged in. */
        async requireAdmin() {
            const s = await this.session();
            if (!s.user) {
                location.replace("/admin/login.html?next=" + encodeURIComponent(location.pathname + location.search));
                await new Promise(() => {}); // stop the caller while the browser navigates
            }
            return s;
        },
    };
})();
