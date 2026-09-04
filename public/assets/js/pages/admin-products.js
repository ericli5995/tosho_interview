/* Admin product list: GET /api/admin/products, DELETE /api/admin/products/{id}.
   Mounts immediately (page chrome paints at once); the session check and data
   load happen in mounted(). */
Vue.createApp({
    data: () => ({ ready: false, items: [], total: 0, page: 1, pages: 1, flash: "", flashTimer: null }),
    async mounted() {
        const { user } = await api.requireAdmin();
        Layout.admin(user);
        this.notify(sessionStorage.getItem("flash"));
        sessionStorage.removeItem("flash");
        await this.load(1);
        this.ready = true;
    },
    methods: {
        /* Success notice that clears itself after 4s. */
        notify(message) {
            clearTimeout(this.flashTimer);
            this.flash = message || "";
            if (this.flash) this.flashTimer = setTimeout(() => { this.flash = ""; }, 4000);
        },
        async load(page) {
            const r = await api.get("/api/admin/products?page=" + page);
            Object.assign(this, { items: r.items, total: r.total, page: r.page, pages: r.pages });
        },
        async remove(p) {
            if (!confirm(`「${p.model_code}」を削除します。よろしいですか？`)) return;
            await api.del("/api/admin/products/" + p.id);
            this.notify("製品を削除しました。");
            this.load(this.page);
        },
    },
    template: `
<header class="page-head"><h1>製品一覧 <span v-if="ready" class="page-head__count">({{ total }})</span></h1>
  <a class="btn btn--primary" href="/admin/product-form">製品を登録</a></header>
<div v-if="flash" class="flash"><p class="flash__item flash__item--success">{{ flash }}</p></div>
<template v-if="ready">
  <div v-if="!items.length" class="panel panel--empty"><p>まだ製品が登録されていません。</p></div>
  <div v-else class="table-wrap"><table class="data-table">
    <thead><tr><th>画像</th><th>型番 / 製品名</th><th>ラベル</th><th>在庫</th><th>状態</th><th>更新日時</th><th></th></tr></thead>
    <tbody><tr v-for="p in items" :key="p.id">
      <td class="data-table__thumb"><img v-if="p.image" :src="p.image.thumb_url" alt="" width="56" height="56"><span v-else class="thumb-empty">—</span></td>
      <td><strong>{{ p.model_code }}</strong><br><span class="muted">{{ p.name }}</span></td>
      <td><span v-for="l in p.labels" :key="l" class="badge">{{ l }}</span></td>
      <td :class="{ 'muted': !p.in_stock }">{{ p.stock }}</td>
      <td><span class="badge" :class="{ 'badge--live': p.is_published }">{{ p.is_published ? "公開" : "下書き" }}</span>
          <span v-if="p.is_featured" class="badge badge--star">代表</span></td>
      <td class="muted">{{ p.updated_at }}</td>
      <td class="data-table__actions"><div class="row-actions">
        <a class="btn btn--ghost btn--sm" :href="'/admin/product-form?id=' + p.id">編集</a>
        <button type="button" class="btn btn--danger btn--sm" @click="remove(p)">削除</button>
      </div></td>
    </tr></tbody>
  </table></div>
  <nav v-if="pages > 1" class="pager">
    <template v-for="n in pages" :key="n"><span v-if="n === page" class="is-current">{{ n }}</span><a v-else href="#" @click.prevent="load(n)">{{ n }}</a></template>
  </nav>
</template>`,
}).mount("#products");
