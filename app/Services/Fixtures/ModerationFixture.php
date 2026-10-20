<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\AdminLog;
use App\Entities\ModerationItem;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-05）用のダミーデータ：通報・モデレーションの待ち行列
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class ModerationFixture
{
    /**
     * 未処理の6件（docs/design/A05-Moderation.dc.html の見本どおり、優先度の高い順）
     *
     * @return list<ModerationItem>
     */
    public static function queue(): array
    {
        $today = Time::now()->setTime(0, 0);
        $rows  = [
            [
                'answer', 90398, 12471, '2組の〇〇がやりそうなこと全部', 'うちの担任の嫌なところ選手権', ['surge', 'ng_word', 'ai_hide'], '18:02', 5, 'hidden',
                '5件（異なる環境から。同一環境の重複2件を除外）／ 誹謗中傷 4・個人情報 1', '「組」＋人名の可能性（部分一致・要確認）', 'なし',
                'hide', '実在の個人を特定し、からかう内容の可能性があります。学校でのいじめにつながるおそれがあります。',
                [['18:20', '自動', '通報が条件に達したため一時非表示'], ['18:21', '自動', '管理者へメール通知']],
            ],
            [
                'oogiri', 12471, 12471, 'うちの担任の嫌なところ選手権', '', ['ng_word', 'ai_review'], '17:40', 3, 'hidden',
                '3件 ／ いじめ・嫌がらせ 3', '「担任」（部分一致・要確認）', 'なし',
                'review', '特定の学校関係者を対象にしたお題の可能性があります。',
                [['17:58', '自動', '通報が条件に達したため一時非表示']],
            ],
            [
                'answer', 90377, 12479, 'LINEのIDはこちら→', '100年後の運動会あるある', ['ng_word', 'ai_review'], '17:31', 1, 'hidden',
                '1件 ／ スパム・宣伝 1', '「LINE」（部分一致・要確認）', 'なし',
                'review', '連絡先の交換を促す内容です。',
                [['17:31', '自動', 'NGワード「要確認」を検知']],
            ],
            [
                'answer', 90301, 12460, '（同じ文面が12件）', 'ロボットが絶対に言わない一言', ['abnormal'], '12:48', 0, 'visible',
                'なし', 'なし', '同一環境から30秒間に12件',
                'none', 'APIの応答がなかったため未判定です。次回のバッチで再判定します。',
                [['12:48', '自動', '連続投稿を検知し、投稿元を1時間制限']],
            ],
            [
                'answer', 90341, 12466, 'このお題、主催者のセンス0点', '絶対に流行らない新しい挨拶', ['ai_review'], '15:40', 2, 'visible',
                '2件 ／ 誹謗中傷 2', 'なし', 'なし',
                'ok', 'お題への軽い批評で、攻撃性は低いと判断しました。',
                [],
            ],
            [
                'oogiri', 12455, 12455, '〇〇駅前のコンビニの店員の特徴', '', ['ng_word'], '09:15', 0, 'open',
                'なし', '「駅前」＋「店員」（部分一致・要確認）', 'なし',
                'review', '実在の店舗の従業員を特定できる可能性があります。',
                [],
            ],
        ];

        $items = [];

        foreach ($rows as [$type, $id, $oogiriId, $text, $odai, $flags, $time, $reports, $status, $reportDetail, $ng, $abnormal, $ai, $aiReason, $history]) {
            $logs = [];

            foreach (array_reverse($history) as $i => [$at, $actor, $what]) {
                $logs[] = new AdminLog(['id' => $i + 1, 'actor' => $actor, 'what' => $what, 'created_at' => self::at($today, $at)]);
            }

            $items[] = new ModerationItem([
                'target_type'   => $type,
                'id'            => $id,
                'oogiri_id'     => $oogiriId,
                'text'          => $text,
                'odai'          => $odai,
                'flags'         => $flags,
                'created_at'    => self::at($today, $time),
                'report_count'  => $reports,
                'target_status' => $status,
                'report_detail' => $reportDetail,
                'ng_detail'     => $ng,
                'abnormal'      => $abnormal,
                'ai_verdict'    => $ai,
                'ai_reason'     => $aiReason,
                'resolved'      => false,
                'history'       => $logs,
            ]);
        }

        return $items;
    }

    /**
     * 絞り込みごとの件数（見本の数字）
     *
     * @return array<string, int> 'all' / ModerationFlag の値 / 'resolved' => 件数
     */
    public static function counts(): array
    {
        return ['all' => 6, 'surge' => 1, 'ng_word' => 3, 'ai_review' => 4, 'abnormal' => 1, 'resolved' => 128];
    }

    private static function at(Time $day, string $hhmm): Time
    {
        [$h, $m] = explode(':', $hhmm);

        return $day->setTime((int) $h, (int) $m);
    }
}
