# 0001 フレームワーク本体を system/ にコミットする

- 日付：2026-10-20
- 状態：採用

## 決めたこと

CodeIgniter 4.6.1 の本体（`system/`）をリポジトリにコミットする。Composer は開発用ツール（php-cs-fixer、PHPStan、PHPUnit）にだけ使う。

## 理由

- 本番の Xserver に Composer を入れずに、ファイルを置くだけで動かせる
- フレームワークの版が `system/` の中身で固定され、全員が同じ版で動く

## 影響

- フレームワークを上げるときは、`system/` を丸ごと入れ替える単独の PR にする（CODING_RULES 1章）
- `composer.json` の `require` は PHP 拡張の宣言だけで、本体は含めない

## 補足

工程0の作業環境では packagist.org に接続できず、開発用ツールを入れられなかった。整形・静的解析・テストの設定ファイルは用意してあるが、**この時点では未実行**。工程1の最初に、Composer が使える環境で `composer install && composer check` を通すこと。
