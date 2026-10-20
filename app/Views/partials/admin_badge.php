<?php
/**
 * 管理画面の状態バッジ（A-03〜A-09 共通）。色は css/components/admin-status.css が状態の値で決める
 *
 * @var string $status 状態の値（OogiriStatus / AnswerStatus / NgWordAction / AdStatus / InquiryStatus / AiVerdict の value、または ok / failed）
 * @var string $label  表示名
 */
?>
<span class="c-status c-status--<?= esc($status, 'attr') ?>"><?= esc($label) ?></span>
