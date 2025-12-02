<?php
namespace ayutenn\core\tests\routing;
use PHPUnit\Framework\TestCase;
use ayutenn\core\routing\Middleware;

/**
 * Middleware のテスト用具象クラス
 */
class TestMiddleware extends Middleware
{
    public function canOverrideRoute(): bool
    {
        return true;
    }
}

/**
 * なんかあとで機能増やしそうだから抽象クラスとして実装したけど、
 * 現状インターフェイスみたいなもんなのでここでチェックすることはあんまない
 */
class MiddlewareTest extends TestCase
{
    /**
     * プロパティデフォルト値の確認
     */
    public function test_DefaultConstructorValues(): void
    {
        $middleware = new TestMiddleware();

        $this->assertSame('view', $middleware->routeAction);
        $this->assertSame('top', $middleware->targetResourceName);
    }

    /**
     *
     */
    public function test_ConstructorAssignsValues(): void
    {
        $middleware = new TestMiddleware('controller', 'home');

        $this->assertSame('controller', $middleware->routeAction);
        $this->assertSame('home', $middleware->targetResourceName);
    }

    /**
     * canOverrideRoute() が実装クラスで呼べることを確認
     */
    public function test_CanOverrideRouteReturnsTrue(): void
    {
        $middleware = new TestMiddleware();
        $this->assertTrue($middleware->canOverrideRoute());
    }
}
