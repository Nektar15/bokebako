<?php
/**
 * 表示時に GA4 へ送るイベント（ADR 0015。js/components/tracking.js が読む）。個人や秘密の情報は params に入れない
 *
 * @var string               $name   GA4 のイベント名（例：event_page_view）
 * @var array<string, mixed> $params パラメータ（省略可）
 * @var string               $once   同じタブで二度送らないときの対象（例：スラッグ）。省略時は毎回送る
 */
?>
<script type="application/json" data-js-ga-page><?= json_encode(['name' => $name, 'params' => (object) ($params ?? []), 'once' => $once ?? ''], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
