<?php
// GA4 タグ。測定IDは .env の app.ga4Id。未設定なら出力しない（FEATURES 0-1）
$ga4Id = (string) env('app.ga4Id', '');
if ($ga4Id === '') {
    return;
}
?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($ga4Id, 'attr') ?>"></script>
<script type="application/json" data-js-config="ga4">{"id": <?= json_encode($ga4Id) ?>}</script>
