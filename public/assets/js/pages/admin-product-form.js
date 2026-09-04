/* Admin create/edit form. ?id= selects edit mode (GET /api/admin/products/{id}).
   Submits multipart FormData to POST /api/admin/products[/{id}]: fields,
   specs[i][label|value|unit], images[] (new files, in the order shown) and
   primary_image_index. Existing images are managed via the image endpoints. */
(async () => {
    const { user } = await api.requireAdmin();
    Layout.admin(user);

    const id = new URLSearchParams(location.search).get("id");
    const FIELDS = ["model_code", "name", "slug", "category_id", "motor_type", "rated_voltage", "gear_ratio",
        "body_diameter", "rated_torque", "rated_speed", "noise_level", "life_hours", "description", "sort_order"];
    const blankSpec = () => ({ label: "", value: "", unit: "" });

    Vue.createApp({
        data: () => ({
            id, categories: [],
            form: Object.fromEntries(FIELDS.map((f) => [f, ""])),
            is_published: false, is_featured: false,
            specs: [blankSpec(), blankSpec(), blankSpec()],
            images: [],            // saved images (edit mode)
            files: [],             // new files: { file, url, name }
            primary: 0,            // index into files
            errors: {}, error: "", flash: "", busy: false,
        }),
        async mounted() {
            this.categories = (await api.get("/api/categories")).categories;
            if (this.id) {
                const { product } = await api.get("/api/admin/products/" + this.id);
                this.apply(product);
            }
        },
        methods: {
            apply(p) {
                FIELDS.forEach((f) => { this.form[f] = p[f] == null ? "" : String(p[f]); });
                this.is_published = p.is_published; this.is_featured = p.is_featured;
                this.specs = p.specs.length ? p.specs.map((s) => ({ ...s, unit: s.unit || "" })) : [blankSpec(), blankSpec(), blankSpec()];
                this.images = p.images;
                this.files.forEach((f) => URL.revokeObjectURL(f.url));
                this.files = []; this.primary = 0;
            },
            addSpec() { this.specs.push(blankSpec()); },
            pick(e) { this.add(e.target.files); e.target.value = ""; },
            drop(e) { this.add(e.dataTransfer.files); },
            add(list) {
                for (const file of list) if (file.type.startsWith("image/")) this.files.push({ file, url: URL.createObjectURL(file), name: file.name });
            },
            removeFile(i) { URL.revokeObjectURL(this.files[i].url); this.files.splice(i, 1); if (this.primary >= this.files.length) this.primary = 0; },
            move(i, d) {
                const j = i + d;
                if (j < 0 || j >= this.files.length) return;
                [this.files[i], this.files[j]] = [this.files[j], this.files[i]];
                if (this.primary === i) this.primary = j; else if (this.primary === j) this.primary = i;
            },
            async deleteImage(img) {
                if (!confirm("この画像を削除しますか？")) return;
                await api.del(`/api/admin/products/${this.id}/images/${img.id}`);
                this.images = (await api.get("/api/admin/products/" + this.id)).product.images;
            },
            async setPrimary(img) {
                await api.put(`/api/admin/products/${this.id}/images/${img.id}/primary`);
                this.images = this.images.map((i) => ({ ...i, is_primary: i.id === img.id }));
            },
            async submit() {
                this.busy = true; this.errors = {}; this.error = ""; this.flash = "";
                const fd = new FormData();
                FIELDS.forEach((f) => fd.append(f, this.form[f]));
                fd.append("is_published", this.is_published ? "1" : "0");
                fd.append("is_featured", this.is_featured ? "1" : "0");
                this.specs.forEach((s, i) => ["label", "value", "unit"].forEach((k) => fd.append(`specs[${i}][${k}]`, s[k])));
                this.files.forEach((f) => fd.append("images[]", f.file, f.name));
                if (this.files.length) fd.append("primary_image_index", String(this.primary));
                try {
                    const { product } = await api.post(this.id ? "/api/admin/products/" + this.id : "/api/admin/products", fd);
                    if (!this.id) {
                        sessionStorage.setItem("flash", `製品「${product.model_code}」を登録しました。`);
                        location.replace("/admin/product-form.html?id=" + product.id);
                        return;
                    }
                    this.apply(product);
                    this.flash = "製品を更新しました。";
                    scrollTo({ top: 0 });
                } catch (e) {
                    this.error = e.message; this.errors = e.errors;
                    scrollTo({ top: 0 });
                } finally {
                    this.busy = false;
                }
            },
        },
        created() { this.flash = sessionStorage.getItem("flash") || ""; sessionStorage.removeItem("flash"); },
        template: `
<header class="page-head"><h1>{{ id ? "製品を編集" : "製品を登録" }}</h1><a class="btn btn--ghost" href="/admin/products.html">一覧へ戻る</a></header>
<div v-if="flash" class="flash"><p class="flash__item flash__item--success">{{ flash }}</p></div>
<div v-if="error" class="flash"><p class="flash__item flash__item--error">{{ error }}</p></div>

<form class="form product-form" @submit.prevent="submit">
<div class="form-grid">
  <section class="panel">
    <h2>基本情報</h2>
    <div class="field"><label>型番 <span class="req">必須</span></label><input v-model.trim="form.model_code" required maxlength="60" placeholder="TE-22BK"><p v-if="errors.model_code" class="field-error">{{ errors.model_code[0] }}</p></div>
    <div class="field"><label>製品名 <span class="req">必須</span></label><input v-model.trim="form.name" required maxlength="200"><p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p></div>
    <div class="field"><label>スラッグ (URL)</label><input v-model.trim="form.slug" maxlength="220" placeholder="空欄なら型番から自動生成"><p v-if="errors.slug" class="field-error">{{ errors.slug[0] }}</p></div>
    <div class="field-row">
      <div class="field"><label>カテゴリ</label><select v-model="form.category_id"><option value="">未設定</option><option v-for="c in categories" :key="c.id" :value="String(c.id)">{{ c.name }}</option></select><p v-if="errors.category_id" class="field-error">{{ errors.category_id[0] }}</p></div>
      <div class="field"><label>モータ種類</label><select v-model="form.motor_type"><option value="">未設定</option><option value="brushless">DCブラシレス</option><option value="brushed">DCブラシ</option></select></div>
    </div>
    <div class="field-row">
      <div class="field"><label>定格電圧 (V)</label><input type="number" step="0.01" v-model="form.rated_voltage"><p v-if="errors.rated_voltage" class="field-error">{{ errors.rated_voltage[0] }}</p></div>
      <div class="field"><label>減速比</label><input v-model.trim="form.gear_ratio" maxlength="30" placeholder="1/120"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>外径 (mm)</label><input type="number" v-model="form.body_diameter"><p v-if="errors.body_diameter" class="field-error">{{ errors.body_diameter[0] }}</p></div>
      <div class="field"><label>定格トルク (mN・m)</label><input type="number" step="0.01" v-model="form.rated_torque"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>定格回転数 (r/min)</label><input type="number" v-model="form.rated_speed"></div>
      <div class="field"><label>騒音 (dB)</label><input type="number" step="0.1" v-model="form.noise_level"></div>
    </div>
    <div class="field-row">
      <div class="field"><label>想定寿命 (h)</label><input type="number" v-model="form.life_hours"></div>
      <div class="field"><label>表示順</label><input type="number" v-model="form.sort_order"></div>
    </div>
    <div class="field"><label>説明</label><textarea rows="5" maxlength="5000" v-model.trim="form.description"></textarea></div>
    <div class="field-inline">
      <label><input type="checkbox" v-model="is_published"> 公開する</label>
      <label><input type="checkbox" v-model="is_featured"> 代表製品（トップページに表示）</label>
    </div>
  </section>

  <section class="panel">
    <h2>代表スペック表</h2>
    <p class="muted">トップページ・詳細ページの「REPRESENTATIVE SPEC」に表示されます。空行は無視されます。</p>
    <div class="spec-rows">
      <div v-for="(s, i) in specs" :key="i" class="spec-rows__row">
        <input v-model.trim="s.label" placeholder="項目 (例: 定格電圧)"><input v-model.trim="s.value" placeholder="値 (例: 24)"><input v-model.trim="s.unit" placeholder="単位 (例: V)">
      </div>
    </div>
    <button type="button" class="btn btn--ghost btn--sm" @click="addSpec">行を追加</button>

    <h2 class="mt">製品画像</h2>
    <p class="muted">JPEG / PNG / WebP。保存時にまとめてアップロードされます。</p>
    <div class="uploader__dropzone" @dragover.prevent @drop.prevent="drop">
      <input type="file" multiple accept="image/jpeg,image/png,image/webp" @change="pick">
      <p>画像を選択（複数可）。ここにドラッグ＆ドロップもできます。</p>
    </div>
    <p v-if="errors.images" class="field-error">{{ errors.images[0] }}</p>
    <ul v-if="files.length" class="uploader__list">
      <li v-for="(f, i) in files" :key="f.url" class="uploader__item" :class="{ 'is-primary': i === primary }">
        <img :src="f.url" :alt="f.name">
        <div class="uploader__meta"><span class="uploader__name">{{ f.name }}</span><span class="uploader__size">{{ (f.file.size / 1024).toFixed(0) }} KB</span></div>
        <div class="uploader__controls">
          <label class="uploader__radio"><input type="radio" :checked="i === primary" @change="primary = i"> 主画像</label>
          <button type="button" @click="move(i, -1)" :disabled="i === 0">&uarr;</button>
          <button type="button" @click="move(i, 1)" :disabled="i === files.length - 1">&darr;</button>
          <button type="button" class="uploader__remove" @click="removeFile(i)">削除</button>
        </div>
      </li>
    </ul>
  </section>
</div>
<div class="form-actions"><button type="submit" class="btn btn--primary btn--lg" :disabled="busy">{{ id ? "変更を保存" : "登録する" }}</button><a class="btn btn--ghost" href="/admin/products.html">キャンセル</a></div>
</form>

<section v-if="id && images.length" class="panel">
  <h2>登録済みの画像</h2>
  <div class="image-manage">
    <figure v-for="img in images" :key="img.id" class="image-manage__item">
      <img :src="img.thumb_url" alt="">
      <figcaption>
        <span v-if="img.is_primary" class="badge badge--star">主画像</span>
        <button v-else type="button" class="btn btn--ghost btn--sm" @click="setPrimary(img)">主画像にする</button>
        <button type="button" class="btn btn--danger btn--sm" @click="deleteImage(img)">削除</button>
      </figcaption>
    </figure>
  </div>
</section>`,
    }).mount("#product-form");
})();
