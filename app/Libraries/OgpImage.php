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

    /**
     * 画像に載せる文言の初期値（A-08 で変えられる。SettingService::ogpTexts()。ADR 0012）
     */
    public const TEXTS = [
        'recruit_pill'  => '24時間限定 回答募集中',
        'answer_prefix' => 'お題：',
        'result_stamp'  => '結果発表',
        'result_prefix' => 'お題：',
        'result_count'  => '全{count}回答',
        'result_ranks'  => 3,
    ];

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
     * 募集用：ロゴ、お題、右上の札（初期値「24時間限定 回答募集中」）
     *
     * @param array<string, int|string> $texts 載せる文言（TEXTS と同じキー。足りないものは初期値）
     */
    public function recruit(string $odai, array $texts = []): GdImage
    {
        $t   = $texts + self::TEXTS;
        $img = $this->canvas(OgpKind::Recruit);
        // 背景：回答カードの一覧をぼかして薄く敷く（回答はまだないので、文字の形の線だけ。回答前の人に回答を見せない：SPEC U-04）
        $this->backdrop($img);
        $this->brand($img);
        $this->pill($img, self::WIDTH - 80, 56, (string) $t['recruit_pill']);
        // 中央：お題を大きく
        $this->card($img, 130, 150, self::WIDTH - 130, 500, self::CARD, 8);
        $this->centeredParagraph($img, $odai, self::WIDTH / 2, 175, self::WIDTH - 340, 300, 64, 34, self::INK);
        imagefilledrectangle($img, 0, self::HEIGHT - 18, self::WIDTH, self::HEIGHT, $this->color($img, self::SHU));

        return $img;
    }

    /**
     * 回答シェア用：お題、回答、ロゴ
     *
     * @param array<string, int|string> $texts
     */
    public function answer(string $odai, string $answer, array $texts = []): GdImage
    {
        $t   = $texts + self::TEXTS;
        $img = $this->canvas(OgpKind::Answer);
        $this->backdrop($img);
        // 上：お題。中央：回答を山吹のカードに大きく。下：ロゴ
        $this->card($img, 110, 40, self::WIDTH - 110, 120, self::CARD, 6);
        $this->centered($img, $this->fit($t['answer_prefix'] . $odai, 30, self::WIDTH - 300), self::WIDTH / 2, 80, 30, self::INK);
        $this->card($img, 110, 150, self::WIDTH - 110, 520, self::KIN, 8);
        $this->centeredParagraph($img, $answer, self::WIDTH / 2, 172, self::WIDTH - 340, 326, 64, 32, self::INK);
        imagefilledrectangle($img, 0, 548, self::WIDTH, self::HEIGHT, $this->color($img, self::PAPER));
        $this->brand($img, 70, 560);

        return $img;
    }

    /**
     * 結果用：お題、1〜3位の回答、回答数、ロゴ、「結果発表」（ADR 0011）
     *
     * 1位だけなら大きく1件。2位・3位があれば、1位を2行まで、2位・3位を1行ずつ（入り切らなければ「…」）
     *
     * @param list<array{rank: int, body: string}> $ranked 順位の若い順（空なら「選出なし」）。載せる件数は呼ぶ側で決める（result_ranks）
     * @param array<string, int|string>            $texts
     */
    public function result(string $odai, array $ranked, int $answerCount, array $texts = []): GdImage
    {
        $t   = $texts + self::TEXTS;
        $img = $this->canvas(OgpKind::Result);
        // 上：お題（中央）と判子（右）。下：ロゴ（左）と回答数（右）
        $this->stamp($img, self::WIDTH - 92, 66, (string) $t['result_stamp']);
        $this->centered($img, $this->fit($t['result_prefix'] . $odai, 28, 880), self::WIDTH / 2, 62, 28, self::INK);

        if ($ranked === []) {
            $this->centeredParagraph($img, '（選出なし）', self::WIDTH / 2, 240, 900, 120, 48, 40, self::SUB);
        } else {
            $first = array_shift($ranked);
            // 1位：中央に大きく（2位・3位がなければ下まで使う）
            $bottom = $ranked === [] ? 530 : 364;
            $this->card($img, 140, 128, self::WIDTH - 140, $bottom, self::KIN, 8);
            $this->centeredParagraph($img, $first['body'], self::WIDTH / 2, 150, self::WIDTH - 400, $bottom - 150 - 22, $ranked === [] ? 76 : 60, 30, self::INK);
            $this->circle($img, 170, 132, 34, self::SHU, $first['rank'] . '位', 24, self::CARD);

            // 2位は左、3位は右（2位だけなら中央）
            $slots = count($ranked) === 1 ? [[300, 900]] : [[70, 586], [614, 1130]];

            foreach ($ranked as $i => $row) {
                [$x1, $x2] = $slots[$i];
                $this->card($img, $x1, 392, $x2, 532, self::CARD, 6);
                $this->centeredParagraph($img, $row['body'], intdiv($x1 + $x2, 2), 404, $x2 - $x1 - 110, 116, 32, 20, self::INK);
                $this->circle($img, $x1 + 24, 394, 25, self::INK, $row['rank'] . '位', 17, self::CARD);
            }
        }
        $this->brand($img, 70, 552);
        $count = str_replace('{count}', number_format($answerCount), (string) $t['result_count']);

        if ($count !== '') {
            $box = $this->textWidth($count, 24);
            $this->text($img, $count, self::WIDTH - 70 - $box, 594, 24, self::INK);
        }

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
     * 角の丸いカード（墨の枠と、右下にずらした墨の影）
     *
     * @param array{int, int, int} $fill
     */
    private function card(GdImage $img, int $x1, int $y1, int $x2, int $y2, array $fill, int $shadow): void
    {
        if ($shadow > 0) {
            $this->roundedRect($img, $x1 + $shadow, $y1 + $shadow, $x2 + $shadow, $y2 + $shadow, 24, self::INK);
        }
        $this->roundedRect($img, $x1, $y1, $x2, $y2, 24, self::INK);
        $this->roundedRect($img, $x1 + 4, $y1 + 4, $x2 - 4, $y2 - 4, 20, $fill);
    }

    /**
     * @param array{int, int, int} $rgb
     */
    private function roundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, array $rgb): void
    {
        $c = $this->color($img, $rgb);
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $c);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $c);

        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $c);
        }
    }

    /**
     * 募集用の背景：回答カードが並ぶ様子を、ぼかして薄く重ねる。カードの中は文字の形の線（本物の回答は載せない）
     */
    private function backdrop(GdImage $img): void
    {
        $layer = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopy($layer, $img, 0, 0, 0, 0, self::WIDTH, self::HEIGHT);
        $line = [0x8A, 0x80, 0x74];
        // 3段×4列。段ごとに横へずらし、カードの色と線の長さを変える（毎回同じ絵になるよう固定の並び）
        $widths = [[0.92, 0.7], [0.85, 0.55, 0.4], [0.9, 0.62], [0.78, 0.5], [0.95, 0.8, 0.3], [0.7, 0.45]];
        $n      = 0;

        for ($row = 0; $row < 4; $row++) {
            $y = -30 + $row * 170;

            for ($col = -1; $col < 4; $col++) {
                $x    = $col * 330 + ($row % 2 === 0 ? 40 : 200);
                $fill = $n % 3 === 1 ? self::KIN : self::CARD;
                $this->card($layer, $x, $y, $x + 290, $y + 140, $fill, 6);

                foreach ($widths[$n % count($widths)] as $i => $w) {
                    $this->roundedRect($layer, $x + 28, $y + 34 + $i * 30, $x + 28 + (int) (230 * $w), $y + 48 + $i * 30, 7, $line);
                }
                $n++;
            }
        }
        // ぼかす：小さくしてから戻し、さらにガウスぼかしをかける
        $small = imagecreatetruecolor(intdiv(self::WIDTH, 10), intdiv(self::HEIGHT, 10));
        imagecopyresampled($small, $layer, 0, 0, 0, 0, imagesx($small), imagesy($small), self::WIDTH, self::HEIGHT);
        imagecopyresampled($layer, $small, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, imagesx($small), imagesy($small));
        imagedestroy($small);

        for ($i = 0; $i < 6; $i++) {
            imagefilter($layer, IMG_FILTER_GAUSSIAN_BLUR);
        }
        // 薄く重ねる（不透明度 55%）
        imagecopymerge($img, $layer, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, 55);
        imagedestroy($layer);
    }

    /**
     * 中央そろえの段落（$cx を中心に、$y から下へ）。入り切らなければ文字を小さくし、それでも溢れたら末尾を「…」にする。
     * 行数が少なければ、$height の中で上下も中央に寄せる
     *
     * @param array{int, int, int} $rgb
     */
    private function centeredParagraph(GdImage $img, string $text, int $cx, int $y, int $width, int $height, int $maxSize, int $minSize, array $rgb): void
    {
        $text  = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = [];

        for ($size = $maxSize; $size >= $minSize; $size -= 2) {
            $lines = $this->wrap($text, $size, $width - $size);

            if (count($lines) * (int) round($size * 1.45) <= $height) {
                break;
            }
        }
        $size = max($size, $minSize);

        // 少し（2割まで）小さくすれば行が減るなら、そちらにする（「チロルチョ／コ」のような折り返しを避ける）
        for ($smaller = $size - 2; count($lines) > 1 && $smaller >= max($minSize, (int) round($size * 0.8)); $smaller -= 2) {
            $fewer = $this->wrap($text, $smaller, $width - $smaller);

            if (count($fewer) < count($lines)) {
                [$lines, $size] = [$fewer, $smaller];

                break;
            }
        }
        $lines      = $this->balance($text, $lines, $size, $width - $size);
        $lineHeight = (int) round($size * 1.45);
        $max        = max(1, intdiv($height, $lineHeight));

        if (count($lines) > $max) {
            $lines           = array_slice($lines, 0, $max);
            $lines[$max - 1] = $this->clip($lines[$max - 1], max(1, mb_strlen($lines[$max - 1]) - 1));
        }
        $top = $y + intdiv($height - count($lines) * $lineHeight, 2);

        foreach ($lines as $i => $line) {
            $this->centered($img, $line, $cx, $top + $i * $lineHeight + intdiv($lineHeight, 2), $size, $rgb);
        }
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
     * 行の長さをそろえる（最後の行に1〜2文字だけ落ちるのを避ける）。行数が増えない範囲で幅を狭めて折り返し直す
     *
     * @param list<string> $lines
     *
     * @return list<string>
     */
    private function balance(string $text, array $lines, int $size, int $width): array
    {
        // 最後の行が行幅の3割より長ければ、そのまま（無理にそろえると言葉の途中で切れやすい）
        if (count($lines) < 2 || str_contains($text, "\n") || $this->textWidth((string) end($lines), $size) >= $width * 0.3) {
            return $lines;
        }
        $total = $this->textWidth($text, $size);

        for ($w = (int) ceil($total / count($lines)); $w < $width; $w += $size) {
            $candidate = $this->wrap($text, $size, $w);

            if (count($candidate) <= count($lines)) {
                return $candidate;
            }
        }

        return $lines;
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
