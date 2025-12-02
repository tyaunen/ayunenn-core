<?php
namespace ayutenn\core\tests\database;
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;
use ayutenn\core\config\Config;
use ayutenn\core\database\DbConnector;

class DbConnectorTest extends TestCase
{
    private $pdoMock;

    protected function setUp(): void
    {
        // テスト用のDB接続情報を設定
        Config::$baseDirectory = __DIR__. '/config';
        Config::setConfigForUnitTest('config', 'PDO_DSN', 'sqlite::memory:');
        Config::setConfigForUnitTest('config', 'PDO_USERNAME', 'user');
        Config::setConfigForUnitTest('config', 'PDO_PASSWORD', 'pass');

        // PDOモックを用意
        $this->pdoMock = $this->createMock(PDO::class);
    }

    public function test_シングルトンなPDOインスタンスを作成する()
    {
        // 実際に接続
        $pdo = DbConnector::connectWithPdo();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame($pdo, DbConnector::connectWithPdo(), "二度目も同じインスタンスであること");
    }

    public function test_ロールバック_トランザクションがある場合、trueを返してロールバック()
    {
        // モックPDOを接続として注入
        $this->injectConnection($this->pdoMock);

        $this->pdoMock->method('inTransaction')->willReturn(true);
        $this->pdoMock->expects($this->once())->method('rollback');

        $result = DbConnector::rollbackIfInTransaction();
        $this->assertTrue($result);
    }

    public function test_ロールバック_トランザクションがない場合、falseを返して何もしない()
    {
        $this->injectConnection($this->pdoMock);

        $this->pdoMock->method('inTransaction')->willReturn(false);
        $this->pdoMock->expects($this->never())->method('rollback');

        $result = DbConnector::rollbackIfInTransaction();
        $this->assertFalse($result);
    }

    public function test_ロールバック_未接続なら、falseを返して何もしない()
    {
        $this->resetConnection();
        $result = DbConnector::rollbackIfInTransaction();
        $this->assertFalse($result);
    }

    private function injectConnection(PDO $pdo): void
    {
        $ref = new ReflectionClass(DbConnector::class);
        $prop = $ref->getProperty('connection');
        $prop->setValue($pdo);
    }

    private function resetConnection(): void
    {
        $ref = new ReflectionClass(DbConnector::class);
        $prop = $ref->getProperty('connection');
        $prop->setValue(null);
    }
}
