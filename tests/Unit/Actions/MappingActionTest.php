<?php

declare(strict_types=1);

namespace FrogUri\Test\Unit\Actions;

use FrogUri\Actions\MappingAction;
use FrogUri\Collections\RouteCollection;
use PHPUnit\Framework\TestCase;

final class MappingActionTest extends TestCase
{
    public function test_handle_creates_route_collection_from_valid_json(): void
    {
        $jsonData = [
            [
                'method' => 'GET',
                'uri' => 'api/users',
                'name' => 'users.index',
                'action' => 'UserController@index',
                'middleware' => ['auth'],
            ],
            [
                'method' => 'POST',
                'uri' => 'api/users',
                'name' => 'users.store',
                'action' => 'UserController@store',
                'middleware' => ['auth', 'verified'],
            ],
        ];

        $collection = MappingAction::handle($jsonData);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertEquals(2, $collection->count());
        $this->assertEquals($jsonData, $collection->toArray());
    }

    public function test_handle_with_empty_array(): void
    {
        $jsonData = [];

        $collection = MappingAction::handle($jsonData);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertEquals(0, $collection->count());
        $this->assertEquals([], $collection->toArray());
    }

    public function test_handle_with_single_route(): void
    {
        $jsonData = [
            [
                'method' => 'GET',
                'uri' => 'api/test',
                'name' => 'test.route',
                'action' => 'TestController@test',
                'middleware' => [],
            ],
        ];

        $collection = MappingAction::handle($jsonData);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertEquals(1, $collection->count());
        $this->assertEquals($jsonData, $collection->toArray());
    }

    public function test_handle_preserves_route_structure(): void
    {
        $jsonData = [
            [
                'method' => 'GET|HEAD',
                'uri' => 'api/complex/{id}/nested/{slug}',
                'name' => 'complex.nested.route',
                'action' => 'ComplexController@nestedAction',
                'middleware' => ['auth', 'throttle:60,1', 'role:admin'],
            ],
        ];

        $collection = MappingAction::handle($jsonData);
        $routes = $collection->toArray();

        $this->assertEquals('GET|HEAD', $routes[0]['method']);
        $this->assertEquals('api/complex/{id}/nested/{slug}', $routes[0]['uri']);
        $this->assertEquals('complex.nested.route', $routes[0]['name']);
        $this->assertEquals('ComplexController@nestedAction', $routes[0]['action']);
        $this->assertEquals(['auth', 'throttle:60,1', 'role:admin'], $routes[0]['middleware']);
    }

    public function test_handle_with_malformed_data_still_creates_collection(): void
    {
        $malformedData = [
            [
                'uri' => 'incomplete/route',
                // missing other required fields
            ],
            [
                'method' => 'POST',
                'action' => 'SomeController@action',
                // missing uri, name
                'middleware' => null,
            ],
        ];

        $collection = MappingAction::handle($malformedData);

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertEquals(2, $collection->count());
    }

    public function test_handle_maintains_original_data_integrity(): void
    {
        $originalData = [
            [
                'method' => 'GET',
                'uri' => 'original/route',
                'name' => 'original.route',
                'action' => 'OriginalController@action',
                'middleware' => ['original'],
            ],
        ];

        $collection = MappingAction::handle($originalData);
        $retrievedData = $collection->toArray();

        // Ensure the mapping didn't modify the original data
        $this->assertEquals($originalData, $retrievedData);
    }

    public function test_handle_returns_collection_with_proper_methods(): void
    {
        $jsonData = [
            [
                'method' => 'GET',
                'uri' => 'test/route',
                'name' => 'test.route',
                'action' => 'TestController@action',
                'middleware' => ['auth'],
            ],
        ];

        $collection = MappingAction::handle($jsonData);

        // Verify the collection has all expected methods available
        $this->assertTrue(method_exists($collection, 'count'));
        $this->assertTrue(method_exists($collection, 'toArray'));
        $this->assertTrue(method_exists($collection, 'filterByMiddleware'));
        $this->assertTrue(method_exists($collection, 'filterByMethod'));
        $this->assertTrue(method_exists($collection, 'getRoutesWithoutMiddleware'));
    }
}
