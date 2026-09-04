/* 製品検索: filters -> GET /api/products -> grid + pager. Filter options come
   from GET /api/products/options. Filtering/paging happen server-side. */
Layout.public();

Vue.createApp({
    data: () => ({
        diameters: [], voltages: [],
        filters: { q: "", motor_type: "", diameters: [], voltages: [], sort: "featured" },
        page: 1, items: [], total: 0, pages: 1,
        loading: false, loaded: false, error: false, timer: null,
    }),
    async mounted() {
        const o = await api.get("/api/products/options").catch(() => ({ diameters: [], voltages: [] }));
        this.diameters = o.diameters;
        this.voltages = o.voltages;
        this.load();
    },
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
        reset() { this.filters = { q: "", motor_type: "", diameters: [], voltages: [], sort: "featured" }; this.reload(); },
        query() {
            const q = new URLSearchParams();
            if (this.filters.q) q.set("q", this.filters.q);
            if (this.filters.motor_type) q.set("motor_type", this.filters.motor_type);
            this.filters.diameters.forEach((d) => q.append("diameter[]", d));
            this.filters.voltages.forEach((v) => q.append("voltage[]", v));
            q.set("sort", this.filters.sort);
            q.set("page", this.page);
            return q.toString();
        },
        async load() {
            this.loading = true; this.error = false;
            try {
                const r = await api.get("/api/products?" + this.query());
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
    <input type="search" v-model.trim="filters.q" @input="debounced" placeholder="型番・製品名"></div>
  <div class="filter-group"><p>モータ種類</p>
    <label><input type="radio" value="" v-model="filters.motor_type" @change="reload"> すべて</label>
    <label><input type="radio" value="brushless" v-model="filters.motor_type" @change="reload"> DCブラシレス</label>
    <label><input type="radio" value="brushed" v-model="filters.motor_type" @change="reload"> DCブラシ</label></div>
  <div class="filter-group" v-if="diameters.length"><p>外径 (mm)</p>
    <label v-for="d in diameters" :key="d"><input type="checkbox" :value="d" v-model="filters.diameters" @change="reload"> &#8709;{{ d }}</label></div>
  <div class="filter-group" v-if="voltages.length"><p>定格電圧 (V)</p>
    <label v-for="v in voltages" :key="v"><input type="checkbox" :value="v" v-model="filters.voltages" @change="reload"> {{ v }} V</label></div>
  <div class="filter-group"><p>並び順</p>
    <select v-model="filters.sort" @change="reload">
      <option value="featured">おすすめ順</option><option value="code">型番順</option><option value="diameter">外径が小さい順</option>
    </select></div>
  <button type="button" class="btn btn--ghost btn--sm filters__reset" @click="reset">条件をリセット</button>
</aside>
<div class="results">
  <div class="results__head"><p class="results__count"><strong>{{ total }}</strong> 件</p><span v-if="loading">読み込み中...</span></div>
  <p v-if="error" class="state-msg">検索結果を取得できませんでした。時間をおいて再度お試しください。</p>
  <p v-else-if="loaded && !items.length" class="state-msg">条件に一致する製品が見つかりませんでした。</p>
  <div v-else class="product-grid">
    <a v-for="item in items" :key="item.id" class="product-card" :href="item.url">
      <span class="product-card__media"><img v-if="item.image" :src="item.image.thumb_url" :alt="item.name" loading="lazy"><span v-else>NO IMAGE</span></span>
      <span class="product-card__body">
        <span class="product-card__code">{{ item.model_code }}</span>
        <span class="product-card__name">{{ item.name }}</span>
        <span class="product-card__chips">
          <span v-if="item.motor_type_label" class="tag">{{ item.motor_type_label }}</span>
          <span v-if="item.body_diameter" class="tag">&#8709;{{ item.body_diameter }}</span>
          <span v-if="item.voltage_label" class="tag">{{ item.voltage_label }}</span>
          <span v-if="item.gear_ratio" class="tag">{{ item.gear_ratio }}</span>
        </span>
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
