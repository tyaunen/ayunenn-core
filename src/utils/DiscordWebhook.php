<?php
namespace ayutenn\core\utils;

use ayutenn\core\config\Config;

/**
 * 【概要】
 * Discord Webhook送信用クラス
 *
 * 【解説】
 * DiscordのWebhookを利用して、指定したチャンネルにメッセージを送信します。
 * $embedsは外部サービスでjsonデータを作ることを想定しています。
 * https://discohook.app/
 *
 * 【無駄口】
 * とくになし
 *
 */
class DiscordWebhook
{
    public function __construct(
        private array $embeds
    ){}

    public function sendWebhook($webhookurl)
    {
        $username = Config::getConfig('DISCORD_WEBHOOK_USER_NAME');
        $avatar_url = Config::getConfig('DISCORD_WEBHOOK_AVATAR_ICON');

        // Webhookデータの構築
        $webhook_data = [
            'username' => $username,
            'avatar_url' => $avatar_url,
            'embeds' => $this->embeds
        ];

        // JSON変換
        $json_data = json_encode($webhook_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // curlオプションを配列で設定
        $curl_options = [
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1
        ];

        // curl実行
        $ch = curl_init($webhookurl);
        foreach ($curl_options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }
        $response = curl_exec($ch);

        return $response;
    }
}
