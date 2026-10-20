<?php
/**
 * A-10 運用設定
 *
 * @var string $screenId
 * @var string $title
 * @var array<string, bool|string|list<string>> $values   現在の設定値（SettingService のキー。数値は文字列）
 * @var array<string, string> $defaultLabels 「初期値 …」の表示（キー => 整形済み。例：「100文字」）
 * @var array<string, string> $limitMethods 連続投稿の制限方法（値 => 表示名）
 * @var array<string, string> $aiTargets    AI判定の対象（値 => 表示名）
 * @var array<string, bool>   $aiSelected   AI判定の対象の現在値（値 => 選択中か）
 * @var string $aiUsed  AI判定の今月の使用件数（整形済み）
 * @var list<array{when: string, what: string}> $history 変更履歴（新しい順）
 * @var array<string, string> $errors 入力エラー（キー => 文言）
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= asset_url('css/pages/a10-settings.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form class="p-settings" method="post" action="<?= admin_url('settings') ?>" data-js-once data-js-settings-form>
<?= csrf_field() ?>
<div class="p-settings__main">

<section class="c-panel p-settings__section">
<div class="p-settings__section-head"><h2 class="c-panel__title c-panel__title--lg">投稿の文字数</h2><span class="p-settings__ref">U-01 / U-04</span></div>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => 'お題の最大文字数', 'desc' => '開催画面の入力欄と、サーバー側の検証に使います', 'default' => $defaultLabels['odai_max'], 'inputs' => [['key' => 'odai_max', 'value' => $values['odai_max'], 'unit' => '文字', 'width' => 'md']], 'error' => $errors['odai_max'] ?? '', 'extra' => '']) ?>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => '回答の最大文字数', 'desc' => '回答画面の入力欄と、サーバー側の検証に使います', 'default' => $defaultLabels['answer_max'], 'inputs' => [['key' => 'answer_max', 'value' => $values['answer_max'], 'unit' => '文字', 'width' => 'md']], 'error' => $errors['answer_max'] ?? '', 'extra' => '']) ?>
</section>

<section class="c-panel p-settings__section">
<div class="p-settings__section-head"><h2 class="c-panel__title c-panel__title--lg">募集と結果発表</h2><span class="p-settings__ref">S-01 / S-02</span></div>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => '募集時間', 'desc' => '開催してから回答を締め切るまで', 'default' => $defaultLabels['open_hours'], 'inputs' => [['key' => 'open_hours', 'value' => $values['open_hours'], 'unit' => '時間', 'width' => 'md']], 'error' => $errors['open_hours'] ?? '', 'extra' => '']) ?>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => '結果選出の期限', 'desc' => '締切から、開催者がベスト回答を選べる時間。過ぎると「結果未選出」で確定します', 'default' => $defaultLabels['select_hours'], 'inputs' => [['key' => 'select_hours', 'value' => $values['select_hours'], 'unit' => '時間', 'width' => 'md']], 'error' => $errors['select_hours'] ?? '', 'extra' => '']) ?>
</section>

<section class="c-panel p-settings__section">
<div class="p-settings__section-head"><h2 class="c-panel__title c-panel__title--lg">連続投稿の制限</h2><span class="p-settings__ref">S-03</span></div>
<?= part('partials/admin_setting', ['type' => 'pair', 'name' => '判定する回数と時間', 'desc' => '同じ環境からこの回数を超えて投稿すると、異常投稿として扱います', 'default' => $defaultLabels['burst'], 'inputs' => [['key' => 'burst_count', 'value' => $values['burst_count'], 'unit' => '回', 'width' => 'sm'], ['key' => 'burst_window_sec', 'value' => $values['burst_window_sec'], 'unit' => '秒以内', 'width' => 'md']], 'error' => ($errors['burst_count'] ?? '') . ($errors['burst_window_sec'] ?? ''), 'extra' => '']) ?>
<?= part('partials/admin_setting', ['type' => 'select', 'name' => '制限の方法', 'desc' => '開催・通報にも同じ考え方で制限をかけます', 'default' => $defaultLabels['limit_method'], 'key' => 'limit_method', 'value' => $values['limit_method'], 'options' => $limitMethods, 'error' => $errors['limit_method'] ?? '', 'extra' => '', 'inputs' => []]) ?>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => '停止する時間', 'desc' => '「一定時間、投稿を停止する」を選んだときの長さ', 'default' => $defaultLabels['ban_minutes'], 'inputs' => [['key' => 'ban_minutes', 'value' => $values['ban_minutes'], 'unit' => '分', 'width' => 'md']], 'error' => $errors['ban_minutes'] ?? '', 'extra' => '']) ?>
</section>

<section class="c-panel p-settings__section">
<div class="p-settings__section-head"><h2 class="c-panel__title c-panel__title--lg">通報による一時非表示</h2><span class="p-settings__ref">S-04</span></div>
<?= part('partials/admin_setting', ['type' => 'pair', 'name' => '非表示にする条件', 'desc' => '異なる環境からの通報がこの件数に達したら、一時非表示にして管理者に確認を回します。同一環境からの連続通報は1件と数えます', 'default' => $defaultLabels['report'], 'inputs' => [['key' => 'report_count', 'value' => $values['report_count'], 'unit' => '件', 'width' => 'sm'], ['key' => 'report_window_min', 'value' => $values['report_window_min'], 'unit' => '分以内', 'width' => 'md']], 'error' => ($errors['report_count'] ?? '') . ($errors['report_window_min'] ?? ''), 'extra' => '']) ?>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => '急増の通知', 'desc' => 'この件数を超えたら、通知を1通にまとめて送ります', 'default' => $defaultLabels['surge_count'], 'inputs' => [['key' => 'surge_count', 'value' => $values['surge_count'], 'unit' => '件 ／ 1時間', 'width' => 'sm']], 'error' => $errors['surge_count'] ?? '', 'extra' => '']) ?>
</section>

<section class="c-panel p-settings__section">
<div class="p-settings__section-head"><h2 class="c-panel__title c-panel__title--lg">AI補助判定</h2><span class="p-settings__ref">S-05</span></div>
<?= part('partials/admin_setting', ['type' => 'switch', 'name' => 'AI補助判定', 'desc' => 'OFFにすると、通報とNGワードだけで運用します', 'default' => $defaultLabels['ai_enabled'], 'key' => 'ai_enabled', 'checked' => (bool) $values['ai_enabled'], 'onLabel' => 'ON', 'offLabel' => 'OFF', 'extra' => '', 'error' => '', 'inputs' => []]) ?>
<div class="c-setting__group<?= $values['ai_enabled'] ? '' : ' is-disabled' ?>" data-js-ai-group>
<?= part('partials/admin_setting', ['type' => 'checks', 'name' => '判定する投稿', 'desc' => '全投稿は判定しません', 'default' => $defaultLabels['ai_targets'], 'key' => 'ai_targets', 'options' => $aiTargets, 'selected' => $aiSelected, 'error' => $errors['ai_targets'] ?? '', 'extra' => '', 'inputs' => []]) ?>
<?= part('partials/admin_setting', ['type' => 'number', 'name' => '月間の上限件数', 'desc' => '上限に達した月は、残りを「未判定」にします', 'default' => $defaultLabels['ai_monthly_limit'], 'inputs' => [['key' => 'ai_monthly_limit', 'value' => $values['ai_monthly_limit'], 'unit' => '件 ／ 月', 'width' => 'lg']], 'extra' => '今月 ' . $aiUsed . '件 使用', 'error' => $errors['ai_monthly_limit'] ?? '']) ?>
<?= part('partials/admin_setting', ['type' => 'switch', 'name' => 'AIの判定だけで非表示にする', 'desc' => 'ONにすると、管理者の確認前に「非表示推奨」の投稿を非表示にします', 'default' => $defaultLabels['ai_auto_hide'], 'key' => 'ai_auto_hide', 'checked' => (bool) $values['ai_auto_hide'], 'onLabel' => 'ON（注意）', 'offLabel' => 'OFF', 'extra' => '', 'error' => '', 'inputs' => []]) ?>
</div>
</section>

<section class="c-panel p-settings__section">
<div class="p-settings__section-head"><h2 class="c-panel__title c-panel__title--lg">Xシェアの投稿文</h2><span class="p-settings__ref">U-03 / U-06 / U-10</span></div>
<p class="p-settings__vars">使える変数：<code class="c-code">{odai}</code><code class="c-code">{answer}</code><code class="c-code">{first}</code><code class="c-code">{url}</code></p>
<?= part('partials/admin_setting', ['type' => 'textarea', 'name' => '募集シェア', 'desc' => '使える変数は上の一覧を参照', 'default' => $defaultLabels['tpl'], 'key' => 'tpl_recruit', 'value' => $values['tpl_recruit'], 'error' => $errors['tpl_recruit'] ?? '', 'extra' => '', 'inputs' => []]) ?>
<?= part('partials/admin_setting', ['type' => 'textarea', 'name' => '回答シェア', 'desc' => '使える変数は上の一覧を参照', 'default' => $defaultLabels['tpl'], 'key' => 'tpl_answer', 'value' => $values['tpl_answer'], 'error' => $errors['tpl_answer'] ?? '', 'extra' => '', 'inputs' => []]) ?>
<?= part('partials/admin_setting', ['type' => 'textarea', 'name' => '結果シェア', 'desc' => '使える変数は上の一覧を参照', 'default' => $defaultLabels['tpl'], 'key' => 'tpl_result', 'value' => $values['tpl_result'], 'error' => $errors['tpl_result'] ?? '', 'extra' => '', 'inputs' => []]) ?>
<?= part('partials/admin_setting', ['type' => 'text', 'name' => 'ハッシュタグ', 'desc' => '先頭の # は不要です', 'default' => $defaultLabels['hashtag'], 'key' => 'hashtag', 'value' => $values['hashtag'], 'error' => $errors['hashtag'] ?? '', 'extra' => '', 'inputs' => []]) ?>
</section>

</div>
<div class="p-settings__side">
<div class="c-panel c-panel--raised p-settings__save">
<span class="p-settings__dirty" data-js-settings-dirty data-js-dirty-text="保存していない変更があります" data-js-clean-text="変更はすべて保存済みです">変更はすべて保存済みです</span>
<p class="c-panel__note">保存すると、次のアクセスから反映されます。進行中の大喜利の締切時刻は変わりません。</p>
<button type="submit" class="c-abutton c-abutton--primary c-abutton--tall">設定を保存する</button>
<button type="reset" class="c-abutton c-abutton--link">変更を取り消す</button>
</div>
<section class="c-panel c-panel--tight">
<h2 class="c-panel__title">変更履歴</h2>
<?= part('partials/admin_history', ['logs' => $history, 'empty' => 'まだ変更はありません']) ?>
</section>
</div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= asset_url('js/pages/settings.js') ?>"></script>
<?= $this->endSection() ?>
