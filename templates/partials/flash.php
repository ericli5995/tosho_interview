<?php
/** @var array<int,array{type:string,message:string}> $flash */
$flash = $flash ?? [];
if ($flash === []) {
    return;
}
?>
<div class="flash" role="status">
    <?php foreach ($flash as $item): ?>
        <?php $type = in_array($item['type'] ?? '', ['success', 'error', 'info'], true) ? $item['type'] : 'info'; ?>
        <p class="flash__item flash__item--<?= e($type) ?>"><?= e($item['message'] ?? '') ?></p>
    <?php endforeach; ?>
</div>
