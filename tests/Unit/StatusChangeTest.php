<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Entities\AdminUser;
use App\Enums\AdminRole;
use App\Enums\ErrorCode;
use App\Enums\OogiriStatus;
use App\Exceptions\AppException;
use App\Models\AnswerModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Config\Services;
use RuntimeException;
use Tests\Support\DatabaseTestCase;

/**
 * 非表示・再公開・削除と回答数、保存と件数のトランザクション（レビュー「状態遷移の集約」「保存と副作用の境界」）
 *
 * @internal
 */
final class StatusChangeTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        service('currentAdmin')->set(new AdminUser(1, 'test-admin', AdminRole::Super));
    }

    private function answerCount(int $oogiriId): int
    {
        return (int) $this->db->table('oogiris')->where('id', $oogiriId)->get()->getRow()->answer_count;
    }

    public function testAnswerCountStaysConsistentThroughRepeatedOperations(): void
    {
        $before = $this->answerCount(12480);
        service('answerAdmin')->hide(90411);
        service('answerAdmin')->hide(90411);   // 2回目は何もしない
        $this->assertSame($before - 1, $this->answerCount(12480));
        service('answerAdmin')->delete(90411); // 非表示 → 削除では減らない
        $this->assertSame($before - 1, $this->answerCount(12480));
        service('answerAdmin')->publish(90411);
        $this->assertSame($before, $this->answerCount(12480));
    }

    public function testDeletedDecidedOogiriIsRestoredAsDecided(): void
    {
        // 確定済み（12452）を削除 → 再公開しても、募集中に戻らず確定済みのまま
        service('oogiriAdmin')->delete(12452);
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => OogiriStatus::Deleted->value, 'status_before_hide' => 'decided']);
        service('oogiriAdmin')->publish(12452);
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => OogiriStatus::Decided->value]);
    }

    public function testHiddenThenDeletedKeepsTheOriginalState(): void
    {
        service('oogiriAdmin')->hide(12452);
        service('oogiriAdmin')->delete(12452);
        service('oogiriAdmin')->publish(12452);
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => OogiriStatus::Decided->value]);
    }

    public function testPublishDoesNothingToVisibleOogiri(): void
    {
        service('oogiriAdmin')->publish(12452);
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => OogiriStatus::Decided->value]);
    }

    public function testHostCannotDeleteDecidedOogiri(): void
    {
        $request = new IncomingRequest(new App(), new SiteURI(new App(), 'host/x'), null, new UserAgent());
        $request->setHeader('X-Host-Token', 'dev-token-q8Lr4nTb');
        Services::injectMock('request', $request);

        try {
            service('host')->delete(service('oogiri')->findBySlug('q8Lr4nTb'));
            $this->fail('確定済みは消せないはず');
        } catch (AppException $e) {
            $this->assertSame(ErrorCode::Forbidden, $e->errorCode);
        }
        $this->seeInDatabase('oogiris', ['id' => 12452, 'status' => OogiriStatus::Decided->value]);
    }

    public function testTransactionRollsBackOnException(): void
    {
        $model = model(AnswerModel::class);

        try {
            $model->transaction(function () use ($model): void {
                $model->update(90411, ['body' => 'CHANGED']);
                $this->db->table('oogiris')->where('id', 12480)->set('answer_count', 'answer_count + 1', false)->update();

                throw new RuntimeException('途中で失敗');
            });
        } catch (RuntimeException) {
        }
        $this->dontSeeInDatabase('answers', ['id' => 90411, 'body' => 'CHANGED']);
    }
}
