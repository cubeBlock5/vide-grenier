<?php

namespace Tests\Core;

use Core\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    // --- add() / getRoutes() -------------------------------------------------

    public function testAddConvertsRouteWithCustomRegexToStudlyKey(): void
    {
        $router = new Router();
        $router->add('/posts/{id:\d+}', ['controller' => 'Posts']);

        $expected = [
            '/^\/posts\/(?P<id>\d+)$/i' => ['controller' => 'Posts'],
        ];

        $this->assertSame($expected, $router->getRoutes());
    }

    public function testAddConvertsPlainVariableRoute(): void
    {
        $router = new Router();
        $router->add('{controller}/{action}');

        $expected = [
            '/^(?P<controller>[a-z-]+)\/(?P<action>[a-z-]+)$/i' => [],
        ];

        $this->assertSame($expected, $router->getRoutes());
    }

    public function testAddStoresMultipleRoutes(): void
    {
        $router = new Router();
        $router->add('', ['controller' => 'Home', 'action' => 'index']);
        $router->add('login', ['controller' => 'User', 'action' => 'login']);

        $this->assertCount(2, $router->getRoutes());
    }

    // --- match() ---------------------------------------------------------------

    public function testMatchReturnsTrueAndMergesNamedCaptureGroups(): void
    {
        $router = new Router();
        $router->add('/posts/{id:\d+}', ['controller' => 'Posts', 'action' => 'show']);

        $result = $router->match('/posts/5');

        $this->assertTrue($result);
        $this->assertSame(
            ['controller' => 'Posts', 'action' => 'show', 'id' => '5'],
            $router->getParams()
        );
    }

    public function testMatchReturnsFalseWhenNoRouteMatches(): void
    {
        $router = new Router();
        $router->add('/posts/{id:\d+}', ['controller' => 'Posts', 'action' => 'show']);

        $result = $router->match('/unknown-url');

        $this->assertFalse($result);
    }

    public function testMatchWithPlainVariablesCapturesControllerAndAction(): void
    {
        $router = new Router();
        $router->add('{controller}/{action}');

        $result = $router->match('home/index');

        $this->assertTrue($result);
        $this->assertSame(
            ['controller' => 'home', 'action' => 'index'],
            $router->getParams()
        );
    }

    public function testMatchIsCaseInsensitive(): void
    {
        $router = new Router();
        $router->add('login', ['controller' => 'User', 'action' => 'login']);

        $this->assertTrue($router->match('LOGIN'));
    }

    // --- dispatch() happy path --------------------------------------------------

    public function testDispatchHappyPathCallsControllerAction(): void
    {
        $router = new Router();
        $router->add('', ['controller' => 'Home', 'action' => 'index']);

        ob_start();
        try {
            $router->dispatch('');
        } finally {
            $output = ob_get_clean();
        }

        // We only care that dispatch ran the real Home::indexAction() without
        // throwing; the rendered HTML content is View's concern, not Router's.
        $this->assertNotSame('', $output);
    }

    public function testDispatchStripsQueryStringVariablesBeforeMatching(): void
    {
        $router = new Router();
        $router->add('product', ['controller' => 'Home', 'action' => 'index']);

        ob_start();
        try {
            $router->dispatch('product&page=2');
        } finally {
            ob_get_clean();
        }

        $this->addToAssertionCount(1); // reaching here means no exception was thrown
    }

    public function testDispatchWithSoleQueryStringParamMatchesEmptyRoute(): void
    {
        $router = new Router();
        $router->add('', ['controller' => 'Home', 'action' => 'index']);

        ob_start();
        try {
            $router->dispatch('page=2');
        } finally {
            ob_get_clean();
        }

        $this->addToAssertionCount(1); // reaching here means no exception was thrown
    }

    // --- dispatch() private routes ----------------------------------------------

    public function testDispatchThrowsWhenPrivateRouteAndNotLoggedIn(): void
    {
        $_SESSION = [];

        $router = new Router();
        $router->add('secure', ['controller' => 'Home', 'action' => 'index', 'private' => true]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in');

        $router->dispatch('secure');
    }

    public function testDispatchAllowsPrivateRouteWhenLoggedIn(): void
    {
        $_SESSION['user']['id'] = 42;

        $router = new Router();
        $router->add('secure', ['controller' => 'Home', 'action' => 'index', 'private' => true]);

        ob_start();
        try {
            $router->dispatch('secure');
        } finally {
            ob_get_clean();
        }

        $this->addToAssertionCount(1); // reaching here means no exception was thrown
    }

    // --- dispatch() error paths --------------------------------------------------

    public function testDispatchThrowsWhenControllerNotFound(): void
    {
        $router = new Router();
        $router->add('bogus', ['controller' => 'NoSuchController', 'action' => 'index']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Controller class App\Controllers\NoSuchController not found');

        $router->dispatch('bogus');
    }

    public function testDispatchThrowsWhenActionEndsInActionSuffix(): void
    {
        $router = new Router();
        $router->add('guarded', ['controller' => 'Home', 'action' => 'index-action']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Method indexAction in controller App\Controllers\Home cannot be called directly - remove the Action suffix to call this method'
        );

        $router->dispatch('guarded');
    }

    public function testDispatchThrowsNoRouteMatchedWithCode404(): void
    {
        $router = new Router();
        $router->add('login', ['controller' => 'User', 'action' => 'login']);

        try {
            $router->dispatch('this-route-does-not-exist');
            $this->fail('Expected an Exception to be thrown.');
        } catch (\Exception $e) {
            $this->assertSame('No route matched.', $e->getMessage());
            $this->assertSame(404, $e->getCode());
        }
    }
}
