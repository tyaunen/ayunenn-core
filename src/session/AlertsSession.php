<?php
namespace ayutenn\core\session;

/**
 * 【処理概要】
 * アラートメッセージなどをセッションに格納する機能を提供する。
 *
 * 【解説】
 * Info： ログインに成功しました！みたいな、正常な処理の終了を通知するメッセージ
 * Alert： 未入力の欄があります！みたいな、正常なフローの中でユーザーに対応を促したいときの通知メッセージ
 * Error： DB接続に失敗しました！みたいな、正規の手順による操作で発生することを想定していないイベントの通知メッセージ
 *
 * 【無駄口】
 * 特になし
 *
 */

class AlertsSession
{

    // メッセージ分類定数
    const ERROR = 'error';
    const ALERT = 'alert';
    const INFO = 'info';

    /**
     * getAlerts
     * セッションに格納されているアラートメッセージのarrayを取得する。
     * 取得時、アラートメッセージセッションはクリアされる。
     *
     * @return array
     */
    public static function getAlerts(): array
    {
        if (isset($_SESSION["AY_INFO_MESSAGE"])) {
            $rtn = $_SESSION["AY_INFO_MESSAGE"];
            unset($_SESSION["AY_INFO_MESSAGE"]);
            return $rtn;
        }
        return [];
    }

    /**
     * putAlertMessageIntoSession
     * アラート通知を作成する。
     *
     * @param string $text メッセージ本体
     * @return void
     */
    public static function putAlertMessageIntoSession(
        string $text
    ): void {
        self::putIntoSession(
            self::ALERT,
            $_SESSION['AY_ACCESS_KEY'] ?? "",
            $text
        );
    }

    /**
     * putInfoMessageIntoSession
     * info通知を作成する。
     *
     * @param string $text メッセージ本体
     * @return void
     */
    public static function putInfoMessageIntoSession(
        string $text
    ): void {
        self::putIntoSession(
            self::INFO,
            $_SESSION['AY_ACCESS_KEY'] ?? "",
            $text
        );
    }

    /**
     * putErrorMessageIntoSession
     * エラー通知を作成する。
     *
     * @param string $message_id メッセージID エラー原因調査のための一意なログのID
     * @param string $text メッセージ本体
     * @return void
     */
    public static function putErrorMessageIntoSession(
        string $text
    ): void
    {
        self::putIntoSession(
            self::ERROR,
            $_SESSION['AY_ACCESS_KEY'] ?? "",
            $text
        );
    }

    /**
     * putIntoSession
     * Info通知を作成する。
     *
     * @param string $alert_type アラートタイプ AlertsSession::INFO or ALERT or ERROR
     * @param string $alert_id メッセージID エラー原因調査のための一意なログのID info, alertの場合は空欄でもよい
     * @param string $text メッセージ本体
     * @return void
     */
    private static function putIntoSession(
        string $alert_type,
        string $alert_id,
        string $text
    ): void
    {
        if (!isset($_SESSION["AY_INFO_MESSAGE"])) {
            $_SESSION["AY_INFO_MESSAGE"] = [];
        }

        $_SESSION["AY_INFO_MESSAGE"][] = [
            "alert_type" => $alert_type,
            "alert_id" => $alert_id,
            "text" => $text
        ];
    }
}
