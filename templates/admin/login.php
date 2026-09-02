<?php /** @var string|null $title */ ?>
<div class="auth-card">
    <h1 class="auth-card__title">管理画面ログイン</h1>
    <p class="auth-card__note">製品・画像のアップロードは管理者のみ行えます。</p>

    <form method="post" action="/admin/login" class="form">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username">
        </div>
        <div class="field">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn--primary btn--block">ログイン</button>
    </form>

    <p class="auth-card__hint">
        アカウント作成: <code>php bin/create-admin.php you@example.com "password"</code>
    </p>
</div>
