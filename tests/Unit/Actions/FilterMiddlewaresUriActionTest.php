<?php

declare(strict_types=1);

namespace FrogUri\Test\Unit\Actions;

use FrogUri\Actions\FilterMiddlewaresUriAction;
use FrogUri\Collections\RouteCollection;
use PHPUnit\Framework\TestCase;

final class FilterMiddlewaresUriActionTest extends TestCase
{
    public function test_filters_routes_by_selected_middlewares(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/api/test1',
                'name' => 'test1',
                'action' => 'TestController@index',
                'middleware' => ['api', 'auth'],
            ],
            [
                'method' => 'POST',
                'uri' => '/api/test2',
                'name' => 'test2',
                'action' => 'TestController@store',
                'middleware' => ['api', 'auth', 'admin'],
            ],
            [
                'method' => 'GET',
                'uri' => '/public/test',
                'name' => 'public',
                'action' => 'PublicController@index',
                'middleware' => [],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $selectedMiddlewares = ['auth'];

        $result = FilterMiddlewaresUriAction::handle($collection, $selectedMiddlewares);

        $this->assertCount(2, $result->toArray());
        $this->assertSame('test1', $result->toArray()[0]['name']);
        $this->assertSame('test2', $result->toArray()[1]['name']);
    }

    public function test_returns_all_routes_when_no_middlewares_selected(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/api/test1',
                'name' => 'test1',
                'action' => 'TestController@index',
                'middleware' => ['api', 'auth'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $selectedMiddlewares = [];

        $result = FilterMiddlewaresUriAction::handle($collection, $selectedMiddlewares, false);

        $this->assertCount(1, $result->toArray());
        $this->assertSame($collection->toArray(), $result->toArray());
    }

    public function test_returns_empty_collection_when_no_routes_match(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/public/test',
                'name' => 'public',
                'action' => 'PublicController@index',
                'middleware' => [],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $selectedMiddlewares = ['auth'];

        $result = FilterMiddlewaresUriAction::handle($collection, $selectedMiddlewares);

        $this->assertCount(0, $result->toArray());
    }

    public function test_exclude_mode_filters_routes_without_middleware(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/public/test',
                'name' => 'public',
                'action' => 'PublicController@index',
                'middleware' => [],
            ],
            [
                'method' => 'GET',
                'uri' => '/api/test',
                'name' => 'api.test',
                'action' => 'ApiController@index',
                'middleware' => ['api'],
            ],
            [
                'method' => 'GET',
                'uri' => '/protected/test',
                'name' => 'protected',
                'action' => 'ProtectedController@index',
                'middleware' => ['auth'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $selectedMiddlewares = ['auth'];

        $result = FilterMiddlewaresUriAction::handle($collection, $selectedMiddlewares, true);

        $this->assertCount(2, $result->toArray());
        $this->assertSame('public', $result->toArray()[0]['name']);
        $this->assertSame('api.test', $result->toArray()[1]['name']);
    }

    public function test_include_method_works_as_expected(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/api/test1',
                'name' => 'test1',
                'action' => 'TestController@index',
                'middleware' => ['api', 'auth'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $selectedMiddlewares = ['auth'];

        $result = FilterMiddlewaresUriAction::include($collection, $selectedMiddlewares);

        $this->assertCount(1, $result->toArray());
        $this->assertSame('test1', $result->toArray()[0]['name']);
    }

    public function test_exclude_method_works_as_expected(): void
    {
        $routeData = [
            [
                'method' => 'GET',
                'uri' => '/public/test',
                'name' => 'public',
                'action' => 'PublicController@index',
                'middleware' => [],
            ],
            [
                'method' => 'GET',
                'uri' => '/protected/test',
                'name' => 'protected',
                'action' => 'ProtectedController@index',
                'middleware' => ['auth'],
            ],
        ];

        $collection = new RouteCollection($routeData);
        $selectedMiddlewares = ['auth'];

        $result = FilterMiddlewaresUriAction::exclude($collection, $selectedMiddlewares);

        $this->assertCount(1, $result->toArray());
        $this->assertSame('public', $result->toArray()[0]['name']);
    }
}
