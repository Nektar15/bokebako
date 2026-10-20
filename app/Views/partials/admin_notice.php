<?php
/**
 * 操作後の通知と入力エラー（管理画面共通）。JS なしで見えるように、ページの先頭に静的に出す
 *
 * @var string|null           $notice 操作後の通知（flash）
 * @var array<string, string> $errors 入力エラー（項目名 => 文言）。空なら出さない
 */
?>
<?php if ($notice !== null && $notice !== ''): ?>
<p class="c-notice" role="status"><?= esc($notice) ?></p>
<?php endif; ?>
<?php if ($errors !== []): ?>
<div class="c-notice c-notice--error" role="alert">
<ul class="c-notice__list">
<?php foreach ($errors as $message): ?>
<li><?= esc($message) ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>
