<?php
namespace ayutenn\core\tests\requests;
use PHPUnit\Framework\TestCase;
use ayutenn\core\requests\Api;
use ayutenn\core\utils\Redirect;
use ayutenn\core\config\Config;

class ApiTest extends TestCase
{
    protected function setUp(): void
    {
        //---------------------
        // 自分のためのセットアップ
        //---------------------
        Redirect::$isTest = true;

        // グローバル変数のリセット
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_SESSION = [];

        //---------------------
        // 依存するModelクラスのための、ディレクトリ基準パス構成上書き
        //---------------------
        // 存在しないなら空のコンフィグファイルを作っておく
        // (ないとConfigでエラーになるため)
        $this->createJsonFile(__DIR__ . "/test_data/config.json", []);
        $this->createJsonFile(__DIR__ . "/test_data/app.json", []);

        // コンフィグファイルの設定上書き
        Config::$baseDirectory = __DIR__ . '/test_data';
        Config::setConfigForUnitTest('app', 'MODEL_DIR', '/test_data');

        // phpunitでテストすると＄_SERVERの値がセットされないので上書きする
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;

        //---------------------
        // ここから自分のためのセットアップ
        //---------------------
        Redirect::$isTest = true;
    }

    protected function tearDown(): void
    {
        // グローバル変数のリセット
        $_GET = [];
        $_POST = [];
        $_SERVER = [];

        // 一時モデルファイルの削除
        foreach (glob(__DIR__ . '/temp_model_file/*.json') as $file_name) {
            unlink($file_name);
        }
    }

    // JSONファイル作成
    private function createJsonFile(string $path, array $dict): void
    {
        file_put_contents($path, json_encode($dict, JSON_UNESCAPED_UNICODE));
    }

    private function create_api_mock_class(): Api {
        return new class extends Api {
            public static array $response = ['status' => 0, 'payload' => []];
            protected array $RequestParameterFormat = [
                'name' => [
                    'type' => 'item',
                    'name' => '名前',
                    'format' => 'phpunit_RequestValidatorTest_user_name',
                    'require' => true
                ],
                'seq' => [
                    'type' => 'item',
                    'name' => 'SEQ',
                    'format' => 'phpunit_RequestValidatorTest_user_seq',
                    'require' => true
                ],
                'icon' => [
                    'type' => 'list',
                    'name' => 'アイコンリスト',
                    'require' => false,
                    'items' => [
                        'type' => 'item',
                        'name' => 'アイコンパス',
                        'format' => 'phpunit_RequestValidatorTest_user_icon_path'
                    ]
                ],
            ];
            public function main(): array
            {
                return self::$response;
            }
        };
    }

    public function test_GETリクエスト()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['name' => 'テスト', 'seq' => 100, 'icon' => ['テスト']];

        // テスト用のApi実装クラス
        $api = $this->create_api_mock_class();
        $api->run();

        $this->assertEquals(['status' => 0, 'payload' => []], Redirect::$lastApiResponse);
    }

    public function test_POSTリクエスト()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 100, 'icon' => ['テスト']];

        // テスト用のApi実装クラス
        $api = $this->create_api_mock_class();
        $api->run();

        $this->assertEquals(['status' => 0, 'payload' => []], Redirect::$lastApiResponse);
    }

    public function test_バリデート結果でNG_パラメタ不足()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'icon' => ['テスト']];

        // テスト用のApi実装クラス
        $api = $this->create_api_mock_class();
        $api->run();
        $response = Redirect::$lastApiResponse;

        $this->assertEquals(9, $response['status']);
        $this->assertEquals('リクエストパラメータにエラーがあります。', $response['payload']['message']);
        $this->assertEquals('リクエストに必要な値が設定されていません。(seq)', $response['payload']['errors'][0]);
        $this->assertCount(1, $response['payload']['errors']);
    }

    public function test_バリデート結果でNG_フォーマットNG()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 'aaa', 'icon' => ['テスト']];

        // テスト用のApi実装クラス
        $api = $this->create_api_mock_class();
        $api->run();
        $response = Redirect::$lastApiResponse;

        $this->assertEquals(9, $response['status']);
        $this->assertEquals('リクエストパラメータにエラーがあります。', $response['payload']['message']);
        $this->assertEquals('SEQは、データの形式が不正です。', $response['payload']['errors'][0]);
        $this->assertCount(1, $response['payload']['errors']);
    }

    public function test_必須ではないパラメタは無くでもOK()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'テスト', 'seq' => 100];

        // テスト用のApi実装クラス
        $api = $this->create_api_mock_class();
        $api->run();
        $response = Redirect::$lastApiResponse;

        $this->assertEquals(['status' => 0, 'payload' => []], $response);
    }
}