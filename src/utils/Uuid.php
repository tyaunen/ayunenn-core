<?php
namespace ayutenn\core\utils;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * 【概要】
 * UUID生成クラス
 *
 * 【解説】
 * UUIDv7を生成するためのクラスです。
 *
 * 【無駄口】
 * UUIDv7は、タイムスタンプを使用しているのでソートした時に時系列順に並ぶ。
 * 主に、サロゲートキーだと困るシーンに使う。
 *
 * 例えばURLに現れるとスクレイピングされるから困る時とか。
 * インサート前にPrimary Keyがわかっていて欲しい時とか。
 *
 */
class Uuid
{
    /**
     * UUIDv7を生成する
     *
     * @return string UUIDv7形式のUUID
     */
    static function generateUuid7(): string
    {
        // 現在のタイムスタンプ（ミリ秒単位）
        $time = (int) (microtime(true) * 1000);

        // タイムスタンプをビッグエンディアン形式の16進数文字列に変換
        $timeHex = str_pad(dechex($time), 12, '0', STR_PAD_LEFT);

        // ランダムデータ（バージョン部分を含む）
        $random = bin2hex(random_bytes(10));

        // UUIDのフォーマットに従って組み立て
        $uuid7 = sprintf(
            '%s-%s-7%s-%s-%s',
            substr($timeHex, 0, 8),  // time_high
            substr($timeHex, 8, 4),  // time_mid
            substr($random, 0, 3),   // time_low_and_version (version 7)
            substr($random, 3, 4),   // clock_seq_and_reserved
            substr($random, 7, 12)   // node
        );

        return $uuid7;
    }
}
