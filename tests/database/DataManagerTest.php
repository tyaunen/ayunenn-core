<?php
namespace ayutenn\core\tests\database;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;
use ayutenn\core\database\DataManager;

/**
 * DataManagerのテスト
 */
class DataManagerTest extends TestCase
{
    private $pdoMock;
    private $stmtMock;
    private $dataManager;

    protected function setUp(): void
    {
        // PDOStatementのモック
        $this->stmtMock = $this->createMock(PDOStatement::class);

        // PDOのモック
        $this->pdoMock = $this->createMock(PDO::class);

        // prepareが呼ばれたらstmtMockを返すように設定
        $this->pdoMock->method('prepare')
            ->willReturn($this->stmtMock);

        // テスト用ダミーサブクラス
        $this->dataManager = new class($this->pdoMock) extends DataManager {
            public function publicExecuteStatement(string $sql, array $params): PDOStatement
            {
                return $this->executeStatement($sql, $params);
            }
            public function publicExecuteAndFetchAll(string $sql, array $params): array
            {
                return $this->executeAndFetchAll($sql, $params);
            }
        };
    }

    /* @test */
    public function test_クエリに対して、値をバインドして実行する()
    {
        $sql = 'SELECT * FROM users WHERE id = :id';
        $params = [':id' => [1, PDO::PARAM_INT]];

        // bindValueが正しく呼ばれることを期待
        $this->stmtMock->expects($this->once())
            ->method('bindValue')
            ->with(':id', 1, PDO::PARAM_INT);

        // executeが呼ばれることを期待
        $this->stmtMock->expects($this->once())
            ->method('execute');

        $result = $this->dataManager->publicExecuteStatement($sql, $params);
        $this->assertInstanceOf(PDOStatement::class, $result);
    }

    /* @test */
    public function test_結果配列を返す()
    {
        $sql = 'SELECT * FROM users';
        $params = [];

        // fetchAllが呼ばれたら特定の配列を返すように設定
        $expected = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $this->stmtMock->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $result = $this->dataManager->publicExecuteAndFetchAll($sql, $params);

        $this->assertSame($expected, $result);
    }

    /* @test */
    public function test_結果が空の場合、空配列を返す()
    {
        $sql = 'SELECT * FROM users';
        $params = [];

        $this->stmtMock->method('fetchAll')
            ->willReturn([]); // fetchAllが空配列を返すケース

        $result = $this->dataManager->publicExecuteAndFetchAll($sql, $params);

        $this->assertSame([], $result);
    }
}
