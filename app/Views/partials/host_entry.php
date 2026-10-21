<?php
/**
 * 「開催者の方へ」：別の端末やブラウザから、募集URLと合言葉で開催者画面を開く入口（SPEC U-08「募集URLと合言葉を入力して本人確認・復旧」。監査 2026-10-21）
 *
 * @var string $slug
 */
?>
<p class="u-sub c-host-entry">開催者の方へ：<a href="<?= site_url('host/' . $slug) ?>">別の端末から開催者画面を開く（合言葉）</a></p>
