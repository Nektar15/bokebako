<?php /* 共通ヘッダー（ロゴ・遊び方リンク） */ ?>
<header class="l-header">
<a class="l-header__logo" href="<?= site_url('/') ?>">
<?= $this->include('partials/logo') ?>
<span class="l-header__name">ぼけばこ</span>
</a>
<a class="l-header__link" href="<?= site_url('about') ?>">遊び方</a>
</header>
