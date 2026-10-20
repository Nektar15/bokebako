<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * 操作履歴の1件（誰が・いつ・何をしたか。CODING_RULES 7-1）
 *
 * TODO(inner): 工程4で admin_logs テーブル（SPEC 3.3 の admins に併設）に保存する。
 *
 * @property string $actor      操作した人（管理者名、「開催者」、「自動」）
 * @property Time   $created_at
 * @property int    $id
 * @property string $what       操作の内容（例：「結果を確定（3件）」）
 */
final class AdminLog extends Entity
{
    protected $dates = ['created_at'];
    protected $casts = ['id' => 'int'];
}
