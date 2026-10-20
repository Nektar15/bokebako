<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventName;
use App\Models\EventModel;
use CodeIgniter\I18n\Time;

/**
 * 計測イベントの記録（S-06）。生ログに書くだけで、集計は日次バッチ（stats:daily）が行う
 */
final class EventService
{
    public function __construct(
        private readonly EventModel $model,
        private readonly IdentityService $identity,
    ) {
    }

    /**
     * @param array{oogiri_id?: int|null, answer_id?: int|null, parent_oogiri_id?: int|null, referer?: string|null, utm?: string|null} $attrs
     */
    public function record(EventName $name, array $attrs = []): void
    {
        $this->model->insert([
            'name'             => $name->value,
            'oogiri_id'        => $attrs['oogiri_id'] ?? null,
            'answer_id'        => $attrs['answer_id'] ?? null,
            'parent_oogiri_id' => $attrs['parent_oogiri_id'] ?? null,
            'browser_id_hash'  => $this->identity->browserIdHash() ?: null,
            'referer'          => isset($attrs['referer']) ? mb_substr((string) $attrs['referer'], 0, 255) : null,
            'utm'              => isset($attrs['utm']) ? mb_substr((string) $attrs['utm'], 0, 255) : null,
            'created_at'       => Time::now()->toDateTimeString(),
        ]);
    }

    /**
     * 募集URL流入（U-04 表示時）。リファラと UTM を残す
     */
    public function recordVisit(int $oogiriId): void
    {
        $request = service('request');
        $utm     = [];

        foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $key) {
            $value = (string) $request->getGet($key);
            if ($value !== '') {
                $utm[] = $key . '=' . $value;
            }
        }
        $this->record(EventName::Visit, [
            'oogiri_id' => $oogiriId,
            'referer'   => (string) $request->getServer('HTTP_REFERER'),
            'utm'       => implode('&', $utm),
        ]);
    }
}
