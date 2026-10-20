<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

use App\Entities\AdminLog;
use App\Entities\Inquiry;
use CodeIgniter\I18n\Time;

/**
 * 管理画面（A-09）用のダミーデータ：お問い合わせ
 *
 * TODO(inner): 工程4で Service が DB を読むようになったら Seeder に移して削除する。
 */
final class InquiryFixture
{
    /**
     * @return list<Inquiry>
     */
    public static function all(): array
    {
        $now  = Time::now();
        $rows = [
            // id, type, 何時間前, email, url, status, body, memos[ [何時間前, 誰, メモ] ]
            [17, 'delete', 20, 'yuki.t@example.com', 'https://bokebako.com/o/k7Qm2xPz', 'todo', '自分のことを書かれていると思われる回答があります。削除をお願いします。', []],
            [16, 'ad', 32, 'ad@marumaru.example.jp', '', 'todo', 'バナー広告の掲載を検討しています。料金と掲載できる期間を教えてください。', []],
            [15, 'rights', 63, 'legal@studio.example.co.jp', 'https://bokebako.com/o/Zp3cW9aQ', 'doing', '当社のキャラクター名を使ったお題が公開されています。確認をお願いします。', [[61, 'dev-admin', '対象のお題を一時非表示にした。先方へ確認の返信済み。']]],
            [14, 'general', 102, 'nekonote@example.com', '', 'done', '開催者ページを開けなくなりました。合言葉を忘れてしまいました。', [[90, 'dev-admin', '合言葉は運営でも確認できない旨を返信。']]],
        ];

        $items = [];

        foreach ($rows as [$id, $type, $hoursAgo, $email, $url, $status, $body, $memos]) {
            $logs = [];

            foreach ($memos as $i => [$memoHoursAgo, $by, $text]) {
                $logs[] = new AdminLog(['id' => $i + 1, 'actor' => $by, 'what' => $text, 'created_at' => $now->subHours($memoHoursAgo)]);
            }

            $items[] = new Inquiry([
                'id'         => $id,
                'type'       => $type,
                'email'      => $email,
                'url'        => $url,
                'status'     => $status,
                'body'       => $body,
                'memos'      => $logs,
                'created_at' => $now->subHours($hoursAgo),
            ]);
        }

        return $items;
    }

    /**
     * 対応状況ごとの件数（見本の数字）
     *
     * @return array<string, int> InquiryStatus の値（'all' を含む） => 件数
     */
    public static function counts(): array
    {
        return ['todo' => 2, 'doing' => 1, 'done' => 14, 'all' => 17];
    }
}
