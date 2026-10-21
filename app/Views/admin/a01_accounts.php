<?php

use App\Entities\AdminAccount;
use App\Enums\AdminRole;

/**
 * A-01 管理者アカウント（追加・権限設定・パスワード。未確定事項10 → ADR 0007-4）
 *
 * @var string                                                                                          $screenId
 * @var string                                                                                          $title
 * @var bool                                                                                            $isSuper  上位管理者か（false なら自分のパスワード変更だけを出す）
 * @var list<array{account: AdminAccount, role: string, lastLogin: string, locked: bool, isSelf: bool}> $rows 一覧の行（上位管理者のときだけ）
 * @var list<AdminRole>                                                                                 $roles
 * @var AdminAccount|null                                                                               $editing 編集中のアカウント（追加なら null）
 * @var array{login_id: string, name: string, role: string}                                             $form フォームの初期値
 * @var int                                                                                             $passwordMin パスワードの最小文字数
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a01-accounts.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="p-accounts">
<div class="p-accounts__main">

<?php if ($isSuper): ?>
<div class="c-panel c-panel--table">
<div class="c-table p-accounts__table" role="table">
<div class="c-table__row c-table__row--head" role="row">
<div class="c-table__head" role="columnheader">ID</div>
<div class="c-table__head" role="columnheader">表示名</div>
<div class="c-table__head" role="columnheader">権限</div>
<div class="c-table__head" role="columnheader">最終ログイン</div>
<div class="c-table__head" role="columnheader">操作</div>
</div>
<?php foreach ($rows as $row): ?>
<?php $account = $row['account']; ?>
<div class="c-table__row" role="row">
<div class="c-table__cell c-table__cell--break" role="cell" data-label="ID"><?= esc($account->login_id) ?><?php if ($row['isSelf']): ?>（自分）<?php endif; ?></div>
<div class="c-table__cell c-table__cell--break" role="cell" data-label="表示名"><?= esc($account->name) ?></div>
<div class="c-table__cell c-table__cell--sub" role="cell" data-label="権限"><?= esc($row['role']) ?></div>
<div class="c-table__cell c-table__cell--time" role="cell" data-label="最終ログイン"><?= esc($row['lastLogin']) ?><?php if ($row['locked']): ?> <?= part('partials/admin_badge', ['status' => 'failed', 'label' => '一時ロック中']) ?><?php endif; ?></div>
<div class="c-table__cell" role="cell">
<div class="c-table__actions">
<a class="c-abutton c-abutton--tiny" href="<?= admin_url('accounts?edit=' . $account->id) ?>">編集</a>
<?php if (! $row['isSelf']): ?>
<?= part('partials/admin_action_form', ['action' => admin_url('accounts/' . $account->id . '/delete'), 'label' => '削除', 'variant' => 'icon-tiny', 'iconOnly' => true, 'confirm' => '管理者「' . $account->name . '」を削除しますか？', 'id' => 'delete-' . $account->id, 'hidden' => []]) ?>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<p class="c-panel__note">上位管理者は投稿元識別情報（ハッシュ化した IP・ブラウザ ID）を見られ、このページで管理者を追加・変更できます。一般管理者は自分のパスワードだけを変えられます。</p>
<?php endif; ?>

<form class="c-panel c-panel--raised p-accounts__form" method="post" action="<?= admin_url('accounts/me/password') ?>" data-js-once>
<?= csrf_field() ?>
<h2 class="c-panel__title c-panel__title--lg">自分のパスワードを変更</h2>
<div class="c-afield">
<label class="c-afield__label" for="me-current">今のパスワード</label>
<input class="c-ainput c-ainput--full" id="me-current" name="current_password" type="password" autocomplete="current-password" required>
</div>
<div class="c-afield">
<label class="c-afield__label" for="me-new">新しいパスワード</label>
<input class="c-ainput c-ainput--full" id="me-new" name="password" type="password" autocomplete="new-password" minlength="<?= esc((string) $passwordMin, 'attr') ?>" required>
<p class="c-afield__help"><?= esc((string) $passwordMin) ?>文字以上。ID を含めない</p>
</div>
<button type="submit" class="c-abutton c-abutton--dark">変更する</button>
</form>

</div>

<?php if ($isSuper): ?>
<div class="p-accounts__side">
<form class="c-panel c-panel--raised p-accounts__form" method="post" action="<?= $editing !== null ? admin_url('accounts/' . $editing->id) : admin_url('accounts') ?>" data-js-once>
<?= csrf_field() ?>
<h2 class="c-panel__title c-panel__title--lg"><?= $editing !== null ? '管理者を編集' : '管理者を追加' ?></h2>
<div class="c-afield">
<?php if ($editing !== null): ?>
<span class="c-afield__label">ID</span>
<p><?= esc($editing->login_id) ?></p>
<?php else: ?>
<label class="c-afield__label" for="account-login-id">ID（メールアドレスも可）</label>
<input class="c-ainput c-ainput--full" id="account-login-id" name="login_id" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" required value="<?= esc($form['login_id'], 'attr') ?>">
<?php endif; ?>
</div>
<div class="c-afield">
<label class="c-afield__label" for="account-name">表示名（操作履歴に出る名前）</label>
<input class="c-ainput c-ainput--full" id="account-name" name="name" type="text" maxlength="64" required value="<?= esc($form['name'], 'attr') ?>">
</div>
<div class="c-afield">
<label class="c-afield__label" for="account-role">権限</label>
<select class="c-aselect c-aselect--full" id="account-role" name="role">
<?php foreach ($roles as $role): ?>
<option value="<?= esc($role->value, 'attr') ?>"<?= $role->value === $form['role'] ? ' selected' : '' ?>><?= esc($role->label()) ?></option>
<?php endforeach; ?>
</select>
</div>
<?php if ($editing === null): ?>
<div class="c-afield">
<label class="c-afield__label" for="account-password">初期パスワード</label>
<input class="c-ainput c-ainput--full" id="account-password" name="password" type="password" autocomplete="new-password" minlength="<?= esc((string) $passwordMin, 'attr') ?>" required>
<p class="c-afield__help"><?= esc((string) $passwordMin) ?>文字以上。本人に安全な方法で伝え、ログイン後に変えてもらう</p>
</div>
<?php endif; ?>
<button type="submit" class="c-abutton c-abutton--primary"><?= $editing !== null ? '保存する' : '追加する' ?></button>
<?php if ($editing !== null): ?>
<a class="c-abutton c-abutton--text" href="<?= admin_url('accounts') ?>">やめる</a>
<?php endif; ?>
</form>

<?php if ($editing !== null): ?>
<form class="c-panel p-accounts__form" method="post" action="<?= admin_url('accounts/' . $editing->id . '/password') ?>" data-js-once>
<?= csrf_field() ?>
<h2 class="c-panel__title">パスワードを再設定</h2>
<div class="c-afield">
<label class="c-afield__label" for="reset-password">新しいパスワード</label>
<input class="c-ainput c-ainput--full" id="reset-password" name="password" type="password" autocomplete="new-password" minlength="<?= esc((string) $passwordMin, 'attr') ?>" required>
<p class="c-afield__help">一時ロックも解除されます。ほかの端末のログインは切れます</p>
</div>
<button type="submit" class="c-abutton c-abutton--dark">再設定する</button>
</form>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
<?= $this->endSection() ?>
