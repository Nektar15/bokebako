<?php
/**
 * U-16 プライバシーポリシー（本文は発注者支給。ページキャッシュの対象）
 *
 * @var bool $showDraftNote
 */
$toc = [
    ['id' => 's1', 'title' => '1. 取得する情報'],
    ['id' => 's2', 'title' => '2. 利用目的'],
    ['id' => 's3', 'title' => '3. アクセス解析・広告'],
    ['id' => 's4', 'title' => '4. AIによる投稿内容の確認'],
    ['id' => 's5', 'title' => '5. 第三者への提供'],
    ['id' => 's6', 'title' => '6. 保存期間'],
    ['id' => 's7', 'title' => '7. お問い合わせ窓口'],
];
?>
<?= $this->extend('layouts/front') ?>
<?= $this->section('meta') ?>
<title>プライバシーポリシー｜ぼけばこ</title>
<meta name="description" content="ぼけばこのプライバシーポリシー。取得する情報と使いみち、アクセス解析について。">
<?= $this->endSection() ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/doc.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= part('partials/doc_layout', ['title' => 'プライバシーポリシー', 'updatedAt' => '[最終更新日]', 'toc' => $toc, 'articlesView' => 'front/u16_privacy_body', 'showDraftNote' => $showDraftNote]) ?>
<?= $this->endSection() ?>
