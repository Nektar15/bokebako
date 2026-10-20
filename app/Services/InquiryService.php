<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Inquiry;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\Fixtures\InquiryFixture;

/**
 * お問い合わせ管理（A-09）：一覧・詳細・対応状況・対応メモ
 *
 * 受け付ける側（U-18）は ContactService。
 *
 * @phpstan-type InquiryFilter array{status: string, type: string, period: string}
 */
final class InquiryService
{
    /**
     * 期間の選択肢（値 => 表示名）
     */
    public const PERIODS = [
        'all'    => '期間：すべて',
        '7d'     => '7日',
        '30d'    => '30日',
        'custom' => '期間指定',
    ];

    /**
     * 一覧（新しい順。絞り込み）
     *
     * @param InquiryFilter $filter
     *
     * @return list<Inquiry>
     */
    public function list(array $filter): array
    {
        // TODO(inner): InquiryModel から取る（LIMIT 必須）。戻り値の形は変えない
        $items = InquiryFixture::all();

        if ($filter['status'] !== 'all') {
            $items = array_filter($items, static fn (Inquiry $i) => $i->status()->value === $filter['status']);
        }
        if ($filter['type'] !== 'all') {
            $items = array_filter($items, static fn (Inquiry $i) => $i->type()->value === $filter['type']);
        }

        return array_values($items);
    }

    /**
     * 対応状況ごとの件数
     *
     * @return array<string, int> InquiryStatus の値（'all' を含む） => 件数
     */
    public function counts(): array
    {
        // TODO(inner): status ごとに COUNT する
        return InquiryFixture::counts();
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Inquiry
    {
        foreach (InquiryFixture::all() as $inquiry) {
            if ($inquiry->id === $id) {
                return $inquiry;
            }
        }

        throw new NotFoundException();
    }

    /**
     * 対応状況の変更と対応メモの追加（どちらか一方でもよい）
     *
     * @param array{status?: string, memo?: string} $input
     *
     * @throws ValidationException
     */
    public function update(int $id, array $input): void
    {
        $this->find($id);
        $status = (string) ($input['status'] ?? '');
        $memo   = trim((string) ($input['memo'] ?? ''));

        if ($status !== '' && InquiryStatus::tryFrom($status) === null) {
            throw new ValidationException(['status' => '対応状況を選んでください']);
        }
        if ($status === '' && $memo === '') {
            throw new ValidationException(['memo' => '対応メモを入力してください']);
        }
        // TODO(inner): status の保存、memo を対応メモ（管理者名・日時つき）として保存、操作履歴の記録
    }

    /**
     * 種別の選択肢（値 => 表示名）
     *
     * @return array<string, string>
     */
    public function types(): array
    {
        return InquiryType::options();
    }
}
