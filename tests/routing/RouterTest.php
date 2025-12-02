<?php
namespace ayutenn\core\tests\routing;
use PHPUnit\Framework\TestCase;
use ayutenn\core\routing\Router;
use ayutenn\core\utils\Redirect;
use ayutenn\core\config\Config;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        Redirect::$isTest = true;
        Config::reset(__DIR__ . '/RouterTest/config');

        // テストだと$_SERVER['DOCUMENT_ROOT']が空になるので、現在ディレクトリ基準のテスト用設定
        // これテストとしてどうなんだろう
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    }

    public function test_通常のディスパッチ() {
        // テスト用のルート定義ディレクトリ（./RouterTest/route）
        $route_dir = __DIR__ . '/RouterTest/route';
        $router = new Router($route_dir, '/ayutenn');

        // /ayutenn/test にGETリクエストをディスパッチ
        // ./RouterTest/route/route1.phpに定義がある
        $router->dispatch('GET', '/ayutenn/test1');

        // windows対応
        $lastRedirectUrl = str_replace("\\", '/', Redirect::$lastRedirectUrl);

        $this->assertStringEndsWith('/ayutenn_core/tests/routing/RouterTest/view/nest/view.php',  $lastRedirectUrl);

        // 複数のルート定義ファイルを読み込めているか確認するついでに異常系もテスト
        try {
            // /ayutenn/test2 にGETリクエストをディスパッチ
            // ./RouterTest/route/route2.phpに定義があるが、リダイレクト先のビューファイルが存在しない
            $router->dispatch('GET', '/ayutenn/test2');
            $this->fail("存在しないビューファイルを指定したとき、例外が発生しなければならない");
        } catch (\Exception $e) {
            // ビューが見つからない旨の例外が投げられるはず
            $this->assertStringContainsString('ビューファイルが見つかりません。', $e->getMessage());
        }
    }

    public function test_ルートファイルがarrayを返さなかったら例外() {
        $this->expectException(\Exception::class);

        // テスト用のルート定義ディレクトリ（./RouterTest/route_ng1）
        $route_dir = __DIR__ . '/RouterTest/route_ng1';
        $router = new Router($route_dir, '/ayutenn');
    }

    public function test_ルートファイルが返す配列にRouteかRouteGroup以外のデータが含まれていたら例外() {
        $this->expectException(\Exception::class);

        // テスト用のルート定義ディレクトリ（./RouterTest/route_ng2）
        $route_dir = __DIR__ . '/RouterTest/route_ng2';
        $router = new Router($route_dir, '/ayutenn');
    }
}
