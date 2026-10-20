<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Answer;
use App\Entities\OgpJob;
use App\Entities\Oogiri;
use App\Enums\AnswerStatus;
use App\Enums\OgpKind;
use App\Enums\OogiriStatus;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Libraries\OgpImage;
use App\Models\AnswerModel;
use App\Models\OgpJobModel;
use App\Models\OogiriModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use GdImage;
use Throwable;

/**
 * OGP 画像の生成・再生成・テンプレート画像の差し替え（S-07、A-08）
 *
 * 画像は public/ogp/{kind}/{key}.png に保存して静的配信する（SPEC 4.1）。key は募集用・結果用がスラッグ、回答シェア用が回答ID。
 * 募集用は開催時、結果用は確定時、回答シェア用は初回アクセス時に生成する。
 */
final class OgpService
{
    /**
     * テンプレート画像の種類（値 => 表示名）
     */
    public const ASSETS = [
        'background' => '背景',
        'logo'       => 'ロゴ',
    ];

    /**
     * removeOogiri() で一度に取る回答の件数
     */
    private const REMOVE_CHUNK = 500;

    public function __construct(
        private readonly OgpImage $image,
        private readonly OgpJobModel $jobs,
        private readonly OogiriModel $oogiris,
        private readonly AnswerModel $answers,
    ) {
    }

    /**
     * 画像の公開 URL。ファイルがなければ既定の画像（public/assets/img/ogp-default.png）
     */
    public function url(OgpKind $kind, string $key): string
    {
        $path = $this->path($kind, $key);

        return is_file($path) ? base_url('ogp/' . $kind->value . '/' . $key . '.png') . '?v=' . filemtime($path) : base_url('assets/img/ogp-default.png');
    }

    public function path(OgpKind $kind, string $key): string
    {
        return FCPATH . 'ogp/' . $kind->value . '/' . basename($key) . '.png';
    }

    public function exists(OgpKind $kind, string $key): bool
    {
        return is_file($this->path($kind, $key));
    }

    /**
     * 募集用（開催時）
     */
    public function generateRecruit(Oogiri $oogiri, string $actor = AdminLogService::ACTOR_AUTO): bool
    {
        return $this->run(OgpKind::Recruit, $oogiri->id, $oogiri->slug, '#' . $oogiri->id . ' ' . $oogiri->odai, $actor, fn () => $this->image->recruit($oogiri->odai));
    }

    /**
     * 結果用（確定時）
     */
    public function generateResult(Oogiri $oogiri, ?Answer $first, string $actor = AdminLogService::ACTOR_AUTO): bool
    {
        return $this->run(OgpKind::Result, $oogiri->id, $oogiri->slug, '#' . $oogiri->id . ' ' . $oogiri->odai, $actor, fn () => $this->image->result($oogiri->odai, $first?->body ?? '（選出なし）', $oogiri->answer_count));
    }

    /**
     * 回答シェア用（初回アクセス時。すでにあれば何もしない）
     */
    public function ensureAnswer(Oogiri $oogiri, Answer $answer): bool
    {
        if ($this->exists(OgpKind::Answer, (string) $answer->id)) {
            return true;
        }

        return $this->run(OgpKind::Answer, $answer->id, (string) $answer->id, 'A-' . $answer->id, AdminLogService::ACTOR_AUTO, fn () => $this->image->answer($oogiri->odai, $answer->body));
    }

    /**
     * 結果画像に載せる1位：順位のついた公開中の回答のうち最上位（U-10 の表示と同じ並び）。なければ null
     */
    public function firstRanked(Oogiri $oogiri): ?Answer
    {
        return $this->answers->where('oogiri_id', $oogiri->id)->where('rank IS NOT NULL')->where('status', AnswerStatus::Visible->value)->orderBy('rank', 'ASC')->first();
    }

    /**
     * 大喜利を非表示・削除したとき、公開ディレクトリに残る画像（募集・結果・その回答のシェア用）を消す（レビュー指摘 7）
     *
     * 消すのはこのサーバーのファイルだけ。CDN や SNS 側に保存された画像は消えない（docs/admin-manual.md）
     */
    public function removeOogiri(Oogiri $oogiri): void
    {
        $this->remove(OgpKind::Recruit, $oogiri->slug);
        $this->remove(OgpKind::Result, $oogiri->slug);
        $lastId = 0;

        // 回答は数が多いことがあるので、ID 順に区切って取る（CODING_RULES 6章：上限なしで全件を取らない）
        do {
            $ids = $this->answers->where('oogiri_id', $oogiri->id)->where('id >', $lastId)->orderBy('id', 'ASC')->limit(self::REMOVE_CHUNK)->findColumn('id') ?? [];

            foreach ($ids as $id) {
                $this->remove(OgpKind::Answer, (string) $id);
                $lastId = (int) $id;
            }
        } while (count($ids) === self::REMOVE_CHUNK);
    }

    /**
     * 大喜利を再公開したとき、消した募集・結果の画像を作り直す（回答シェア用は初回アクセス時に作られる）
     */
    public function restoreOogiri(Oogiri $oogiri): void
    {
        $this->generateRecruit($oogiri);

        if (in_array($oogiri->status(), [OogiriStatus::Decided, OogiriStatus::NoResult], true)) {
            $this->generateResult($oogiri, $this->firstRanked($oogiri));
        }
    }

    /**
     * 回答を非表示・削除・再公開したとき：その回答のシェア用画像を消し、結果画像を今の公開状態で作り直す（レビュー指摘 7）
     */
    public function refreshForAnswer(Answer $answer, Oogiri $oogiri): void
    {
        $this->remove(OgpKind::Answer, (string) $answer->id);

        if (in_array($oogiri->status(), [OogiriStatus::Decided, OogiriStatus::NoResult], true)) {
            $this->generateResult($oogiri, $this->firstRanked($oogiri));
        }
    }

    private function remove(OgpKind $kind, string $key): void
    {
        $path = $this->path($kind, $key);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * 最近の生成（新しい順）
     *
     * @return list<OgpJob>
     */
    public function recentJobs(int $limit = 20): array
    {
        return $this->jobs->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }

    /**
     * 個別に再生成する（A-08）
     *
     * @param string $target 大喜利のURL・スラッグ・ID、または回答ID（例：A-90412）
     *
     * @throws ValidationException
     */
    public function regenerate(OgpKind $kind, string $target): void
    {
        $target = trim($target);
        $actor  = service('currentAdmin')->has() ? service('currentAdmin')->get()->name : AdminLogService::ACTOR_AUTO;

        if ($target === '') {
            throw new ValidationException(['target' => $kind === OgpKind::Answer ? '回答IDを入力してください' : '大喜利のURL・スラッグ・IDを入力してください']);
        }

        try {
            if ($kind === OgpKind::Answer) {
                $answer = $this->answers->find((int) ltrim(strtoupper($target), 'A-'));
                $oogiri = $answer !== null ? $this->oogiris->find($answer->oogiri_id) : null;

                if ($answer === null || $oogiri === null) {
                    throw new NotFoundException();
                }
                $ok = $this->run($kind, $answer->id, (string) $answer->id, 'A-' . $answer->id, $actor, fn () => $this->image->answer($oogiri->odai, $answer->body));
            } else {
                $oogiri = $this->findOogiri($target);
                $ok     = $kind === OgpKind::Recruit
                    ? $this->generateRecruit($oogiri, $actor)
                    : $this->generateResult($oogiri, $this->firstRanked($oogiri), $actor);
            }
        } catch (NotFoundException) {
            throw new ValidationException(['target' => '対象が見つかりません']);
        }

        if (! $ok) {
            throw new ValidationException(['target' => '生成に失敗しました（フォントの配置を確認してください）']);
        }
    }

    /**
     * テンプレートの背景・ロゴを差し替える（writable/ogp-templates/）
     *
     * @param string $asset ASSETS のキー
     *
     * @throws ValidationException
     */
    public function replaceAsset(OgpKind $kind, string $asset, ?UploadedFile $file): void
    {
        if (! isset(self::ASSETS[$asset])) {
            throw new ValidationException(['asset' => '差し替える画像の種類を選んでください']);
        }
        if ($file === null || ! $file->isValid()) {
            throw new ValidationException(['file' => '画像ファイルを選んでください']);
        }
        if (! in_array($file->getMimeType(), ['image/png', 'image/jpeg'], true)) {
            throw new ValidationException(['file' => 'PNG か JPG の画像を選んでください']);
        }
        $dir  = WRITEPATH . 'ogp-templates';
        $name = ($asset === 'logo' ? 'logo' : $kind->value . '-background') . '.png';

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file->move($dir, $name, true);
        service('adminLog')->record('ogp', null, $kind->label() . 'の' . self::ASSETS[$asset] . 'を差し替え');
    }

    /**
     * 生成して記録する。失敗しても例外にしない（投稿の流れを止めない）
     *
     * @param callable(): GdImage $draw
     */
    private function run(OgpKind $kind, int $targetId, string $key, string $target, string $actor, callable $draw): bool
    {
        $error = null;

        try {
            $this->image->save($draw(), $this->path($kind, $key));
        } catch (Throwable $e) {
            // 作り直しに失敗したら古い画像を残さない（非表示にした本文が載ったままにならないように。既定の画像が使われる）
            $this->remove($kind, $key);
            $error = mb_substr($e->getMessage(), 0, 255);
            log_message('error', 'OGP 生成に失敗 (' . $kind->value . ' ' . $targetId . '): ' . $e->getMessage());
        }
        $this->jobs->insert([
            'kind'       => $kind->value,
            'target_id'  => $targetId,
            'target'     => mb_substr($target, 0, 255),
            'actor'      => $actor,
            'succeeded'  => $error === null ? 1 : 0,
            'error'      => $error,
            'created_at' => Time::now()->toDateTimeString(),
        ]);

        return $error === null;
    }

    /**
     * @throws NotFoundException
     */
    private function findOogiri(string $target): Oogiri
    {
        $target = ltrim($target, '#');
        $oogiri = ctype_digit($target) ? $this->oogiris->find((int) $target) : null;

        if ($oogiri === null) {
            // URL かスラッグ
            $slug   = preg_match('#/o/([A-Za-z0-9]+)#', $target, $m) === 1 ? $m[1] : $target;
            $oogiri = $this->oogiris->where('slug', $slug)->first();
        }
        if ($oogiri === null) {
            throw new NotFoundException();
        }

        return $oogiri;
    }
}
