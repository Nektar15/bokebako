<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Ad;
use App\Enums\AdSlot;
use App\Enums\AdStatus;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\AdModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;

/**
 * 広告（A-07）：ユーザー向けの枠への表示と、管理画面の登録・編集
 *
 * @phpstan-type SlotAd  array{id:int, image:string, alt:string}
 * @phpstan-type AdInput array{name?: string, sponsor?: string, url?: string, slot?: string, starts_at?: string, ends_at?: string, action?: string}
 */
final class AdService
{
    public const CACHE_KEY = 'ads_live';

    /**
     * 画像の置き場（public/ からの相対）
     */
    public const IMAGE_DIR = 'assets/ads';

    public function __construct(private readonly AdModel $ads)
    {
    }

    /**
     * ユーザー向けの枠に出す広告。掲載期間内の公開中の広告から枠ごとに1本（複数あればローテーション）。表示回数を記録する
     *
     * @return array{top: SlotAd|null, bottom: SlotAd|null}
     */
    public function slots(): array
    {
        $live = cache(self::CACHE_KEY);

        if (! is_array($live)) {
            $now  = Time::now()->toDateTimeString();
            $rows = $this->ads->where('status', Ad::STATUS_PUBLISHED)->where('starts_at <=', $now)->where('ends_at >=', $now)->findAll(50);
            $live = ['top' => [], 'bottom' => []];

            foreach ($rows as $ad) {
                $live[$ad->slot][] = ['id' => $ad->id, 'image' => base_url($ad->image), 'alt' => $ad->name . '（' . $ad->sponsor . '）'];
            }
            cache()->save(self::CACHE_KEY, $live, 300);
        }
        $slots = ['top' => null, 'bottom' => null];

        foreach (array_keys($slots) as $slot) {
            $candidates = $live[$slot] ?? [];

            if ($candidates === []) {
                continue;
            }
            $ad = $candidates[array_rand($candidates)]; // ローテーション
            $this->ads->increment($ad['id'], 'impressions');
            $slots[$slot] = $ad;
        }

        return $slots;
    }

    /**
     * クリック計測つきのリダイレクト先（/ad/{id}/click）。公開中でなければ null
     */
    public function click(int $id): ?string
    {
        $ad = $this->ads->find($id);

        if ($ad === null || $ad->status() !== AdStatus::Live) {
            return null;
        }
        $this->ads->increment($id, 'clicks');

        return $ad->url;
    }

    /**
     * 管理画面の一覧（新しい順）
     *
     * @return list<Ad>
     */
    public function list(): array
    {
        return $this->ads->orderBy('starts_at', 'DESC')->orderBy('id', 'DESC')->findAll(100);
    }

    /**
     * 枠ごとの実績（掲載中の本数と、累計の表示回数・クリック数）
     *
     * @return array<string, array{live: int, impressions: int, clicks: int}> AdSlot の値 => 実績
     */
    public function slotSummary(): array
    {
        $summary = ['top' => ['live' => 0, 'impressions' => 0, 'clicks' => 0], 'bottom' => ['live' => 0, 'impressions' => 0, 'clicks' => 0]];

        foreach ($this->ads->findAll(500) as $ad) {
            $summary[$ad->slot]['impressions'] += $ad->impressions;
            $summary[$ad->slot]['clicks'] += $ad->clicks;
            $summary[$ad->slot]['live'] += $ad->status() === AdStatus::Live ? 1 : 0;
        }

        return $summary;
    }

    /**
     * @throws NotFoundException
     */
    public function find(int $id): Ad
    {
        $ad = $this->ads->find($id);

        if ($ad === null) {
            throw new NotFoundException();
        }

        return $ad;
    }

    /**
     * 登録する（action が 'publish' なら公開、それ以外は下書き）
     *
     * @param AdInput $input
     *
     * @throws ValidationException
     */
    public function create(array $input, ?UploadedFile $image): void
    {
        $this->validate($input);
        $path = $this->storeImage($image);

        if ($path === null) {
            throw new ValidationException(['image' => '広告画像（PNG・JPG）を選んでください']);
        }
        $id = $this->ads->insert($this->row($input) + ['image' => $path]);
        cache()->delete(self::CACHE_KEY);
        service('adminLog')->record('ad', $id, '広告を登録（' . (($input['action'] ?? '') === 'publish' ? '公開' : '下書き') . '）：' . trim((string) $input['name']));
    }

    /**
     * 編集する（公開・停止もここ）
     *
     * @param AdInput $input
     *
     * @throws ValidationException
     */
    public function update(int $id, array $input, ?UploadedFile $image): void
    {
        $before = $this->find($id);
        $this->validate($input);
        $row  = $this->row($input);
        $path = $this->storeImage($image);

        if ($path !== null) {
            $row['image'] = $path;
        }
        $this->ads->update($id, $row);

        if ($path !== null) {
            $this->removeImage($before->image); // 差し替えた古い画像を残さない（監査 2026-10-21）
        }
        cache()->delete(self::CACHE_KEY);
        service('adminLog')->record('ad', $id, '広告を編集（' . (($input['action'] ?? '') === 'publish' ? '公開' : '停止') . '）：' . trim((string) $input['name']));
    }

    public function delete(int $id): void
    {
        $ad = $this->find($id);
        $this->ads->delete($id);
        $this->removeImage($ad->image); // 画像も消す（管理画面マニュアル「物理削除」。監査 2026-10-21）
        cache()->delete(self::CACHE_KEY);
        service('adminLog')->record('ad', $id, '広告を削除：' . $ad->name);
    }

    /**
     * @param AdInput $input
     *
     * @return array<string, string>
     */
    private function row(array $input): array
    {
        return [
            'name'    => trim((string) $input['name']),
            'sponsor' => trim((string) $input['sponsor']),
            'url'     => trim((string) $input['url']),
            'slot'    => (string) $input['slot'],
            'status'  => match ($input['action'] ?? '') {
                'publish' => Ad::STATUS_PUBLISHED,
                'draft'   => Ad::STATUS_DRAFT,
                default   => Ad::STATUS_STOPPED,
            },
            'starts_at' => str_replace('T', ' ', (string) $input['starts_at']) . ':00',
            'ends_at'   => str_replace('T', ' ', (string) $input['ends_at']) . ':00',
        ];
    }

    /**
     * 画像を public/assets/ads/ に保存して相対パスを返す。ファイルがなければ null
     *
     * @throws ValidationException PNG・JPG 以外
     */
    /**
     * 広告画像のファイルを消す（広告画像の置き場所の中だけ）
     */
    private function removeImage(string $path): void
    {
        if (! str_starts_with($path, self::IMAGE_DIR . '/')) {
            return;
        }
        $file = FCPATH . self::IMAGE_DIR . '/' . basename($path);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if ($image === null || ! $image->isValid()) {
            return null;
        }
        if (! in_array($image->getMimeType(), ['image/png', 'image/jpeg'], true)) {
            throw new ValidationException(['image' => '広告画像は PNG か JPG にしてください']);
        }
        $name = $image->getRandomName();
        $image->move(FCPATH . self::IMAGE_DIR, $name);

        return self::IMAGE_DIR . '/' . $name;
    }

    /**
     * @param AdInput $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): void
    {
        $errors = [];
        $url    = trim((string) ($input['url'] ?? ''));

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = '広告名を入力してください';
        }
        if (trim((string) ($input['sponsor'] ?? '')) === '') {
            $errors['sponsor'] = 'スポンサー名を入力してください';
        }
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || ! str_starts_with($url, 'https://')) {
            $errors['url'] = 'リンク先URLを https:// から入力してください';
        }
        if (AdSlot::tryFrom((string) ($input['slot'] ?? '')) === null) {
            $errors['slot'] = '掲載場所を選んでください';
        }

        // 日時の形（datetime-local：YYYY-MM-DDTHH:MM）を確かめる。ほかの形を「:00」を足して保存しない（監査 2026-10-21）
        foreach (['starts_at', 'ends_at'] as $key) {
            if (($input[$key] ?? '') !== '' && preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/', (string) $input[$key]) !== 1) {
                $errors['period'] = '掲載期間の日時を正しく入力してください';
            }
        }
        if (($input['starts_at'] ?? '') === '' || ($input['ends_at'] ?? '') === '') {
            $errors['period'] = '掲載期間を入力してください';
        } elseif ($input['starts_at'] >= $input['ends_at']) {
            $errors['period'] = '掲載終了は掲載開始より後にしてください';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
