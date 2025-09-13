<?php

declare(strict_types=1);

namespace FrogUri\Test\Unit\Collections;

use FrogUri\Collections\RouteCollection;
use PHPUnit\Framework\TestCase;

final class RouteCollectionTest extends TestCase
{
    private array $sampleRoutes;

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
            [
                'method' => 'GET|HEAD',
                'uri' => 'api/public-data',
                'name' => 'public.data',
                'action' => 'PublicController@data',
                'middleware' => [],
            ],
        ];
    }

    public function test_constructor_creates_collection_from_array(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertEquals(5, $collection->count());
    }

    public function test_from_json_creates_valid_collection(): void
    {
        $collection = RouteCollection::fromJson($this->sampleRoutes);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertEquals($this->sampleRoutes, $collection->toArray());
    }

    public function test_count_returns_correct_number(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);

        $this->assertEquals(5, $collection->count());

        $emptyCollection = new RouteCollection([]);
        $this->assertEquals(0, $emptyCollection->count());
    }

    public function test_to_array_returns_original_data(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);

        $this->assertEquals($this->sampleRoutes, $collection->toArray());
    }

    public function test_to_collection_returns_illuminate_collection(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $illuminateCollection = $collection->toCollection();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $illuminateCollection);
        $this->assertEquals(5, $illuminateCollection->count());
    }

    public function test_filter_by_middleware_with_single_middleware(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMiddleware('auth');

        $this->assertEquals(3, $filtered->count());

        $routes = $filtered->toArray();
        foreach ($routes as $route) {
            $this->assertContains('auth', $route['middleware']);
        }
    }

    public function test_filter_by_middleware_with_multiple_middlewares(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMiddleware(['auth', 'verified']);

        $this->assertEquals(3, $filtered->count());
    }

    public function test_filter_by_middleware_with_nonexistent_middleware(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMiddleware('nonexistent');

        $this->assertEquals(0, $filtered->count());
    }

    public function test_filter_by_method_with_single_method(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMethod('GET');

        $this->assertEquals(3, $filtered->count());
    }

    public function test_filter_by_method_with_multiple_methods(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMethod(['GET', 'POST']);

        $this->assertEquals(4, $filtered->count());
    }

    public function test_filter_by_method_case_insensitive(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMethod('get');

        $this->assertEquals(3, $filtered->count());
    }

    public function test_filter_by_uri_with_exact_match(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByUri('api/users');

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals('api/users', $filtered->toArray()[0]['uri']);
    }

    public function test_filter_by_uri_with_wildcard_patterns(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByUri('api/admin/*');

        $this->assertEquals(1, $filtered->count());
    }

    public function test_filter_by_name_with_exact_match(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByName('users.index');

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals('users.index', $filtered->toArray()[0]['name']);
    }

    public function test_filter_by_name_with_wildcard_patterns(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByName('*.index');

        $this->assertEquals(1, $filtered->count());
    }

    public function test_get_middlewares_for_existing_route(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $middlewares = $collection->getMiddlewaresForRoute('posts.store');

        $this->assertEquals(['auth', 'verified'], $middlewares);
    }

    public function test_get_middlewares_for_nonexistent_route(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $middlewares = $collection->getMiddlewaresForRoute('nonexistent.route');

        $this->assertEquals([], $middlewares);
    }

    public function test_get_routes_with_middleware(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $routes = $collection->getRoutesWithMiddleware('auth');

        $this->assertCount(3, $routes);
    }

    public function test_get_routes_by_method(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $routes = $collection->getRoutesByMethod('GET');

        $this->assertCount(3, $routes);
    }

    public function test_get_all_middlewares_returns_unique_sorted(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $middlewares = $collection->getAllMiddlewares();

        $expectedMiddlewares = ['auth', 'permission:delete-users', 'role:admin', 'verified'];
        $this->assertEquals($expectedMiddlewares, $middlewares);
    }

    public function test_get_all_methods_returns_unique_sorted(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $methods = $collection->getAllMethods();

        $expectedMethods = ['DELETE', 'GET', 'HEAD', 'POST'];
        $this->assertEquals($expectedMethods, $methods);
    }

    public function test_group_by_middleware_correct_grouping(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $grouped = $collection->groupByMiddleware();

        $this->assertArrayHasKey('auth', $grouped);
        $this->assertArrayHasKey('verified', $grouped);
        $this->assertArrayHasKey('role:admin', $grouped);
        $this->assertCount(3, $grouped['auth']);
        $this->assertCount(1, $grouped['verified']);
    }

    public function test_group_by_method_correct_grouping(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $grouped = $collection->groupByMethod();

        $this->assertArrayHasKey('GET', $grouped);
        $this->assertArrayHasKey('POST', $grouped);
        $this->assertArrayHasKey('DELETE', $grouped);
        $this->assertArrayHasKey('HEAD', $grouped);

        $this->assertCount(3, $grouped['GET']);
        $this->assertCount(1, $grouped['POST']);
        $this->assertCount(1, $grouped['DELETE']);
        $this->assertCount(1, $grouped['HEAD']);
    }

    public function test_get_routes_without_middleware(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $routesWithoutMiddleware = $collection->getRoutesWithoutMiddleware();

        $this->assertEquals(2, $routesWithoutMiddleware->count());

        $routes = $routesWithoutMiddleware->toArray();
        foreach ($routes as $route) {
            $this->assertEmpty($route['middleware']);
        }
    }

    public function test_get_dangerous_routes_returns_unprotected_routes(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $dangerousRoutes = $collection->getDangerousRoutes();

        $this->assertCount(2, $dangerousRoutes);

        foreach ($dangerousRoutes as $route) {
            $this->assertEmpty($route['middleware']);
        }
    }

    public function test_filter_methods_return_new_instances(): void
    {
        $collection = new RouteCollection($this->sampleRoutes);
        $filtered = $collection->filterByMethod('GET');

        $this->assertNotSame($collection, $filtered);
        $this->assertEquals(5, $collection->count());
        $this->assertEquals(3, $filtered->count());
    }

    public function test_empty_collection_handling(): void
    {
        $collection = new RouteCollection([]);

        $this->assertEquals(0, $collection->count());
        $this->assertEquals([], $collection->toArray());
        $this->assertEquals([], $collection->getAllMiddlewares());
        $this->assertEquals([], $collection->getAllMethods());
        $this->assertEquals(0, $collection->getRoutesWithoutMiddleware()->count());
    }

    public function test_routes_with_missing_properties(): void
    {
        $routesWithMissingProps = [
            [
                'method' => 'GET',
                'uri' => 'test/route',
                // missing name, action, middleware
            ],
            [
                'uri' => 'another/route',
                'middleware' => ['auth'],
                // missing method, name, action
            ],
        ];

        $collection = new RouteCollection($routesWithMissingProps);

        $this->assertEquals(2, $collection->count());

        // getAllMiddlewares() may return null values from routes with missing middleware property
        $middlewares = $collection->getAllMiddlewares();
        $filteredMiddlewares = array_filter($middlewares, function ($value) {
            return $value !== null;
        });

        $this->assertContains('auth', $filteredMiddlewares);
    }
}
