<?php

declare(strict_types=1);

namespace FrogUri\Actions;

use FrogUri\Collections\RouteCollection;

final class MappingAction
{
    /**
     * @param  array<int, array<string, mixed>>  $json_data
     */
    public static function handle(array $json_data): RouteCollection
    {
        return RouteCollection::fromJson($json_data);
    }
}
