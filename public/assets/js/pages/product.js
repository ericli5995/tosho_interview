/* Product detail: /products/{slug} is rewritten to this page; the slug comes
   from the URL and the product from GET /api/products/{slug}. */
Layout.public();

Vue.createApp({
    data: () => ({ product: null, current: null, notFound: false }),
    async mounted() {
        const slug = decodeURIComponent(location.pathname.split("/").pop());
        try {
            this.product = (await api.get("/api/products/" + encodeURIComponent(slug))).product;
            this.current = this.product.image;
            document.title = `${this.product.name} | THINK ENGINEERING`;
        } catch (e) {
            this.notFound = true;
        }
    },
    computed: {
        /* Curated specs first, then core fields not already covered by a curated label. */
        rows() {
            const p = this.product;
            const fmt = (n) => String(+n);
            const derived = [
                p.motor_type_label && ["モータ種類", p.motor_type_label],
                p.voltage_label && ["定格電圧", p.voltage_label],
                p.gear_ratio && ["減速比", p.gear_ratio],
                p.body_diameter != null && ["外径", "ø" + p.body_diameter, "mm"],
                p.rated_torque != null && ["定格トルク", fmt(p.rated_torque), "mN・m"],
                p.rated_speed != null && ["定格回転数", String(p.rated_speed), "r/min"],
                p.life_hours != null && ["想定寿命", Number(p.life_hours).toLocaleString(), "h"],
            ].filter(Boolean).map(([label, value, unit]) => ({ label, value, unit }));
            const taken = new Set(p.specs.map((s) => s.label));
            return [...p.specs, ...derived.filter((d) => !taken.has(d.label))];
        },
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
        <img v-if="current" :src="current.medium_url" :alt="product.name">
        <span v-else class="product-card__media"><span>OUTLINE DRAWING</span></span>
      </div>
      <div v-if="product.images.length > 1" class="detail__thumbs">
        <img v-for="img in product.images" :key="img.id" :src="img.thumb_url" :alt="product.name"
             class="detail-thumb" :class="{ 'is-active': current && img.id === current.id }" @click="current = img">
      </div>
    </div>
    <div class="detail__info">
      <p class="detail__code">{{ product.model_code }}</p>
      <h1 class="detail__name">{{ product.name }}</h1>
      <p v-if="product.category"><span class="tag">{{ product.category.name }}</span></p>
      <template v-if="rows.length">
        <p class="spec-panel__title" style="margin-top:20px">REPRESENTATIVE SPEC</p>
        <table class="spec-table"><tbody>
          <tr v-for="s in rows" :key="s.label"><th>{{ s.label }}</th><td>{{ s.unit ? s.value + ' ' + s.unit : s.value }}</td></tr>
        </tbody></table>
      </template>
      <p v-if="product.description" class="detail__desc">{{ product.description }}</p>
      <p class="detail__cta"><a class="btn btn--primary btn--lg" href="/contact">この製品について問い合わせる</a></p>
    </div>
  </div></div>
</template>`,
}).mount("#product");
