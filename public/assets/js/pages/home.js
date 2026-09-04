/* Top page: the featured-product spec panel (GET /api/products/featured). */
Layout.public();

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
    computed: {
        /* Curated spec rows first; fall back to a few derived from the core fields. */
        rows() {
            const p = this.product;
            if (!p) return [];
            if (p.specs.length) return p.specs.slice(0, 5);
            return [
                p.motor_type_label && { label: "モータ種類", value: p.motor_type_label },
                p.voltage_label && { label: "定格電圧", value: p.voltage_label },
                p.gear_ratio && { label: "減速比", value: p.gear_ratio },
            ].filter(Boolean);
        },
    },
    template: `
<div v-if="product" class="spec-panel">
  <div class="spec-panel__head"><span>{{ product.model_code }} SERIES / OUTLINE DRAWING</span><span>UNIT : mm</span></div>
  <div class="spec-panel__figure">
    <img v-if="product.image" :src="product.image.medium_url" :alt="product.name">
    <div v-else class="spec-panel__placeholder">OUTLINE DRAWING</div>
  </div>
  <div class="spec-panel__body">
    <p class="spec-panel__title">REPRESENTATIVE SPEC</p>
    <table class="spec-table"><tbody>
      <tr v-for="s in rows" :key="s.label"><th>{{ s.label }}</th><td>{{ s.unit ? s.value + ' ' + s.unit : s.value }}</td></tr>
    </tbody></table>
  </div>
</div>
<div v-else-if="loaded" class="spec-panel spec-panel--empty">
  <p>代表製品が未登録です。</p><p><a href="/admin/login.html">管理画面</a>から製品を登録してください。</p>
</div>`,
}).mount("#featured");
