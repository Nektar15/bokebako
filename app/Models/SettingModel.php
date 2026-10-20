<?php

declare(strict_types=1);

namespace App\Models;

/**
 * 運用設定（settings テーブル）の読み書き
 */
final class SettingModel extends BaseModel
{
    protected $table         = 'settings';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'value'];
}
