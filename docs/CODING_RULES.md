# ぼけばこ コーディングルール

このリポジトリで作業するすべての開発者（人・AIを問わない）が守るルールです。外側（View・CSS・JS）と中身（PHP・DB）の両方に適用します。

## 0. このルールの読み方

- **必須**：例外なく守る。守れない場合は実装前に相談し、合意内容をこの文書に追記する。
- **推奨**：原則守る。外れる場合は理由をPRに書く。
- **禁止**：理由があっても行わない。
- ルールを変えるときは、この文書を修正するPRを出し、もう一人の承認を得てから変える。口頭やチャットだけの合意で運用を変えない。
- この文書と仕様書が食い違う場合は、仕様書を優先し、この文書を直す。

---

## 1. 環境とバージョン

- **必須**：PHP 8.2以上、CodeIgniter 4.x、MariaDB 10.5。CodeIgniterのバージョンは `composer.lock` で固定し、上げるときは単独のPRにする。
- **必須**：文字コードはUTF-8（BOMなし）、DBは `utf8mb4` / `utf8mb4_general_ci`。
- **必須**：改行コードはLF。インデントはスペース4つ。`.editorconfig` に従う。
- **必須**：タイムゾーンはアプリもDBも Asia/Tokyo に統一する。`Config\App::$appTimezone = 'Asia/Tokyo'`、DB接続時にセッションのタイムゾーンを `+09:00` にする。
- **必須**：環境ごとの値（DB接続、APIキー、管理画面パス、GA4測定ID、ハッシュ用の秘密鍵）はすべて `.env` に置く。`.env` はコミットしない。項目を追加したら `.env.example` にも追加する。
- **必須**：環境は本番（production）とステージング（staging）の2つ。ローカルは `development`。

---

## 2. ディレクトリ構成

```
app/
  Commands/           cron から実行する Spark コマンド（S-01〜S-07）
  Config/             設定。Routes.php もここ
  Controllers/
    Front/            ユーザー向け画面（U-xx）
    Host/             開催者専用画面（U-08・U-09）
    Admin/            管理画面（A-xx）
    Api/V1/           JSON を返す API
  Entities/           DB の1行を表すクラス
  Enums/              状態などの列挙（OogiriStatus など）
  Exceptions/         業務エラーの例外クラス
  Filters/            認証、IP制限、レート制限、セキュリティヘッダー
  Helpers/            View で使ってよい表示用ヘルパー（view_helper.php）
  Libraries/          外部サービスとの接続（OpenAI、OGP画像生成、CAPTCHA）
  Models/             DB アクセスのみ
  Services/           業務ロジック（外側フェーズではダミーを返す）
    Fixtures/         外側フェーズ用のダミーデータ
  Views/
    layouts/          front.php / admin.php
    partials/         ヘッダー、フッター、広告枠、モーダルなどの部品
    front/            U-xx の画面（1画面1ファイル）
    admin/            A-xx の画面
    errors/           エラーページ
  Database/
    Migrations/
    Seeds/
public/
  assets/
    css/              tokens.css / base.css / components/ / pages/
    js/               lib/ / components/ / pages/
    img/
  ogp/                生成した OGP 画像（コミットしない）
docs/                 仕様関連ドキュメント（この文書、FEATURES.md、ADR）
tests/
```

- **必須**：上記以外のフォルダを `app/` 直下に作らない。必要になったらこの文書に追記してから作る。
- **必須**：Xserverでは `public/` の中身だけを公開ディレクトリに置く。`app/`、`system/`、`writable/`、`.env` はWebから見えない場所に置く。

---

## 3. 層ごとの責務

処理の流れは次の一方向だけにする。

```
ルート → Filter → Controller → Service → Model → DB
                      ↓
                    View
```

| 層 | やること | やってはいけないこと |
|---|---|---|
| Controller | 入力の受け取り、Service の呼び出し、View またはリダイレクトを返す | 業務判断、DB アクセス、SQL、HTML の組み立て |
| Service | 業務ルール（締切判定、NG判定、状態遷移、レート制限の判断） | `$this->request` への直接アクセス、HTML の出力 |
| Model | DB の読み書き | 業務判断、他の Model の呼び出し |
| View | 受け取った値の表示 | DB・Service・Model の呼び出し、計算、日時の加工 |
| Filter | 認証、IP制限、ヘッダー付与など全画面共通の処理 | 画面固有の処理 |
| Command | cron の入口。Service を呼ぶだけ | 業務ロジックを直接書く |

- **必須**：Controller の1メソッドはおおむね30行以内に収める。超える場合は Service に移す。
- **必須**：Service は `app/Config/Services.php` に登録し、`service('oogiri')` のように取得する。Controller の中で `new` しない。
- **必須**：「締切を過ぎたか」「回答を受け付けてよいか」などの判定は、必ずサーバー側の Service で行う。JS の判定は表示の補助に限る。
- **禁止**：View から Model や Service を呼ぶこと。`view_cell` の中で DB を読むことも同様に禁止する。

---

## 4. 命名規則

| 対象 | 規則 | 例 |
|---|---|---|
| クラス | PascalCase。役割を接尾辞にする | `OogiriController`、`AnswerService`、`AnswerModel` |
| メソッド・変数・プロパティ | camelCase | `findBySlug()`、`$answerCount` |
| 定数・Enum の case | UPPER_SNAKE / PascalCase | `MAX_RETRY` / `OogiriStatus::AwaitingSelection` |
| DB のテーブル・カラム | snake_case。テーブル名は複数形 | `oogiris`、`answers`、`ends_at` |
| 状態の値（DBに入る文字列） | snake_case の英語 | `open`、`awaiting_selection`、`decided` |
| URL | 小文字とハイフン | `/o/{slug}/answers` |
| View ファイル | snake_case。画面IDを先頭につける | `front/u04_answer.php`、`admin/a03_oogiri_list.php` |
| 部品の View | snake_case | `partials/ad_slot.php`、`partials/modal_confirm.php` |
| CSS クラス | 5章の接頭辞＋BEM | `c-button--primary`、`p-answer__timer` |
| JS ファイル | kebab-case | `char-counter.js`、`host-select.js` |
| JS から触る要素の目印 | `data-js-` 属性 | `data-js-char-counter` |

- **必須**：コード上の用語は下の表に統一する。同じものを別の名前で呼ばない。

| 日本語 | コード上の名前 |
|---|---|
| 大喜利（1回の開催） | `oogiri` |
| お題 | `odai` |
| 回答（ボケ） | `answer`（本文は `body`） |
| 開催者 | `host` |
| 合言葉 | `passphrase` |
| 開催者トークン | `hostToken` / `host_token_hash` |
| 匿名ブラウザID | `browserId` / `browser_id_hash` |
| 派生元大喜利 | `parentOogiri` / `parent_oogiri_id` |
| 通報 | `report` |
| 選出期限 | `selectionDeadline` / `selection_deadline` |

---

## 5. PHP の書き方

- **必須**：コードスタイルは PSR-12 に加え、`codeigniter/coding-standard`（php-cs-fixer）の設定に従う。コミット前に自動整形をかける。
- **必須**：`app/` の自作ファイルは先頭に `declare(strict_types=1);` を書く。
- **必須**：引数、戻り値、プロパティには型を書く。`mixed` は外部入力を受け取る箇所だけに使う。
- **必須**：PHPStan（レベル6）でエラー0件にする。
- **必須**：数値や文言を直接書かない。文字数上限、時間、閾値は運用設定（A-10）から読む。コード内で固定する値はクラス定数にする。
- **必須**：状態は `app/Enums/` の Enum で扱う。文字列を直接比較しない（`$status === 'open'` は書かない）。
- **必須**：入力は `$this->request->getPost()` / `getGet()` / `getJSON()` から取る。
- **必須**：業務エラーは `app/Exceptions/` の例外で投げ、Controller で受けて画面に表示する。エラーを `false` や `null` の戻り値で表さない。
- **推奨**：早期 return で入れ子を浅くする。入れ子は3段まで。
- **禁止**：`$_GET`、`$_POST`、`$_COOKIE`、`$_SERVER` の直接参照。
- **禁止**：`@` によるエラー抑制、`eval`、`extract`、グローバル変数。
- **禁止**：`var_dump`、`print_r`、`dd`、`die` をコミットに含めること。

---

## 6. データベース

- **必須**：テーブルの変更はすべてマイグレーションで行う。phpMyAdmin などで直接変更しない。一度マージしたマイグレーションは書き換えず、新しいマイグレーションを追加する。
- **必須**：SQL は Query Builder で書く。生 SQL が必要な場合はバインド（`?`）を使い、PRでその理由を説明する。
- **必須**：全テーブルに `id`（BIGINT UNSIGNED、自動採番）、`created_at`、`updated_at` を持たせる。
- **必須**：回答数・PVなどのカウンタは `SET answer_count = answer_count + 1` の形で更新する。読み出してから加算して書き戻さない。
- **必須**：複数テーブルを更新する処理はトランザクションで囲む（大喜利の作成、結果確定、削除など）。
- **必須**：検索条件や並び替えに使うカラム（`slug`、`status`、`ends_at`、`selection_deadline`、`oogiri_id`）にはインデックスを張る。
- **必須**：一覧取得は必ず件数制限（LIMIT）をつける。上限なしで全件を取る処理は書かない。
- **禁止**：ループの中でクエリを発行すること（N+1）。まとめて取得する。
- **禁止**：IP、開催者トークン、合言葉、ブラウザIDを平文で保存すること。

---

## 7. セキュリティ（必須事項）

- **必須**：View で変数を出すときは必ず `esc()` を通す（`<?= esc($odai) ?>`）。属性値の中は `esc($x, 'attr')`、JS に渡す値は `esc($x, 'js')` を使う。
- **必須**：CSRF フィルタを全体に適用する。フォームには `<?= csrf_field() ?>`、JS からの POST はヘッダーでトークンを送る。
- **必須**：入力チェックはサーバー側の Validation で行う。JS のチェックだけで済ませない。
- **必須**：ハッシュ化の方法は次のとおりにする。
  - 合言葉：`password_hash()` / `password_verify()`
  - 開催者トークン、IP、ブラウザID：`.env` の秘密鍵を使った `hash_hmac('sha256', ...)`
- **必須**：推測困難な値（スラッグ、トークン）は `random_bytes()` から作る。`rand()` や `uniqid()` は使わない。
- **必須**：管理画面のルートはすべて認証 Filter の内側に置く。権限が必要な操作は Service でも権限を確認する。
- **必須**：レート制限は CodeIgniter の Throttler を使い、キーは「操作の種類＋ブラウザIDのハッシュ＋IPのハッシュ」で作る。
- **必須**：ログにトークン、合言葉、APIキー、生IP、メールアドレスを出さない。
- **禁止**：ユーザーの入力をそのまま `header()`、リダイレクト先、ファイルパス、正規表現に使うこと。

### 7-1. ログイン画面ができるまでの管理画面

A-01（管理者ログイン）は最終工程の近くで実装する。それまでは次のルールで管理画面を開発する。

- **必須**：管理画面のルートは、最初から `adminAuth` Filter のグループに入れる。ログイン画面ができたときに変えるのは Filter の中身だけで、ルートや Controller は変えない。
- **必須**：仮の `adminAuth` Filter は環境ごとに次のとおり動かす。

| 環境 | 動作 |
|---|---|
| development | 固定の仮管理者（ID `dev-admin`、権限は上位管理者）でログイン済みとして扱う |
| staging | `.env` の `ADMIN_ALLOWED_IPS` に含まれるIPからのアクセスだけ、仮管理者として扱う。それ以外は403 |
| production | 常に403。仮管理者は使えない |

- **必須**：判定に迷う場合（環境名が読めない、設定がないなど）は拒否する。許可する方向に倒さない。
- **必須**：Controller と Service は、Filter が用意した「現在の管理者」オブジェクトから管理者ID・権限を取得する。仮管理者でも本物の管理者でも同じ形で受け取れるようにし、権限チェックと操作履歴（誰が、いつ、何をしたか）は最初から実装する。
- **必須**：仮認証の箇所には `// TODO(inner): A-01 実装時に本認証へ置き換える` をつける。
- **必須**：production で管理画面が403になることを確認するテストを書く。
- **禁止**：A-01 と二段階認証が完成し、仮認証のコードを削除する前に、本番環境へ管理画面を公開すること。

---

## 8. View（外側）のルール

### 8-1. 構成

- **必須**：すべての画面は `layouts/front.php` または `layouts/admin.php` を継承する（`<?= $this->extend('layouts/front') ?>`）。
- **必須**：1画面につき View は1ファイル。モーダル（U-02・U-05・U-09）は呼び出し元の画面ファイルに `partials/` の部品として含める。
- **必須**：2画面以上で使う表示は `partials/` に切り出し、`$this->include()` で読み込む。ヘッダー、フッター、広告枠、お題カード、残り時間バー、回答カード、確認モーダル、通報モーダルは最初から部品にする。
- **必須**：title、description、OGP は `meta` セクションで各画面から渡す。レイアウト側にページ固有の値を書かない。

### 8-2. View の中で書いてよいもの

- **必須**：View で使ってよい PHP は次の範囲に限る。
  - `esc()` での出力
  - `if` / `foreach` の制御構文（`if (): ... endif;` の形）
  - `$this->extend` / `section` / `include`
  - `site_url()` / `base_url()` / `csrf_field()`
  - 表示用ヘルパー（`app/Helpers/view_helper.php` に定義したものだけ）
- **必須**：View ファイルの先頭に、受け取る変数の一覧をコメントで書く。これを「View 契約」と呼び、外側と中身の受け渡しの取り決めにする。

```php
<?php
/**
 * U-04 大喜利回答画面
 *
 * @var array{slug:string, odai:string, endsAt:string, answerCount:int} $oogiri
 * @var string   $remaining        表示用の残り時間（例：「18時間32分」）
 * @var int      $progressPercent  残り時間バーの幅（0〜100）
 * @var int      $answerMax        回答の最大文字数
 * @var string[] $errors
 */
?>
```

- **必須**：日時の整形、残り時間の計算、URL の組み立ては Controller か Service で済ませ、完成した文字列を View に渡す。
- **禁止**：View の中での計算、日時関数（`date()`、`strtotime()`）、DB アクセス、`<?php echo` の長い処理ブロック。
- **禁止**：`style="..."` 属性と `<style>` タグ。スタイルはすべて CSS ファイルに書く。
- **禁止**：`onclick="..."` などのインラインイベントと、View 内の `<script>` での処理。JS はすべて外部ファイルに書く。唯一の例外は、JS に初期値を渡すための `<script type="application/json" data-js-config>` だけとする。

### 8-3. HTML とアクセシビリティ

- **必須**：押せるものは `<button>` か `<a href>` で作る。`<div>` や `<span>` にクリック処理をつけない。
- **必須**：入力欄には必ず `<label for>` を対応させる。アイコンだけのボタンには `aria-label` をつける。
- **必須**：タップ領域は44×44px以上。主ボタンの高さは56〜58px。
- **必須**：文字と背景のコントラストは4.5:1以上（24px以上の文字は3:1以上）。スタイルガイドのトークン以外の色を使わない。
- **必須**：見出しは `h1` を1画面に1つ。階層を飛ばさない。
- **必須**：`<img>` には `width`、`height`、`alt` を書く。飾りの画像は `alt=""`。
- **必須**：JS が動かなくても、開催・回答・お問い合わせの送信はできるようにする（フォームは通常の POST で送れる作りにする）。

---

## 9. CSS と JS（外側）のルール

### 9-1. CSS

- **必須**：自前の CSS で書く。ビルド工程は持たない（Tailwind は採用しない。変える場合はこの文書を改定する）。
- **必須**：色・書体・角丸・影・余白は `tokens.css` の CSS 変数だけを使う。値を直接書かない。

```css
:root {
  --paper: #F4EEE2;  --card: #FFFDF8;  --ink: #1D1A16;  --ink-sub: #4A433B;
  --shu: #D6401E;    --shu-deep: #A93115;  --kin: #F2C230;  --line: #D9CFBE;
  --font-display: "Dela Gothic One", "Hiragino Sans", sans-serif;
  --font-body: "Zen Kaku Gothic New", "Hiragino Sans", "Yu Gothic", sans-serif;
  --radius-input: 12px; --radius-button: 14px; --radius-card: 18px; --radius-sheet: 24px;
  --border: 2px solid var(--ink);
  --shadow-press: 0 4px 0 var(--ink);
  --space-1: 8px; --space-2: 12px; --space-3: 18px; --space-4: 24px; --gutter: 20px;
}
```

- **必須**：クラス名は次の接頭辞＋BEM（`block__element--modifier`）にする。

| 接頭辞 | 用途 | 置き場所 |
|---|---|---|
| `l-` | レイアウト（ヘッダー、フッター、本文幅） | `css/base.css` |
| `c-` | 使い回す部品（ボタン、カード、モーダル、広告枠） | `css/components/` 1部品1ファイル |
| `p-` | 画面固有 | `css/pages/` 1画面1ファイル（`u04-answer.css` など） |
| `u-` | 単機能の補助（非表示、余白調整） | `css/base.css` |
| `is-` | 状態（選択中、無効、開いている） | 各部品の中 |

- **必須**：スマホを基準に書き、PC 向けは `@media (min-width: 768px)` で上書きする。ブレークポイントはこの1つだけ。
- **必須**：`!important` は `u-` クラスの中でだけ使ってよい。
- **禁止**：ID セレクタでのスタイル指定、タグ名だけのセレクタ（`base.css` のリセットを除く）、4段以上の子孫セレクタ。
- **禁止**：`c-` の部品の中で余白（外側の margin）を決めること。置く側（`l-` か `p-`）で決める。

### 9-2. JavaScript

- **必須**：素の JavaScript（ES2020）で書き、`<script type="module">` で読み込む。jQuery などのライブラリは入れない。
- **必須**：1機能1ファイルにする。要素の取得は `data-js-*` 属性だけで行い、CSS 用のクラス名を JS から参照しない。
- **必須**：各ファイルは初期化関数を1つ export し、`public/assets/js/main.js` からまとめて呼ぶ。

```js
// public/assets/js/components/char-counter.js
export function initCharCounter(root = document) {
  root.querySelectorAll('[data-js-char-counter]').forEach((input) => {
    // ...
  });
}
```

- **必須**：サーバーとの通信は `js/lib/api.js` の共通関数だけを使う。この関数が CSRF トークンの付与とエラー処理を担う。
- **必須**：ブラウザに保存するキーは次に限る。増やす場合はこの表に追記する。

| 保存先 | キー | 中身 |
|---|---|---|
| localStorage | `bk:host:{slug}` | 開催者トークン |
| localStorage | `bk:answered:{slug}` | 回答済みの印 |
| Cookie（サーバー発行） | `bk_bid` | 匿名ブラウザID |

- **必須**：選択状態などの見た目の切り替えは `is-` クラスの付け外しで行い、JS から `style` を直接書き換えない。
- **禁止**：グローバル変数（`window.xxx`）、`document.write`、`innerHTML` へのユーザー入力の代入（`textContent` を使う）。

---

## 10. 外側と中身の分担

外側を先に完成させ、あとから中身を差し込む。二人が同時に作業しても壊れないように、次の取り決めを守る。

- **必須**：Controller は最初から本番と同じ形で書く。外側フェーズでは、Service のメソッドが `app/Services/Fixtures/` のダミーデータを返す。

```php
// 外側フェーズ
public function findOpenBySlug(string $slug): array
{
    // TODO(inner): DB から取得する。戻り値の形は変えない。
    return Fixtures\OogiriFixture::open();
}
```

- **必須**：外側で仮実装した箇所には必ず `// TODO(inner):` をつける。中身の担当はこの文字列を検索して作業する。
- **必須**：URL のクエリ `?fixture=` でダミーの状態を切り替えられるようにする（例：`/o/demo?fixture=decided`）。この切り替えは `development` 環境でだけ有効にする。
- **必須**：View 契約（8-2）が外側と中身の境界になる。変更する場合は、先に View ファイル先頭のコメントを直すPRを出して合意を取る。
- **禁止**：中身の担当が View・CSS・JS を、外側の担当が Service の本実装・Model を、相手に断らずに変更すること。修正が必要ならPRで依頼する。

---

## 11. API

- **必須**：API は `/api/v1/` の下に置き、JSON を返す。
- **必須**：レスポンスの形は次の2つに統一する。

```json
{ "ok": true,  "data": { } }
{ "ok": false, "error": { "code": "RATE_LIMITED", "message": "少し時間をおいてから投稿してください" } }
```

- **必須**：HTTP ステータスは 200（成功）、400（入力エラー）、403（権限なし）、404（対象なし）、409（状態が合わない。例：締切後の回答）、429（レート制限）、500（サーバーエラー）を使い分ける。
- **必須**：エラーコードは `app/Enums/ErrorCode.php` に定義し、そこにないコードは返さない。

---

## 12. Git の運用

- **必須**：ブランチは `main`（本番）、`develop`（ステージング）、作業用 `feature/` の3種類にする。
- **必須**：作業ブランチの名前は `feature/{画面ID}-{内容}` にする（例：`feature/U04-answer-form`）。
- **必須**：コミットメッセージは `[画面ID] 内容` の形で、日本語で書く（例：`[U-04] 回答フォームに文字数カウンターを追加`）。画面に紐づかない変更は `[共通]`、`[DB]`、`[docs]` を使う。
- **必須**：`main` と `develop` への直接 push は禁止。PR を出し、もう一人のレビュー承認を得てからマージする。
- **必須**：PR には次を書く。
  - 対応した画面ID・機能ID（FEATURES.md の項目）
  - 変更内容
  - 確認した方法
  - 画面を変えた場合はスマホ幅のスクリーンショット
- **必須**：1つの PR は1つの目的にする。整形だけの変更と機能追加を混ぜない。

---

## 13. テスト

- **必須**：Service の業務ルールには PHPUnit のテストを書く。特に次の処理は、テストなしでマージしない。
  - 状態遷移（S-01・S-02）
  - 締切判定
  - 結果確定（1〜3件、確定済みへの再送信）
  - NGワード判定
  - レート制限
  - 開催者認証
- **必須**：各 URL に対して、ステータスコードと表示される View を確認する Feature テストを書く（外側フェーズではダミーデータで通す）。
- **必須**：PR の前に、php-cs-fixer、PHPStan、PHPUnit がすべて通ることを確認する。

---

## 14. コメントとドキュメント

- **必須**：コメントは日本語で書く。「何をしているか」ではなく「なぜそうしているか」を書く。
- **必須**：public メソッドには PHPDoc を書く。仕様書の項目に対応する処理には、仕様書の ID を書く（例：`// 仕様 S-04：同一環境からの連続通報は1件として数える`）。
- **必須**：設計上の判断（ライブラリの選定、仕様の解釈、未確定事項の決定）は `docs/adr/` に記録を残す（`0001-captcha-service.md` のように連番をつける）。
- **必須**：FEATURES.md の「未確定事項」が決まったら、その一覧から消し、本文と ADR に反映する。

---

## 15. 完了の条件（Definition of Done）

次をすべて満たしたときだけ「完了」とする。

- [ ] FEATURES.md の該当項目がすべて実装されている
- [ ] View 契約のコメントと、実際に渡している値が一致している
- [ ] スマホ幅（390px）とPC幅（1280px）で表示が崩れない
- [ ] キーボード操作だけで主要な操作ができる
- [ ] JS を切っても送信系の操作ができる
- [ ] php-cs-fixer、PHPStan、PHPUnit がすべて通る
- [ ] `// TODO(inner):` が残っている場合、中身の担当に伝わる形で PR に書いてある
- [ ] もう一人のレビュー承認を得ている
