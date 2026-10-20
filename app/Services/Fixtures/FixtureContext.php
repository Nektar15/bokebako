<?php

declare(strict_types=1);

namespace App\Services\Fixtures;

/**
 * ?fixture= でダミーの状態を切り替える（development 限定。CODING_RULES 10章）
 *
 * TODO(inner): 工程4で Fixture を外すときに削除する。
 */
final class FixtureContext
{
    public static function name(string $default = OogiriFixture::DEFAULT): string
    {
        if (ENVIRONMENT !== 'development' && ENVIRONMENT !== 'testing') {
            return $default;
        }

        $name = (string) service('request')->getGet('fixture');

        return $name === '' ? $default : $name;
    }

    /** ?fixture=error_xxx の xxx を返す。なければ null */
    public static function error(): ?string
    {
        $name = self::name('');

        return str_starts_with($name, 'error_') ? substr($name, 6) : null;
    }
}
