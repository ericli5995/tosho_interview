<?php
/**
 * @var string $mode      'create' | 'edit'
 * @var string $action
 * @var \App\Entity\Product $product
 * @var list<\App\Entity\Category> $categories
 */
$hasOld = !empty($GLOBALS['_old_input']);
$val = static fn (string $key, string $default = ''): string => $hasOld ? old($key) : $default;
$checked = static function (string $key, bool $default) use ($hasOld): bool {
    return $hasOld ? old($key) === '1' : $default;
};

if ($hasOld) {
    $specs = json_decode(old('specs_json') ?: '[]', true);
    $specs = is_array($specs) ? $specs : [];
} else {
    $specs = $product->specs;
}
while (count($specs) < 3) {
    $specs[] = ['label' => '', 'value' => '', 'unit' => ''];
}
$isEdit = $mode === 'edit';
?>
<header class="page-head">
    <h1><?= $isEdit ? '製品を編集' : '製品を登録' ?></h1>
    <a class="btn btn--ghost" href="/admin/products">一覧へ戻る</a>
</header>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form product-form">
    <?= csrf_field() ?>

    <div class="form-grid">
        <section class="panel">
            <h2>基本情報</h2>

            <div class="field">
                <label for="model_code">型番 <span class="req">必須</span></label>
                <input type="text" id="model_code" name="model_code" required maxlength="60"
                       value="<?= e($val('model_code', $product->modelCode)) ?>" placeholder="TE-22BK">
            </div>

            <div class="field">
                <label for="name">製品名 <span class="req">必須</span></label>
                <input type="text" id="name" name="name" required maxlength="200"
                       value="<?= e($val('name', $product->name)) ?>" placeholder="φ22 ブラシレスギヤードモータ">
            </div>

            <div class="field">
                <label for="slug">スラッグ (URL)</label>
                <input type="text" id="slug" name="slug" maxlength="220"
                       value="<?= e($val('slug', $product->slug)) ?>" placeholder="空欄なら型番から自動生成">
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="category_id">カテゴリ</label>
                    <select id="category_id" name="category_id">
                        <option value="">未設定</option>
                        <?php $selCat = $hasOld ? old('category_id') : (string) ($product->categoryId ?? ''); ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category->id ?>" <?= $selCat === (string) $category->id ? 'selected' : '' ?>>
                                <?= e($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="motor_type">モータ種類</label>
                    <?php $selMotor = $hasOld ? old('motor_type') : $product->motorType; ?>
                    <select id="motor_type" name="motor_type">
                        <option value="" <?= $selMotor === '' ? 'selected' : '' ?>>未設定</option>
                        <option value="brushless" <?= $selMotor === 'brushless' ? 'selected' : '' ?>>DCブラシレス</option>
                        <option value="brushed" <?= $selMotor === 'brushed' ? 'selected' : '' ?>>DCブラシ</option>
                    </select>
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="rated_voltage">定格電圧 (V)</label>
                    <input type="number" step="0.01" id="rated_voltage" name="rated_voltage"
                           value="<?= e($val('rated_voltage', $product->ratedVoltage !== null ? (string) $product->ratedVoltage : '')) ?>">
                </div>
                <div class="field">
                    <label for="gear_ratio">減速比</label>
                    <input type="text" id="gear_ratio" name="gear_ratio" maxlength="30"
                           value="<?= e($val('gear_ratio', (string) ($product->gearRatio ?? ''))) ?>" placeholder="1/120">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="body_diameter">外径 (mm)</label>
                    <input type="number" id="body_diameter" name="body_diameter"
                           value="<?= e($val('body_diameter', $product->bodyDiameter !== null ? (string) $product->bodyDiameter : '')) ?>">
                </div>
                <div class="field">
                    <label for="rated_torque">定格トルク (mN・m)</label>
                    <input type="number" step="0.01" id="rated_torque" name="rated_torque"
                           value="<?= e($val('rated_torque', $product->ratedTorque !== null ? (string) $product->ratedTorque : '')) ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="rated_speed">定格回転数 (r/min)</label>
                    <input type="number" id="rated_speed" name="rated_speed"
                           value="<?= e($val('rated_speed', $product->ratedSpeed !== null ? (string) $product->ratedSpeed : '')) ?>">
                </div>
                <div class="field">
                    <label for="noise_level">騒音 (dB)</label>
                    <input type="number" step="0.1" id="noise_level" name="noise_level"
                           value="<?= e($val('noise_level', $product->noiseLevel !== null ? (string) $product->noiseLevel : '')) ?>">
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="life_hours">想定寿命 (h)</label>
                    <input type="number" id="life_hours" name="life_hours"
                           value="<?= e($val('life_hours', $product->lifeHours !== null ? (string) $product->lifeHours : '')) ?>">
                </div>
                <div class="field">
                    <label for="sort_order">表示順</label>
                    <input type="number" id="sort_order" name="sort_order"
                           value="<?= e($val('sort_order', (string) $product->sortOrder)) ?>">
                </div>
            </div>

            <div class="field">
                <label for="description">説明</label>
                <textarea id="description" name="description" rows="5" maxlength="5000"><?= e($val('description', $product->description)) ?></textarea>
            </div>

            <div class="field-inline">
                <label><input type="checkbox" name="is_published" value="1" <?= $checked('is_published', $product->isPublished) ? 'checked' : '' ?>> 公開する</label>
                <label><input type="checkbox" name="is_featured" value="1" <?= $checked('is_featured', $product->isFeatured) ? 'checked' : '' ?>> 代表製品（トップページに表示）</label>
            </div>
        </section>

        <section class="panel">
            <h2>代表スペック表</h2>
            <p class="muted">トップページ・詳細ページの「REPRESENTATIVE SPEC」に表示されます。空行は無視されます。</p>
            <div id="spec-rows" class="spec-rows">
                <?php foreach ($specs as $i => $spec): ?>
                    <div class="spec-rows__row">
                        <input type="text" name="specs[<?= $i ?>][label]" placeholder="項目 (例: 定格電圧)" value="<?= e((string) ($spec['label'] ?? '')) ?>">
                        <input type="text" name="specs[<?= $i ?>][value]" placeholder="値 (例: 24)" value="<?= e((string) ($spec['value'] ?? '')) ?>">
                        <input type="text" name="specs[<?= $i ?>][unit]" placeholder="単位 (例: V)" value="<?= e((string) ($spec['unit'] ?? '')) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" id="add-spec-row">行を追加</button>

            <h2 class="mt">製品画像</h2>
            <p class="muted">JPEG / PNG / WebP、1枚あたり最大 <?= (int) round((int) config('app.upload_max_bytes', 5242880) / 1048576) ?> MB。保存時にまとめてアップロードされます。</p>
            <div id="image-uploader">
                <div class="uploader__dropzone">
                    <input type="file" id="images-input" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
                    <p>画像を選択（複数可）。ここにドラッグ＆ドロップもできます。</p>
                </div>
                <input type="hidden" id="primary_image_index" name="primary_image_index" value="">
                <div id="image-preview"><!-- Vue renders the selected-image preview list here --></div>
            </div>
        </section>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg"><?= $isEdit ? '変更を保存' : '登録する' ?></button>
        <a class="btn btn--ghost" href="/admin/products">キャンセル</a>
    </div>
</form>

<?php if ($isEdit && $product->images !== []): ?>
    <section class="panel">
        <h2>登録済みの画像</h2>
        <div class="image-manage">
            <?php foreach ($product->images as $image): ?>
                <figure class="image-manage__item">
                    <img src="<?= e($image->thumbUrl()) ?>" alt="">
                    <figcaption>
                        <?php if ($image->isPrimary): ?>
                            <span class="badge badge--star">主画像</span>
                        <?php else: ?>
                            <form method="post" action="/admin/products/<?= (int) $product->id ?>/images/primary">
                                <?= csrf_field() ?>
                                <input type="hidden" name="image_id" value="<?= (int) $image->id ?>">
                                <button type="submit" class="btn btn--ghost btn--sm">主画像にする</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="/admin/products/<?= (int) $product->id ?>/images/<?= (int) $image->id ?>/delete"
                              onsubmit="return confirm('この画像を削除しますか？');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--danger btn--sm">削除</button>
                        </form>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<script>
    /* Spec repeater - plain JS, adds another row of inputs. */
    (function () {
        var wrap = document.getElementById("spec-rows");
        var btn = document.getElementById("add-spec-row");
        if (!wrap || !btn) { return; }
        btn.addEventListener("click", function () {
            var i = wrap.querySelectorAll(".spec-rows__row").length;
            var row = document.createElement("div");
            row.className = "spec-rows__row";
            row.innerHTML =
                '<input type="text" name="specs[' + i + '][label]" placeholder="項目">' +
                '<input type="text" name="specs[' + i + '][value]" placeholder="値">' +
                '<input type="text" name="specs[' + i + '][unit]" placeholder="単位">';
            wrap.appendChild(row);
        });
    })();
</script>
<script src="<?= asset('js/vendor/vue.global.prod.js') ?>"></script>
<script src="<?= asset('js/admin-product-form.js') ?>"></script>
