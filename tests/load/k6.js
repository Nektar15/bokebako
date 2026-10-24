/**
 * 負荷試験（SPEC 4.2）：回答画面・回答投稿・結果ページに毎秒20リクエストずつ
 *
 * 使い方（ステージングに対して。DevSeeder を流した状態で）：
 *   k6 run -e BASE=https://staging.example.com tests/load/k6.js
 * 結果は docs/load-test.md に記録する。
 */
import http from 'k6/http';
import { check } from 'k6';
import { parseHTML } from 'k6/html';

const BASE = __ENV.BASE || 'http://localhost:8080';
const OPEN_SLUG = __ENV.OPEN_SLUG || 'k7Qm2xPz';     // 募集中
const RESULT_SLUG = __ENV.RESULT_SLUG || 'q8Lr4nTb'; // 確定済み（ページキャッシュ配信）
const RATE = Number(__ENV.RATE || 20);
const DURATION = __ENV.DURATION || '30s';

export const options = {
  scenarios: {
    answer_page: { executor: 'constant-arrival-rate', exec: 'answerPage', rate: RATE, timeUnit: '1s', duration: DURATION, preAllocatedVUs: 40, maxVUs: 200 },
    answer_post: { executor: 'constant-arrival-rate', exec: 'answerPost', rate: RATE, timeUnit: '1s', duration: DURATION, preAllocatedVUs: 300, maxVUs: 900 },
    result_page: { executor: 'constant-arrival-rate', exec: 'resultPage', rate: RATE, timeUnit: '1s', duration: DURATION, preAllocatedVUs: 40, maxVUs: 200 },
    // 2026-10-24 に足した画面・API（ADR 0015・0016）：トップ＋開催の一覧 API、みんなのボケ（古い順）、サイトマップ
    added: { executor: 'constant-arrival-rate', exec: 'added', rate: RATE, timeUnit: '1s', duration: DURATION, preAllocatedVUs: 40, maxVUs: 200 },
  },
  thresholds: {
    'http_req_duration{scenario:answer_page}': ['p(95)<2000'],
    'http_req_duration{scenario:answer_post}': ['p(95)<2000'],
    'http_req_duration{scenario:result_page}': ['p(95)<2000'],
    'http_req_duration{scenario:added}': ['p(95)<2000'],
    http_req_failed: ['rate<0.01'],
  },
};

export function answerPage() {
  const res = http.get(`${BASE}/o/${OPEN_SLUG}`, { tags: { name: 'U-04' } });
  check(res, { 'U-04 200': (r) => r.status === 200 });
  const st = http.get(`${BASE}/api/v1/o/${OPEN_SLUG}/status`, { tags: { name: 'status api' } });
  check(st, { 'status 200': (r) => r.status === 200 });
}

export function answerPost() {
  // 反復ごとに新しいブラウザとして振る舞う（匿名ブラウザIDを捨てる＝レート制限のキーを変える）
  http.cookieJar().clear(BASE);
  const page = http.get(`${BASE}/o/${OPEN_SLUG}`, { tags: { name: 'U-04 (for token)' } });
  const token = parseHTML(page.body).find('input[name="csrf_token"]').first().attr('value');
  const res = http.post(`${BASE}/o/${OPEN_SLUG}/answers`, { csrf_token: token, body: `負荷試験のボケ ${__VU}-${__ITER}` }, { redirects: 0, tags: { name: 'POST answers' } });
  check(res, { 'POST → 303 done': (r) => r.status === 303 && String(r.headers.Location || '').endsWith('/done') });
}

export function resultPage() {
  const res = http.get(`${BASE}/o/${RESULT_SLUG}`, { tags: { name: 'U-10' } });
  check(res, { 'U-10 200': (r) => r.status === 200 });
}

export function added() {
  const top = http.get(`${BASE}/`, { tags: { name: 'U-01' } });
  check(top, { 'U-01 200': (r) => r.status === 200 });
  const hosted = http.post(`${BASE}/api/v1/hosted`, JSON.stringify({ items: [{ slug: OPEN_SLUG, token: `dev-token-${OPEN_SLUG}` }] }), { headers: { 'Content-Type': 'application/json' }, tags: { name: 'hosted api' } });
  check(hosted, { 'hosted 200': (r) => r.status === 200 });
  const list = http.get(`${BASE}/sitemap.xml`, { tags: { name: 'sitemap' } });
  check(list, { 'sitemap 200': (r) => r.status === 200 });
}
