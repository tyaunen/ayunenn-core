<?php
namespace ayutenn\core\tests\requests;
use PHPUnit\Framework\TestCase;
use ayutenn\core\requests\Model;
use ayutenn\core\config\Config;

class ModelTest extends TestCase
{
    private array $tempFilePaths = [];

    protected function setUp(): void
    {
        // 存在しないなら空のコンフィグファイルを作っておく
        // (ないとConfigでエラーになるため)
        $this->createJsonFile(__DIR__ . "/test_data/config.json", []);
        $this->createJsonFile(__DIR__ . "/test_data/app.json", []);

        // コンフィグファイルの設定上書き
        Config::$baseDirectory = __DIR__ . '/test_data';
        Config::setConfigForUnitTest('app', 'MODEL_DIR', '/test_data');

        // phpunitでテストすると＄_SERVERの値がセットされないので上書きする
        $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    }

    protected function tearDown(): void
    {
        // 仮モデルファイルの削除
        foreach ($this->tempFilePaths as $file_path) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    // テスト用のモデルファイルを作る
    private function createModelFile(array $format) :string
    {
        $unique_file_name = 'test_format_' . uniqid() . '_' . getmypid();
        $temp_model_path = __DIR__ . "/test_data/{$unique_file_name}.json";

        $this->tempFilePaths[] = $temp_model_path;
        $this->createJsonFile($temp_model_path, $format);

        return $unique_file_name;
    }

    // JSONファイル作成
    private function createJsonFile(string $path, array $dict): void
    {
        file_put_contents($path, json_encode($dict, JSON_UNESCAPED_UNICODE));
    }


    // ===============================
    // 基本チェック
    // ===============================

    public function test_モデルファイルが存在しないなら例外発生()
    {
        try {
            new Model('not_exist');
            $this->fail('存在しないモデルファイルを指定した時、エラーになるべき');
        } catch (\Exception $e) {
            $this->assertStringContainsString('modelファイルが見つかりませんでした。', $e->getMessage());
        }
    }

    public function test_必須項目でないなら、空値は常にチェックOK()
    {
        $model_name = $this->createModelFile(['type' => 'string', 'min_length' => 100]);
        $model = new Model($model_name);
        $result = $model->validate('', false);
        $this->assertSame('', $result);
    }

    public function test_最小文字数と最大文字数を指定する()
    {
        $model_name = $this->createModelFile(['type' => 'string', 'min_length' => 2, 'max_length' => 4]);
        $model = new Model($model_name);
        $this->assertStringContainsString('2文字以上である必要があります', $model->validate('a', true));
        $this->assertSame('', $model->validate('ab', true));
        $this->assertSame('', $model->validate('abcd', true));
        $this->assertStringContainsString('4文字以下である必要があります', $model->validate('abcde', true));


        $model_name = $this->createModelFile(['type' => 'string', 'min_length' => 2]);
        $model = new Model($model_name);
        $this->assertStringContainsString('2文字以上である必要があります', $model->validate('a', true));
        $this->assertSame('', $model->validate('ab', true));


        $model_name = $this->createModelFile(['type' => 'string', 'max_length' => 4]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('abcd', true));
        $this->assertStringContainsString('4文字以下である必要があります', $model->validate('abcde', true));
    }

    public function test_最小行数と最大行数を指定する()
    {
        $model_name = $this->createModelFile(['type' => 'string', 'min_line' => 2, 'max_line' => 3]);
        $model = new Model($model_name);

        $this->assertStringContainsString('2行以上である必要があります', $model->validate(
            "1"
        , true));
        $this->assertSame('', $model->validate(
            "1
            2"
        , true));
        $this->assertStringContainsString('3行以下である必要があります', $model->validate(
            "1
            2
            3
            4"
        , true));

        $model_name = $this->createModelFile(['type' => 'string', 'min_line' => 2]);
        $model = new Model($model_name);

        $this->assertStringContainsString('2行以上である必要があります', $model->validate(
            "1"
        , true));
        $this->assertSame('', $model->validate(
            "1
            2"
        , true));

        $model_name = $this->createModelFile(['type' => 'string', 'max_line' => 3]);
        $model = new Model($model_name);

        $this->assertSame('', $model->validate(
            "1
            2"
        , true));
        $this->assertStringContainsString('3行以下である必要があります', $model->validate(
            "1
            2
            3
            4"
        , true));
    }

    public function test_最小値と最大値を指定する()
    {
        $model_name = $this->createModelFile(['type' => 'int', 'min' => 2, 'max' => 5]);
        $model = new Model($model_name);
        $this->assertStringContainsString('2以上である必要があります', $model->validate(1, true));
        $this->assertSame('', $model->validate(2, true));
        $this->assertSame('', $model->validate(5, true));
        $this->assertStringContainsString('5以下である必要があります', $model->validate(6, true));

        $model_name = $this->createModelFile(['type' => 'int', 'min' => 2]);
        $model = new Model($model_name);
        $this->assertStringContainsString('2以上である必要があります', $model->validate(1, true));
        $this->assertSame('', $model->validate(2, true));

        $model_name = $this->createModelFile(['type' => 'int', 'max' => 5]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate(5, true));
        $this->assertStringContainsString('5以下である必要があります', $model->validate(6, true));
    }

    // ===============================
    // 型チェック
    // ===============================

    public function test_型チェック_int()
    {
        $model_name = $this->createModelFile(['type' => 'int']);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate(123, true));
        $this->assertSame('', $model->validate('123', true));
        $this->assertStringContainsString('不正', $model->validate('abc', true));
    }

    public function test_型チェック_number()
    {
        $model_name = $this->createModelFile(['type' => 'number']);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate(12.3, true));
        $this->assertSame('', $model->validate('12.3', true));
        $this->assertStringContainsString('不正', $model->validate('abc', true));
    }

    public function test_型チェック_boolean()
    {
        $model_name = $this->createModelFile(['type' => 'boolean']);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate(true, true));
        $this->assertSame('', $model->validate(1, true));
        $this->assertStringContainsString('不正', $model->validate('abc', true));
    }

    public function test_型チェック_array()
    {
        $model_name = $this->createModelFile(['type' => 'array']);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate(['a', 'b'], true));
        $this->assertStringContainsString('不正', $model->validate('abc', true));
    }

    public function test_型チェック_string()
    {
        $model_name = $this->createModelFile(['type' => 'string']);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('abc', true));
        $this->assertStringContainsString('不正', $model->validate(123, true));
    }

    // ===============================
    // condition チェック群
    // ===============================

    public function test_condition_email()
    {
        $model_name = $this->createModelFile(['condition' => ['email']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('test@example.com', true));
        $this->assertStringContainsString('メールアドレスの形式である必要があります。', $model->validate('invalid_mail', true));
        $this->assertStringContainsString('メールアドレスの形式である必要があります。', $model->validate('invalid_mail@nono', true));
    }

    public function test_condition_url()
    {
        $model_name = $this->createModelFile(['condition' => ['url']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('https://example.com', true));
        $this->assertStringContainsString('URL', $model->validate('example', true));
    }

    public function test_condition_alphanumeric()
    {
        $model_name = $this->createModelFile(['condition' => ['alphanumeric']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('abc123', true));
        $this->assertStringContainsString('英数字のみ', $model->validate('abc-123', true));
    }

    public function test_condition_alphabets()
    {
        $model_name = $this->createModelFile(['condition' => ['alphabets']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('ABCdef', true));
        $this->assertStringContainsString('英字のみ', $model->validate('123', true));
    }

    public function test_condition_symbols()
    {
        $model_name = $this->createModelFile(['condition' => ['symbols']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('abc!?', true));
        $this->assertStringContainsString('英数字+記号', $model->validate('あいう', true));
    }

    public function test_condition_color_code()
    {
        $model_name = $this->createModelFile(['condition' => ['color_code']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('#a1b2c3', true));
        $this->assertStringContainsString('カラーコード', $model->validate('123456', true));
    }

    public function test_condition_datetime()
    {
        $model_name = $this->createModelFile(['condition' => ['datetime']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('2025/10/17 12:34:56', true));
        $this->assertStringContainsString('日付+時刻の形式である必要があります。', $model->validate('2025/10/17 12:34:6', true));
        $this->assertStringContainsString('日付+時刻の形式である必要があります。', $model->validate('2025-10-17', true));
    }

    public function test_condition_local_file()
    {
        $model_name = $this->createModelFile(['condition' => ['local_file']]);
        $model = new Model($model_name);
        $this->assertSame('', $model->validate('dir/sub/file.txt', true));
        $this->assertStringContainsString('ファイルパス', $model->validate('invalid\\path', true));
    }

    // ===============================
    // cast チェック
    // ===============================

    public function test_cast変換()
    {
        $cases = [
            'int' => ['input' => '123', 'expectedType' => 'integer'],
            'number' => ['input' => '12.3', 'expectedType' => 'double'],
            'string' => ['input' => 123, 'expectedType' => 'string'],
            'boolean' => ['input' => '1', 'expectedType' => 'boolean'],
            'array' => ['input' => 'abc', 'expectedType' => 'array'],
        ];

        foreach ($cases as $type => $case) {
            $model_name = $this->createModelFile(['type' => $type]);
            $model = new Model($model_name);
            $casted = $model->cast($case['input']);
            $this->assertSame($case['expectedType'], gettype($casted), "Failed at {$type}");
        }
    }

    // ===============================
    // getFormLabel
    // ===============================

    public function test_getFormLabel_数値タイプ範囲ラベル()
    {
        $model_name = $this->createModelFile(['type' => 'int', 'min' => 1, 'max' => 10]);
        $model = new Model($model_name);
        $label = $model->getFormLabel();
        $this->assertStringContainsString('1～10の数値', $label);
    }

    public function test_getFormLabel_文字列と条件複合()
    {
        $model_name = $this->createModelFile([
            'type' => 'string',
            'min_length' => 3,
            'max_length' => 10,
            'condition' => ['email', 'url']
        ]);
        $model = new Model($model_name);
        $label = $model->getFormLabel();
        $this->assertStringContainsString('3～10文字', $label);
        $this->assertStringContainsString('メールアドレス', $label);
        $this->assertStringContainsString('URL形式', $label);
    }
}