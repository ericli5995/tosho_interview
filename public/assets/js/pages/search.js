/* 製品検索: keyword + sort -> GET /api/products -> grid + pager.
   The keyword matches model code / name, or a label exactly (server-side). */
Layout.public();

Vue.createApp({
    data: () => ({
        filters: { q: "", sort: "featured" },
        page: 1, items: [], total: 0, pages: 1,
        loading: false, loaded: false, error: false, timer: null,
    }),
    mounted() { this.load(); },
    computed: {
        window() {
            const from = Math.max(1, this.page - 2), to = Math.min(this.pages, this.page + 2);
            return Array.from({ length: to - from + 1 }, (_, i) => from + i);
        },
    },
    methods: {
        reload() { this.page = 1; this.load(); },
        debounced() { clearTimeout(this.timer); this.timer = setTimeout(this.reload, 300); },
        goTo(p) { if (p >= 1 && p <= this.pages && p !== this.page) { this.page = p; this.load(); scrollTo({ top: 0, behavior: "smooth" }); } },
        reset() { this.filters = { q: "", sort: "featured" }; this.reload(); },
        async load() {
            this.loading = true; this.error = false;
            const q = new URLSearchParams({ sort: this.filters.sort, page: this.page });
            if (this.filters.q) q.set("q", this.filters.q);
            try {
                const r = await api.get("/api/products?" + q);
                this.items = r.items; this.total = r.total; this.pages = r.pages;
            } catch (e) {
                this.items = []; this.total = 0; this.pages = 1; this.error = true;
            } finally {
                this.loading = false; this.loaded = true;
            }
        },
    },
    template: `
<aside class="filters">
  <h2>絞り込み</h2>
  <div class="filter-group filter-search"><p>キーワード</p>
    <input type="search" v-model.trim="filters.q" @input="debounced" placeholder="型番・製品名・ラベル"></div>
  <div class="filter-group"><p>並び順</p>
    <select v-model="filters.sort" @change="reload">
      <option value="featured">おすすめ順</option><option value="code">型番順</option><option value="stock">在庫が多い順</option>
    </select></div>
  <button type="button" class="btn btn--ghost btn--sm filters__reset" @click="reset">条件をリセット</button>
</aside>
<div class="results">
  <div class="results__head"><p class="results__count"><strong>{{ total }}</strong> 件</p><span v-if="loading">読み込み中...</span></div>
  <p v-if="error" class="state-msg">検索結果を取得できませんでした。時間をおいて再度お試しください。</p>
  <p v-else-if="loaded && !items.length" class="state-msg">条件に一致する製品が見つかりませんでした。</p>
  <div v-else class="product-grid">
    <a v-for="item in items" :key="item.id" class="product-card" :href="item.url">
      <span class="product-card__media"><img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy"><span v-else>NO IMAGE</span></span>
      <span class="product-card__body">
        <span class="product-card__code">{{ item.model_code }}</span>
        <span class="product-card__name">{{ item.name }}</span>
        <span class="product-card__chips"><span v-for="l in item.labels" :key="l" class="tag">{{ l }}</span></span>
        <span class="stock" :class="item.in_stock ? 'stock--in' : 'stock--out'">{{ item.in_stock ? '在庫あり（' + item.stock + '）' : '在庫なし' }}</span>
      </span>
    </a>
  </div>
  <nav class="pager" v-if="pages > 1" aria-label="ページ送り">
    <button type="button" :disabled="page === 1" @click="goTo(page - 1)">前へ</button>
    <button type="button" v-for="p in window" :key="p" :class="{ 'is-current': p === page }" @click="goTo(p)">{{ p }}</button>
    <button type="button" :disabled="page === pages" @click="goTo(page + 1)">次へ</button>
  </nav>
</div>`,
}).mount("#product-search");
