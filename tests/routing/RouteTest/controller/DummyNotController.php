<?php
namespace ayutenn\core\tests\routing\RouteTest\controller;
use ayutenn\core\utils\Redirect;

class DummyNotController
{
    public static function name(): string {return 'DummyNotController';}
    protected function main(): void {
        Redirect::redirect('/dummy_success');
    }
}

return new DummyNotController();