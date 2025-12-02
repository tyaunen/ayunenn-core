<?php
namespace ayutenn\core\routing\RouteTest\api;

use ayutenn\core\requests\Api;

class DummyApi extends Api
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

return new DummyApi();