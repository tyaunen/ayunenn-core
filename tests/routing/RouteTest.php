<?php
namespace ayutenn\core\tests\routing;
use PHPUnit\Framework\TestCase;
use ayutenn\core\routing\Route;
use ayutenn\core\routing\Middleware;
use ayutenn\core\utils\Redirect;
use ayutenn\core\config\Config;

/**
 * Middleware のテスト用具象クラス
 */
class TestMiddlewareForRoute extends Middleware
{
    public function canOverrideRoute(): bool
    {
        return true;
    }
}

class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        Redirect::$isTest = true;
        Config::reset(__DIR__ . '/RouteTest/config');

        // phpunitのプロセスだと$_SERVER['DOCUMENT_ROOT']が空になるので、現在ディレクトリ基準のテスト用設定
        // これテストとしてどうなんだろう
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;

        // 同様に未設定のREQUEST_METHODを設定
        // マッチングしないのでREQUEST_METHODは処理に影響しないけれど、
        // 未定義だとControllerやAPIの起動のとき警告が出るので適当にGETを設定しておく
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function test_値セットチェック(): void
    {
        $mw1 = new TestMiddlewareForRoute('controller', 'home');
        $mw2 = new TestMiddlewareForRoute('api', 'data');

        $route = new Route(
            method: 'POST',
            path: '/submit',
            routeAction: 'controller',
            targetResourceName: 'FormController',
            middleware: [$mw1, $mw2]
        );

        $this->assertSame('POST', $route->method);
        $this->assertSame('/submit', $route->path);
        $this->assertSame('controller', $route->routeAction);
        $this->assertSame('FormController', $route->targetResourceName);
        $this->assertCount(2, $route->middleware);
        $this->assertInstanceOf(Middleware::class, $route->middleware[0]);
        $this->assertInstanceOf(Middleware::class, $route->middleware[1]);
    }

    public function test_スラッシュ単体でも空白文字でもルートにマッチングできる(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/',
            routeAction: 'view',
            targetResourceName: '/dummy_view',
            middleware: []
        );

        $this->assertTrue($route->matches('GET', ''));
        $this->assertTrue($route->matches('GET', '/'));

        $route = new Route(
            method: 'GET',
            path: '',
            routeAction: 'view',
            targetResourceName: '/dummy_view',
            middleware: []
        );

        $this->assertTrue($route->matches('GET', ''));
        $this->assertTrue($route->matches('GET', '/'));
    }

    public function test_ビューを起動(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'view',
            targetResourceName: '/dummy_view',
            middleware: []
        );

        $route->executeRouteAction();
        $this->assertStringEndsWith('tests\routing/RouteTest/view/dummy_view.php', Redirect::$lastRedirectUrl);
    }

    public function test_ビューを起動するが、対象ファイルがなくて例外発生(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'view',
            targetResourceName: '/dummy_view_not_exist',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('エラー: ビューファイルが見つかりません。（', $e->getMessage());
        }
    }

    public function test_コントローラーを起動(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'controller',
            targetResourceName: '/DummyController',
            middleware: []
        );

        $route->executeRouteAction();
        $this->assertSame('/dummy_success', Redirect::$lastRedirectUrl);
    }

    public function test_コントローラーを起動するが、対象ファイルがなくて例外発生(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'controller',
            targetResourceName: '/DummyControllerNotExist',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('エラー: コントローラーファイルが見つかりません。', $e->getMessage());
        }
    }

    public function test_コントローラーファイルが自身のインスタンスを返していなくて例外発生(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'controller',
            targetResourceName: '/DummyControllerNoReturn',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('コントローラーファイルは、ファイル末尾でインスタンスを返す必要があります。', $e->getMessage());
        }
    }

    public function test_コントローラーファイルにコントローラーではなくて例外発生ものが記述されている(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'controller',
            targetResourceName: '/DummyNotController',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('インスタンスがControllerクラスを継承していません。', $e->getMessage());
        }
    }

    public function test_APIを起動(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'api',
            targetResourceName: '/DummyApi',
            middleware: []
        );

        $route->executeRouteAction();
        $this->assertSame(
            [
                'status' => 0,
                'payload' => [
                    'message' => 'API呼び出し成功',
                ],
            ],
            Redirect::$lastApiResponse
        );
    }

    public function test_APIを起動するが、対象ファイルがなくて例外発生(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'api',
            targetResourceName: '/DummyApiNotExist',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('エラー: APIファイルが見つかりません。', $e->getMessage());
        }
    }

    public function test_APIファイルが自身のインスタンスを返していなくて例外発生(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'api',
            targetResourceName: '/DummyApiNoReturn',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('APIファイルは、ファイル末尾でインスタンスを返す必要があります。', $e->getMessage());
        }
    }


    public function test_APIファイルにAPIではなくて例外発生ものが記述されている(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'api',
            targetResourceName: '/DummyNotApi',
            middleware: []
        );

        try {
            $route->executeRouteAction();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('インスタンスがApiクラスを継承していません。', $e->getMessage());
        }
    }

    public function test_リダイレクトを起動(): void
    {
        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'redirect',
            targetResourceName: '/test_redirect_target',
            middleware: []
        );

        // phpunitだと$_SERVERが設定されないので手書き
        $_SERVER['HTTP_HOST'] = 'testhost';

        $route->executeRouteAction();
        $this->assertSame('http://testhost/ayutenn/test_redirect_target', Redirect::$lastRedirectUrl);

    }

    public function test_ミドルウェアによるルート上書き(): void
    {
        $mw = new TestMiddlewareForRoute('view', '/overridden_view');

        $route = new Route(
            method: 'GET',
            path: '/something',
            routeAction: 'controller',
            targetResourceName: '/DummyController',
            middleware: [
                $mw
            ]
        );

        $route->executeRouteAction();
        $this->assertStringContainsString('/RouteTest/view/overridden_view.php', Redirect::$lastRedirectUrl);
    }

    public function test_404リダイレクト(): void
    {
        Route::showNotFoundPage();
        $this->assertStringEndsWith('/RouteTest/view/dummy_404.php', Redirect::$lastRedirectUrl);
    }

    public function test_404リダイレクト先ページがなくて例外発生(): void
    {
        $pre_404_view = Config::getAppSetting('404_VIEW_FILE');
        Config::setConfigForUnitTest('app', '404_VIEW_FILE', '/view_not_exist');

        try {
            Route::showNotFoundPage();
            $this->fail("例外が発生しなければならなくて例外発生");

        } catch (\Exception $e) {
            $this->assertStringContainsString('エラー: 404ビューファイルが見つかりません。（', $e->getMessage());
        }
    }
}
