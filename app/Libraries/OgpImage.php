<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Enums\OgpKind;
use GdImage;
use RuntimeException;

/**
 * OGP 画像の生成（S-07）。1200×630、PHP GD、日本語フォントはサーバーに置いたものを使う
 *
 * 文字数に合わせて折り返し、入り切らなければ文字を小さくする。色は tokens.css と同じ値。
 * フォント：.env の ogp.font（既定 writable/fonts/NotoSansJP-Bold.ttf）。背景・ロゴは writable/ogp-templates/{kind}-background.png / logo.png があれば使う。
 */
final class OgpImage
{
    public const WIDTH  = 1200;
    public const HEIGHT = 630;
    private const PAPER = [0xF4, 0xEE, 0xE2];
    private const INK   = [0x1D, 0x1A, 0x16];
    private const SUB   = [0x4A, 0x43, 0x3B];
    private const SHU   = [0xD6, 0x40, 0x1E];
    private const KIN   = [0xF2, 0xC2, 0x30];
    private const CARD  = [0xFF, 0xFD, 0xF8];

    /**
     * 行頭に置かない文字（句読点・閉じかっこ・長音・小書きの仮名など）
     */
    private const NO_LINE_START = '、。，．,.）)」』】〕〉》！？!?ー〜…‥・ぁぃぅぇぉっゃゅょゎァィゥェォッャュョヮヵヶ';

    private string $font;

    public function __construct(?string $font = null)
    {
        $font ??= (string) env('ogp.font', '');
        $this->font = $font !== '' ? $font : WRITEPATH . 'fonts/NotoSansJP-Bold.ttf';
    }

    /**
     * 生成に必要なもの（GD・フォント）が揃っているか
     */
    public function isAvailable(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagettftext') && is_file($this->font);
    }

    /**
     * 募集用：ロゴ、お題、「24時間限定」
     */
    public function recruit(string $odai): GdImage
    {
        $img = $this->canvas(OgpKind::Recruit);
        $this->brand($img);
        $this->pill($img, self::WIDTH - 80, 56, '24時間限定 回答募集中');
        $this->paragraph($img, $odai, 80, 200, 1040, 330, 64, 36, self::INK);
        imagefilledrectangle($img, 0, self::HEIGHT - 18, self::WIDTH, self::HEIGHT, $this->color($img, self::SHU));

        return $img;
    }

    /**
     * 回答シェア用：お題、回答、ロゴ
     */
    public function answer(string $odai, string $answer): GdImage
    {
        $img = $this->canvas(OgpKind::Answer);
        $this->text($img, $this->fit('お題：' . $odai, 30, self::WIDTH - 160), 80, 100, 30, self::SUB);
        // 山吹のカードに回答
        imagefilledrectangle($img, 80, 150, 1120, 480, $this->color($img, self::KIN));
        imagerectangle($img, 80, 150, 1120, 480, $this->color($img, self::INK));
        $this->paragraph($img, $answer, 120, 200, 960, 250, 56, 32, self::INK);
        $this->brand($img, 80, 560);

        return $img;
    }

    /**
     * 結果用：お題、1〜3位の回答、回答数、ロゴ、「結果発表」（ADR 0011）
     *
     * 1位だけなら大きく1件。2位・3位があれば、1位を2行まで、2位・3位を1行ずつ（入り切らなければ「…」）
     *
     * @param list<array{rank: int, body: string}> $ranked 順位の若い順（空なら「選出なし」）
     */
    public function result(string $odai, array $ranked, int $answerCount): GdImage
    {
        $img = $this->canvas(OgpKind::Result);
        $this->brand($img);
        $this->stamp($img, self::WIDTH - 140, 80, '結果発表');
        $this->text($img, $this->fit('お題：' . $odai, 30, self::WIDTH - 160), 80, 190, 30, self::SUB);

        if ($ranked === []) {
            $this->paragraph($img, '（選出なし）', 170, 270, 950, 220, 60, 34, self::SUB);
        } elseif (count($ranked) === 1) {
            $this->circle($img, 110, 300, 30, self::SHU, $ranked[0]['rank'] . '位', 22, self::CARD);
            $this->paragraph($img, $ranked[0]['body'], 170, 270, 950, 220, 60, 34, self::INK);
        } else {
            $first = array_shift($ranked);
            $this->circle($img, 110, 268, 30, self::SHU, $first['rank'] . '位', 22, self::CARD);
            $this->paragraph($img, $first['body'], 170, 238, 950, 132, 44, 30, self::INK);
            imageline($img, 80, 388, self::WIDTH - 80, 388, $this->color($img, self::INK));

            foreach ($ranked as $i => $row) {
                $cy = 432 + $i * 64;
                $this->circle($img, 110, $cy, 24, self::INK, $row['rank'] . '位', 17, self::CARD);
                $this->text($img, $this->fit(str_replace(["\r\n", "\r", "\n"], ' ', $row['body']), 30, 950), 160, $cy + 11, 30, self::INK);
            }
        }
        $this->text($img, "全{$answerCount}回答", 80, 590, 26, self::INK);

        return $img;
    }

    /**
     * PNG として保存する（ディレクトリがなければ作る）
     */
    public function save(GdImage $img, string $path): void
    {
        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("ディレクトリを作れません: {$dir}");
        }
        if (! imagepng($img, $path, 6)) {
            throw new RuntimeException("画像を保存できません: {$path}");
        }
        imagedestroy($img);
    }

    private function canvas(OgpKind $kind): GdImage
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('OGP 画像を生成できません（GD かフォントがありません）: ' . $this->font);
        }
        $background = WRITEPATH . 'ogp-templates/' . $kind->value . '-background.png';

        if (is_file($background)) {
            $bg = imagecreatefrompng($background);

            if ($bg !== false) {
                $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
                imagecopyresampled($img, $bg, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, imagesx($bg), imagesy($bg));
                imagedestroy($bg);

                return $img;
            }
        }
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagefill($img, 0, 0, $this->color($img, self::PAPER));

        return $img;
    }

    /**
     * ロゴとサービス名。writable/ogp-templates/logo.png があればそれを使う
     */
    private function brand(GdImage $img, int $x = 80, int $y = 56): void
    {
        $logo = WRITEPATH . 'ogp-templates/logo.png';

        if (is_file($logo) && ($png = imagecreatefrompng($logo)) !== false) {
            imagecopyresampled($img, $png, $x, $y, 0, 0, 48, 48, imagesx($png), imagesy($png));
            imagedestroy($png);
        } else {
            // 紙が朱色の箱に入るロゴの簡略形
            imagefilledrectangle($img, $x + 4, $y + 18, $x + 44, $y + 46, $this->color($img, self::SHU));
            imagerectangle($img, $x + 4, $y + 18, $x + 44, $y + 46, $this->color($img, self::INK));
            imagefilledrectangle($img, $x + 16, $y + 4, $x + 32, $y + 22, $this->color($img, self::KIN));
        }
        $this->text($img, 'ぼけばこ', $x + 60, $y + 40, 30, self::INK);
    }

    /**
     * @param array{int, int, int} $rgb
     */
    private function color(GdImage $img, array $rgb): int
    {
        return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * @param array{int, int, int} $rgb
     */
    private function text(GdImage $img, string $text, int $x, int $baseline, int $size, array $rgb): void
    {
        imagettftext($img, $size, 0, $x, $baseline, $this->color($img, $rgb), $this->font, $text);
    }

    /**
     * 文字幅に合わせた札。右端を $right にそろえる（文字が枠に触れないよう左右に余白をとる）
     */
    private function pill(GdImage $img, int $right, int $y, string $text): void
    {
        $size  = 20;
        $width = $this->textWidth($text, $size) + 2 * 20;
        $x     = $right - $width;
        imagefilledrectangle($img, $x, $y, $right, $y + 48, $this->color($img, self::KIN));
        imagerectangle($img, $x, $y, $right, $y + 48, $this->color($img, self::INK));
        $this->text($img, $text, $x + 20, $y + 34, $size, self::INK);
    }

    /**
     * 判子風の丸。文字は丸の内側に収まる大きさにして中央に置く
     */
    private function stamp(GdImage $img, int $cx, int $cy, string $text): void
    {
        $diameter = 100;
        imageellipse($img, $cx, $cy, $diameter, $diameter, $this->color($img, self::SHU));
        imageellipse($img, $cx, $cy, $diameter - 4, $diameter - 4, $this->color($img, self::SHU));
        $size = 18;

        while ($size > 10 && $this->textWidth($text, $size) > $diameter - 20) {
            $size--;
        }
        $this->centered($img, $text, $cx, $cy, $size, self::SHU);
    }

    /**
     * @param array{int, int, int} $fill
     * @param array{int, int, int} $textColor
     */
    private function circle(GdImage $img, int $cx, int $cy, int $r, array $fill, string $label, int $size, array $textColor): void
    {
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $this->color($img, $fill));
        $this->centered($img, $label, $cx, $cy, $size, $textColor);
    }

    /**
     * (cx, cy) を中心に1行の文字を置く
     *
     * @param array{int, int, int} $rgb
     */
    private function centered(GdImage $img, string $text, int $cx, int $cy, int $size, array $rgb): void
    {
        $box = imagettfbbox($size, 0, $this->font, $text);

        if ($box === false) {
            return;
        }
        // imagettfbbox は基準線からの座標を返す。上端 $box[7]・下端 $box[1] の真ん中を cy に合わせる
        $x = (int) round($cx - ($box[2] - $box[0]) / 2 - $box[0]);
        $y = (int) round($cy - ($box[1] + $box[7]) / 2);
        $this->text($img, $text, $x, $y, $size, $rgb);
    }

    private function textWidth(string $text, int $size): int
    {
        $box = imagettfbbox($size, 0, $this->font, $text);

        return $box === false ? 0 : $box[2] - $box[0];
    }

    /**
     * 折り返しつきの段落。入り切らなければ文字を小さくして、それでも溢れたら末尾を「…」にする
     *
     * @param array{int, int, int} $rgb
     */
    private function paragraph(GdImage $img, string $text, int $x, int $y, int $width, int $height, int $maxSize, int $minSize, array $rgb): void
    {
        $text  = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = [];

        for ($size = $maxSize; $size >= $minSize; $size -= 4) {
            $lines      = $this->wrap($text, $size, $width - $size); // 行末にぶら下げる句読点の分、1文字あけておく
            $lineHeight = (int) round($size * 1.5);

            if (count($lines) * $lineHeight <= $height) {
                break;
            }
        }
        $lineHeight = (int) round($size * 1.5);
        $max        = max(1, intdiv($height, $lineHeight));

        if (count($lines) > $max) {
            $lines           = array_slice($lines, 0, $max);
            $lines[$max - 1] = $this->clip($lines[$max - 1], max(1, mb_strlen($lines[$max - 1]) - 1));
        }

        foreach ($lines as $i => $line) {
            $this->text($img, $line, $x, $y + $size + $i * $lineHeight, $size, $rgb);
        }
    }

    /**
     * 幅に収まるように文字単位で折り返す（日本語なので単語境界は見ない。行頭禁則だけは守る）
     *
     * @return list<string>
     */
    private function wrap(string $text, int $size, int $width): array
    {
        $lines = [];

        foreach (explode("\n", $text) as $paragraph) {
            $line = '';

            foreach (mb_str_split($paragraph) as $char) {
                $box = imagettfbbox($size, 0, $this->font, $line . $char);

                if ($line !== '' && $box !== false && ($box[2] - $box[0]) > $width) {
                    // 句読点・閉じかっこなどは行頭に置かず、前の行の末尾にぶら下げる（禁則処理）
                    if (mb_strpos(self::NO_LINE_START, $char) !== false) {
                        $lines[] = $line . $char;
                        $line    = '';

                        continue;
                    }
                    $lines[] = $line;
                    $line    = '';
                }
                $line .= $char;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * 1行に収まらなければ、収まる長さで切って末尾を「…」にする
     */
    private function fit(string $text, int $size, int $width): string
    {
        if ($this->textWidth($text, $size) <= $width) {
            return $text;
        }
        $chars = mb_str_split($text);

        while ($chars !== [] && $this->textWidth(implode('', $chars) . '…', $size) > $width) {
            array_pop($chars);
        }

        return implode('', $chars) . '…';
    }

    private function clip(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
    }
}
