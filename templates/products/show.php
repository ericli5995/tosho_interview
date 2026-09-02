<?php
/** @var \App\Entity\Product $product */
$images = $product->images;
$primary = $product->primaryImage();
$coreSpecs = [];
if ($product->motorTypeLabel() !== '') {
    $coreSpecs[] = ['label' => 'モータ種類', 'value' => $product->motorTypeLabel(), 'unit' => null];
}
if ($product->voltageLabel() !== '') {
    $coreSpecs[] = ['label' => '定格電圧', 'value' => $product->voltageLabel(), 'unit' => null];
}
if ($product->gearRatio) {
    $coreSpecs[] = ['label' => '減速比', 'value' => $product->gearRatio, 'unit' => null];
}
if ($product->bodyDiameter !== null) {
    $coreSpecs[] = ['label' => '外径', 'value' => 'ø' . $product->bodyDiameter, 'unit' => 'mm'];
}
if ($product->ratedTorque !== null) {
    $coreSpecs[] = ['label' => '定格トルク', 'value' => rtrim(rtrim(number_format($product->ratedTorque, 2, '.', ''), '0'), '.'), 'unit' => 'mN・m'];
}
if ($product->ratedSpeed !== null) {
    $coreSpecs[] = ['label' => '定格回転数', 'value' => (string) $product->ratedSpeed, 'unit' => 'r/min'];
}
if ($product->lifeHours !== null) {
    $coreSpecs[] = ['label' => '想定寿命', 'value' => number_format($product->lifeHours), 'unit' => 'h'];
}
?>
<section class="wrap">
    <p class="breadcrumb">
        <a href="/">トップ</a> &rsaquo;
        <a href="/products/search">製品検索</a> &rsaquo;
        <?= e($product->modelCode) ?>
    </p>
</section>

<div class="wrap">
    <div class="detail">
        <div class="detail__gallery">
            <div class="detail__main-img">
                <?php if ($primary !== null): ?>
                    <img id="detail-main" src="<?= e($primary->mediumUrl()) ?>" alt="<?= e($product->name) ?>">
                <?php else: ?>
                    <span class="product-card__media"><span>OUTLINE DRAWING</span></span>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
                <div class="detail__thumbs">
                    <?php foreach ($images as $i => $image): ?>
                        <img
                            src="<?= e($image->thumbUrl()) ?>"
                            data-full="<?= e($image->mediumUrl()) ?>"
                            alt="<?= e($product->name) ?> 画像<?= $i + 1 ?>"
                            class="detail-thumb<?= $i === 0 ? ' is-active' : '' ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail__info">
            <p class="detail__code"><?= e($product->modelCode) ?></p>
            <h1 class="detail__name"><?= e($product->name) ?></h1>

            <?php if ($product->category !== null): ?>
                <p><span class="tag"><?= e($product->category->name) ?></span></p>
            <?php endif; ?>

            <?php if ($coreSpecs !== [] || $product->specs !== []): ?>
                <p class="spec-panel__title" style="margin-top:20px;">REPRESENTATIVE SPEC</p>
                <table class="spec-table">
                    <tbody>
                    <?php foreach ($coreSpecs as $spec): ?>
                        <tr>
                            <th><?= e($spec['label']) ?></th>
                            <td><?= e($spec['value']) ?><?= $spec['unit'] ? ' ' . e($spec['unit']) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($product->specs as $spec): ?>
                        <tr>
                            <th><?= e($spec['label']) ?></th>
                            <td><?= e($spec['value']) ?><?= ($spec['unit'] ?? '') !== '' ? ' ' . e($spec['unit']) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($product->description !== ''): ?>
                <p class="detail__desc"><?= e($product->description) ?></p>
            <?php endif; ?>

            <p class="detail__cta">
                <a class="btn btn--primary btn--lg" href="/contact">この製品について問い合わせる</a>
            </p>
        </div>
    </div>
</div>

<script>
    (function () {
        var main = document.getElementById("detail-main");
        var thumbs = document.querySelectorAll(".detail-thumb");
        thumbs.forEach(function (thumb) {
            thumb.addEventListener("click", function () {
                if (main) {
                    main.src = thumb.getAttribute("data-full");
                }
                thumbs.forEach(function (t) { t.classList.remove("is-active"); });
                thumb.classList.add("is-active");
            });
        });
    })();
</script>
