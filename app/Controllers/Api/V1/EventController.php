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
        $json = $this->request->getJSON(true);
        $body = is_array($json) ? $json : (array) $this->request->getPost();
        $name = EventName::tryFrom((string) ($body['name'] ?? ''));

        if ($name === null || ! $name->allowedFromBrowser()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => ['code' => ErrorCode::ValidationFailed->value, 'message' => ErrorCode::ValidationFailed->message()]]);
        }
        $oogiriId = $this->oogiriId((string) ($body['slug'] ?? ''));
        service('event')->record($name, [
            'oogiri_id'        => $oogiriId,
            'answer_id'        => isset($body['answerId']) ? (int) $body['answerId'] : null,
            'parent_oogiri_id' => $name === EventName::ClickCreate ? $oogiriId : $this->oogiriId((string) ($body['from'] ?? '')),
        ]);

        if (in_array($name, [EventName::ShareRecruit, EventName::ShareAnswer, EventName::ShareResult], true) && $oogiriId !== null) {
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
