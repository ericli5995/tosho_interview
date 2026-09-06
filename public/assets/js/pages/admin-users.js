/* Admin accounts: list / create / reset password / delete (/api/admin/users).
   Every write sends the caller's own password again on top of the session. */
const EMPTY = { email: "", name: "", password: "", current_password: "" };

Vue.createApp({
    data: () => ({
        ready: false, me: null, items: [],
        form: { ...EMPTY },                       // create form
        action: null,                             // { type: "reset" | "delete", user, password, current_password }
        errors: {}, error: "", flash: "", flashTimer: null, busy: false,
    }),
    async mounted() {
        const { user } = await api.requireAdmin();
        Layout.admin(user);
        this.me = user;
        await this.load();
        this.ready = true;
    },
    methods: {
        notify(message) {
            clearTimeout(this.flashTimer);
            this.flash = message || "";
            if (this.flash) this.flashTimer = setTimeout(() => { this.flash = ""; }, 4000);
        },
        async load() { this.items = (await api.get("/api/admin/users")).items; },
        begin(type, user) { this.action = { type, user, password: "", current_password: "" }; this.errors = {}; this.error = ""; },
        cancel() { this.action = null; this.errors = {}; this.error = ""; },
        /* Runs a write; fn returns the success message. Errors land in error / errors. */
        async run(fn) {
            this.busy = true; this.errors = {}; this.error = "";
            try { this.notify(await fn()); await this.load(); }
            catch (e) { this.error = e.message; this.errors = e.errors; }
            finally { this.busy = false; }
        },
        create() {
            return this.run(async () => {
                const { user } = await api.post("/api/admin/users", this.form);
                this.form = { ...EMPTY };
                return `管理者「${user.email}」を追加しました。`;
            });
        },
        confirmAction() {
            const a = this.action;
            return this.run(async () => {
                if (a.type === "reset") await api.post(`/api/admin/users/${a.user.id}/password`, { password: a.password, current_password: a.current_password });
                else await api.del(`/api/admin/users/${a.user.id}`, { current_password: a.current_password });
                this.action = null;
                return a.type === "reset" ? `「${a.user.email}」のパスワードを変更しました。` : `「${a.user.email}」を削除しました。`;
            });
        },
    },
    template: `
<header class="page-head"><h1>管理者 <span v-if="ready" class="page-head__count">({{ items.length }})</span></h1>
  <a class="btn btn--ghost" href="/admin/products">製品一覧へ</a></header>
<div v-if="flash" class="flash"><p class="flash__item flash__item--success">{{ flash }}</p></div>
<div v-if="error" class="flash"><p class="flash__item flash__item--error">{{ error }}</p></div>

<div class="form-grid" v-if="ready">
  <section class="panel">
    <h2>登録済みの管理者</h2>
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>メールアドレス</th><th>表示名</th><th>最終ログイン</th><th></th></tr></thead>
      <tbody><tr v-for="u in items" :key="u.id">
        <td><strong>{{ u.email }}</strong> <span v-if="me && u.id === me.id" class="badge badge--live">あなた</span></td>
        <td>{{ u.name }}</td>
        <td class="muted">{{ u.last_login_at || "—" }}</td>
        <td class="data-table__actions"><div class="row-actions">
          <button type="button" class="btn btn--ghost btn--sm" @click="begin('reset', u)">パスワード変更</button>
          <button v-if="!me || u.id !== me.id" type="button" class="btn btn--danger btn--sm" @click="begin('delete', u)">削除</button>
        </div></td>
      </tr></tbody>
    </table></div>

    <form v-if="action" class="form" style="margin-top:18px" @submit.prevent="confirmAction" autocomplete="off">
      <h2 class="mt">{{ action.type === "reset" ? "パスワード変更" : "管理者を削除" }}：{{ action.user.email }}</h2>
      <p v-if="action.type === 'delete'" class="muted">この管理者はログインできなくなります。元に戻せません。</p>
      <div v-if="action.type === 'reset'" class="field"><label>新しいパスワード <span class="req">必須</span></label>
        <input type="password" v-model="action.password" required minlength="8" maxlength="72" autocomplete="new-password">
        <p class="muted">8文字以上。</p>
        <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p></div>
      <div class="field"><label>あなたの現在のパスワード <span class="req">必須</span></label>
        <input type="password" v-model="action.current_password" required autocomplete="current-password">
        <p v-if="errors.current_password" class="field-error">{{ errors.current_password[0] }}</p></div>
      <div class="form-actions">
        <button type="submit" class="btn" :class="action.type === 'delete' ? 'btn--danger' : 'btn--primary'" :disabled="busy">{{ action.type === "reset" ? "変更する" : "削除する" }}</button>
        <button type="button" class="btn btn--ghost" @click="cancel">キャンセル</button>
      </div>
    </form>
  </section>

  <section class="panel">
    <h2>管理者を追加</h2>
    <form class="form" @submit.prevent="create" autocomplete="off">
      <div class="field"><label>メールアドレス <span class="req">必須</span></label>
        <input type="email" v-model.trim="form.email" required maxlength="190" autocomplete="off">
        <p v-if="!action && errors.email" class="field-error">{{ errors.email[0] }}</p></div>
      <div class="field"><label>表示名</label>
        <input type="text" v-model.trim="form.name" maxlength="120" placeholder="Administrator"></div>
      <div class="field"><label>パスワード <span class="req">必須</span></label>
        <input type="password" v-model="form.password" required minlength="8" maxlength="72" autocomplete="new-password">
        <p class="muted">8文字以上。</p>
        <p v-if="!action && errors.password" class="field-error">{{ errors.password[0] }}</p></div>
      <div class="field"><label>あなたの現在のパスワード <span class="req">必須</span></label>
        <input type="password" v-model="form.current_password" required autocomplete="current-password">
        <p class="muted">本人確認のため、ログイン中のあなたのパスワードを入力してください。</p>
        <p v-if="!action && errors.current_password" class="field-error">{{ errors.current_password[0] }}</p></div>
      <div class="form-actions"><button type="submit" class="btn btn--primary" :disabled="busy">追加する</button></div>
    </form>
  </section>
</div>`,
}).mount("#users");
