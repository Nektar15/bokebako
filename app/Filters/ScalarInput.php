<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * フォームの値を文字列にそろえる（監査 2026-10-21）
 *
 * 「body[]=x」のように配列で送られると、(string) で受ける箇所が「Array to string conversion」で 500 になる。
 * 配列で受ける項目（ARRAY_FIELDS）以外の配列は空文字にし、配列で受ける項目の中身も文字列だけにする。
 */
class ScalarInput implements FilterInterface
{
    /**
     * 配列で受ける項目（U-09 の順位、A-10 の AI 判定の対象）
     */
    public const ARRAY_FIELDS = ['ranks', 'ai_targets'];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        foreach (['get' => &$_GET, 'post' => &$_POST] as $name => &$global) {
            $values = $request->{'get' . ucfirst($name)}() ?? [];

            if (! is_array($values)) {
                continue;
            }
            $clean = self::clean($values);

            if ($clean !== $values) {
                $request->setGlobal($name, $clean);
                $global = $clean;
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    public static function clean(array $values): array
    {
        foreach ($values as $key => $value) {
            if (! is_array($value)) {
                continue;
            }
            $values[$key] = in_array($key, self::ARRAY_FIELDS, true)
                ? array_values(array_filter($value, static fn ($v): bool => is_string($v) || is_int($v)))
                : '';
        }

        return $values;
    }
}
