<?php
namespace ayutenn\core\routing\RouteTest\api;

use ayutenn\core\requests\Api;

class DummyApiNoReturn extends Api
{
    public function main(): array
    {
        return $this->createResponse(
            succeed: true,
            payload: [
                'message' => 'API呼び出し成功',
            ]
        );
    }
}

// ここでインスタンスを返さないといけない