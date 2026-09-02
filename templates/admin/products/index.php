<?php
/** @var array{items:list<\App\Entity\Product>,total:int,page:int,per_page:int,pages:int} $result */
$items = $result['items'];
?>
<header class="page-head">
    <h1>製品一覧 <span class="page-head__count">(<?= (int) $result['total'] ?>)</span></h1>
    <a class="btn btn--primary" href="/admin/products/create">製品を登録</a>
</header>

<?php if ($items === []): ?>
    <div class="panel panel--empty">
        <p>まだ製品が登録されていません。</p>
        <p><a class="btn btn--primary" href="/admin/products/create">最初の製品を登録する</a></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>画像</th>
                <th>型番 / 製品名</th>
                <th>外径</th>
                <th>状態</th>
                <th>更新日時</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $product): ?>
                <?php $thumb = $product->primaryImage(); ?>
                <tr>
                    <td class="data-table__thumb">
                        <?php if ($thumb !== null): ?>
                            <img src="<?= e($thumb->thumbUrl()) ?>" alt="" width="56" height="56">
                        <?php else: ?>
                            <span class="thumb-empty">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($product->modelCode) ?></strong><br>
                        <span class="muted"><?= e($product->name) ?></span>
                    </td>
                    <td><?= $product->bodyDiameter !== null ? 'ø' . (int) $product->bodyDiameter : '—' ?></td>
                    <td>
                        <?php if ($product->isPublished): ?>
                            <span class="badge badge--live">公開</span>
                        <?php else: ?>
                            <span class="badge">下書き</span>
                        <?php endif; ?>
                        <?php if ($product->isFeatured): ?>
                            <span class="badge badge--star">代表</span>
                        <?php endif; ?>
                    </td>
                    <td class="muted"><?= e((string) $product->updatedAt) ?></td>
                    <td class="data-table__actions">
                        <div class="row-actions">
                            <a class="btn btn--ghost btn--sm" href="/admin/products/<?= (int) $product->id ?>/edit">編集</a>
                            <form method="post" action="/admin/products/<?= (int) $product->id ?>/delete"
                                  onsubmit="return confirm('「<?= e($product->modelCode) ?>」を削除します。よろしいですか？');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--danger btn--sm">削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
        <nav class="pager" aria-label="ページ送り">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <?php if ($p === $result['page']): ?>
                    <span class="is-current"><?= $p ?></span>
                <?php else: ?>
                    <a href="/admin/products?page=<?= $p ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
