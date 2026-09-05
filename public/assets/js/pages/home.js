/* Top page: featured-product panel + 製品一覧 grid, both from the public API. */
Layout.public();

/* Hero panel: GET /api/products/featured */
Vue.createApp({
    data: () => ({ product: null, loaded: false }),
    async mounted() {
        const { product } = await api.get("/api/products/featured").catch(() => ({ product: null }));
        this.product = product;
        this.loaded = true;
        if (product) {
            const link = document.getElementById("featured-link");
            link.href = product.url;
            link.hidden = false;
        }
    },
    template: `
<div v-if="product" class="spec-panel">
  <div class="spec-panel__head"><span>{{ product.model_code }} SERIES / OUTLINE DRAWING</span><span>SCALE 1:1.4 &nbsp; UNIT : mm</span></div>
  <div class="spec-panel__figure">
    <img v-if="product.image" :src="product.image" :alt="product.name">
    <div v-else class="spec-panel__placeholder">NO IMAGE</div>
  </div>
  <div class="spec-panel__body">
    <p class="spec-panel__title">{{ product.name }}</p>
    <p class="labels"><span v-for="l in product.labels" :key="l" class="tag">{{ l }}</span>
      <span class="stock" :class="product.in_stock ? 'stock--in' : 'stock--out'">{{ product.in_stock ? '在庫あり（' + product.stock + '）' : '在庫なし' }}</span></p>
    <p class="spec-panel__desc">{{ product.description }}</p>
  </div>
</div>
<div v-else-if="loaded" class="spec-panel spec-panel--empty">
  <p>代表製品が未登録です。</p><p><a href="/admin/login">管理画面</a>から製品を登録してください。</p>
</div>`,
}).mount("#featured");

/* 製品一覧: GET /api/products (published, first page) */
Vue.createApp({
    data: () => ({ items: [], total: 0, loaded: false }),
    async mounted() {
        const r = await api.get("/api/products?sort=code&per_page=12").catch(() => ({ items: [], total: 0 }));
        this.items = r.items;
        this.total = r.total;
        this.loaded = true;
    },
    template: `
<p v-if="loaded && !items.length" class="state-msg">公開中の製品はまだありません。</p>
<template v-else>
  <div class="product-grid">
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
  <p v-if="total > items.length" class="section__more"><a class="btn btn--primary" href="/products/search">すべての製品を見る（{{ total }}件）</a></p>
</template>`,
}).mount("#product-list");
