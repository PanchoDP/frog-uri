<?php

declare(strict_types=1);

namespace FrogUri\Test\Unit\Collections;

use FrogUri\Collections\RouteCollection;
use PHPUnit\Framework\TestCase;

final class RouteCollectionExcludeTest extends TestCase
{
    public function test_exclude_by_middleware_with_single_middleware(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/api/public',
                'name' => 'public.api',
                'action' => 'PublicController@index',
                'middleware' => [],
            ],
            [
                'method' => 'GET',
                'uri' => '/api/private',
                'name' => 'private.api',
                'action' => 'PrivateController@index',
                'middleware' => ['auth'],
            ],
            [
                'method' => 'GET',
                'uri' => '/admin',
                'name' => 'admin.dashboard',
                'action' => 'AdminController@index',
                'middleware' => ['auth', 'admin'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $result = $collection->excludeByMiddleware('auth');

        $this->assertCount(1, $result->toArray());
        $this->assertEquals('public.api', $result->toArray()[0]['name']);
    }

    public function test_exclude_by_middleware_with_multiple_middlewares(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/public',
                'name' => 'public.page',
                'action' => 'PublicController@show',
                'middleware' => [],
            ],
            [
                'method' => 'GET',
                'uri' => '/api/user',
                'name' => 'user.api',
                'action' => 'UserController@index',
                'middleware' => ['api', 'auth'],
            ],
            [
                'method' => 'GET',
                'uri' => '/web/user',
                'name' => 'user.web',
                'action' => 'UserController@show',
                'middleware' => ['web', 'auth'],
            ],
            [
                'method' => 'GET',
                'uri' => '/guest',
                'name' => 'guest.page',
                'action' => 'GuestController@index',
                'middleware' => ['web'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $result = $collection->excludeByMiddleware(['auth', 'web']);

        // Should only include routes that have NEITHER auth NOR web middleware
        $this->assertCount(1, $result->toArray());
        $this->assertEquals('public.page', $result->toArray()[0]['name']);
    }

    public function test_exclude_by_middleware_with_no_matching_exclusions(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/api/test1',
                'name' => 'test1',
                'action' => 'TestController@index',
                'middleware' => ['api', 'throttle'],
            ],
            [
                'method' => 'POST',
                'uri' => '/api/test2',
                'name' => 'test2',
                'action' => 'TestController@store',
                'middleware' => ['api', 'cors'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $result = $collection->excludeByMiddleware('auth');

        // All routes should remain as none have 'auth' middleware
        $this->assertCount(2, $result->toArray());
        $this->assertEquals('test1', $result->toArray()[0]['name']);
        $this->assertEquals('test2', $result->toArray()[1]['name']);
    }

    public function test_exclude_by_middleware_with_empty_middleware_routes(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/public',
                'name' => 'public.route',
                'action' => 'PublicController@index',
                'middleware' => [],
            ],
            [
                'method' => 'GET',
                'uri' => '/protected',
                'name' => 'protected.route',
                'action' => 'ProtectedController@index',
                'middleware' => ['auth'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $result = $collection->excludeByMiddleware('auth');

        // Should include only the route without auth middleware
        $this->assertCount(1, $result->toArray());
        $this->assertEquals('public.route', $result->toArray()[0]['name']);
    }

    public function test_get_routes_without_specific_middleware(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/unprotected',
                'name' => 'unprotected',
                'action' => 'UnprotectedController@index',
                'middleware' => ['api'],
            ],
            [
                'method' => 'GET',
                'uri' => '/protected',
                'name' => 'protected',
                'action' => 'ProtectedController@index',
                'middleware' => ['api', 'auth'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $result = $collection->getRoutesWithoutSpecificMiddleware('auth');

        $this->assertCount(1, $result);
        $this->assertEquals('unprotected', $result[0]['name']);
    }

    public function test_exclude_by_middleware_handles_string_middleware(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/test',
                'name' => 'test.route',
                'action' => 'TestController@index',
                'middleware' => 'invalid_non_array_middleware', // Invalid format, should be treated as no middleware
            ],
        ];

        $collection = new RouteCollection($routeData);
        $result = $collection->excludeByMiddleware('auth');

        // Route with invalid middleware should be included (treated as having no middleware)
        $this->assertCount(1, $result->toArray());
        $this->assertEquals('test.route', $result->toArray()[0]['name']);
    }
}
