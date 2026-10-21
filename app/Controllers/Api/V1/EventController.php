<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Enums\ErrorCode;
use App\Enums\EventName;
use App\Models\OogiriModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 計測イベントの記録（S-06）。sendBeacon で届くので CSRF の対象外（ADR 0002）。記録するだけで何も変更しない
 */
final class EventController extends BaseController
{
    /**
     * POST /api/v1/events {name, slug?, answerId?, from?}
     */
    public function create(): ResponseInterface
    {
        $body = $this->apiBody();
        $name = EventName::tryFrom((string) ($body['name'] ?? ''));

        if ($name === null || ! $name->allowedFromBrowser()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => ['code' => ErrorCode::ValidationFailed->value, 'message' => ErrorCode::ValidationFailed->message()]]);
        }
        $identity = service('identity');
        $ip       = substr($identity->ipHash(), 0, 16);

        // 水増しを防ぐ：同じ IP からは1分に120件まで。超えた分は記録せずに ok を返す（監査 2026-10-21）
        if (! service('throttler')->check('ev_' . $ip, 120, 60)) {
            return $this->response->setJSON(['ok' => true, 'data' => null]);
        }
        $oogiriId = $this->oogiriId((string) ($body['slug'] ?? ''));
        service('event')->record($name, [
            'oogiri_id'        => $oogiriId,
            'answer_id'        => isset($body['answerId']) ? (int) $body['answerId'] : null,
            'parent_oogiri_id' => $name === EventName::ClickCreate ? $oogiriId : $this->oogiriId((string) ($body['from'] ?? '')),
        ]);

        // シェア数は、同じブラウザ・同じ IP・同じ大喜利・同じ種類につき1時間に1回だけ数える
        if (in_array($name, [EventName::ShareRecruit, EventName::ShareAnswer, EventName::ShareResult], true) && $oogiriId !== null
            && service('throttler')->check('evs_' . substr($identity->browserIdHash(), 0, 12) . '_' . $ip . '_' . $oogiriId . '_' . $name->value, 1, 3600)) {
            (new OogiriModel())->increment($oogiriId, 'share_count');
        }

        return $this->response->setJSON(['ok' => true, 'data' => null]);
    }

    private function oogiriId(string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }
        $row = (new OogiriModel())->select('id')->where('slug', $slug)->first();

        return $row?->id;
    }
}
