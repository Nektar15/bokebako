// 必須の流れ（指示書 第6章）：開催 → 合言葉 → 募集 URL のシェア → 別の人がアクセス → 回答 → 回答の表示 → X のシェア → シェア URL から開く
// このサービスの合言葉は「開催者が別の端末で開催者ページを開くため」のもの（回答には使わない）。最後に別の端末から合言葉で開く
import { test, expect } from '@playwright/test';
import { createOogiri, postAnswer, intentText, anotherPerson, uniq } from './helpers.mjs';

test('開催から回答・シェア・シェア URL・別の端末の開催者ページまで', async ({ page, browser }, testInfo) => {
  const odai = uniq('E2E のお題');
  const passphrase = '　ぼけ合言葉ＡＢＣ　'; // 前後の全角スペースと全角英字（整えてから照合する）

  // 1. 開催 → U-03
  const slug = await createOogiri(page, { odai, passphrase });
  const recruit = page.locator('a.c-button--x', { hasText: 'Xで回答を募集する' });
  await expect(recruit).toBeVisible();
  const recruitText = intentText(await recruit.getAttribute('href'));
  expect(recruitText).toContain(`お題：${odai}`);
  expect(recruitText).toContain(`/o/${slug}`);
  await expect(page.locator('#share-url')).toHaveValue(new RegExp(`/o/${slug}$`));

  // 2. 別の人が募集 URL を開いて回答 → U-06
  const other = await anotherPerson(browser, testInfo);
  await postAnswer(other, slug, '別の人のボケです');
  const ownShare = other.locator('a.c-button--x', { hasText: 'この回答をXでシェアする' });
  await expect(ownShare).toBeVisible();
  const ownText = intentText(await ownShare.getAttribute('href'));
  expect(ownText).toContain('俺の回答：別の人のボケです');
  const answerUrl = ownText.match(/https?:\/\/\S+\/o\/\S+\/a\/\d+/)[0];

  // 3. 開催者も回答し、みんなのボケで他人の回答のシェア文を確かめる（BUG-008）
  await postAnswer(page, slug, '開催者のボケ');
  await page.goto(`/o/${slug}/answers`);
  const items = page.locator('.c-answers__item');
  await expect(items).toHaveCount(2);
  const othersItem = items.filter({ hasText: '別の人のボケです' });
  expect(intentText(await othersItem.locator('a.c-iconbtn').getAttribute('href'))).toContain('このボケ好き：別の人のボケです');
  const mineItem = items.filter({ hasText: '開催者のボケ' });
  await expect(mineItem.locator('.c-answers__mine')).toBeVisible();
  expect(intentText(await mineItem.locator('a.c-iconbtn').getAttribute('href'))).toContain('俺の回答：開催者のボケ');

  // 4. シェア URL から、3人目が開く（再読み込みも）
  const third = await anotherPerson(browser, testInfo);
  const res = await third.goto(answerUrl);
  expect(res.status()).toBe(200);
  await expect(third.locator('.p-answer__shared-body')).toHaveText('別の人のボケです');
  expect(await third.locator('meta[property="og:description"]').getAttribute('content')).toBe('別の人のボケです');
  expect((await third.reload()).status()).toBe(200);

  // 5. 別の端末で、合言葉（前後の空白なし・半角）で開催者ページを開く
  const device = await anotherPerson(browser, testInfo);
  await device.goto(`/host/${slug}`);
  await device.fill('#passphrase', 'ぼけ合言葉ABC');
  await device.click('button:has-text("開催者ページを開く")');
  await device.waitForURL(new RegExp(`/host/${slug}$`));
  await expect(device.locator('a.c-button--x', { hasText: 'Xで再募集する' })).toBeVisible();
  await expect(device.locator('.p-host__body', { hasText: '別の人のボケです' })).toBeVisible();
});
