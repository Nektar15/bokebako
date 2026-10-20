<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Oogiri;
use App\Enums\ErrorCode;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\FixtureContext;
use App\Services\Fixtures\OogiriFixture;

/**
 * 大喜利の取得・作成（U-01〜U-03、募集URLの振り分け）
 */
final class OogiriService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * 募集URLのスラッグから大喜利を取得する。削除済みも返す（U-13 を出すため）
     *
     * @throws NotFoundException
     */
    public function findBySlug(string $slug): Oogiri
    {
        // TODO(inner): OogiriModel から取得する。戻り値の形は変えない
        if ($slug === 'none') {
            throw new NotFoundException();
        }

        return OogiriFixture::oogiri(FixtureContext::name(), $slug);
    }

    /**
     * 大喜利を作成する（U-02「同意して開催する」）
     *
     * @param array{odai?: string, passphrase?: string, from?: string} $input
     *
     * @throws AppException        レート制限
     * @throws ValidationException 文字数・NGワード
     */
    public function create(array $input): Oogiri
    {
        $errors = $this->validate($input);

        // TODO(inner): NGワード判定（投稿拒否は即エラー、要確認は通して記録）、レート制限、CAPTCHA
        match (FixtureContext::error()) {
            'ngword'    => $errors['odai'] = ErrorCode::NgWord->message(),
            'ratelimit' => throw new AppException(ErrorCode::RateLimited),
            default     => null,
        };

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // TODO(inner): スラッグ・開催者トークンの発行、合言葉のハッシュ保存、OGP生成、「開催」イベントの記録
        return OogiriFixture::oogiri('open_empty', 'k7Qm2xPz');
    }

    /**
     * @param array{odai?: string, passphrase?: string} $input
     *
     * @return array<string, string>
     */
    private function validate(array $input): array
    {
        $odai = trim((string) ($input['odai'] ?? ''));
        $max  = $this->settings->int('odai_max');

        if ($odai === '') {
            return ['odai' => 'お題を入力してください'];
        }
        if (mb_strlen($odai) > $max) {
            return ['odai' => "お題は{$max}文字以内で入力してください"];
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $odai) === 1) {
            return ['odai' => '使えない文字が含まれています'];
        }

        return [];
    }
}
