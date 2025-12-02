<?php
namespace ayutenn\core\tests\database;

use PHPUnit\Framework\TestCase;
use ayutenn\core\database\QueryResult;

class QueryResultTest extends TestCase
{
    public function test_終了コード、メッセージ、データなどクラスプロパティを取得できる()
    {
        $result = new QueryResult(QueryResult::CODE_SUCCESS, 'OK', ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $result->data);
    }

    public function test_終了コードから日本語名を取得できる()
    {
        $success = new QueryResult(QueryResult::CODE_SUCCESS, '');
        $alert = new QueryResult(QueryResult::CODE_ALERT, '');
        $error = new QueryResult(QueryResult::CODE_ERROR, '');
        $unknown = new QueryResult(999, '');

        $this->assertSame('正常終了', $success->getCodeName());
        $this->assertSame('警告', $alert->getCodeName());
        $this->assertSame('エラー', $error->getCodeName());
        $this->assertSame('不明', $unknown->getCodeName());
    }

    public function test_エラーコードに応じてisSuccedがTrue・Falseになる()
    {
        $success = new QueryResult(QueryResult::CODE_SUCCESS, '');
        $error = new QueryResult(QueryResult::CODE_ERROR, '');

        $this->assertTrue($success->isSucceed());
        $this->assertFalse($error->isSucceed());
    }

    public function test_エラーメッセージが取得できる()
    {
        $success = new QueryResult(QueryResult::CODE_SUCCESS, '成功');
        $error = new QueryResult(QueryResult::CODE_ERROR, '失敗');
        $alert = new QueryResult(QueryResult::CODE_ALERT, '注意');

        $this->assertNull($success->getErrorMessage());
        $this->assertSame('【エラー】 失敗', $error->getErrorMessage());
        $this->assertSame('【警告】 注意', $alert->getErrorMessage());
    }

    public function test_Successファクトリー()
    {
        $result = QueryResult::success('完了', ['id' => 1]);
        $this->assertSame(['id' => 1], $result->data);
        $this->assertTrue($result->isSucceed());
    }

    public function test_Errorファクトリー()
    {
        $result = QueryResult::error('エラー発生', ['reason' => 'invalid']);
        $this->assertSame(['reason' => 'invalid'], $result->data);
        $this->assertFalse($result->isSucceed());
    }

    public function test_Alertファクトリー()
    {
        $result = QueryResult::alert('警告です', ['type' => 'limit']);
        $this->assertSame(['type' => 'limit'], $result->data);
        $this->assertFalse($result->isSucceed());
    }
}
