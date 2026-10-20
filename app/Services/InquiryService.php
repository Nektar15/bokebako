<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Inquiry;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\InquiryModel;

/**
 * お問い合わせ管理（A-09）：一覧・詳細・対応状況・対応メモ
 *
 * 受け付ける側（U-18）は ContactService。対応メモは admin_logs（target_type = inquiry）に残す。
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

    public function __construct(private readonly InquiryModel $inquiries)
    {
    }

    /**
     * 一覧（新しい順。絞り込み）
     *
     * @param InquiryFilter $filter
     *
     * @return list<Inquiry>
     */
    public function list(array $filter): array
    {
        $builder = $this->inquiries->builder();

        if ($filter['status'] !== 'all') {
            $builder->where('status', $filter['status']);
        }
        if ($filter['type'] !== 'all') {
            $builder->where('type', $filter['type']);
        }
        OogiriAdminService::applyPeriod($builder, $filter['period']);
        $rows = $builder->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get(100)->getResultArray();

        return array_map(fn (array $row) => $this->withMemos(new Inquiry($row)), $rows);
    }

    /**
     * 対応状況ごとの件数
     *
     * @return array<string, int> InquiryStatus の値（'all' を含む） => 件数
     */
    public function counts(): array
    {
        $counts = ['all' => 0];

        foreach (InquiryStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($this->inquiries->builder()->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $row) {
            $counts[$row['status']] = (int) $row['n'];
            $counts['all'] += (int) $row['n'];
        }

        return $counts;
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Inquiry
    {
        $inquiry = $this->inquiries->find($id);

        if ($inquiry === null) {
            throw new NotFoundException();
        }

        return $this->withMemos($inquiry);
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
        $inquiry = $this->find($id);
        $status  = InquiryStatus::tryFrom((string) ($input['status'] ?? ''));
        $memo    = trim((string) ($input['memo'] ?? ''));

        if (($input['status'] ?? '') !== '' && $status === null) {
            throw new ValidationException(['status' => '対応状況を選んでください']);
        }
        if ($status === null && $memo === '') {
            throw new ValidationException(['memo' => '対応メモを入力してください']);
        }
        if ($status !== null && $status !== $inquiry->status()) {
            $this->inquiries->update($id, ['status' => $status->value]);
            service('adminLog')->record('inquiry', $id, '対応状況を「' . $status->label() . '」に変更');
        }
        if ($memo !== '') {
            service('adminLog')->record('inquiry', $id, $memo);
        }
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

    private function withMemos(Inquiry $inquiry): Inquiry
    {
        $inquiry->memos = service('adminLog')->listFor('inquiry', $inquiry->id, 20);

        return $inquiry;
    }
}
