<?php
/**
 * U-15 利用規約（本文は発注者支給。法務確認のうえ差し替える。ページキャッシュの対象）
 *
 * @var bool $showDraftNote ひな形である旨の注記を出すか（本番では false）
 */
$toc = [
    ['id' => 's1', 'title' => '第1条　適用'],
    ['id' => 's2', 'title' => '第2条　禁止事項'],
    ['id' => 's3', 'title' => '第3条　投稿コンテンツの取扱い'],
    ['id' => 's4', 'title' => '第4条　投稿の非表示・削除'],
    ['id' => 's5', 'title' => '第5条　サービスの停止・変更'],
    ['id' => 's6', 'title' => '第6条　知的財産権'],
    ['id' => 's7', 'title' => '第7条　権利侵害の申し立て'],
    ['id' => 's8', 'title' => '第8条　利用者の責任'],
    ['id' => 's9', 'title' => '第9条　免責'],
    ['id' => 's10', 'title' => '第10条　規約の変更'],
    ['id' => 's11', 'title' => '第11条　準拠法・管轄'],
];
?>
<?= $this->extend('layouts/front') ?>
<?= $this->section('meta') ?>
<title>利用規約｜ぼけばこ</title>
<meta name="description" content="ぼけばこの利用規約。投稿の扱い、禁止事項、非表示・削除について。">
<?= $this->endSection() ?>
<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/doc.css') ?>">
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= part('partials/ga_event', ['name' => 'guideline_view', 'params' => ['doc' => 'terms', 'via' => 'page']]) ?>
<?= part('partials/doc_layout', ['title' => '利用規約', 'updatedAt' => '[最終更新日]', 'toc' => $toc, 'articlesView' => 'front/u15_terms_body', 'showDraftNote' => $showDraftNote]) ?>
<?= $this->endSection() ?>
