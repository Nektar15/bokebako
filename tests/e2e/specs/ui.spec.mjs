// 画面まわり：規約のシート（BUG-007）、並び替え（BUG-009）、共有シート（BUG-003）、開催の一覧（D-02）、横のはみ出し
import { test, expect } from '@playwright/test';
import { createOogiri, postAnswer, uniq } from './helpers.mjs';

test('開催確認から規約・ガイドラインを読み、閉じると入力と確認画面がそのまま（BUG-007）', async ({ page }) => {
  const odai = uniq('規約を読む');
  await page.goto('/');
  await page.fill('#odai', odai);
  await page.fill('#passphrase', 'きやくをよむ');
  await page.click('[data-js-open-dialog="confirm-dialog"]');

  for (const [label, text] of [['利用規約', '第1条'], ['投稿ガイドライン', 'やめてほしいこと']]) {
    await page.locator('#confirm-dialog a', { hasText: label }).click();
    const sheet = page.locator('#doc-dialog');
    await expect(sheet).toBeVisible();
    await expect(sheet.locator('[data-js-doc-title]')).toHaveText(label);
    await expect(sheet.locator('[data-js-doc-body]')).toContainText(text);
    await expect(sheet.locator('[data-js-doc-body] .c-ad, [data-js-doc-body] script')).toHaveCount(0);
    expect(page.url()).toMatch(/\/$/); // ページは移らない
    await sheet.locator('button', { hasText: '開催の確認に戻る' }).click();
    await expect(sheet).toBeHidden();
    await expect(page.locator('#confirm-dialog')).toBeVisible();
  }
  // Esc でも閉じられる
  await page.locator('#confirm-dialog a', { hasText: '利用規約' }).click();
  await expect(page.locator('#doc-dialog')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(page.locator('#doc-dialog')).toBeHidden();

  await expect(page.locator('#odai')).toHaveValue(odai);
  await page.click('#confirm-dialog button[type=submit]');
  await page.waitForURL(/\/created$/);
});

test('みんなのボケを新着順・古い順で並び替えられる（BUG-009）', async ({ page }) => {
  const mine = uniq('並び替え');
  await postAnswer(page, 'k7Qm2xPz', mine);
  await page.goto('/o/k7Qm2xPz/answers');
  const bodies = async () => page.locator('.c-answers__body').allTextContents();
  const newest = await bodies();
  expect(newest[0]).toBe(mine); // 新着順の先頭は今の回答（並びの正確さは PHPUnit の AnswerSortTest）
  await expect(page.locator('.c-segment__item.is-current')).toHaveText('新着順');
  await page.locator('.c-segment__item', { hasText: '古い順' }).click();
  await expect(page).toHaveURL(/sort=old/);
  await expect(page.locator('.c-segment__item.is-current')).toHaveText('古い順');
  const oldest = await bodies();
  expect(oldest[0]).not.toBe(mine);
  expect(oldest.length).toBeGreaterThan(0);
  const box = await page.locator('.c-segment').boundingBox();
  expect(box.width).toBeLessThanOrEqual(page.viewportSize().width);
});

test('共有シートが使える端末では「ほかのアプリで共有」を出し、送り終えたら share_complete（BUG-003）', async ({ page }) => {
  await page.addInitScript(() => {
    window.__ga = [];
    window.gtag = (...args) => window.__ga.push(args);
    window.__shared = [];
    navigator.share = async (data) => { window.__shared.push(data); };
  });
  const slug = await createOogiri(page, { odai: uniq('共有シート'), passphrase: 'きょうゆうしーと' });
  const native = page.locator('[data-js-web-share]');
  await expect(page.locator('a.c-button--x', { hasText: 'Xで回答を募集する' })).toBeVisible();
  await expect(native).toBeVisible();
  await native.click();
  await expect(page.locator('[data-js-toast]')).toHaveText('共有しました'); // 送り終えたことが分かる
  const shared = await page.evaluate(() => window.__shared);
  expect(shared[0].url).toMatch(new RegExp(`/o/${slug}$`));
  const events = await page.evaluate(() => window.__ga.filter((a) => a[0] === 'event').map((a) => a[1]));
  expect(events).toContain('share_complete');
});

test('共有シートが使えない端末では「ほかのアプリで共有」を出さない', async ({ page }) => {
  await page.addInitScript(() => { delete Navigator.prototype.share; });
  await createOogiri(page, { odai: uniq('共有なし'), passphrase: 'きょうゆうなし' });
  await expect(page.locator('[data-js-web-share]')).toBeHidden();
  await expect(page.locator('a.c-button--x', { hasText: 'Xで回答を募集する' })).toBeVisible();
});

test('開催者が自分の募集 URL を開くと、募集のシェアが出る（BUG-003）', async ({ page }) => {
  const slug = await createOogiri(page, { odai: uniq('自分の募集'), passphrase: 'じぶんのぼしゅう' });
  await page.goto(`/o/${slug}`);
  await expect(page.locator('.p-answer__host a.c-button--x', { hasText: 'Xで回答を募集する' })).toBeVisible();
});

test('このブラウザで開催した大喜利の一覧（D-02）', async ({ page, browser }, testInfo) => {
  const odai = uniq('一覧に出る');
  const slug = await createOogiri(page, { odai, passphrase: 'いちらんにでる' });
  await page.goto('/');
  const item = page.locator('.c-hosted__item', { hasText: odai });
  await expect(item).toBeVisible();
  await expect(item).toHaveAttribute('href', `/host/${slug}`);
  await expect(item.locator('.c-hosted__meta')).toContainText('募集中');
  // ほかの人には出ない
  const other = await (await browser.newContext({ ...testInfo.project.use })).newPage();
  await other.goto('/');
  await expect(other.locator('[data-js-hosted]')).toBeHidden();
});

const PAGES = ['/', '/o/k7Qm2xPz', '/o/k7Qm2xPz/a/90412', '/o/q8Lr4nTb', '/o/Bx2gHj8k', '/o/Zp3cW9aQ', '/o/Wq8nRt2v', '/about', '/terms', '/privacy', '/guidelines', '/contact', '/host/Zp3cW9aQ'];

test('主要な画面に横のはみ出しと JS のエラーがない', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  for (const url of PAGES) {
    await page.goto(url);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow, url).toBeLessThanOrEqual(0);
  }
  expect(errors).toEqual([]);
});

test('幅 320px でも開催完了の「Xで回答を募集する」が画面に収まる', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 640 });
  await createOogiri(page, { odai: uniq('320px'), passphrase: 'せまいがめん' });
  const box = await page.locator('a.c-button--x').boundingBox();
  expect(box.x).toBeGreaterThanOrEqual(0);
  expect(box.x + box.width).toBeLessThanOrEqual(320);
});
