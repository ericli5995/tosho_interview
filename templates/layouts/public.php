<?php
/**
 * @var \App\Core\View $view
 * @var string $content
 * @var string $appName
 * @var array<int,array{type:string,message:string}> $flash
 * @var string|null $title
 */
?><!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($title) && $title !== '' ? e($title) . ' | ' : '' ?><?= e($appName ?? 'THINK ENGINEERING') ?></title>
    <meta name="description" content="小型ギヤードモータ専門メーカー。歯車技術とDCモータ技術を組み合わせ、用途・仕様に合わせた最適な製品選定をサポートします。">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<a class="skip-link" href="#main">本文へスキップ</a>

<?= $view->renderPartial('partials/header') ?>

<main id="main" class="site-main">
    <?= $view->renderPartial('partials/flash', ['flash' => $flash ?? []]) ?>
    <?= $content ?>
</main>

<?= $view->renderPartial('partials/footer') ?>

<script src="<?= asset('js/vendor/jquery.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
