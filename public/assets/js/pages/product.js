/* Product detail: /products/{slug} is rewritten to this page; the slug comes
   from the URL and the product from GET /api/products/{slug}. */
Layout.public();

Vue.createApp({
    data: () => ({ product: null, notFound: false }),
    async mounted() {
        const slug = decodeURIComponent(location.pathname.split("/").pop());
        try {
            this.product = (await api.get("/api/products/" + encodeURIComponent(slug))).product;
            document.title = `${this.product.name} | THINK ENGINEERING`;
        } catch (e) {
            this.notFound = true;
        }
    },
    template: `
<section v-if="notFound" class="wrap section">
  <p class="eyebrow">404</p><h1 class="section__title">製品が見つかりません</h1>
  <p><a class="btn btn--primary" href="/products/search">製品検索へ</a></p>
</section>
<template v-else-if="product">
  <section class="wrap"><p class="breadcrumb"><a href="/">トップ</a> &rsaquo; <a href="/products/search">製品検索</a> &rsaquo; {{ product.model_code }}</p></section>
  <div class="wrap"><div class="detail">
    <div class="detail__gallery">
      <div class="detail__main-img">
        <img v-if="product.image" :src="product.image" :alt="product.name">
        <span v-else class="product-card__media"><span>NO IMAGE</span></span>
      </div>
    </div>
    <div class="detail__info">
      <p class="detail__code">{{ product.model_code }}</p>
      <h1 class="detail__name">{{ product.name }}</h1>
      <p class="labels"><span v-for="l in product.labels" :key="l" class="tag">{{ l }}</span></p>
      <p class="stock detail__stock" :class="product.in_stock ? 'stock--in' : 'stock--out'">
        {{ product.in_stock ? '在庫あり（' + product.stock + '）' : '在庫なし・納期はお問い合わせください' }}</p>
      <p v-if="product.description" class="detail__desc">{{ product.description }}</p>
      <p class="detail__cta"><a class="btn btn--primary btn--lg" href="/contact">この製品について問い合わせる</a></p>
    </div>
  </div></div>
</template>`,
}).mount("#product");
