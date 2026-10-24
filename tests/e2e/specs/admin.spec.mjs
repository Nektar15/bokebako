// 管理画面（A-01〜A-10。指示書 第13章）：ログイン、全画面の表示、非表示・再公開、運用設定の保存
import { test, expect } from '@playwright/test';
import { postAnswer, intentText } from './helpers.mjs';

const ADMIN = process.env.E2E_ADMIN_PATH || 'admin';
const PASSWORD = process.env.E2E_ADMIN_PASSWORD || 'dev-password-bokebako';

async function login(page) {
  await page.goto(`/${ADMIN}/login`);
  await page.fill('#login-id', 'dev-admin');
  await page.fill('#login-password', PASSWORD);
  await Promise.all([page.waitForURL((u) => !u.pathname.endsWith('/login')), page.click('button[type=submit]')]);
}

test('ログインしていなければログイン画面へ。パスワードが違えばエラー', async ({ page }) => {
  await page.goto(`/${ADMIN}/oogiri`);
  await expect(page).toHaveURL(new RegExp(`/${ADMIN}/login`));
  await page.fill('#login-id', 'dev-admin');
  await page.fill('#login-password', 'ちがうパスワード');
  await page.click('button[type=submit]');
  await expect(page.getByText('ID またはパスワードが違います')).toBeVisible();
});

test('全画面が開き、JS のエラーと横のはみ出しがない', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  await login(page);
  for (const path of ['', 'oogiri', 'oogiri/12480', 'answers', 'moderation', 'ng-words', 'ads', 'ogp', 'inquiries', 'settings', 'accounts']) {
    const res = await page.goto(`/${ADMIN}/${path}`);
    expect(res.status(), path).toBe(200);
    await expect(page.locator('h1').first(), path).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow, path).toBeLessThanOrEqual(0);
  }
  expect(errors).toEqual([]);
});

test('大喜利を非表示にすると公開ページが非表示になり、再公開で戻る', async ({ page, browser }, testInfo) => {
  await login(page);
  const visitor = await (await browser.newContext({ ...testInfo.project.use })).newPage();

  await page.goto(`/${ADMIN}/oogiri/12480`);
  await page.locator('button[data-js-open-dialog="hide-dialog"]').click();
  await page.locator('#hide-dialog button[type=submit]').click();
  await expect(page.locator('form[action$="/oogiri/12480/publish"] button')).toBeVisible();
  await visitor.goto('/o/k7Qm2xPz');
  await expect(visitor.locator('main h1')).toContainText('公開されていません');

  await page.locator('form[action$="/oogiri/12480/publish"] button').click();
  await expect(page.locator('button[data-js-open-dialog="hide-dialog"]')).toBeVisible();
  await visitor.goto('/o/k7Qm2xPz');
  await expect(visitor.locator('.c-tag', { hasText: '回答募集中' })).toBeVisible();
});

test('運用設定で「ほかの人の回答」のシェア文を変えると、みんなのボケに反映される', async ({ page, browser }, testInfo) => {
  await login(page);
  await page.goto(`/${ADMIN}/settings`);
  const field = page.locator('textarea[name="tpl_answer_other"]');
  const before = await field.inputValue();
  await field.fill('笑った：{answer}\n{url}');
  await page.click('button:has-text("設定を保存する")');
  await expect(page).toHaveURL(new RegExp(`/${ADMIN}/settings`));
  await expect(page.locator('textarea[name="tpl_answer_other"]')).toHaveValue('笑った：{answer}\n{url}');

  const visitor = await (await browser.newContext({ ...testInfo.project.use })).newPage();
  await postAnswer(visitor, 'k7Qm2xPz', '設定の確認のボケ');
  await visitor.goto('/o/k7Qm2xPz/answers');
  const other = visitor.locator('.c-answers__item:not(.is-mine)').first();
  expect(intentText(await other.locator('a.c-iconbtn').getAttribute('href'))).toMatch(/^笑った：/m);

  await page.locator('textarea[name="tpl_answer_other"]').fill(before); // 元に戻す
  await page.click('button:has-text("設定を保存する")');
  await expect(page.locator('textarea[name="tpl_answer_other"]')).toHaveValue(before);
});
