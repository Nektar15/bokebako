<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Enums\AiVerdict;
use Throwable;

/**
 * OpenAI API（GPT-5 nano）で投稿を補助判定する（S-05、SPEC 9）
 *
 * 送るのは本文だけ（識別情報は送らない）。JSON で「ラベル＋理由」を受け取る。エラー時は未判定（None）。
 * キーは .env の openai.apiKey。
 */
final class OpenAiClient
{
    public const MODEL    = 'gpt-5-nano';
    public const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const PROMPT  = <<<'TXT'
        あなたは日本語の匿名大喜利サービスの投稿を審査する補助者です。次の投稿が「問題なし」「要確認」「非表示推奨」のどれかを判定し、理由を短く述べてください。
        非表示推奨：実在の個人への攻撃・個人情報・差別や脅迫・明確に性的・権利侵害・スパム。要確認：判断が分かれるもの。問題なし：単なるボケ。
        必ず次の JSON だけを返してください：{"label":"ok|review|hide","category":"個人攻撃|個人情報|差別|性的|権利侵害|その他|なし","reason":"60文字以内の日本語"}
        TXT;

    /**
     * @return array{verdict: AiVerdict, reason: string}
     */
    public function judge(string $odai, string $body): array
    {
        $apiKey = (string) env('openai.apiKey', '');

        if ($apiKey === '') {
            return ['verdict' => AiVerdict::None, 'reason' => 'APIキーが設定されていないため未判定です。'];
        }

        try {
            $client   = service('curlrequest', ['timeout' => 20]);
            $response = $client->post(self::ENDPOINT, [
                'headers' => ['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'],
                'json'    => [
                    'model'           => self::MODEL,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => self::PROMPT],
                        ['role' => 'user', 'content' => "お題：{$odai}\n投稿：{$body}"],
                    ],
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return ['verdict' => AiVerdict::None, 'reason' => 'APIの応答がなかったため未判定です。次回のバッチで再判定します。'];
            }
            $data    = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $content = json_decode((string) ($data['choices'][0]['message']['content'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
            $verdict = AiVerdict::tryFrom((string) ($content['label'] ?? '')) ?? AiVerdict::None;
            $reason  = trim((string) ($content['reason'] ?? ''));

            if (($content['category'] ?? 'なし') !== 'なし') {
                $reason = '【' . $content['category'] . '】' . $reason;
            }

            return ['verdict' => $verdict, 'reason' => mb_substr($reason, 0, 500)];
        } catch (Throwable $e) {
            log_message('error', 'OpenAI 判定に失敗: ' . $e->getMessage());

            return ['verdict' => AiVerdict::None, 'reason' => 'APIの応答がなかったため未判定です。次回のバッチで再判定します。'];
        }
    }
}
