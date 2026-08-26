<?php

namespace Tests\Core;

use Core\Controller;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    public function testConstructorStoresRouteParams(): void
    {
        $params = ['controller' => 'Home', 'action' => 'index'];
        $controller = new ControllerTestFixture($params);

        $this->assertSame($params, $controller->getRouteParams());
    }

    public function testCallInvokesBeforeActionAndAfterInOrder(): void
    {
        $controller = new ControllerTestFixture([]);

        $controller->foo('a', 'b');

        $this->assertTrue($controller->beforeCalled);
        $this->assertTrue($controller->fooCalled);
        $this->assertSame(['a', 'b'], $controller->fooActionArgs);
        $this->assertTrue($controller->afterCalled);
    }

    public function testCallThrowsWhenActionMethodDoesNotExist(): void
    {
        $controller = new ControllerTestFixture([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Method barAction not found in controller ' . ControllerTestFixture::class
        );

        $controller->bar();
    }

    public function testActionAndAfterAreNotCalledWhenBeforeReturnsFalse(): void
    {
        $controller = new ControllerTestBeforeFalseFixture([]);

        $controller->foo();

        $this->assertFalse($controller->fooCalled);
        $this->assertFalse($controller->afterCalled);
    }
}

/**
 * Fixture controller used to exercise Controller::__call(), before() and
 * after() without depending on any real App\Controllers class.
 */
class ControllerTestFixture extends Controller
{
    public bool $beforeCalled = false;
    public bool $afterCalled = false;
    public bool $fooCalled = false;
    public ?array $fooActionArgs = null;

    public function getRouteParams(): array
    {
        return $this->route_params;
    }

    public function fooAction(...$args): void
    {
        $this->fooCalled = true;
        $this->fooActionArgs = $args;
    }

    protected function before()
    {
        $this->beforeCalled = true;
    }

    protected function after()
    {
        $this->afterCalled = true;
    }
}

/**
 * Fixture controller whose before() returns false, so the action (and
 * after()) must NOT be invoked by Controller::__call().
 */
class ControllerTestBeforeFalseFixture extends Controller
{
    public bool $fooCalled = false;
    public bool $afterCalled = false;

    public function fooAction(): void
    {
        $this->fooCalled = true;
    }

    protected function before()
    {
        return false;
    }

    protected function after()
    {
        $this->afterCalled = true;
    }
}
