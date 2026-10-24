# E2E テスト（Playwright）

ブラウザで主要な流れを通す（ADR 0015）。PHPUnit（`composer check`）では見られない JS・モーダル・スマホ表示・計測を確かめる。

## 流し方（ローカル）

```sh
# 1. 開発用のサーバー（.env は development。DB は SQLite でも MariaDB でもよい）
php spark serve --port 8080

# 2. 別のターミナルで
cd tests/e2e
npm install                       # 初回だけ
npx playwright install chromium   # 初回だけ（インストール済みの Chromium を使うなら E2E_CHROMIUM=パス）
npm test                          # iPhone・Android・PC の画面で全部
npx playwright test --project=iphone specs/main-flow.spec.mjs   # 一部だけ
```

- 始める前に `php spark db:seed E2eSeeder` が走り、**DB の中身を開発用データに入れ直す**（本番・ステージングに向けて流さない）
- 別の URL に向けるときは `E2E_BASE_URL`。DB を入れ直さないときは `E2E_SKIP_SEED=1`
- 結果は `playwright-report/`（HTML）。失敗したテストはスクリーンショットとトレースが残る

## 中身

| ファイル | 確かめること |
|---|---|
| `specs/main-flow.spec.mjs` | 開催 → 合言葉 → 募集 URL のシェア → 別の人が回答 → みんなのボケ → X のシェア文 → シェア URL から開く → 別の端末で合言葉で開催者ページ |
| `specs/errors.spec.mjs` | 合言葉の誤り・短すぎ、連打（二重送信）、再読み込み、存在しない URL、締め切り後の投稿 |
| `specs/ui.spec.mjs` | 規約のシート、並び替え、共有シート、開催の一覧、横のはみ出し |
| `specs/tracking.spec.mjs` | GA4 のイベント（第9章）。gtag を差し替えて、送った名前とパラメータを見る |

実機（iPhone Safari・Android Chrome）と X 上のカードの表示は、このテストでは確かめられない（`docs/delivery/04_品質保証・テスト報告書.md` の手動の項目）。
