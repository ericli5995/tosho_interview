<?php
/** @var string $appName */
$nav = [
    ['label' => '製品情報', 'href' => '/products'],
    ['label' => '製品検索', 'href' => '/products/search'],
    ['label' => '技術情報', 'href' => '/technical'],
    ['label' => '会社情報', 'href' => '/company'],
    ['label' => 'お問い合わせ', 'href' => '/contact'],
];
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Pick the single best-matching nav item: exact path, or the longest href that
// the current path sits under. Prevents /products matching while on /products/search.
$activeHref = '';
foreach ($nav as $item) {
    $href = $item['href'];
    $matches = $current === $href || str_starts_with($current, $href . '/');
    if ($matches && strlen($href) > strlen($activeHref)) {
        $activeHref = $href;
    }
}
?>
<div class="topbar">
    <div class="wrap topbar__inner">
        <span class="topbar__tag">小型ギヤードモータ専門メーカー ｜ 歯車技術 × DCモータ技術</span>
        <span class="topbar__contact">技術・お見積りのお問い合わせ： <strong>03-XXXX-XXXX</strong></span>
    </div>
</div>

<header class="site-header">
    <div class="wrap site-header__inner">
        <a class="brand" href="/">
            <span class="brand__mark">THINK&middot;ENGINEERING</span>
            <span class="brand__sub">シンクエンジニアリング株式会社</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
            <span></span><span></span><span></span>
            <span class="visually-hidden">メニュー</span>
        </button>

        <nav id="site-nav" class="site-nav" aria-label="グローバルナビゲーション">
            <ul>
                <?php foreach ($nav as $item): ?>
                    <li>
                        <a href="<?= e($item['href']) ?>"<?= $item['href'] === $activeHref ? ' aria-current="page"' : '' ?>>
                            <?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn--primary site-nav__cta" href="/contact">お問い合わせ</a>
        </nav>
    </div>
</header>
