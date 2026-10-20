# DB 定義書

`app/Database/Migrations/` のマイグレーションが正（この文書はそこから書き起こしたもの。列を増やしたらここも直す）。ER 図は持たない：外部キー制約は張らず、`*_id` 列で参照する（共有サーバーでの運用と論理削除を優先。CODING_RULES 6章）。

共通：文字コード utf8mb4 / utf8mb4_general_ci、日時は JST の DATETIME（`BaseModel` が接続時に `time_zone='+09:00'`）、`created_at` / `updated_at` は Model が自動で入れる。
個人を特定しうる値（IP・匿名ブラウザID・開催者トークン）は `hash_hmac('sha256', 値, app.hashSecret)` の 64 文字で持ち、生の値は保存しない（ADR 0004）。

## テーブル一覧

| テーブル | 内容 | 仕様 |
|---|---|---|
| `oogiris` | 大喜利 | SPEC 3.3 oogiri |
| `answers` | 回答 | SPEC 3.3 answers |
| `reports` | 通報 | SPEC 3.3 reports、S-04 |
| `ng_words` | NGワード | SPEC 3.3 ng_words |
| `ads` | 広告 | SPEC 3.3 ads |
| `events` | 計測イベントの生ログ。日次で daily_stats に集計する | SPEC 3.3 events、S-06 |
| `inquiries` | お問い合わせ | SPEC 3.3 inquiries |
| `admins` | 管理者アカウント | SPEC 3.3 admins。A-01 で使う。ログイン試行回数は failed_count と locked_until |
| `settings` | 運用設定。キー名と初期値は SettingService が正 | SPEC 3.3 settings、A-10 |
| `admin_logs` | 操作履歴。お問い合わせの対応メモもここ | CODING_RULES 7-1：誰が・いつ・何をしたか |
| `ai_judgements` | AI補助判定のキューと結果。NGワード検知・異常投稿判定の記録もここに持つ | S-05 |
| `ogp_jobs` | OGP 画像の生成記録 | S-07、A-08 |
| `daily_stats` | 日次集計 | S-06。ダッシュボード A-02 はここだけを読む |
| `restrictions` | 投稿元の制限。until を過ぎたら無効 | S-03・A-05「投稿元を制限」 |

## oogiris

大喜利（SPEC 3.3 oogiri）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `slug` | VARCHAR(16) |  |  | 公開URL用（random_bytes から生成） |
| `odai` | VARCHAR(500) |  |  |  |
| `host_token_hash` | CHAR(64) |  |  | hash_hmac sha256（CODING_RULES 7章） |
| `passphrase_hash` | VARCHAR(255) | 可 |  | password_hash |
| `browser_id_hash` | CHAR(64) |  |  | 開催者の匿名ブラウザID |
| `ip_hash` | CHAR(64) |  |  |  |
| `status` | VARCHAR(24) |  | open | OogiriStatus |
| `status_before_hide` | VARCHAR(24) | 可 |  | 再公開時に戻す状態 |
| `fixable` | TINYINT(1) |  | 0 | 非表示が修正可能な違反か（U-13） |
| `starts_at` | DATETIME |  |  |  |
| `ends_at` | DATETIME |  |  |  |
| `selection_deadline` | DATETIME | 可 |  |  |
| `decided_at` | DATETIME | 可 |  |  |
| `pv` | INT unsigned |  | 0 |  |
| `answer_count` | INT unsigned |  | 0 |  |
| `share_count` | INT unsigned |  | 0 |  |
| `derived_count` | INT unsigned |  | 0 |  |
| `report_count` | INT unsigned |  | 0 |  |
| `parent_oogiri_id` | BIGINT unsigned | 可 |  |  |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、UNIQUE (slug)、INDEX (status)、INDEX (ends_at)、INDEX (selection_deadline)、INDEX (parent_oogiri_id)、INDEX (created_at)

## answers

回答（SPEC 3.3 answers）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `oogiri_id` | BIGINT unsigned |  |  |  |
| `body` | VARCHAR(1000) |  |  |  |
| `status` | VARCHAR(16) |  | visible | AnswerStatus |
| `rank` | TINYINT unsigned | 可 |  | 1〜3位 |
| `is_favorite` | TINYINT(1) |  | 0 |  |
| `browser_id_hash` | CHAR(64) |  |  |  |
| `ip_hash` | CHAR(64) |  |  |  |
| `report_count` | INT unsigned |  | 0 |  |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、INDEX (oogiri_id, status, created_at)、INDEX (oogiri_id, browser_id_hash)、INDEX (status)、INDEX (created_at)

## reports

通報（SPEC 3.3 reports、S-04）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `target_type` | VARCHAR(8) |  |  | ReportTarget |
| `target_id` | BIGINT unsigned |  |  |  |
| `reason` | VARCHAR(32) |  |  | 通報理由（選択式） |
| `browser_id_hash` | CHAR(64) |  |  |  |
| `ip_hash` | CHAR(64) |  |  |  |
| `resolved` | TINYINT(1) |  | 0 | 管理者が処理済みにしたか |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、INDEX (target_type, target_id)、INDEX (created_at)、INDEX (resolved)

## ng_words

NGワード（SPEC 3.3 ng_words）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `word` | VARCHAR(255) |  |  |  |
| `match_type` | VARCHAR(8) |  |  | NgWordMatchType |
| `category` | VARCHAR(32) |  |  |  |
| `action` | VARCHAR(8) |  |  | NgWordAction |
| `enabled` | TINYINT(1) |  | 1 |  |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、UNIQUE (word, match_type)、INDEX (enabled)

## ads

広告（SPEC 3.3 ads）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `name` | VARCHAR(100) |  |  |  |
| `sponsor` | VARCHAR(100) |  |  |  |
| `image` | VARCHAR(255) |  |  | public/ からの相対パス |
| `url` | VARCHAR(1000) |  |  |  |
| `slot` | VARCHAR(8) |  |  | AdSlot |
| `status` | VARCHAR(12) |  |  | AdStatus（draft / published / stopped。live・scheduled・ended は期間から判定） |
| `starts_at` | DATETIME |  |  |  |
| `ends_at` | DATETIME |  |  |  |
| `impressions` | INT unsigned |  | 0 |  |
| `clicks` | INT unsigned |  | 0 |  |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、INDEX (slot, status, starts_at, ends_at)

## events

計測イベントの生ログ（SPEC 3.3 events、S-06）。日次で daily_stats に集計する

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `name` | VARCHAR(32) |  |  | EventName |
| `oogiri_id` | BIGINT unsigned | 可 |  |  |
| `answer_id` | BIGINT unsigned | 可 |  |  |
| `parent_oogiri_id` | BIGINT unsigned | 可 |  | 派生元（開催・新規開催クリック） |
| `browser_id_hash` | CHAR(64) | 可 |  |  |
| `referer` | VARCHAR(255) | 可 |  |  |
| `utm` | VARCHAR(255) | 可 |  |  |
| `created_at` | DATETIME |  |  |  |

インデックス：PRIMARY (id)、INDEX (name, created_at)、INDEX (created_at)、INDEX (oogiri_id)

## inquiries

お問い合わせ（SPEC 3.3 inquiries）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `type` | VARCHAR(16) |  |  | InquiryType |
| `email` | VARCHAR(255) |  |  |  |
| `url` | VARCHAR(1000) | 可 |  |  |
| `body` | TEXT |  |  |  |
| `status` | VARCHAR(8) |  | todo | InquiryStatus |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、INDEX (status, created_at)、INDEX (type)

## admins

管理者アカウント（SPEC 3.3 admins。A-01 で使う。ログイン試行回数は failed_count と locked_until）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `login_id` | VARCHAR(64) |  |  |  |
| `name` | VARCHAR(64) |  |  |  |
| `password_hash` | VARCHAR(255) |  |  | bcrypt |
| `role` | VARCHAR(8) |  |  | AdminRole |
| `totp_secret` | VARCHAR(64) | 可 |  |  |
| `failed_count` | INT unsigned |  | 0 |  |
| `locked_until` | DATETIME | 可 |  |  |
| `last_login_at` | DATETIME | 可 |  |  |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、UNIQUE (login_id)

## settings

運用設定（SPEC 3.3 settings、A-10）。キー名と初期値は SettingService が正

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `name` | VARCHAR(32) |  |  |  |
| `value` | TEXT |  |  |  |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、UNIQUE (name)

## admin_logs

操作履歴（CODING_RULES 7-1：誰が・いつ・何をしたか）。お問い合わせの対応メモもここ

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `actor` | VARCHAR(64) |  |  | 管理者名、「開催者」、「自動」 |
| `admin_id` | BIGINT unsigned | 可 |  |  |
| `target_type` | VARCHAR(16) |  |  | oogiri / answer / inquiry / setting / ng_word / ad / ogp |
| `target_id` | BIGINT unsigned | 可 |  |  |
| `what` | VARCHAR(500) |  |  |  |
| `created_at` | DATETIME |  |  |  |

インデックス：PRIMARY (id)、INDEX (target_type, target_id, created_at)、INDEX (created_at)

## ai_judgements

AI補助判定のキューと結果（S-05）。NGワード検知・異常投稿判定の記録もここに持つ

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `target_type` | VARCHAR(8) |  |  |  |
| `target_id` | BIGINT unsigned |  |  |  |
| `trigger` | VARCHAR(16) |  |  | ng_review / reported / abnormal |
| `ng_detail` | VARCHAR(255) | 可 |  |  |
| `abnormal` | VARCHAR(255) | 可 |  |  |
| `verdict` | VARCHAR(8) |  | none | AiVerdict |
| `reason` | VARCHAR(500) | 可 |  |  |
| `queued` | TINYINT(1) |  | 1 | 1 = これから API に送る |
| `resolved` | TINYINT(1) |  | 0 | 管理者が処理済みにしたか |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、UNIQUE (target_type, target_id)、INDEX (queued, created_at)、INDEX (resolved)

## ogp_jobs

OGP 画像の生成記録（S-07、A-08）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `kind` | VARCHAR(8) |  |  | OgpKind |
| `target_id` | BIGINT unsigned |  |  |  |
| `target` | VARCHAR(255) |  |  | 表示用（例：「#12480 こんな…」「A-90412」） |
| `actor` | VARCHAR(64) |  |  |  |
| `succeeded` | TINYINT(1) |  | 0 |  |
| `error` | VARCHAR(255) | 可 |  |  |
| `created_at` | DATETIME |  |  |  |

インデックス：PRIMARY (id)、INDEX (created_at)

## daily_stats

日次集計（S-06。ダッシュボード A-02 はここだけを読む）

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `date` | DATE |  |  |  |
| `pv` | INT unsigned |  | 0 |  |
| `oogiris` | INT unsigned |  | 0 |  |
| `answers` | INT unsigned |  | 0 |  |
| `share_recruit` | INT unsigned |  | 0 |  |
| `share_answer` | INT unsigned |  | 0 |  |
| `share_result` | INT unsigned |  | 0 |  |
| `reports` | INT unsigned |  | 0 |  |
| `hidden` | INT unsigned |  | 0 |  |
| `answerers` | INT unsigned |  | 0 | 回答を完了したブラウザ数 |
| `converted` | INT unsigned |  | 0 | その後に開催したブラウザ数 |
| `derived` | INT unsigned |  | 0 | 派生元つきの開催数 |
| `created_at` | DATETIME | 可 |  |  |
| `updated_at` | DATETIME | 可 |  |  |

インデックス：PRIMARY (id)、UNIQUE (date)

## restrictions

投稿元の制限（S-03・A-05「投稿元を制限」）。until を過ぎたら無効

| 列 | 型 | NULL | 既定値 | 説明 |
|---|---|---|---|---|
| `id` | BIGINT unsigned |  | auto_increment |  |
| `browser_id_hash` | CHAR(64) | 可 |  |  |
| `ip_hash` | CHAR(64) | 可 |  |  |
| `until` | DATETIME |  |  |  |
| `reason` | VARCHAR(255) |  |  |  |
| `created_at` | DATETIME |  |  |  |

インデックス：PRIMARY (id)、INDEX (browser_id_hash, until)、INDEX (ip_hash, until)
