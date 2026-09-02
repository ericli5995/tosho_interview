/* 製品検索 - Vue 3 (global build, no build step).
   Reads filter options from #product-search-data, queries /products/search.json,
   renders the results grid + pager. */
(function () {
    "use strict";

    var mount = document.getElementById("product-search");
    if (!mount || !window.Vue) {
        return;
    }

    var boot = { endpoint: "/products/search.json", diameterOptions: [], voltageOptions: [] };
    try {
        var el = document.getElementById("product-search-data");
        if (el) {
            boot = Object.assign(boot, JSON.parse(el.textContent || "{}"));
        }
    } catch (e) {
        /* keep defaults */
    }

    Vue.createApp({
        data: function () {
            return {
                endpoint: boot.endpoint,
                diameterOptions: boot.diameterOptions,
                voltageOptions: boot.voltageOptions,
                filters: { q: "", motor_type: "", diameters: [], voltages: [], sort: "featured" },
                page: 1,
                items: [],
                total: 0,
                pages: 1,
                loading: false,
                loaded: false,
                error: false,
                debounceTimer: null
            };
        },
        computed: {
            pageWindow: function () {
                var span = 2;
                var start = Math.max(1, this.page - span);
                var end = Math.min(this.pages, this.page + span);
                var out = [];
                for (var i = start; i <= end; i++) {
                    out.push(i);
                }
                return out;
            }
        },
        mounted: function () {
            this.fetchResults();
        },
        methods: {
            reload: function () {
                this.page = 1;
                this.fetchResults();
            },
            debouncedReload: function () {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(this.reload, 300);
            },
            goTo: function (p) {
                if (p < 1 || p > this.pages || p === this.page) {
                    return;
                }
                this.page = p;
                this.fetchResults();
                window.scrollTo({ top: 0, behavior: "smooth" });
            },
            resetFilters: function () {
                this.filters = { q: "", motor_type: "", diameters: [], voltages: [], sort: "featured" };
                this.reload();
            },
            buildQuery: function () {
                var p = new URLSearchParams();
                if (this.filters.q) {
                    p.set("q", this.filters.q);
                }
                if (this.filters.motor_type) {
                    p.set("motor_type", this.filters.motor_type);
                }
                this.filters.diameters.forEach(function (d) { p.append("diameter[]", d); });
                this.filters.voltages.forEach(function (v) { p.append("voltage[]", v); });
                p.set("sort", this.filters.sort);
                p.set("page", this.page);
                return p.toString();
            },
            fetchResults: function () {
                var self = this;
                this.loading = true;
                this.error = false;
                fetch(this.endpoint + "?" + this.buildQuery(), { headers: { Accept: "application/json" } })
                    .then(function (res) {
                        if (!res.ok) {
                            throw new Error("HTTP " + res.status);
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        self.items = data.items || [];
                        self.total = data.total || 0;
                        self.pages = data.pages || 1;
                    })
                    .catch(function () {
                        self.items = [];
                        self.total = 0;
                        self.pages = 1;
                        self.error = true;
                    })
                    .finally(function () {
                        self.loading = false;
                        self.loaded = true;
                    });
            }
        },
        template: [
            '<aside class="filters">',
            '  <h2>絞り込み</h2>',
            '  <div class="filter-group filter-search">',
            '    <p>キーワード</p>',
            '    <input type="search" v-model.trim="filters.q" @input="debouncedReload" placeholder="型番・製品名" />',
            '  </div>',
            '  <div class="filter-group">',
            '    <p>モータ種類</p>',
            '    <label><input type="radio" value="" v-model="filters.motor_type" @change="reload" /> すべて</label>',
            '    <label><input type="radio" value="brushless" v-model="filters.motor_type" @change="reload" /> DCブラシレス</label>',
            '    <label><input type="radio" value="brushed" v-model="filters.motor_type" @change="reload" /> DCブラシ</label>',
            '  </div>',
            '  <div class="filter-group" v-if="diameterOptions.length">',
            '    <p>外径 (mm)</p>',
            '    <label v-for="d in diameterOptions" :key="d">',
            '      <input type="checkbox" :value="d" v-model="filters.diameters" @change="reload" /> &#8709;{{ d }}',
            '    </label>',
            '  </div>',
            '  <div class="filter-group" v-if="voltageOptions.length">',
            '    <p>定格電圧 (V)</p>',
            '    <label v-for="v in voltageOptions" :key="v">',
            '      <input type="checkbox" :value="v" v-model="filters.voltages" @change="reload" /> {{ v }} V',
            '    </label>',
            '  </div>',
            '  <div class="filter-group">',
            '    <p>並び順</p>',
            '    <select v-model="filters.sort" @change="reload">',
            '      <option value="featured">おすすめ順</option>',
            '      <option value="name">型番順</option>',
            '      <option value="diameter">外径が小さい順</option>',
            '    </select>',
            '  </div>',
            '  <button type="button" class="btn btn--ghost btn--sm filters__reset" @click="resetFilters">条件をリセット</button>',
            '</aside>',
            '<div class="results">',
            '  <div class="results__head">',
            '    <p class="results__count"><strong>{{ total }}</strong> 件</p>',
            '    <span v-if="loading">読み込み中...</span>',
            '  </div>',
            '  <p v-if="error" class="state-msg">検索結果を取得できませんでした。時間をおいて再度お試しください。</p>',
            '  <p v-else-if="loaded && !items.length" class="state-msg">条件に一致する製品が見つかりませんでした。</p>',
            '  <div v-else class="product-grid">',
            '    <a v-for="item in items" :key="item.id" class="product-card" :href="item.url">',
            '      <span class="product-card__media">',
            '        <img v-if="item.image" :src="item.image.thumb_url" :alt="item.name" loading="lazy" />',
            '        <span v-else>NO IMAGE</span>',
            '      </span>',
            '      <span class="product-card__body">',
            '        <span class="product-card__code">{{ item.model_code }}</span>',
            '        <span class="product-card__name">{{ item.name }}</span>',
            '        <span class="product-card__chips">',
            '          <span v-if="item.motor_type_label" class="tag">{{ item.motor_type_label }}</span>',
            '          <span v-if="item.body_diameter" class="tag">&#8709;{{ item.body_diameter }}</span>',
            '          <span v-if="item.voltage_label" class="tag">{{ item.voltage_label }}</span>',
            '          <span v-if="item.gear_ratio" class="tag">{{ item.gear_ratio }}</span>',
            '        </span>',
            '      </span>',
            '    </a>',
            '  </div>',
            '  <nav class="pager" v-if="pages > 1" aria-label="ページ送り">',
            '    <button type="button" :disabled="page === 1" @click="goTo(page - 1)">前へ</button>',
            '    <button type="button" v-for="p in pageWindow" :key="p" :class="{ \'is-current\': p === page }" @click="goTo(p)">{{ p }}</button>',
            '    <button type="button" :disabled="page === pages" @click="goTo(page + 1)">次へ</button>',
            '  </nav>',
            '</div>'
        ].join("")
    }).mount("#product-search");
})();
