<?php
/**
 * 管理画面の左メニュー（1024px 未満では引き出し。layouts/admin の「メニュー」で開く）
 *
 * @var string                         $screenId  選択中の画面ID
 * @var array{todo:int, inquiries:int} $badges
 */
$menu = [
    ['A-02', 'ダッシュボード', '', 0],
    ['A-03', '大喜利', 'oogiri', 0],
    ['A-04', '回答', 'answers', 0],
    ['A-05', '通報・モデレーション', 'moderation', $badges['todo']],
    ['A-06', 'NGワード', 'ng-words', 0],
    ['A-07', '広告', 'ads', 0],
    ['A-08', 'OGP', 'ogp', 0],
    ['A-09', 'お問い合わせ', 'inquiries', $badges['inquiries']],
    ['A-10', '運用設定', 'settings', 0],
    ['A-01', '管理者アカウント', 'accounts', 0],
];
$admin = service('currentAdmin');
?>
<aside class="l-sidebar" id="admin-sidebar">
<label for="admin-menu" class="l-sidebar__close" role="button" tabindex="0" data-js-admin-menu-button aria-label="メニューを閉じる">×</label>
<a class="l-sidebar__brand" href="<?= admin_url() ?>">
<?= $this->include('partials/logo') ?>
<span class="l-sidebar__name">ぼけばこ</span>
<span class="c-tag c-tag--small">管理</span>
</a>
<nav aria-label="管理メニュー">
<ul class="l-sidebar__menu">
<?php foreach ($menu as [$id, $label, $path, $count]): ?>
<li>
<a class="l-sidebar__item<?= $id === $screenId ? ' is-active' : '' ?>" href="<?= admin_url($path) ?>"<?= $id === $screenId ? ' aria-current="page"' : '' ?>>
<span class="l-sidebar__id u-mono"><?= esc($id) ?></span>
<span class="l-sidebar__label"><?= esc($label) ?></span>
<?php if ($count > 0): ?><span class="c-badge"><?= esc((string) $count) ?></span><?php endif; ?>
</a>
</li>
<?php endforeach; ?>
</ul>
</nav>
<div class="l-sidebar__user">
<?php if ($admin->has()): ?>
<span class="l-sidebar__username"><?= esc($admin->get()->name) ?></span>
<span class="l-sidebar__role"><?= esc($admin->get()->role->label()) ?></span>
<?php endif; ?>
<form class="c-action" method="post" action="<?= admin_url('logout') ?>">
<?= csrf_field() ?>
<button type="submit" class="c-abutton c-abutton--small l-sidebar__logout">ログアウト</button>
</form>
</div>
</aside>
