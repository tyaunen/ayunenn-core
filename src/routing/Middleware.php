<?php
namespace ayutenn\core\routing;

/**
 * 【概要】
 * ミドルウェア抽象クラス
 *
 * 【解説】
 * ルートに条件を付与したり、ルート前に処理を行うクラス
 *
 * RouterはURLに一致するRouteを見つけた場合、そのRouteが持つMiddlewareを順番に確認する。
 * MiddlewareがcanOverrideRoute()がtrueで返した場合のみ、RouterはRouteの設定をMiddlewareのtargetResoureNameを上書きする。
 *
 * もしMiddlewareのチェック内容に応じた処理を作りたい場合、canOverrideRoute()の副作用として実装できる。
 * 例えば、ログインチェックをするMiddlewareのcanOverrideRoute()に
 * AlertsSession::putInfoMessageIntoSession("ログインが必要です。"); を書くとか。
 *
 * 【無駄口】
 * > もしMiddlewareのチェック内容に応じた処理を作りたい場合、canOverrideRoute()の副作用として実装できる。
 * こいつ正気で書いてんのか……？
 * これは「ここには事実上何を書いてもいいしすべての自由があるよ 何もかも壊してしまえ」という宣言にほかならないわけだが……
 *
 * 直すのはお前だ そうお前 このコメントを読んだお前だよ つまり未来の俺 要するに他人 お前がいいアイデアを出せ
 * 単純にonNG()、onOK()みたいなメソッドを用意するだけでいいのかもしれんが……
 * その場合勉強がてら他のフレームワークの実装を見てみるのがいいと思う
 *
 */
abstract class Middleware
{
    /**
     * ルートの定義
     * @param string $routeAction どのファイルを読み込むか "controller" or "view" or "api" or "redirect"
     * @param string $targetResourceName 読み込むファイルの名前（.phpは省略する）
     *               routeAction = "controller" の場合は、同名クラスのrunメソッドを呼び出す
     *               routeAction = "view" の場合は、同名の.phpファイルへ転送する
     *               routeAction = "api" の場合は、同名クラスのrunメソッドを呼び出す
     *               routeAction = "redirect" の場合は、$targetResourceNameにリダイレクト
     */
    public function __construct(
        public $routeAction = 'view',
        public $targetResourceName = 'top'
    ) {}

    abstract public function canOverrideRoute(): bool;
}
