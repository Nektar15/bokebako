<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\Answer;
use App\Entities\Oogiri;
use CodeIgniter\I18n\Time;

/**
 * 外側フェーズ用のダミーデータ（CODING_RULES 10章、FEATURES 5章）
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら、このクラスは Seeder に移して削除する。
 *
 * 状態名（?fixture= で指定）：
 *   open / open_empty / awaiting / decided / decided_one / no_result / hidden / hidden_fixable / deleted
 */
final class OogiriFixture
{
    public const DEFAULT = 'open';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return ['open', 'open_empty', 'awaiting', 'decided', 'decided_one', 'no_result', 'hidden', 'hidden_fixable', 'deleted'];
    }

    public static function oogiri(string $name, string $slug = 'k7Qm2xPz'): Oogiri
    {
        $now  = Time::now();
        $base = [
            'id'                 => 12480,
            'slug'               => $slug,
            'odai'               => 'こんなコンビニ店員は嫌だ',
            'status'             => 'open',
            'starts_at'          => $now->subHours(5)->subMinutes(28),
            'ends_at'            => $now->addHours(18)->addMinutes(32),
            'selection_deadline' => null,
            'decided_at'         => null,
            'pv'                 => 1284,
            'answer_count'       => 47,
            'parent_oogiri_id'   => 12301,
            'fixable'            => false,
        ];

        $patch = match ($name) {
            'open_empty'     => ['answer_count' => 0, 'pv' => 3, 'starts_at' => $now->subMinutes(2), 'ends_at' => $now->addHours(23)->addMinutes(58)],
            'awaiting'       => ['status' => 'awaiting_selection', 'starts_at' => $now->subHours(26), 'ends_at' => $now->subHours(2), 'selection_deadline' => $now->addHours(70)],
            'decided'        => ['status' => 'decided', 'odai' => '絶対に売れない駄菓子の商品名とは？', 'starts_at' => $now->subDays(2), 'ends_at' => $now->subDays(1), 'decided_at' => $now->subHours(18), 'pv' => 15204, 'answer_count' => 412],
            'decided_one'    => ['status' => 'decided', 'starts_at' => $now->subDays(2), 'ends_at' => $now->subDays(1), 'decided_at' => $now->subHours(18), 'answer_count' => 5],
            'no_result'      => ['status' => 'no_result', 'odai' => '絶対に売れない駄菓子の商品名とは？', 'starts_at' => $now->subDays(5), 'ends_at' => $now->subDays(4), 'selection_deadline' => $now->subDays(1), 'answer_count' => 38],
            'hidden'         => ['status' => 'hidden'],
            'hidden_fixable' => ['status' => 'hidden', 'fixable' => true],
            'deleted'        => ['status' => 'deleted'],
            default          => [],
        };

        return new Oogiri(array_merge($base, $patch));
    }

    /**
     * @return list<Answer>
     */
    public static function answers(string $name, int $oogiriId = 12480): array
    {
        if ($name === 'open_empty') {
            return [];
        }

        $now  = Time::now();
        $rows = [
            ['「袋いりますか？」のあとに、10秒の沈黙を置いてくる', 0, true],
            ['深夜になると、急に関西弁になる', 2, false],
            ['おでんの具を選ぶたびに、小さく「正解」とつぶやく', 5, false],
            ['レシートの裏に、毎回ちがう詩を書いてくれる', 9, false],
            ['「温めますか？」と聞いてから、自分の手で温めはじめる', 14, false],
            ['お釣りを全部1円玉で、ひとつずつ数えながら渡してくる', 21, false],
            ['ポイントカードの有無を、前世の話から聞いてくる', 33, false],
            ["改行を含む回答です。\n2行目はこんな感じで、\n3行目まで続きます", 40, false],
            [str_repeat('あ', 140), 55, false],
        ];

        if (in_array($name, ['decided', 'no_result'], true)) {
            $rows = [
                ['うまい棒・味なし', 0, false], ['ねるねるねるね・ねらない', 3, false], ['ブラックサンダー（晴れ）', 8, false],
                ['よっちゃんイカ・本人', 15, false], ['チロルチョコ 1,980円', 22, false], ['くじ付きガム（全部はずれと書いてある）', 30, false],
            ];
        }

        $ranks = match ($name) {
            'decided'     => [0 => 1, 4 => 2, 3 => 3],
            'decided_one' => [4 => 1],
            default       => [],
        };

        $answers = [];

        foreach ($rows as $i => [$body, $minutesAgo, $mine]) {
            $answers[] = new Answer([
                'id'          => 90412 - $i,
                'oogiri_id'   => $oogiriId,
                'body'        => $body,
                'status'      => 'visible',
                'rank'        => $ranks[$i] ?? null,
                'is_favorite' => $i === 2 || $i === 4,
                'is_mine'     => $mine,
                'created_at'  => $now->subMinutes($minutesAgo),
            ]);
        }

        return $answers;
    }
}
