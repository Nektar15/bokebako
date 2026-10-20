<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 管理者の権限（SPEC A-01：一般管理者／投稿元識別情報を見られる上位管理者）
 */
enum AdminRole: string
{
    case General = 'general';
    case Super   = 'super';

    public function label(): string
    {
        return match ($this) {
            self::General => '一般管理者',
            self::Super   => '上位管理者',
        };
    }

    /**
     * 投稿元識別情報（ハッシュ化IP・ブラウザID）を閲覧できるか
     */
    public function canViewSource(): bool
    {
        return $this === self::Super;
    }
}
