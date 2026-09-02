<?php
/**
 * @var int $totalProducts
 * @var int $publishedProducts
 */
?>
<header class="page-head">
    <h1>ダッシュボード</h1>
    <a class="btn btn--primary" href="/admin/products/create">製品を登録</a>
</header>

<div class="stat-row">
    <div class="stat">
        <p class="stat__num"><?= (int) $totalProducts ?></p>
        <p class="stat__label">登録製品数</p>
    </div>
    <div class="stat">
        <p class="stat__num"><?= (int) $publishedProducts ?></p>
        <p class="stat__label">公開中</p>
    </div>
    <div class="stat">
        <p class="stat__num"><?= (int) $totalProducts - (int) $publishedProducts ?></p>
        <p class="stat__label">下書き</p>
    </div>
</div>

<div class="panel">
    <h2>クイックリンク</h2>
    <ul class="link-list">
        <li><a href="/admin/products">製品一覧を開く</a></li>
        <li><a href="/admin/products/create">新しい製品を登録する</a></li>
        <li><a href="/products/search" target="_blank" rel="noopener">公開サイトの製品検索を確認する</a></li>
    </ul>
</div>
