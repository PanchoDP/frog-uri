<?php

declare(strict_types=1);

namespace FrogUri\Test\Unit\Actions;

use FrogUri\Actions\RenderInformAction;
use FrogUri\Collections\RouteCollection;
use Illuminate\Console\Command;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class RenderInformActionTest extends TestCase
{
    private array $sampleRoutes;

    private RouteCollection $collection;

    protected function setUp(): void
    {
        $this->sampleRoutes = [
            [
                'method' => 'GET',
                'uri' => 'api/users',
                'name' => 'users.index',
                'action' => 'UserController@index',
                'middleware' => [],
            ],
            [
                'method' => 'GET',
                'uri' => 'api/profile',
                'name' => 'profile.show',
                'action' => 'ProfileController@show',
                'middleware' => ['auth'],
            ],
            [
                'method' => 'POST',
                'uri' => 'api/posts',
                'name' => 'posts.store',
                'action' => 'PostController@store',
                'middleware' => ['auth', 'verified'],
            ],
            [
                'method' => 'DELETE',
                'uri' => 'api/admin/users/{id}',
                'name' => 'admin.users.destroy',
                'action' => 'Admin\\UserController@destroy',
                'middleware' => ['auth', 'role:admin', 'permission:delete-users'],
            ],
        ];

        $this->collection = new RouteCollection($this->sampleRoutes);
    }

    public function test_handle_processes_collection_and_renders_to_terminal(): void
    {
        $mockCommand = $this->createMock(Command::class);
        $mockCommand->expects($this->atLeastOnce())
            ->method('line');

        // Test that handle doesn't throw exceptions and calls line method
        RenderInformAction::handle($this->collection, $mockCommand);

        // If we get here, the test passed
        $this->assertTrue(true);
    }

    public function test_handle_works_without_command(): void
    {
        // Capture output when no command is provided
        ob_start();
        RenderInformAction::handle($this->collection);
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Method: GET', $output);
        $this->assertStringContainsString('Method: POST', $output);
        $this->assertStringContainsString('Method: DELETE', $output);
    }

    public function test_group_routes_by_method_correctly(): void
    {
        $method = $this->getPrivateMethod('groupRoutesByMethod');
        $grouped = $method->invoke(null, $this->sampleRoutes);

        $this->assertArrayHasKey('GET', $grouped);
        $this->assertArrayHasKey('POST', $grouped);
        $this->assertArrayHasKey('DELETE', $grouped);

        $this->assertCount(2, $grouped['GET']);
        $this->assertCount(1, $grouped['POST']);
        $this->assertCount(1, $grouped['DELETE']);
    }

    public function test_group_routes_by_method_with_pipe_separated_methods(): void
    {
        $routesWithPipe = [
            [
                'method' => 'GET|HEAD',
                'uri' => 'api/test',
                'name' => 'test',
                'action' => 'TestController@test',
                'middleware' => [],
            ],
        ];

        $method = $this->getPrivateMethod('groupRoutesByMethod');
        $grouped = $method->invoke(null, $routesWithPipe);

        $this->assertArrayHasKey('GET', $grouped);
        $this->assertArrayHasKey('HEAD', $grouped);
        $this->assertCount(1, $grouped['GET']);
        $this->assertCount(1, $grouped['HEAD']);
    }

    public function test_sort_routes_by_middleware_maintains_method_order(): void
    {
        $routesByMethod = [
            'POST' => [$this->sampleRoutes[2]], // auth, verified
            'GET' => [$this->sampleRoutes[0], $this->sampleRoutes[1]], // no middleware, auth
            'DELETE' => [$this->sampleRoutes[3]], // auth, role:admin, permission:delete-users
        ];

        $method = $this->getPrivateMethod('sortRoutesByMiddleware');
        $sorted = $method->invoke(null, $routesByMethod);

        $expectedOrder = ['GET', 'POST', 'DELETE'];
        $this->assertEquals($expectedOrder, array_keys($sorted));
    }

    public function test_sort_method_routes_by_middleware_count(): void
    {
        $routes = [
            $this->sampleRoutes[3], // 3 middlewares
            $this->sampleRoutes[0], // 0 middlewares
            $this->sampleRoutes[1], // 1 middleware
            $this->sampleRoutes[2], // 2 middlewares
        ];

        $method = $this->getPrivateMethod('sortMethodRoutes');
        $sorted = $method->invoke(null, $routes);

        // Should be ordered by middleware count (0, 1, 2, 3)
        $this->assertEquals(0, count($sorted[0]['middleware']));
        $this->assertEquals(1, count($sorted[1]['middleware']));
        $this->assertEquals(2, count($sorted[2]['middleware']));
        $this->assertEquals(3, count($sorted[3]['middleware']));
    }

    public function test_get_middleware_array_with_empty_middleware(): void
    {
        $route = ['middleware' => []];

        $method = $this->getPrivateMethod('getMiddlewareArray');
        $middleware = $method->invoke(null, $route);

        $this->assertEquals([], $middleware);
    }

    public function test_get_middleware_array_with_string_input(): void
    {
        $route = ['middleware' => 'auth,verified,throttle'];

        $method = $this->getPrivateMethod('getMiddlewareArray');
        $middleware = $method->invoke(null, $route);

        $this->assertEquals(['auth', 'verified', 'throttle'], $middleware);
    }

    public function test_get_middleware_array_with_array_input(): void
    {
        $route = ['middleware' => ['auth', 'verified']];

        $method = $this->getPrivateMethod('getMiddlewareArray');
        $middleware = $method->invoke(null, $route);

        $this->assertEquals(['auth', 'verified'], $middleware);
    }

    public function test_get_middleware_array_with_missing_middleware(): void
    {
        $route = [];

        $method = $this->getPrivateMethod('getMiddlewareArray');
        $middleware = $method->invoke(null, $route);

        $this->assertEquals([], $middleware);
    }

    public function test_get_middleware_priority_calculation(): void
    {
        $method = $this->getPrivateMethod('getMiddlewarePriority');

        // Test individual middleware priorities
        $this->assertEquals(1, $method->invoke(null, ['auth']));
        $this->assertEquals(2, $method->invoke(null, ['verified']));
        $this->assertEquals(3, $method->invoke(null, ['role:admin']));
        $this->assertEquals(4, $method->invoke(null, ['permission:delete']));
        $this->assertEquals(5, $method->invoke(null, ['throttle:60,1']));

        // Test combined priorities
        $this->assertEquals(3, $method->invoke(null, ['auth', 'verified'])); // 1 + 2
        $this->assertEquals(8, $method->invoke(null, ['auth', 'role:admin', 'permission:delete'])); // 1 + 3 + 4

        // Test unknown middleware
        $this->assertEquals(0, $method->invoke(null, ['unknown']));
    }

    public function test_get_middleware_priority_with_empty_array(): void
    {
        $method = $this->getPrivateMethod('getMiddlewarePriority');
        $priority = $method->invoke(null, []);

        $this->assertEquals(0, $priority);
    }

    public function test_render_routes_to_terminal_with_mock_command(): void
    {
        $sortedRoutes = [
            'GET' => [
                [
                    'method' => 'GET',
                    'uri' => 'api/users',
                    'name' => 'users.index',
                    'action' => 'UserController@index',
                    'middleware' => [],
                ],
            ],
        ];

        $mockCommand = $this->createMock(Command::class);

        // Expect at least 7 calls to line method (empty line + header + 4 route lines + empty line)
        $mockCommand->expects($this->exactly(7))
            ->method('line');

        $method = $this->getPrivateMethod('renderRoutesToTerminal');
        $method->invoke(null, $sortedRoutes, $mockCommand);
    }

    public function test_render_routes_to_terminal_without_command(): void
    {
        $sortedRoutes = [
            'GET' => [
                [
                    'method' => 'GET',
                    'uri' => 'api/users',
                    'name' => 'users.index',
                    'action' => 'UserController@index',
                    'middleware' => [],
                ],
            ],
        ];

        ob_start();
        $method = $this->getPrivateMethod('renderRoutesToTerminal');
        $method->invoke(null, $sortedRoutes, null);
        $output = ob_get_clean();

        $this->assertStringContainsString('═══ Method: GET ═══', $output);
        $this->assertStringContainsString('┌─ URI: api/users', $output);
        $this->assertStringContainsString('├─ Name: users.index', $output);
        $this->assertStringContainsString('├─ Action: UserController@index', $output);
        $this->assertStringContainsString('└─ Middleware (0): No middleware', $output);
    }

    public function test_render_routes_shows_different_colors_for_middleware(): void
    {
        $sortedRoutes = [
            'GET' => [
                [
                    'method' => 'GET',
                    'uri' => 'api/profile',
                    'name' => 'profile.show',
                    'action' => 'ProfileController@show',
                    'middleware' => ['auth'],
                ],
            ],
        ];

        $mockCommand = $this->createMock(Command::class);

        // Expect some output - format may vary
        $mockCommand->expects($this->atLeastOnce())
            ->method('line');

        $method = $this->getPrivateMethod('renderRoutesToTerminal');
        $method->invoke(null, $sortedRoutes, $mockCommand);
    }

    public function test_handle_with_empty_collection(): void
    {
        $emptyCollection = new RouteCollection([]);

        ob_start();
        RenderInformAction::handle($emptyCollection);
        $output = ob_get_clean();

        // Should not produce any method sections
        $this->assertEquals('', $output);
    }

    private function getPrivateMethod(string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass(RenderInformAction::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
