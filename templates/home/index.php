<?php
/** @var \App\Entity\Product|null $featured */
$title = null;
$img = $featured?->primaryImage();
?>
<section class="hero">
    <div class="wrap hero__inner">
        <div class="hero__copy">
            <div class="hero__chips">
                <span class="chip">&#8709;13&ndash;&#8709;35 小型ギヤードモータ</span>
                <span class="chip">DCブラシ／ブラシレス</span>
                <span class="chip">カスタム・特殊仕様対応</span>
            </div>
            <h1 class="hero__title">小型ギヤードモータの開発・製造で、<br><span class="hero__accent">精密機器の駆動</span>を支える。</h1>
            <p class="hero__lead">
                歯車技術とDCモータ技術を組み合わせ、用途・仕様に合わせた最適な製品選定をサポート。
                小型化・高トルク・低速・低騒音・長寿命のご要望に、設計段階からお応えします。
            </p>
            <p class="hero__actions">
                <a class="btn btn--primary btn--lg" href="/products/search">製品を検索する</a>
                <?php if ($featured !== null): ?>
                    <a class="btn btn--ghost btn--lg" href="/products/<?= e($featured->slug) ?>">代表製品の詳細</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="hero__panel">
            <?php if ($featured !== null): ?>
                <div class="spec-panel">
                    <div class="spec-panel__head">
                        <span><?= e($featured->modelCode) ?> SERIES / OUTLINE DRAWING</span>
                        <span>UNIT : mm</span>
                    </div>
                    <div class="spec-panel__figure">
                        <?php if ($img !== null): ?>
                            <img src="<?= e($img->mediumUrl()) ?>" alt="<?= e($featured->name) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="spec-panel__placeholder">OUTLINE DRAWING</div>
                        <?php endif; ?>
                    </div>
                    <div class="spec-panel__body">
                        <p class="spec-panel__title">REPRESENTATIVE SPEC</p>
                        <table class="spec-table">
                            <tbody>
                            <?php if ($featured->motorType !== ''): ?>
                                <tr><th>モータ種類</th><td><?= e($featured->motorTypeLabel()) ?></td></tr>
                            <?php endif; ?>
                            <?php if ($featured->ratedVoltage !== null): ?>
                                <tr><th>定格電圧</th><td><?= e($featured->voltageLabel()) ?></td></tr>
                            <?php endif; ?>
                            <?php foreach (array_slice($featured->specs, 0, 3) as $spec): ?>
                                <tr>
                                    <th><?= e($spec['label']) ?></th>
                                    <td><?= e($spec['value']) ?><?= ($spec['unit'] ?? '') !== '' ? ' ' . e($spec['unit']) : '' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="spec-panel spec-panel--empty">
                    <p>代表製品が未登録です。</p>
                    <p><a href="/admin/login">管理画面</a>から製品を登録してください。</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
