<?php
/**
 * CAPTCHA の枠（FEATURES 0-3）。Cloudflare Turnstile を仮採用（未確定事項3、ADR 0004）
 * siteKey が空なら何も出さない（検証もしない）。読み込むスクリプトは Turnstile のものだけ（CODING_RULES 8-2 の例外として ADR に記録）。
 *
 * @var string $siteKey
 */
?>
<?php if ($siteKey !== ''): ?>
<div class="c-captcha">
<div class="cf-turnstile" data-sitekey="<?= esc($siteKey, 'attr') ?>" data-theme="light"></div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</div>
<?php endif; ?>
