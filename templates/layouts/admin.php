<?php
/**
 * @var \App\Core\View $view
 * @var string $content
 * @var string $appName
 * @var array<int,array{type:string,message:string}> $flash
 * @var string|null $title
 */
$authed = \App\Security\Auth::check();
?><!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($title) && $title !== '' ? e($title) . ' | ' : '' ?>管理画面</title>
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin<?= $authed ? '' : ' admin--auth' ?>">
<?php if ($authed): ?>
    <header class="admin-bar">
        <a class="admin-bar__brand" href="/admin">THINK&middot;ENGINEERING <span>管理画面</span></a>
        <nav class="admin-bar__nav">
            <a href="/admin">ダッシュボード</a>
            <a href="/admin/products">製品一覧</a>
            <a href="/" target="_blank" rel="noopener">サイトを表示</a>
        </nav>
        <form method="post" action="/admin/logout" class="admin-bar__logout">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--ghost btn--sm">ログアウト</button>
        </form>
    </header>
<?php endif; ?>

<main class="admin-main">
    <div class="admin-wrap">
        <?= $view->renderPartial('partials/flash', ['flash' => $flash ?? []]) ?>
        <?= $content ?>
    </div>
</main>
</body>
</html>
