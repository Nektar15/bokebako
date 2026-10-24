// 異常系（指示書 第6章）：誤入力、連打・二重送信、再読み込み、存在しない URL、締め切り後の投稿、権限のない操作
import { test, expect } from '@playwright/test';
import { createOogiri, postAnswer, anotherPerson, uniq } from './helpers.mjs';

test('合言葉が短いと、理由を出して開催しない（BUG-002）', async ({ page }) => {
  const odai = uniq('短い合言葉');
  await page.goto('/');
  await page.fill('#odai', odai);
  // ブラウザの入力チェック（minlength）を外して、サーバーの検証を確かめる
  await page.locator('#passphrase').evaluate((el) => el.removeAttribute('minlength'));
  await page.fill('#passphrase', 'abc');
  await page.click('[data-js-open-dialog="confirm-dialog"]');
  await page.click('#confirm-dialog button[type=submit]');
  await page.waitForURL((url) => url.pathname === '/');
  await expect(page.locator('#passphrase-error')).toHaveText('合言葉は4〜32文字で入力してください');
  await expect(page.locator('#odai')).toHaveValue(odai); // 入力は残る
});

test('合言葉が空なら、確認のモーダルを開かない（ブラウザの必須チェック）', async ({ page }) => {
  await page.goto('/');
  await page.fill('#odai', uniq('空の合言葉'));
  await page.click('[data-js-open-dialog="confirm-dialog"]');
  await expect(page.locator('#confirm-dialog')).toBeHidden();
  expect(await page.locator('#passphrase').evaluate((el) => el.validity.valueMissing)).toBe(true);
});

test('合言葉の表示ボタンで伏せ字を切り替えられる', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('#passphrase')).toHaveAttribute('type', 'password');
  await page.click('[data-js-reveal="passphrase"]');
  await expect(page.locator('#passphrase')).toHaveAttribute('type', 'text');
  await expect(page.locator('[data-js-reveal="passphrase"]')).toHaveAttribute('aria-pressed', 'true');
});

test('「同意して開催する」の連打で二重に開催されない', async ({ page }) => {
  const odai = uniq('連打');
  await page.goto('/');
  await page.fill('#odai', odai);
  await page.fill('#passphrase', 'れんだのことば');
  await page.click('[data-js-open-dialog="confirm-dialog"]');
  // 人の連打と同じく、同じボタンを続けて3回押す（1回目で押せなくなる）
  await Promise.all([
    page.waitForURL(/\/created$/),
    page.locator('#confirm-dialog button[type=submit]').evaluate((b) => { b.click(); b.click(); b.click(); }),
  ]);
  const slug = page.url().match(/\/o\/([A-Za-z0-9]{8})/)[1];
  // トップの「あなたが開催した大喜利」に同じお題が1件だけ出る
  await page.goto('/');
  await expect(page.locator('[data-js-hosted]')).toBeVisible();
  await expect(page.locator('.c-hosted__odai', { hasText: odai })).toHaveCount(1);
  expect(slug).toBeTruthy();
});

test('回答の連打で二重に登録されず、完了画面の再読み込みでも増えない', async ({ page, browser }, testInfo) => {
  const host = await anotherPerson(browser, testInfo);
  const slug = await createOogiri(host, { odai: uniq('回答の連打'), passphrase: 'かいとうれんだ' });
  await page.goto(`/o/${slug}`);
  await page.fill('textarea[name=body]', '連打したボケ');
  await page.click('[data-js-answer-submit]');
  await Promise.all([
    page.waitForURL(/\/done$/),
    page.locator('#answer-confirm button[type=submit]').evaluate((b) => { b.click(); b.click(); b.click(); }),
  ]);
  await page.reload();
  await expect(page).toHaveURL(/\/done$/);
  await page.goto(`/o/${slug}/answers`);
  await expect(page.locator('.c-answers__item', { hasText: '連打したボケ' })).toHaveCount(1);
});

test('存在しない URL は 404', async ({ page }) => {
  for (const url of ['/o/zzzzzzzz', '/o/k7Qm2xPz/a/999999', '/o/Wq8nRt2v/a/1', '/host/zzzzzzzz', '/nope']) {
    const res = await page.goto(url);
    expect(res.status(), url).toBe(404);
  }
});

test('締め切りのあとに送った回答は登録されず、理由を出す（N-09）', async ({ page }) => {
  // 結果選択待ちの大喜利（開発用データ）に、回答のフォームから送ったことにする
  await page.goto('/o/k7Qm2xPz');
  await page.locator('form[action$="/answers"]').evaluate((form) => { form.action = form.action.replace('k7Qm2xPz', 'Zp3cW9aQ'); });
  await page.fill('textarea[name=body]', '締め切り後のボケ');
  await page.click('[data-js-answer-submit]');
  await page.click('#answer-confirm button[type=submit]');
  await expect(page.getByRole('alert')).toHaveText('送信する前に募集が締め切られたため、回答は登録されませんでした。');
});

test('開催者でない人は、ベスト回答の確定や削除ができない', async ({ page }) => {
  await page.goto('/o/k7Qm2xPz');
  const token = await page.locator('input[name=csrf_token]').first().inputValue();
  for (const path of ['/host/Zp3cW9aQ/result', '/host/k7Qm2xPz/delete']) {
    const res = await page.request.post(path, { form: { csrf_token: token, 'ranks[]': '90412' }, maxRedirects: 0 });
    expect([302, 303, 403], path).toContain(res.status());
  }
  await page.goto('/o/k7Qm2xPz');
  await expect(page.locator('.c-tag', { hasText: '回答募集中' })).toBeVisible(); // 消えていない
});

test('間違った合言葉では開催者ページを開けない', async ({ page }) => {
  await page.goto('/host/Zp3cW9aQ');
  await page.fill('#passphrase', 'ちがうことば');
  await page.click('button:has-text("開催者ページを開く")');
  await expect(page.getByRole('alert')).toContainText('合言葉が違います');
});
