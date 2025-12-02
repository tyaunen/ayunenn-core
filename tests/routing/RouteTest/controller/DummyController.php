<?php
namespace ayutenn\core\tests\routing\RouteTest\controller;
use ayutenn\core\requests\Controller;
use ayutenn\core\utils\Redirect;

class DummyController extends Controller
{
    public static function name(): string {return 'DummyController';}
    protected function main(): void {
        Redirect::redirect('/dummy_success');
    }
}

return new DummyController();