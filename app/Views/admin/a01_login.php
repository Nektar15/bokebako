<?php
/**
 * A-01 管理者ログイン
 *
 * 二段階認証（TOTP）の入力欄は最終工程で足す（ADR 0007-6）。
 *
 * @var string $screenId
 * @var string $title
 * @var bool   $guest    true（左メニューを出さない）
 * @var string $loginId  入力エラーで戻ったときの ID（パスワードは戻さない）
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a01-login.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form class="c-panel c-panel--raised p-login" method="post" action="<?= admin_url('login') ?>" data-js-once>
<?= csrf_field() ?>
<div class="p-login__brand">
<?= $this->include('partials/logo') ?>
<span class="p-login__name">ぼけばこ</span>
<span class="c-tag c-tag--small">管理</span>
</div>
<h1 class="p-login__title">管理画面にログイン</h1>
<div class="c-afield">
<label class="c-afield__label" for="login-id">ID またはメールアドレス</label>
<input class="c-ainput c-ainput--full" id="login-id" name="login_id" type="text" autocomplete="username" autocapitalize="off" spellcheck="false" required autofocus value="<?= esc($loginId, 'attr') ?>">
</div>
<div class="c-afield">
<label class="c-afield__label" for="login-password">パスワード</label>
<input class="c-ainput c-ainput--full" id="login-password" name="password" type="password" autocomplete="current-password" required>
</div>
<button type="submit" class="c-abutton c-abutton--primary c-abutton--tall p-login__submit">ログイン</button>
<p class="p-login__note">続けて失敗すると、しばらくログインできなくなります。パスワードを忘れたときは、上位管理者に再設定を依頼してください。</p>
</form>
<?= $this->endSection() ?>
