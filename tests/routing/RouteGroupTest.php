<?php
namespace ayutenn\core\tests\routing;
use PHPUnit\Framework\TestCase;
use ayutenn\core\routing\Route;
use ayutenn\core\routing\Middleware;
use ayutenn\core\routing\RouteGroup;

/**
 * Middleware のテスト用具象クラス
 */
class TestMiddlewareForRouteGroup extends Middleware
{
    public function canOverrideRoute(): bool
    {
        return true;
    }
}

class RouteGroupTest extends TestCase
{
    /**
     * デフォルト値の確認
     */
    public function test_DefaultConstructorValues(): void
    {
        $group = new RouteGroup();

        $this->assertSame('/', $group->group);
        $this->assertSame([], $group->routes);
        $this->assertSame([], $group->middleware);
    }

    /**
     * コンストラクタ引数で値が正しく設定されることを確認
     */
    public function test_ConstructorAssignsValues(): void
    {
        $route1 = new Route('GET', '/home', 'view', 'home');
        $route2 = new Route('POST', '/submit', 'controller', 'FormController');
        $mw1 = new TestMiddlewareForRouteGroup('controller', 'AuthMiddleware');
        $mw2 = new TestMiddlewareForRouteGroup('api', 'CsrfMiddleware');

        $group = new RouteGroup(
            group: '/admin',
            routes: [$route1, $route2],
            middleware: [$mw1, $mw2],
        );

        $this->assertSame('/admin', $group->group);

        // ルート配列の確認
        $this->assertCount(2, $group->routes);
        $this->assertInstanceOf(Route::class, $group->routes[0]);
        $this->assertInstanceOf(Route::class, $group->routes[1]);

        // ミドルウェア配列の確認
        $this->assertCount(2, $group->middleware);
        $this->assertInstanceOf(Middleware::class, $group->middleware[0]);
        $this->assertInstanceOf(Middleware::class, $group->middleware[1]);
    }

    /**
     * 不正な型を渡した場合の挙動を確認（PHPの型宣言では弾かれないため）
     */
    public function test_InvalidTypesAccepted(): void
    {
        $group = new RouteGroup(
            group: '/test',
            routes: ['not_a_route'],
            middleware: ['not_a_middleware'],
        );

        $this->assertSame('not_a_route', $group->routes[0]);
        $this->assertSame('not_a_middleware', $group->middleware[0]);
    }
}
