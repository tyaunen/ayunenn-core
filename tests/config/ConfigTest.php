<?php
namespace ayutenn\core\tests\config;
use PHPUnit\Framework\TestCase;
use Exception;
use ayutenn\core\config\Config;

class ConfigTest extends TestCase
{
    private string $defaultLastLoadedTime;

    protected function setUp(): void
    {
        Config::reset(__DIR__ . '/data');
    }

    public function test_Configファイルからプロパティを取得する()
    {
        $app_name = Config::getConfig('BASE_URL');
        $this->assertSame($app_name, 'localhost/ayutenn');
    }

    public function test_Configファイルに存在しないプロパティを読もうとすると例外()
    {
        Config::$baseDirectory = __DIR__ . '/not_exist';
        $this->expectException(Exception::class);
        $not_exist = Config::getConfig('NOT_EXIST');
    }


    public function test_Appファイルからプロパティを取得する()
    {
        $app_name = Config::getAppSetting('APP_NAME');
        $this->assertSame($app_name, 'ayutenn_core_test');
    }

    public function test_Appファイルに存在しないプロパティを読もうとすると例外()
    {
        $this->expectException(Exception::class);
        $not_exist = Config::getAppSetting('NOT_EXIST');
    }

    public function test_取得1回目はファイルを読み込み、lastLoadedTimeが更新される()
    {
        $before = Config::$lastLoadedTime;
        Config::getConfig('BASE_URL');
        $after = Config::$lastLoadedTime;
        $this->assertNotSame($before, $after);
    }

    public function test_取得2回目以降は、前回ロードしたファイルを使用し、ファイルの再読み込みをしない()
    {
        Config::getConfig('BASE_URL');
        $before = Config::$lastLoadedTime;
        sleep(1);
        Config::getConfig('BASE_URL');
        $after = Config::$lastLoadedTime;
        $this->assertSame($before, $after);
    }
}