<?php

declare(strict_types=1);

namespace FrogUri\Actions;

use FrogUri\Collections\RouteCollection;

final class FilterMiddlewaresUriAction
{
    /**
     * Filter routes based on middleware inclusion or exclusion
     *
     * @param  array<int, string>  $selectedMiddlewares
     */
    public static function handle(RouteCollection $collection, array $selectedMiddlewares, bool $exclude = false): RouteCollection
    {
        if (empty($selectedMiddlewares)) {
            return $collection;
        }

        return $exclude
            ? $collection->excludeByMiddleware($selectedMiddlewares)
            : $collection->filterByMiddleware($selectedMiddlewares);
    }

    /**
     * Filter routes that contain the specified middlewares (include mode)
     *
     * @param  array<int, string>  $selectedMiddlewares
     */
    public static function include(RouteCollection $collection, array $selectedMiddlewares): RouteCollection
    {
        return self::handle($collection, $selectedMiddlewares, false);
    }

    /**
     * Filter routes that do NOT contain the specified middlewares (exclude mode)
     *
     * @param  array<int, string>  $selectedMiddlewares
     */
    public static function exclude(RouteCollection $collection, array $selectedMiddlewares): RouteCollection
    {
        return self::handle($collection, $selectedMiddlewares, true);
    }
}
