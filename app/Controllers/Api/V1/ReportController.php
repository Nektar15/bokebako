<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 通報の受付（S-04）。JS からヘッダーで CSRF トークンを送る（CODING_RULES 7章）
 */
final class ReportController extends BaseController
{
    /**
     * POST /api/v1/reports {slug, type: odai|answer, id, reason}
     */
    public function create(): ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $body = is_array($json) ? $json : (array) $this->request->getPost();

        try {
            $oogiri = service('oogiri')->findBySlug((string) ($body['slug'] ?? ''));
            service('report')->create($oogiri, ['type' => (string) ($body['type'] ?? ''), 'id' => (string) ($body['id'] ?? ''), 'reason' => (string) ($body['reason'] ?? '')]);
        } catch (ValidationException $e) {
            return $this->fail(ErrorCode::ValidationFailed, implode(' ', $e->errors));
        } catch (NotFoundException) {
            return $this->fail(ErrorCode::NotFound);
        } catch (AppException $e) {
            return $this->fail($e->errorCode, $e->getMessage());
        }

        return $this->response->setJSON(['ok' => true, 'data' => null]);
    }

    private function fail(ErrorCode $code, ?string $message = null): ResponseInterface
    {
        return $this->response->setStatusCode($code->httpStatus())->setJSON(['ok' => false, 'error' => ['code' => $code->value, 'message' => $message ?? $code->message()]]);
    }
}
