<?php
/**
 * @var list<int> $diameterOptions
 * @var list<int> $voltageOptions
 */
$bootData = [
    'endpoint' => '/products/search.json',
    'diameterOptions' => $diameterOptions,
    'voltageOptions' => $voltageOptions,
];
?>
<section class="wrap">
    <p class="breadcrumb"><a href="/">トップ</a> &rsaquo; 製品検索</p>
    <h1 class="section__title">製品検索</h1>
    <p class="placeholder-note">外径・モータ種類・定格電圧・キーワードで絞り込めます。</p>
</section>

<div class="wrap">
    <div id="product-search" class="search-layout">
        <noscript>
            <p class="state-msg">この検索機能のご利用には JavaScript を有効にしてください。</p>
        </noscript>
    </div>
</div>

<script type="application/json" id="product-search-data"><?= json_encode($bootData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= asset('js/vendor/vue.global.prod.js') ?>"></script>
<script src="<?= asset('js/product-search.js') ?>"></script>
