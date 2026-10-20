<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\AdminLog;
use App\Models\AdminLogModel;
use CodeIgniter\I18n\Time;

/**
 * 操作履歴（CODING_RULES 7-1：誰が・いつ・何をしたか）。管理者の操作、開催者の操作、自動処理をすべてここに残す
 */
final class AdminLogService
{
    public const ACTOR_HOST = '開催者';
    public const ACTOR_AUTO = '自動';

    public function __construct(private readonly AdminLogModel $model)
    {
    }

    /**
     * 記録する。actor を省くと「現在の管理者」（なければ「自動」）
     *
     * @param string $targetType oogiri / answer / inquiry / setting / ng_word / ad / ogp
     */
    public function record(string $targetType, ?int $targetId, string $what, ?string $actor = null): void
    {
        $current = service('currentAdmin');
        $adminId = null;

        if ($actor === null) {
            $actor   = $current->has() ? $current->get()->name : self::ACTOR_AUTO;
            $adminId = $current->has() ? $current->get()->id : null;
        }
        $this->model->insert([
            'actor'       => $actor,
            'admin_id'    => $adminId,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'what'        => mb_substr($what, 0, 500),
            'created_at'  => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * 対象の履歴（新しい順）
     *
     * @return list<AdminLog>
     */
    public function listFor(string $targetType, int $targetId, int $limit = 50): array
    {
        return $this->model->where('target_type', $targetType)->where('target_id', $targetId)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }
}
