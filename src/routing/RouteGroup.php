<?php
namespace ayutenn\core\routing;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * 【概要】
 * ルートグループクラス
 *
 * 【解説】
 * ルートのグループを定義するクラスです。
 * グループ内のすべてのルートに共通のミドルウェアを適用できます。
 *
 * ルートグループは、特定のパスに対して一括で設定を行うために使用します。
 * 例えば、認証が必要なAPIやビューをまとめて管理することができます。
 *
 * 【無駄口】
 * とくにいうことなし
 *
 */
class RouteGroup
{
    /**
     * ルートグループの定義
     * @param string $group グループのパス
     * @param array $middleware ミドルウェア 自分の子のすべてに適用される
     * @param array $routes ルートの配列 array of Route class instance
     */
    public function __construct(
        public string $group = '/',
        public array $routes = [],
        public array $middleware = [],
    ) {}
}
