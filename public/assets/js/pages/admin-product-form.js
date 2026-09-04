/* Admin create/edit form. ?id= selects edit mode (GET /api/admin/products/{id}).
   Submits multipart FormData to POST /api/admin/products[/{id}]: text fields,
   labels (comma-separated), an optional new `image`, and remove_image=1. */
const id = new URLSearchParams(location.search).get("id");
const FIELDS = ["model_code", "name", "slug", "description", "stock", "labels", "sort_order"];

Vue.createApp({
    data: () => ({
        id,
        form: { model_code: "", name: "", slug: "", description: "", stock: "0", labels: "", sort_order: "0" },
        is_published: false, is_featured: false,
        current: null,          // saved image urls (edit mode)
        file: null, preview: "", // newly chosen image
        removeImage: false,
        errors: {}, error: "", flash: "", flashTimer: null, busy: false,
    }),
    created() { this.notify(sessionStorage.getItem("flash")); sessionStorage.removeItem("flash"); },
    async mounted() {
        const { user } = await api.requireAdmin();
        Layout.admin(user);
        if (this.id) this.apply((await api.get("/api/admin/products/" + this.id)).product);
    },
    methods: {
        /* Success notice that clears itself after 4s. */
        notify(message) {
            clearTimeout(this.flashTimer);
            this.flash = message || "";
            if (this.flash) this.flashTimer = setTimeout(() => { this.flash = ""; }, 4000);
        },
        apply(p) {
            FIELDS.forEach((f) => { this.form[f] = f === "labels" ? p.labels.join(", ") : String(p[f] ?? ""); });
            this.is_published = p.is_published; this.is_featured = p.is_featured;
            this.current = p.image;
            this.clearFile(); this.removeImage = false;
        },
        pick(e) { this.setFile(e.target.files[0]); e.target.value = ""; },
        drop(e) { this.setFile(e.dataTransfer.files[0]); },
        setFile(f) {
            if (!f || !f.type.startsWith("image/")) return;
            this.clearFile();
            this.file = f; this.preview = URL.createObjectURL(f); this.removeImage = false;
        },
        clearFile() { if (this.preview) URL.revokeObjectURL(this.preview); this.file = null; this.preview = ""; },
        async submit() {
            this.busy = true; this.errors = {}; this.error = ""; this.flash = "";
            const fd = new FormData();
            FIELDS.forEach((f) => fd.append(f, this.form[f]));
            fd.append("is_published", this.is_published ? "1" : "0");
            fd.append("is_featured", this.is_featured ? "1" : "0");
            if (this.file) fd.append("image", this.file, this.file.name);
            if (this.removeImage) fd.append("remove_image", "1");
            try {
                const { product } = await api.post(this.id ? "/api/admin/products/" + this.id : "/api/admin/products", fd);
                if (!this.id) {
                    sessionStorage.setItem("flash", `製品「${product.model_code}」を登録しました。`);
                    location.replace("/admin/product-form?id=" + product.id);
                    return;
                }
                this.apply(product);
                this.notify("製品を更新しました。");
            } catch (e) {
                this.error = e.message; this.errors = e.errors;
            } finally {
                this.busy = false;
                scrollTo({ top: 0 });
            }
        },
    },
    template: `
<header class="page-head"><h1>{{ id ? "製品を編集" : "製品を登録" }}</h1><a class="btn btn--ghost" href="/admin/products">一覧へ戻る</a></header>
<div v-if="flash" class="flash"><p class="flash__item flash__item--success">{{ flash }}</p></div>
<div v-if="error" class="flash"><p class="flash__item flash__item--error">{{ error }}</p></div>

<form class="form product-form" @submit.prevent="submit">
<div class="form-grid">
  <section class="panel">
<h2>基本情報</h2>
<div class="field"><label>型番 <span class="req">必須</span></label><input v-model.trim="form.model_code" required maxlength="60" placeholder="TE-22BK"><p v-if="errors.model_code" class="field-error">{{ errors.model_code[0] }}</p></div>
<div class="field"><label>製品名 <span class="req">必須</span></label><input v-model.trim="form.name" required maxlength="200"><p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p></div>
<div class="field"><label>スラッグ (URL)</label><input v-model.trim="form.slug" maxlength="220" placeholder="空欄なら型番から自動生成"><p v-if="errors.slug" class="field-error">{{ errors.slug[0] }}</p></div>
<div class="field"><label>ラベル</label><input v-model.trim="form.labels" maxlength="400" placeholder="ブラシレス, φ22, 24V （カンマ区切り）"><p class="muted">検索・絞り込みに使われます。</p><p v-if="errors.labels" class="field-error">{{ errors.labels[0] }}</p></div>
<div class="field-row">
  <div class="field"><label>在庫数</label><input type="number" min="0" v-model="form.stock"><p v-if="errors.stock" class="field-error">{{ errors.stock[0] }}</p></div>
  <div class="field"><label>表示順</label><input type="number" v-model="form.sort_order"></div>
</div>
<div class="field"><label>説明</label><textarea rows="6" maxlength="5000" v-model.trim="form.description"></textarea></div>
<div class="field-inline">
  <label><input type="checkbox" v-model="is_published"> 公開する</label>
  <label><input type="checkbox" v-model="is_featured"> 代表製品（トップページに表示）</label>
</div>
  </section>

  <section class="panel">
<h2>製品画像</h2>
<p class="muted">JPEG / PNG / WebP、1枚。保存時にアップロードされます。</p>
<div class="uploader__dropzone" @dragover.prevent @drop.prevent="drop">
  <input type="file" accept="image/jpeg,image/png,image/webp" @change="pick">
  <p>画像を選択、またはここにドラッグ＆ドロップ</p>
</div>
<p v-if="errors.image" class="field-error">{{ errors.image[0] }}</p>
<div v-if="preview" class="image-current">
  <img :src="preview" alt=""><div><p class="muted">新しい画像（保存で反映）</p><button type="button" class="btn btn--ghost btn--sm" @click="clearFile">取り消す</button></div>
</div>
<div v-else-if="current && !removeImage" class="image-current">
  <img :src="current.thumb_url" alt=""><div><p class="muted">現在の画像</p><button type="button" class="btn btn--danger btn--sm" @click="removeImage = true">削除する</button></div>
</div>
<p v-else-if="removeImage" class="muted">保存すると現在の画像が削除されます。<button type="button" class="btn btn--ghost btn--sm" @click="removeImage = false">やめる</button></p>
  </section>
</div>
<div class="form-actions"><button type="submit" class="btn btn--primary btn--lg" :disabled="busy">{{ id ? "変更を保存" : "登録する" }}</button><a class="btn btn--ghost" href="/admin/products">キャンセル</a></div>
</form>`,
}).mount("#product-form");
