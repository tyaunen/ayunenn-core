<?php
namespace ayutenn\core\tests\requests;
use PHPUnit\Framework\TestCase;
use ayutenn\core\requests\RequestValidator;
use ayutenn\core\config\Config;
use Exception;

class RequestValidatorTest extends TestCase
{
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

    // JSONファイル作成
    private function createJsonFile(string $path, array $dict): void
    {
        file_put_contents($path, json_encode($dict, JSON_UNESCAPED_UNICODE));
    }

    public function test_パラメータ単体を検証()
    {
        $format = [
            'user_seq' => [
                'type' => 'item',
                'name' => 'user_seq',
                'format' => 'phpunit_RequestValidatorTest_user_seq',
                'require' => true
            ],
        ];

        $value = [
            'user_seq' => '10',
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        // エラーがないことを確認
        $this->assertCount(0, $errors);

        // クリーンなパラメータが正しいことを確認
        $this->assertEquals(10, $validator->cleanRequestParameter['user_seq']);

        // 型がintにキャストされていることを確認
        $this->assertIsInt($validator->cleanRequestParameter['user_seq']);
    }

    public function test_パラメータ複数を検証()
    {
        $format = [
            'user_seq' => [
                'type' => 'item',
                'name' => 'user_seq',
                'format' => 'phpunit_RequestValidatorTest_user_seq',
                'require' => true
            ],
            'user_name' => [
                'type' => 'item',
                'name' => 'user_name',
                'format' => 'phpunit_RequestValidatorTest_user_name',
                'require' => true
            ],
        ];

        $value = [
            'user_seq' => '10',
            'user_name' => 'テストユーザー',
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        // エラーがないことを確認
        $this->assertCount(0, $errors);

        // クリーンなパラメータが正しいことを確認
        $this->assertEquals(10, $validator->cleanRequestParameter['user_seq']);
        $this->assertEquals('テストユーザー', $validator->cleanRequestParameter['user_name']);

        // 型がintにキャストされていることを確認
        $this->assertIsInt($validator->cleanRequestParameter['user_seq']);
    }

    public function test_必須パラメータが1つでも無い場合、エラー()
    {
        $format = [
            'user_seq' => [
                'type' => 'item',
                'name' => 'user_seq',
                'format' => 'phpunit_RequestValidatorTest_user_seq',
                'require' => true
            ],
            'user_name' => [
                'type' => 'item',
                'name' => 'user_name',
                'format' => 'phpunit_RequestValidatorTest_user_name',
                'require' => true
            ]
        ];

        $value = [
            'user_seq' => '10',
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        // エラーが1件のはず
        $this->assertCount(1, $errors);
        $this->assertEquals('リクエストに必要な値が設定されていません。(user_name)', $errors[0]);
    }

    public function test_必須ではないパラメータが渡された時でもキャストはする()
    {
        $format = [
            'user_seq' => [
                'type' => 'item',
                'name' => 'user_seq',
                'format' => 'phpunit_RequestValidatorTest_user_seq',
                'require' => false
            ],
            'user_name' => [
                'type' => 'item',
                'name' => 'user_name',
                'format' => 'phpunit_RequestValidatorTest_user_name',
                'require' => true
            ],
        ];

        $value = [
            'user_seq' => '10',
            'user_name' => 'テストユーザー',
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        // 必須ではなかったとしても、キャストはする
        $this->assertCount(2, $validator->cleanRequestParameter);
        $this->assertIsInt($validator->cleanRequestParameter['user_seq']);
    }

    public function test_必須ではないパラメータが渡されなくても他があればOK()
    {
        $format = [
            'user_seq' => [
                'type' => 'item',
                'name' => 'user_seq',
                'format' => 'phpunit_RequestValidatorTest_user_seq',
                'require' => false
            ],
            'user_name' => [
                'type' => 'item',
                'name' => 'user_name',
                'format' => 'phpunit_RequestValidatorTest_user_name',
                'require' => true
            ],
        ];

        $value = [
            'user_name' => 'テストユーザー',
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        // 必須ではなかったとしても、キャストはする
        $this->assertCount(1, $validator->cleanRequestParameter);
        $this->assertEquals('テストユーザー', $validator->cleanRequestParameter['user_name']);
    }

    public function test_パラメータリストの検証に全て成功()
    {
        $format = [
            'user_icons' => [
                'type' => 'list',
                'name' => 'アイコンリスト',
                'items' => [
                    'type' => 'item',
                    'name' => 'アイコン',
                    'format' => 'phpunit_RequestValidatorTest_user_icon_path',
                    'require' => true,
                ],
            ]
        ];

        $value = [
            'user_icons' => [
                'user_icon_10.jpg',
                'user_icon_20.jpg',
                'user_icon_30.jpg',
            ]
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        // 必須ではなかったとしても、キャストはする
        $this->assertCount(0, $errors);
        $this->assertEquals('user_icon_10.jpg', $validator->cleanRequestParameter['user_icons'][0]);
        $this->assertEquals('user_icon_20.jpg', $validator->cleanRequestParameter['user_icons'][1]);
        $this->assertEquals('user_icon_30.jpg', $validator->cleanRequestParameter['user_icons'][2]);
    }

    public function test_パラメータリストの検証が1件以上失敗()
    {
        $format = [
            'user_names' => [
                'type' => 'list',
                'name' => 'ユーザー名リスト',
                'items' => [
                    'type' => 'item',
                    'name' => 'ユーザー名',
                    'format' => 'phpunit_RequestValidatorTest_user_name',
                    'require' => true,
                ],
            ]
        ];

        $value = [
            'user_names' => [
                '1234567890123456',
                '1234567890123456',
                '12345678901234567',
            ]
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('ユーザー名は、16文字以下である必要があります。(現在: 17文字)', $errors[0]);
    }


    public function test_パラメータオブジェクトの検証に全て成功()
    {
        $format = [
            'user' => [
                'type' => 'object',
                'name' => 'ユーザー',
                'properties' => [
                    'user_seq' => [
                        'type' => 'item',
                        'name' => 'user_seq',
                        'format' => 'phpunit_RequestValidatorTest_user_seq',
                        'require' => true
                    ],
                    'user_name' => [
                        'type' => 'item',
                        'name' => 'user_name',
                        'format' => 'phpunit_RequestValidatorTest_user_name',
                        'require' => true
                    ],
                ]
            ],
        ];

        $value = [
            'user' => [
                'user_seq' => '10',
                'user_name' => '1234567890123456',
            ]
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        $this->assertCount(0, $errors);

        // クリーンなパラメータが正しいことを確認
        $this->assertEquals('10', $validator->cleanRequestParameter['user']['user_seq']);
        $this->assertEquals('1234567890123456', $validator->cleanRequestParameter['user']['user_name']);

        // 型がintにキャストされていることを確認
        $this->assertIsInt($validator->cleanRequestParameter['user']['user_seq']);
        $this->assertIsString($validator->cleanRequestParameter['user']['user_name']);
    }

    public function test_パラメータオブジェクトの検証に1プロパティ以上失敗()
    {
        $format = [
            'user' => [
                'type' => 'object',
                'name' => 'ユーザー',
                'properties' => [
                    'user_seq' => [
                        'type' => 'item',
                        'name' => 'user_seq',
                        'format' => 'phpunit_RequestValidatorTest_user_seq',
                        'require' => true
                    ],
                    'user_name' => [
                        'type' => 'item',
                        'name' => 'ユーザー名',
                        'format' => 'phpunit_RequestValidatorTest_user_name',
                        'require' => true
                    ],
                ]
            ],
        ];

        $value = [
            'user' => [
                'user_seq' => '10',
                'user_name' => '12345678901234567',
            ]
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('ユーザー名は、16文字以下である必要があります。(現在: 17文字)', $errors[0]);
    }

    public function test_オブジェクトinリスト、リストinオブジェクトの検証成功()
    {
        $format = [

            'user' => [
                'type' => 'object',
                'name' => 'ユーザー',
                'properties' => [
                    'user_seq' => [
                        'type' => 'item',
                        'name' => 'user_seq',
                        'format' => 'phpunit_RequestValidatorTest_user_seq',
                        'require' => true
                    ],
                    'user_name' => [
                        'type' => 'item',
                        'name' => 'ユーザー名',
                        'format' => 'phpunit_RequestValidatorTest_user_name',
                        'require' => true
                    ],
                    'icon_list' => [
                        'type' => 'list',
                        'name' => 'アイコンリスト',
                        'items' => [
                            'type' => 'item',
                            'name' => 'アイコン',
                            'format' => 'phpunit_RequestValidatorTest_user_name',
                            'require' => true,
                        ],
                    ],
                    'friends' => [
                        'type' => 'list',
                        'name' => 'フレンドリスト',
                        'items' => [
                            'type' => 'object',
                            'name' => 'フレンド',
                            'properties' => [
                                'user_seq' => [
                                    'type' => 'item',
                                    'name' => 'user_seq',
                                    'format' => 'phpunit_RequestValidatorTest_user_seq',
                                    'require' => true
                                ],
                                'user_name' => [
                                    'type' => 'item',
                                    'name' => 'ユーザー名',
                                    'format' => 'phpunit_RequestValidatorTest_user_name',
                                    'require' => true
                                ],
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $value = [
            'user' => [
                'user_seq' => '10',
                'user_name' => '1234567890123456',
                'icon_list' => [
                    'icon_1.jpg',
                    'icon_2.jpg',
                ],
                'friends' => [
                    [
                        'user_seq' => '20',
                        'user_name' => 'friend_1',
                    ],
                    [
                        'user_seq' => '30',
                        'user_name' => 'friend_2',
                    ],
                ]
            ]
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        $this->assertCount(0, $errors);
    }

    public function test_オブジェクトinリスト、リストinオブジェクトの検証失敗()
    {
        $format = [
            'user' => [
                'type' => 'object',
                'name' => 'ユーザー',
                'properties' => [
                    'user_seq' => [
                        'type' => 'item',
                        'name' => 'user_seq',
                        'format' => 'phpunit_RequestValidatorTest_user_seq',
                        'require' => true
                    ],
                    'user_name' => [
                        'type' => 'item',
                        'name' => 'ユーザー名',
                        'format' => 'phpunit_RequestValidatorTest_user_name',
                        'require' => true
                    ],
                    'icon_list' => [
                        'type' => 'list',
                        'name' => 'アイコンリスト',
                        'items' => [
                            'type' => 'item',
                            'name' => 'アイコン',
                            'format' => 'phpunit_RequestValidatorTest_user_name',
                            'require' => true,
                        ],
                    ],
                    'friends' => [
                        'type' => 'list',
                        'name' => 'フレンドリスト',
                        'items' => [
                            'type' => 'object',
                            'name' => 'フレンド',
                            'properties' => [
                                'user_seq' => [
                                    'type' => 'item',
                                    'name' => 'フレンドSEQ',
                                    'format' => 'phpunit_RequestValidatorTest_user_seq',
                                    'require' => true
                                ],
                                'user_name' => [
                                    'type' => 'item',
                                    'name' => 'フレンド名',
                                    'format' => 'phpunit_RequestValidatorTest_user_name',
                                    'require' => true
                                ],
                                'icon_list' => [
                                    'type' => 'list',
                                    'name' => 'フレンド名リスト',
                                    'items' => [
                                        'type' => 'item',
                                        'name' => 'フレンド別名',
                                        'format' => 'phpunit_RequestValidatorTest_user_name',
                                        'require' => true,
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $value = [
            'user' => [
                'user_seq' => 'aaa',
                'user_name' => 'tyaunen',
                'icon_list' => [
                    'icon_1.jpg',
                    'icon_2.jpg',
                ],
                'friends' => [
                    [
                        'user_seq' => 'bbb',
                        'user_name' => 'friend_1',
                        'icon_list' => [
                            '12345678901234567',
                            '1234567890123456',
                        ],
                    ],
                    [
                        'user_seq' => '30',
                        'user_name' => '123456789012345678',
                    ],
                ]
            ]
        ];

        $validator = new RequestValidator($format, $value);
        $errors = $validator->validate();

        $this->assertCount(5, $errors);
        $this->assertStringContainsString('user_seqは、データの形式が不正です。', $errors[0]);
        $this->assertStringContainsString('フレンドSEQは、データの形式が不正です。', $errors[1]);
        $this->assertStringContainsString('フレンド別名は、16文字以下である必要があります。(現在: 17文字)', $errors[2]);
        $this->assertStringContainsString('フレンド名は、16文字以下である必要があります。(現在: 18文字)', $errors[3]);
        $this->assertStringContainsString('リクエストに必要な値が設定されていません。(user.friends[1].icon_list)', $errors[4]);
    }


    public function test_フォーマットファイルが存在しないなら例外発生()
    {
        $this->expectException(Exception::class);

        $format = [
            'user_seq' => [
                'type' => 'item',
                'name' => 'user_seq',
                'format' => 'not_exist_format_file',
                'require' => true
            ],
        ];

        $value = [
            'user_seq' => '10',
        ];

        $validator = new RequestValidator($format, $value);
        $validator->validate();
    }

}