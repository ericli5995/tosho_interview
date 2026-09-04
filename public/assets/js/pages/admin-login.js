/* Admin login: GET /api/session for the CSRF token, POST /api/admin/login. */
Vue.createApp({
    data: () => ({ email: "", password: "", error: "", busy: false }),
    async mounted() {
        const { user } = await api.session();
        if (user) this.done();
    },
    methods: {
        done() {
            const next = new URLSearchParams(location.search).get("next");
            location.replace(next && next.startsWith("/admin/") ? next : "/admin/products.html");
        },
        async submit() {
            this.busy = true; this.error = "";
            try {
                await api.post("/api/admin/login", { email: this.email, password: this.password });
                this.done();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },
    },
    template: `
<div class="auth-card">
  <h1 class="auth-card__title">管理画面ログイン</h1>
  <p class="auth-card__note">製品・画像のアップロードは管理者のみ行えます。</p>
  <div v-if="error" class="flash"><p class="flash__item flash__item--error">{{ error }}</p></div>
  <form class="form" @submit.prevent="submit">
    <div class="field"><label for="email">メールアドレス</label><input id="email" type="email" v-model.trim="email" required autofocus autocomplete="username"></div>
    <div class="field"><label for="password">パスワード</label><input id="password" type="password" v-model="password" required autocomplete="current-password"></div>
    <button type="submit" class="btn btn--primary btn--block" :disabled="busy">ログイン</button>
  </form>
  <p class="auth-card__hint">アカウント作成: <code>php bin/create-admin.php you@example.com "password"</code></p>
</div>`,
}).mount("#login");
