// E2E の共通操作
import { expect } from '@playwright/test';

/** トップから開催して、スラッグを返す（U-01 → U-02 → U-03） */
export async function createOogiri(page, { odai, passphrase }) {
  await page.goto('/');
  await page.fill('#odai', odai);
  await page.fill('#passphrase', passphrase);
  await page.click('[data-js-open-dialog="confirm-dialog"]');
  await expect(page.locator('#confirm-dialog')).toBeVisible();
  await page.click('#confirm-dialog button[type=submit]');
  await page.waitForURL(/\/o\/[A-Za-z0-9]{8}\/created$/);
  return page.url().match(/\/o\/([A-Za-z0-9]{8})\/created/)[1];
}

/** 募集 URL から回答する（U-04 → U-05 → U-06） */
export async function postAnswer(page, slug, body) {
  await page.goto(`/o/${slug}`);
  await page.fill('textarea[name=body]', body);
  await page.click('[data-js-answer-submit]');
  await expect(page.locator('#answer-confirm')).toBeVisible();
  await page.click('#answer-confirm button[type=submit]');
  await page.waitForURL(new RegExp(`/o/${slug}/done$`));
}

/** X の投稿画面の URL から本文を取り出す */
export function intentText(href) {
  return decodeURIComponent(new URL(href).searchParams.get('text') || '');
}

/** 同じブラウザの別の人（Cookie・localStorage が別） */
export async function anotherPerson(browser, testInfo) {
  const context = await browser.newContext({ ...testInfo.project.use, baseURL: testInfo.project.use.baseURL });
  return context.newPage();
}

export function uniq(label) {
  return `${label} ${Date.now().toString(36)}`;
}
