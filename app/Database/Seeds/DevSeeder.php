<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Entities\Ad;
use App\Services\IdentityService;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * 開発用データ（工程1・2の Fixture と同じ内容）。本番では使わない
 *
 * 実行：php spark db:seed DevSeeder（既存の行はすべて消してから入れる）
 *
 * 開催者トークンは「dev-token-{slug}」、合言葉はすべて「ぼけばこ」。
 * 例：/host/k7Qm2xPz を開くには Cookie bk_host_k7Qm2xPz=dev-token-k7Qm2xPz、または合言葉で復旧する。
 */
class DevSeeder extends Seeder
{
    public const PASSPHRASE = 'ぼけばこ';

    /**
     * 開発用の管理者（A-01）。上位管理者 dev-admin と一般管理者 dev-general。パスワードは共通（本番では使わない）
     */
    public const ADMIN_PASSWORD = 'dev-password-bokebako';

    public function run(): void
    {
        $identity = new IdentityService();
        $now      = Time::now();

        foreach (['admins', 'admin_logs', 'ai_judgements', 'reports', 'answers', 'oogiris', 'ng_words', 'ads', 'inquiries', 'ogp_jobs', 'daily_stats', 'events', 'restrictions', 'settings'] as $table) {
            $this->db->table($table)->truncate();
        }

        // ---- 管理者（A-01）。ログイン：dev-admin / dev-general、パスワードは ADMIN_PASSWORD ----
        foreach ([[1, 'dev-admin', 'dev-admin', 'super'], [2, 'dev-general', 'dev-general', 'general']] as [$aid, $loginId, $name, $role]) {
            $this->db->table('admins')->insert([
                'id'           => $aid, 'login_id' => $loginId, 'name' => $name, 'role' => $role, 'password_hash' => password_hash(self::ADMIN_PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]),
                'failed_count' => 0, 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ]);
        }

        // ---- 大喜利（docs/design/A03-Oogiri の見本） ----
        $oogiris = [
            // id, slug, odai, 作成の何時間前, status, pv, shares, derived, parent
            [12480, 'k7Qm2xPz', 'こんなコンビニ店員は嫌だ', 5, 'open', 1284, 63, 2, 12301],
            [12479, 'Hd3kLp9a', '100年後の運動会あるある', 6, 'open', 412, 9, 0, null],
            [12471, 'Wq8nRt2v', 'うちの担任の嫌なところ選手権', 9, 'hidden', 233, 4, 0, null],
            [12466, 'Zp3cW9aQ', '絶対に流行らない新しい挨拶', 28, 'awaiting_selection', 3911, 201, 12, null],
            [12460, 'Mn6bXs4e', 'ロボットが絶対に言わない一言', 31, 'awaiting_selection', 806, 25, 1, null],
            [12452, 'q8Lr4nTb', '絶対に売れない駄菓子の商品名とは？', 38, 'decided', 15204, 688, 38, 12301],
            [12441, 'Cv5tYu7i', 'こんな結婚式の余興は嫌だ', 50, 'decided', 7740, 302, 19, null],
            [12398, 'Bx2gHj8k', '宇宙人が地球で驚いたこと', 110, 'no_result', 610, 20, 1, null],
            [12377, 'Ty9pQw1z', '（削除済みのお題）', 130, 'deleted', 98, 0, 0, null],
            [12301, 'Aa1bCc2d', 'こんな○○は嫌だ（派生元の見本）', 200, 'decided', 2200, 90, 5, null],
            [12481, 'Ee4fGg5h', '回答がまだない大喜利', 0, 'open', 3, 0, 0, null],
        ];

        foreach ($oogiris as [$id, $slug, $odai, $hoursAgo, $status, $pv, $shares, $derived, $parent]) {
            $created = $now->subHours($hoursAgo);
            $ends    = $created->addHours(24);
            $this->db->table('oogiris')->insert([
                'id'                 => $id,
                'slug'               => $slug,
                'odai'               => $odai,
                'host_token_hash'    => $identity->hash('dev-token-' . $slug),
                'passphrase_hash'    => password_hash(self::PASSPHRASE, PASSWORD_DEFAULT),
                'browser_id_hash'    => $identity->hash('dev-browser-host'),
                'ip_hash'            => $identity->hash('127.0.0.1'),
                'status'             => $status,
                'status_before_hide' => $status === 'hidden' ? 'open' : null,
                'fixable'            => 0,
                'starts_at'          => $created->toDateTimeString(),
                'ends_at'            => $ends->toDateTimeString(),
                'selection_deadline' => $status === 'open' ? null : $ends->addHours(72)->toDateTimeString(),
                'decided_at'         => $status === 'decided' ? $ends->addHours(6)->addMinutes(37)->toDateTimeString() : null,
                'pv'                 => $pv,
                'answer_count'       => 0, // 回答を入れたあとに数え直す
                'share_count'        => $shares,
                'derived_count'      => $derived,
                'report_count'       => 0,
                'parent_oogiri_id'   => $parent,
                'created_at'         => $created->toDateTimeString(),
                'updated_at'         => $created->toDateTimeString(),
            ]);
        }

        // ---- 回答 ----
        $answersOpen = [
            ['「袋いりますか？」のあとに、10秒の沈黙を置いてくる', 0], ['深夜になると、急に関西弁になる', 2], ['おでんの具を選ぶたびに、小さく「正解」とつぶやく', 5],
            ['レシートの裏に、毎回ちがう詩を書いてくれる', 9], ['「温めますか？」と聞いてから、自分の手で温めはじめる', 14], ['お釣りを全部1円玉で、ひとつずつ数えながら渡してくる', 21],
            ['ポイントカードの有無を、前世の話から聞いてくる', 33], ["改行を含む回答です。\n2行目はこんな感じで、\n3行目まで続きます", 40], [str_repeat('あ', 140), 55],
        ];
        $answersDecided = [
            ['うまい棒・味なし', 0, 1], ['ねるねるねるね・ねらない', 3, null], ['ブラックサンダー（晴れ）', 8, null],
            ['よっちゃんイカ・本人', 15, 3], ['チロルチョコ 1,980円', 22, 2], ['くじ付きガム（全部はずれと書いてある）', 30, null],
        ];
        $rows = [
            // id, oogiri_id, body, 何分前, status, rank, reports, browser
            [90412, 12480, $answersOpen[0][0], 6, 'visible', null, 0, 'dev-browser-me'],
            [90411, 12480, $answersOpen[1][0], 22, 'visible', null, 0, 'b1'],
            [90410, 12480, $answersOpen[2][0], 35, 'visible', null, 0, 'b2'],
            [90409, 12480, $answersOpen[3][0], 49, 'visible', null, 0, 'b3'],
            [90408, 12480, $answersOpen[4][0], 74, 'visible', null, 0, 'b4'],
            [90407, 12480, $answersOpen[5][0], 81, 'visible', null, 0, 'b5'],
            [90406, 12480, $answersOpen[6][0], 93, 'visible', null, 0, 'b6'],
            [90405, 12480, $answersOpen[7][0], 100, 'visible', null, 0, 'b7'],
            [90404, 12480, $answersOpen[8][0], 115, 'visible', null, 0, 'b8'],
            [90398, 12471, '2組の〇〇がやりそうなこと全部', 258, 'hidden', null, 5, 'aa02'],
            [90377, 12479, 'LINEのIDはこちら→', 289, 'hidden', null, 1, 'c810'],
            [90360, 12479, '玉入れの玉が全部ドローン', 315, 'visible', null, 0, 'b9'],
            [90341, 12466, 'このお題、主催者のセンス0点', 400, 'visible', null, 2, '19dd'],
            [90322, 12466, '「おはようございました」', 488, 'visible', null, 0, 'b10'],
            [90301, 12460, '（同じ文面の連続投稿）', 572, 'deleted', null, 0, 'aa02'],
            [90288, 12460, '「充電させてください」と言って、人に寄りかかる', 660, 'visible', null, 0, 'b11'],
        ];
        $id = 88301;

        foreach ($answersDecided as [$body, $minutes, $rank]) {
            $rows[] = [$id++, 12452, $body, 38 * 60 - $minutes, 'visible', $rank, 0, 'b' . $id];
        }

        foreach ([12441, 12398, 12301] as $oid) {
            foreach ($answersOpen as $i => [$body, $minutes]) {
                if ($i < 4) {
                    $rows[] = [$id++, $oid, $body, 48 * 60 + $minutes, 'visible', $oid === 12441 && $i === 0 ? 1 : null, 0, 'b' . $id];
                }
            }
        }

        foreach ($rows as [$aid, $oid, $body, $minutesAgo, $status, $rank, $reports, $browser]) {
            $at = $now->subMinutes($minutesAgo)->toDateTimeString();
            $this->db->table('answers')->insert([
                'id'              => $aid, 'oogiri_id' => $oid, 'body' => $body, 'status' => $status, 'rank' => $rank, 'is_favorite' => $aid === 90410 || $aid === 90408 ? 1 : 0,
                'browser_id_hash' => $identity->hash($browser), 'ip_hash' => $identity->hash('203.0.113.' . (crc32($browser) % 200)),
                'report_count'    => $reports, 'created_at' => $at, 'updated_at' => $at,
            ]);
        }
        $o = $this->db->prefixTable('oogiris');
        $a = $this->db->prefixTable('answers');
        $this->db->query("UPDATE {$o} SET answer_count = (SELECT COUNT(*) FROM {$a} WHERE {$a}.oogiri_id = {$o}.id AND {$a}.status = 'visible')");
        $this->db->query("UPDATE {$o} SET report_count = (SELECT COALESCE(SUM({$a}.report_count), 0) FROM {$a} WHERE {$a}.oogiri_id = {$o}.id)");
        $this->db->table('oogiris')->where('id', 12471)->update(['report_count' => 5]);
        $this->db->table('oogiris')->where('id', 12377)->update(['report_count' => 7]);

        // ---- 通報・AI判定（A-05 の見本） ----
        $reports = [
            ['answer', 90398, 'abuse', 4], ['answer', 90398, 'personal', 1], ['oogiri', 12471, 'abuse', 3], ['answer', 90377, 'spam', 1], ['answer', 90341, 'abuse', 2],
        ];

        foreach ($reports as [$type, $tid, $reason, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $at = $now->subHours(2)->subMinutes($i * 7)->toDateTimeString();
                $this->db->table('reports')->insert([
                    'target_type'     => $type, 'target_id' => $tid, 'reason' => $reason,
                    'browser_id_hash' => $identity->hash("reporter-{$tid}-{$i}"), 'ip_hash' => $identity->hash("198.51.100.{$i}"),
                    'resolved'        => 0, 'created_at' => $at, 'updated_at' => $at,
                ]);
            }
        }
        $judgements = [
            ['answer', 90398, 'reported', '「組」＋人名の可能性（部分一致・要確認）', null, 'hide', '実在の個人を特定し、からかう内容の可能性があります。学校でのいじめにつながるおそれがあります。'],
            ['oogiri', 12471, 'ng_review', '「担任」（部分一致・要確認）', null, 'review', '特定の学校関係者を対象にしたお題の可能性があります。'],
            ['answer', 90377, 'ng_review', '「LINE」（部分一致・要確認）', null, 'review', '連絡先の交換を促す内容です。'],
            ['answer', 90301, 'abnormal', null, '同一環境から30秒間に12件', 'none', null],
            ['answer', 90341, 'reported', null, null, 'ok', 'お題への軽い批評で、攻撃性は低いと判断しました。'],
        ];

        foreach ($judgements as [$type, $tid, $trigger, $ng, $abnormal, $verdict, $reason]) {
            $at = $now->subHours(3)->toDateTimeString();
            $this->db->table('ai_judgements')->insert([
                'target_type' => $type, 'target_id' => $tid, 'trigger' => $trigger, 'ng_detail' => $ng, 'abnormal' => $abnormal,
                'verdict'     => $verdict, 'reason' => $reason, 'queued' => $verdict === 'none' ? 1 : 0, 'resolved' => 0, 'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        // ---- NGワード ----
        $words = [
            ['殺す', 'partial', '脅迫', 'reject', 1], ['0\d{1,4}-?\d{1,4}-?\d{4}', 'regex', '個人情報', 'reject', 1], ['[\w.+-]+@[\w-]+\.[\w.]+', 'regex', '個人情報', 'reject', 1],
            ['LINE', 'partial', '個人情報', 'review', 1], ['担任', 'partial', 'いじめ・学校', 'review', 1], ['住所', 'partial', '個人情報', 'review', 1],
            ['死ね', 'exact', '誹謗中傷', 'review', 1], ['http', 'partial', 'スパム', 'review', 0],
        ];

        foreach ($words as $i => [$word, $match, $category, $action, $enabled]) {
            $at = $now->subDays(11 - $i)->toDateTimeString();
            $this->db->table('ng_words')->insert(['word' => $word, 'match_type' => $match, 'category' => $category, 'action' => $action, 'enabled' => $enabled, 'created_at' => $at, 'updated_at' => $at]);
        }

        // ---- 広告 ----
        $year = (int) $now->format('Y');
        $ads  = [
            ['秋の新商品キャンペーン', 'まるや製菓', 'top', '10-01', '10-31', Ad::STATUS_PUBLISHED, 238410, 2101],
            ['ネタ帳アプリ「ネタノート」', 'ネタノート', 'top', '09-15', '11-15', Ad::STATUS_PUBLISHED, 174470, 1200],
            ['大喜利の本・フェア', 'コトバ書房', 'bottom', '10-01', '10-20', Ad::STATUS_PUBLISHED, 298104, 1612],
            ['お笑いライブ告知', 'スタジオ・ワライ', 'bottom', '10-21', '11-10', Ad::STATUS_PUBLISHED, 0, 0],
            ['夏のキャンペーン', 'まるや製菓', 'top', '08-01', '08-31', Ad::STATUS_PUBLISHED, 512033, 3890],
            ['テスト配信', 'ぼけばこ運営', 'bottom', '09-01', '09-30', Ad::STATUS_STOPPED, 1204, 3],
        ];

        foreach ($ads as [$name, $sponsor, $slot, $from, $to, $status, $imp, $clk]) {
            $this->db->table('ads')->insert([
                'name'        => $name, 'sponsor' => $sponsor, 'image' => 'assets/img/ad-sample.svg', 'url' => 'https://example.com/campaign',
                'slot'        => $slot, 'status' => $status, 'starts_at' => "{$year}-{$from} 00:00:00", 'ends_at' => "{$year}-{$to} 23:59:59",
                'impressions' => $imp, 'clicks' => $clk, 'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ]);
        }

        // ---- お問い合わせ ----
        $inquiries = [
            [17, 'delete', 20, 'yuki.t@example.com', 'https://bokebako.com/o/k7Qm2xPz', 'todo', '自分のことを書かれていると思われる回答があります。削除をお願いします。'],
            [16, 'ad', 32, 'ad@marumaru.example.jp', '', 'todo', 'バナー広告の掲載を検討しています。料金と掲載できる期間を教えてください。'],
            [15, 'rights', 63, 'legal@studio.example.co.jp', 'https://bokebako.com/o/Zp3cW9aQ', 'doing', '当社のキャラクター名を使ったお題が公開されています。確認をお願いします。'],
            [14, 'general', 102, 'nekonote@example.com', '', 'done', '開催者ページを開けなくなりました。合言葉を忘れてしまいました。'],
        ];

        foreach ($inquiries as [$iid, $type, $hoursAgo, $email, $url, $status, $body]) {
            $at = $now->subHours($hoursAgo)->toDateTimeString();
            $this->db->table('inquiries')->insert(['id' => $iid, 'type' => $type, 'email' => $email, 'url' => $url, 'body' => $body, 'status' => $status, 'created_at' => $at, 'updated_at' => $at]);
        }

        // ---- 操作履歴（お問い合わせの対応メモ・設定の変更履歴・大喜利の履歴） ----
        $logs = [
            ['dev-admin', 'inquiry', 15, '対象のお題を一時非表示にした。先方へ確認の返信済み。', 61],
            ['dev-admin', 'inquiry', 14, '合言葉は運営でも確認できない旨を返信。', 90],
            ['dev-admin', 'setting', null, '連続投稿の判定回数 5 → 3', 5 * 24],
            ['dev-admin', 'setting', null, 'AI補助判定の上限 5,000 → 10,000', 9 * 24],
            ['dev-admin', 'setting', null, '初期設定', 12 * 24],
            ['自動', 'answer', 90398, '通報が条件に達したため一時非表示', 2],
            ['自動', 'answer', 90398, '管理者へメール通知', 2],
            ['自動', 'oogiri', 12471, '通報が条件に達したため一時非表示', 2],
            ['自動', 'answer', 90377, 'NGワード「要確認」を検知', 5],
            ['自動', 'answer', 90301, '連続投稿を検知し、投稿元を1時間制限', 9],
        ];

        foreach ($logs as [$actor, $type, $tid, $what, $hoursAgo]) {
            $this->db->table('admin_logs')->insert(['actor' => $actor, 'admin_id' => null, 'target_type' => $type, 'target_id' => $tid, 'what' => $what, 'created_at' => $now->subHours($hoursAgo)->toDateTimeString()]);
        }

        // ---- OGP 生成記録 ----
        $jobs = [
            ['recruit', 12480, '#12480 こんなコンビニ店員は嫌だ', '自動', 1, 5], ['answer', 90412, 'A-90412', '自動', 1, 4],
            ['result', 12452, '#12452 絶対に売れない駄菓子の商品名とは？', '自動', 1, 219], ['result', 12441, '#12441 こんな結婚式の余興は嫌だ', 'dev-admin', 1, 248],
            ['answer', 90122, 'A-90122', '自動', 0, 797], ['recruit', 12441, '#12441 こんな結婚式の余興は嫌だ', '自動', 1, 1350],
        ];

        foreach ($jobs as [$kind, $tid, $target, $actor, $ok, $minutesAgo]) {
            $this->db->table('ogp_jobs')->insert(['kind' => $kind, 'target_id' => $tid, 'target' => $target, 'actor' => $actor, 'succeeded' => $ok, 'error' => $ok ? null : 'フォントファイルが見つかりません', 'created_at' => $now->subMinutes($minutesAgo)->toDateTimeString()]);
        }

        // ---- 日次集計（直近30日） ----
        $daily = [148, 162, 155, 171, 190, 238, 226, 181, 176, 199, 204, 263, 248, 214];

        for ($d = 30; $d >= 1; $d--) {
            $oogiriCount = $daily[13 - min(13, $d - 1)];
            $date        = $now->subDays($d)->toDateString();
            $this->db->table('daily_stats')->insert([
                'date'       => $date, 'pv' => 32410, 'oogiris' => $oogiriCount, 'answers' => 6980, 'share_recruit' => 188, 'share_answer' => 1412, 'share_result' => 96,
                'reports'    => 18, 'hidden' => 4, 'answerers' => 3120, 'converted' => 200, 'derived' => (int) round($oogiriCount * 1.42),
                'created_at' => $now->toDateTimeString(), 'updated_at' => $now->toDateTimeString(),
            ]);
        }
    }
}
