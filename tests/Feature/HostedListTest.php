<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filters\AjaxOnly;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * このブラウザで開催した大喜利の一覧（D-02、ADR 0015）：正しいトークンのものだけ返す
 *
 * @internal
 */
final class HostedListTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    private function hosted(array $items): array
    {
        $result = $this->withHeaders([AjaxOnly::HEADER => 'XMLHttpRequest'])->withBodyFormat('json')->post('/api/v1/hosted', ['items' => $items]);
        $result->assertOK();

        return json_decode((string) $result->getJSON(), true)['data']['items'];
    }

    public function testReturnsOnlyOogiriWithValidToken(): void
    {
        $items = $this->hosted([
            ['slug' => 'k7Qm2xPz', 'token' => 'dev-token-k7Qm2xPz'],
            ['slug' => 'q8Lr4nTb', 'token' => 'dev-token-q8Lr4nTb'],
            ['slug' => 'Ee4fGg5h', 'token' => 'まちがったトークン'],
            ['slug' => 'zzzzzzzz', 'token' => 'dev-token-zzzzzzzz'],
        ]);

        $this->assertSame(['k7Qm2xPz', 'q8Lr4nTb'], array_column($items, 'slug'));
        $this->assertSame('募集中', $items[0]['statusLabel']);
        $this->assertSame(['slug', 'odai', 'status', 'statusLabel', 'answerCount', 'startsAt'], array_keys($items[0]));
    }

    public function testMalformedInputReturnsEmpty(): void
    {
        $this->assertSame([], $this->hosted([]));
        $this->assertSame([], $this->hosted([['slug' => ['x'], 'token' => '1'], ['slug' => 'k7Qm2xPz']]));
    }

    public function testListIsCapped(): void
    {
        $items = array_fill(0, 50, ['slug' => 'k7Qm2xPz', 'token' => 'dev-token-k7Qm2xPz']);
        $this->assertCount(1, $this->hosted($items));
    }

    public function testSectionIsOnTopAndHostPages(): void
    {
        $this->get('/')->assertSee('あなたが開催した大喜利');
        $this->asHost()->get('/host/k7Qm2xPz')->assertSee('あなたが開催した大喜利');
    }
}
