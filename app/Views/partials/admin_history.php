<?php
/**
 * 操作履歴・対応履歴・変更履歴の一覧（A-03 詳細、A-05、A-09、A-10）。値は DisplayService::logs() が整形する
 *
 * @var list<array{when: string, what: string}> $logs  新しい順。when は「10/09 18:41・自動」の形
 * @var string                                  $empty 0件のときの文言
 */
?>
<?php if ($logs === []): ?>
<p class="c-history__empty"><?= esc($empty) ?></p>
<?php else: ?>
<ul class="c-history">
<?php foreach ($logs as $log): ?>
<li class="c-history__item"><span class="c-history__dot" aria-hidden="true"></span><div class="c-history__body"><span class="c-history__when"><?= esc($log['when']) ?></span><span class="c-history__what"><?= esc($log['what']) ?></span></div></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
