// GA4 のイベント（指示書 第9章、ADR 0015）。gtag を差し替えて、送った名前とパラメータを見る
import { test, expect } from '@playwright/test';
import { createOogiri, postAnswer, uniq } from './helpers.mjs';

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    window.__ga = [];
    window.gtag = (...args) => window.__ga.push(args);
  });
});

const events = (page) => page.evaluate(() => window.__ga.filter((a) => a[0] === 'event').map((a) => ({ name: a[1], params: a[2] || {} })));
const names = async (page) => (await events(page)).map((e) => e.name);

test('開催：開始・失敗・完了（完了は再読み込みで二重にならない）', async ({ page }) => {
  await page.goto('/');
  await page.fill('#odai', uniq('計測'));
  expect(await names(page)).toEqual(['event_create_start']);
  await page.locator('#passphrase').evaluate((el) => el.removeAttribute('minlength'));
  await page.fill('#passphrase', 'abc');
  await page.click('[data-js-open-dialog="confirm-dialog"]');
  await page.click('#confirm-dialog button[type=submit]');
  await page.waitForURL((u) => u.pathname === '/');
  const failed = await events(page);
  expect(failed.map((e) => e.name)).toContain('event_create_error');
  expect(failed.find((e) => e.name === 'event_create_error').params.fields).toBe('passphrase');

  await createOogiri(page, { odai: uniq('計測'), passphrase: 'けいそくのことば' });
  expect(await names(page)).toContain('event_create_complete');
  await page.reload();
  expect(await names(page)).not.toContain('event_create_complete');
});

test('回答：開始・完了、詳細の閲覧、シェアのクリック、並び替え', async ({ page }) => {
  await page.goto('/o/k7Qm2xPz');
  expect(await names(page)).toContain('event_page_view');
  await page.fill('textarea[name=body]', '計測のボケ');
  expect(await names(page)).toContain('answer_start');
  await page.click('[data-js-answer-submit]');
  await page.click('#answer-confirm button[type=submit]');
  await page.waitForURL(/\/done$/);
  expect(await names(page)).toContain('answer_complete');

  await page.locator('a.c-button--x').evaluate((a) => { a.removeAttribute('target'); a.href = '#'; });
  await page.locator('a.c-button--x').click();
  const share = (await events(page)).find((e) => e.name === 'share_x_click');
  expect(share.params).toMatchObject({ share_type: 'answer', oogiri: 'k7Qm2xPz' });
  expect(await names(page)).not.toContain('share_complete'); // X の投稿画面では完了が分からないので送らない

  await page.goto('/o/k7Qm2xPz/answers');
  await page.locator('.c-segment__item', { hasText: '古い順' }).evaluate((a) => a.addEventListener('click', (e) => e.preventDefault()));
  await page.locator('.c-segment__item', { hasText: '古い順' }).click();
  expect((await events(page)).find((e) => e.name === 'sort_change').params).toMatchObject({ sort: 'old', oogiri: 'k7Qm2xPz' });

  await page.goto('/o/k7Qm2xPz/a/90412');
  expect(await names(page)).toContain('answer_page_view');
});

test('開催者ページ・ガイドラインの閲覧', async ({ page }) => {
  const slug = await createOogiri(page, { odai: uniq('計測の開催者'), passphrase: 'かいさいしゃ' });
  await page.goto(`/host/${slug}`);
  expect(await names(page)).toContain('organizer_page_view');
  await page.goto('/guidelines');
  expect((await events(page)).find((e) => e.name === 'guideline_view').params).toMatchObject({ doc: 'guidelines', via: 'page' });
});

test('イベントに合言葉や回答の本文が入らない', async ({ page }) => {
  const secret = 'ひみつのあいことば';
  await createOogiri(page, { odai: uniq('秘密'), passphrase: secret });
  await postAnswer(page, 'k7Qm2xPz', '本文はイベントに入らない');
  const all = JSON.stringify(await page.evaluate(() => window.__ga));
  expect(all).not.toContain(secret);
  expect(all).not.toContain('本文はイベントに入らない');
});
