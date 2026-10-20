<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Oogiri;

/**
 * 大喜利（oogiris テーブル）の読み書き
 *
 * @method Oogiri|null  find(int|string|null $id = null)
 * @method list<Oogiri> findAll(int $limit = 0, int $offset = 0)
 * @method Oogiri|null  first()
 */
final class OogiriModel extends BaseModel
{
    protected $table         = 'oogiris';
    protected $returnType    = Oogiri::class;
    protected $allowedFields = ['slug', 'odai', 'host_token_hash', 'passphrase_hash', 'browser_id_hash', 'ip_hash', 'status', 'status_before_hide', 'fixable', 'starts_at', 'ends_at', 'selection_deadline', 'decided_at', 'pv', 'answer_count', 'share_count', 'derived_count', 'report_count', 'parent_oogiri_id'];
}
