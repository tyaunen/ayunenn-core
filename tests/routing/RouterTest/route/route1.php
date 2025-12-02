<?php
namespace ayutenn\core\tests\routing\RouterTest;
use ayutenn\core\routing\Route;

return [
    new Route('GET', '/test1',   'view', '/nest/view'),
];
