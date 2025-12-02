<?php
namespace ayutenn\core\config;

require_once __DIR__ . '/../../vendor/autoload.php';
use Exception;

/**
 * 【概要】
 * コンフィグクラス
 *
 * 【解説】
 * 環境によって異なる設定を扱うcongig.jsonファイルを扱います。
 *
 * 【無駄口】
 * 環境依存の設定を管理するためのクラス。
 * 環境じゃなくアプリに依存する設定はAppクラスで扱う。
 *
 */
class Config
{
    // ファイルごとの設定値を保持する多次元配列
    // 例: ['config' => [...], 'app' => [...]]
    private static array $repository = [];

    // 設定ファイルがあるベースディレクトリ
    public static string $baseDirectory = __DIR__;

    // 最後に読み込んだファイルの時刻
    public static string $lastLoadedTime = '';

    /**
     * 読み込んだものをリセット
     *
     * @param string $directory ディレクトリパス
     * @return void
     */
    public static function reset(string $directory): void
    {
        // 末尾のスラッシュを除去して統一
        self::$baseDirectory = rtrim($directory, '/');
        self::$repository = [];
        self::$lastLoadedTime = '';
    }

    /**
     * 共通読み込み処理（内部用）
     * * @param string $name ファイル名（拡張子なし: 'config' や 'app'）
     * @return void
     */
    private static function load(string $name): void
    {
        // すでに読み込み済みなら何もしない
        if (isset(self::$repository[$name])) {
            return;
        }

        $filePath = self::$baseDirectory . '/' . $name . '.json';

        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            self::$repository[$name] = json_decode($json, true);

            // 読み込み時間を更新（最後に読み込んだファイルの時刻）
            self::$lastLoadedTime = date('YmdHis');
        } else {
            throw new Exception("
                {$name}.jsonファイルが見つかりませんでした。
                指定したディレクトリにファイルが存在することを確認してください。
                （{$filePath}）"
            );
        }
    }

    /**
     * 汎用的な値取得メソッド（内部用）
     * * @param string $name ファイル名（拡張子なし）
     * @param string $key  JSONのキー
     * @return string
     */
    private static function getValue(string $name, string $key): string
    {
        // 未ロードなら読み込む
        self::load($name);

        $val = self::$repository[$name][$key] ?? null;

        if (is_null($val)) {
            $filePath = self::$baseDirectory . '/' . $name . '.json';
            throw new Exception("
                {$name}の設定キー '{$key}' が見つかりませんでした。
                {$name}.jsonファイルの設定を確認してください。
                （{$filePath}）"
            );
        }

        return (string)$val;
    }

    /**
     * config.json から設定を取得
     *
     * @param string $key config.jsonのキー
     * @return string
     */
    public static function getConfig(string $key): string
    {
        return self::getValue('config', $key);
    }

    /**
     * app.json から設定を取得
     *
     * @param string $key app.jsonのキー
     * @return string
     */
    public static function getAppSetting(string $key): string
    {
        return self::getValue('app', $key);
    }

    /**
     * テスト用にコンフィグを設定
     *
     * @param string $name ファイル名（'config' または 'app'）
     * @param string $key キー
     * @param string $value 設定値
     * @return void
     */
    public static function setConfigForUnitTest(string $name, string $key, string $value): void
    {
        if (!isset(self::$repository[$name])) {
            self::load($name);
        }
        self::$repository[$name][$key] = $value;
    }
}