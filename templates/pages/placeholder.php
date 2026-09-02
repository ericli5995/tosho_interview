<?php
/**
 * @var string $title
 * @var string $note
 */
?>
<section class="wrap section">
    <p class="eyebrow">PLACEHOLDER</p>
    <h1 class="section__title"><?= e($title) ?></h1>
    <p class="placeholder-note"><?= e($note ?? '') ?></p>
    <div class="placeholder-card">
        <p>このページはデモの対象外です。</p>
        <p>実装済みの機能は <a href="/products/search"><strong>製品検索</strong></a> と、管理画面からの製品・画像アップロードです。</p>
    </div>
</section>
