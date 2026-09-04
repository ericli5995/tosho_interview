/* Admin product list: GET /api/admin/products, DELETE /api/admin/products/{id}. */
(async () => {
    const { user } = await api.requireAdmin();
    Layout.admin(user);

    Vue.createApp({
        data: () => ({ items: [], total: 0, page: 1, pages: 1, flash: sessionStorage.getItem("flash") || "" }),
        mounted() {
            sessionStorage.removeItem("flash");
            this.load(1);
        },
        methods: {
            async load(page) {
                const r = await api.get("/api/admin/products?page=" + page);
                Object.assign(this, { items: r.items, total: r.total, page: r.page, pages: r.pages });
            },
            async remove(p) {
                if (!confirm(`「${p.model_code}」を削除します。よろしいですか？`)) return;
                await api.del("/api/admin/products/" + p.id);
                this.flash = "製品を削除しました。";
                this.load(this.page);
            },
        },
        template: `
<header class="page-head"><h1>製品一覧 <span class="page-head__count">({{ total }})</span></h1>
  <a class="btn btn--primary" href="/admin/product-form.html">製品を登録</a></header>
<div v-if="flash" class="flash"><p class="flash__item flash__item--success">{{ flash }}</p></div>
<div v-if="!items.length" class="panel panel--empty"><p>まだ製品が登録されていません。</p></div>
<div v-else class="table-wrap"><table class="data-table">
  <thead><tr><th>画像</th><th>型番 / 製品名</th><th>外径</th><th>状態</th><th>更新日時</th><th></th></tr></thead>
  <tbody><tr v-for="p in items" :key="p.id">
    <td class="data-table__thumb"><img v-if="p.image" :src="p.image.thumb_url" alt="" width="56" height="56"><span v-else class="thumb-empty">—</span></td>
    <td><strong>{{ p.model_code }}</strong><br><span class="muted">{{ p.name }}</span></td>
    <td>{{ p.body_diameter != null ? "ø" + p.body_diameter : "—" }}</td>
    <td><span class="badge" :class="{ 'badge--live': p.is_published }">{{ p.is_published ? "公開" : "下書き" }}</span>
        <span v-if="p.is_featured" class="badge badge--star">代表</span></td>
    <td class="muted">{{ p.updated_at }}</td>
    <td class="data-table__actions"><div class="row-actions">
      <a class="btn btn--ghost btn--sm" :href="'/admin/product-form.html?id=' + p.id">編集</a>
      <button type="button" class="btn btn--danger btn--sm" @click="remove(p)">削除</button>
    </div></td>
  </tr></tbody>
</table></div>
<nav v-if="pages > 1" class="pager">
  <template v-for="n in pages" :key="n"><span v-if="n === page" class="is-current">{{ n }}</span><a v-else href="#" @click.prevent="load(n)">{{ n }}</a></template>
</nav>`,
    }).mount("#products");
})();
