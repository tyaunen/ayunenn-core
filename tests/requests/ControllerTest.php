<?php
namespace ayutenn\core\tests\requests;
use PHPUnit\Framework\TestCase;
use ayutenn\core\requests\Controller;
use ayutenn\core\utils\Redirect;
use ayutenn\core\config\Config;

class ControllerTest extends TestCase
{
    protected function setUp(): void
    {
        //---------------------
        // 自分のためのセットアップ
        //---------------------
        Redirect::$isTest = true;

        // 存在しないなら空のコンフィグファイルを作っておく
        // (ないとConfigでエラーになるため)
        $this->createJsonFile(__DIR__ . "/test_data/config.json", []);
        $this->createJsonFile(__DIR__ . "/test_data/app.json", []);

        // コンフィグファイルの設定上書き
        Config::$baseDirectory = __DIR__ . '/test_data';
        Config::setConfigForUnitTest('app', 'PATH_ROOT', '/ayutenn');

        // グローバル変数のリセット
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_SESSION = [];

        //---------------------
        // 依存するModelクラスのための、ディレクトリ基準パス構成上書き
        //---------------------
        Config::setConfigForUnitTest('app', 'MODEL_DIR', '/test_data');

        // phpunitでテストすると＄_SERVERの値がセットされないので上書きする
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;

    }

    protected function tearDown(): void
    {
        // グローバル変数のリセット
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_SESSION = [];
    }

    // JSONファイル作成
    private function createJsonFile(string $path, array $dict): void
    {
        file_put_contents($path, json_encode($dict, JSON_UNESCAPED_UNICODE));
    }

    protected function createFormatFile(string $file_name, array $format): void
    {
        $filepath = __DIR__ . "/../../../src/app/model/{$file_name}.json";
        if (!file_exists($filepath)) {
            file_put_contents($filepath, json_encode($format, JSON_UNESCAPED_UNICODE));
        }
    }

    private function create_controller_mock_class(
        bool $remainRequestParameter=false,
        bool $keepGetParameter = false
    ): Controller {
        return new class (
            $remainRequestParameter,
            $keepGetParameter
        ) extends Controller {
            public static function name():string {return 'TestController';}
            protected string $redirectUrlWhenError = '/error';
            protected array $RequestParameterFormat = [
                'name' => [
                    'type' => 'item',
                    'name' => '名前',
                    'format' => 'phpunit_ControllerTest_user_name',
                    'require' => true
                ],
                'seq' => [
                    'type' => 'item',
                    'name' => 'SEQ',
                    'format' => 'phpunit_ControllerTest_user_seq',
                    'require' => true
                ],
                'icon' => [
                    'type' => 'list',
                    'name' => 'アイコンリスト',
                    'require' => false,
                    'items' => [
                        'type' => 'item',
                        'name' => 'アイコンパス',
                        'format' => 'phpunit_ControllerTest_user_icon_path'
                    ]
                ],
            ];

            protected function main(): void
            {
                Redirect::redirect('/success', ['test' => 'ok']);
            }

            // テスト用のセッター
            public function __construct(bool $remainRequestParameter=false, bool $keepGetParameter=false)
            {
                $this->remainRequestParameter = $remainRequestParameter;
                $this->keepGetParameter = $keepGetParameter;
            }
        };
    }

    public function test_GETリクエスト()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['name' => 'テスト', 'seq' => 100, 'icon' => ['テスト']];

        $controller = $this->create_controller_mock_class();
        $controller->run();

        $this->assertEquals('/success?test=ok', Redirect::$lastRedirectUrl);
    }

    public function test_POSTリクエスト()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 100, 'icon' => ['テスト']];

        $controller = $this->create_controller_mock_class();
        $controller->run();

        $this->assertEquals('/success?test=ok', Redirect::$lastRedirectUrl);
    }

    public function test_バリデート結果でNG_必須パラメタが渡されていない()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト'];

        // テスト用のApi実装クラス
        $controller = $this->create_controller_mock_class();
        $controller->run();

        // エラーメッセージがセッションに格納されているはず
        $info_message = $_SESSION["AY_INFO_MESSAGE"];
        $this->assertEquals(
            [
                'alert_type' => 'alert',
                'alert_id' => '',
                'text' => 'リクエストに必要な値が設定されていません。(seq)'
            ],
            $info_message[0]
        );

        $this->assertEquals('/ayutenn/error', Redirect::$lastRedirectUrl);
    }

    public function test_バリデート結果でNG_パラメタのフォーマットNG()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 'aaa'];

        $controller = $this->create_controller_mock_class();
        $controller->run();

        // エラーメッセージがセッションに格納されているはず
        $info_message = $_SESSION["AY_INFO_MESSAGE"];
        $this->assertEquals(
            [
                'alert_type' => 'alert',
                'alert_id' => '',
                'text' => 'SEQは、データの形式が不正です。'
            ],
            $info_message[0]
        );

        $this->assertEquals('/ayutenn/error', Redirect::$lastRedirectUrl);
    }

    public function test_フォーマットNGかつ入力保存ありの場合、リクエストパラメタをSESSIONに保存する()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 'aaa'];

        $controller = $this->create_controller_mock_class(true);
        $controller->run();

        // エラーメッセージがセッションに格納されているはず
        $info_message = $_SESSION["AY_INFO_MESSAGE"];
        $this->assertEquals(
            [
                'alert_type' => 'alert',
                'alert_id' => '',
                'text' => 'SEQは、データの形式が不正です。'
            ],
            $info_message[0]
        );
        $this->assertEquals('/ayutenn/error', Redirect::$lastRedirectUrl);
        $this->assertEquals(
            [
                'name' => 'テスト',
                'seq' => 'aaa'
            ],
            $_SESSION['remain_TestController']
        );

        //複数回実行しても、入力保存は上書きされるだけ
        $controller->run();
        $info_message = $_SESSION["AY_INFO_MESSAGE"];
        $this->assertEquals(
            [
                'alert_type' => 'alert',
                'alert_id' => '',
                'text' => 'SEQは、データの形式が不正です。'
            ],
            $info_message[0]
        );
        $this->assertEquals('/ayutenn/error', Redirect::$lastRedirectUrl);
        $this->assertEquals(
            [
                'name' => 'テスト',
                'seq' => 'aaa'
            ],
            $_SESSION['remain_TestController']
        );

        // 入力保存の削除
        $controller::unsetRemain();
        $this->assertFalse(isset($_SESSION['remain_TestController']));
    }

    public function test_フォーマットNGかつ入力保存なしの場合、リクエストパラメタはSESSIONに保存しない()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 'aaa'];

        // テスト用のApi実装クラス
        $controller = $this->create_controller_mock_class(false);
        $controller->run();

        // エラーメッセージがセッションに格納されているはず
        $info_message = $_SESSION["AY_INFO_MESSAGE"];
        $this->assertEquals(
            [
                'alert_type' => 'alert',
                'alert_id' => '',
                'text' => 'SEQは、データの形式が不正です。'
            ],
            $info_message[0]
        );

        $this->assertEquals('/ayutenn/error', Redirect::$lastRedirectUrl);

        // 入力保存なし
        $this->assertFalse(isset($_SESSION['remain_TestController']));
    }

    public function test_フォーマットNGかつURL保存ありの場合、エラーリダイレクト先にも同じGETパラメタを付与()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['name' => 'test', 'seq' => 'aaa'];

        // テスト用のApi実装クラス
        $controller = $this->create_controller_mock_class(false, true);
        $controller->run();

        // エラーメッセージがセッションに格納されているはず
        $info_message = $_SESSION["AY_INFO_MESSAGE"];
        $this->assertEquals(
            [
                'alert_type' => 'alert',
                'alert_id' => '',
                'text' => 'SEQは、データの形式が不正です。'
            ],
            $info_message[0]
        );

        $this->assertEquals('/ayutenn/error?name=test&seq=aaa', Redirect::$lastRedirectUrl);
    }

    public function test_必須ではないパラメタは無くてもOK()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 100];

        // テスト用のApi実装クラス
        $controller = $this->create_controller_mock_class();
        $controller->run();

        $this->assertEquals('/success?test=ok', Redirect::$lastRedirectUrl);
    }
}