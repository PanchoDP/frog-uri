<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use FrogUri\Actions\MappingAction;
use FrogUri\Actions\RenderInformAction;

// Datos de ejemplo simulando la salida de php artisan route:list --json
$sampleRoutes = [
    [
        'method' => 'GET',
        'uri' => 'api/users',
        'name' => 'users.index',
        'action' => 'App\\Http\\Controllers\\UserController@index',
        'middleware' => [],
    ],
    [
        'method' => 'GET',
        'uri' => 'api/profile',
        'name' => 'profile.show',
        'action' => 'App\\Http\\Controllers\\ProfileController@show',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'uri' => 'api/posts',
        'name' => 'posts.store',
        'action' => 'App\\Http\\Controllers\\PostController@store',
        'middleware' => ['auth', 'verified'],
    ],
    [
        'method' => 'GET',
        'uri' => 'api/public-data',
        'name' => 'public.data',
        'action' => 'App\\Http\\Controllers\\PublicController@data',
        'middleware' => [],
    ],
    [
        'method' => 'DELETE',
        'uri' => 'api/admin/users/{id}',
        'name' => 'admin.users.destroy',
        'action' => 'App\\Http\\Controllers\\Admin\\UserController@destroy',
        'middleware' => ['auth', 'role:admin', 'permission:delete-users'],
    ],
];

echo "=== PRUEBA DE FUNCIONALIDAD ===\n\n";

// Crear collection y renderizar las rutas
$collection = MappingAction::handle($sampleRoutes);
RenderInformAction::handle($collection);

echo "\n=== FIN DE PRUEBA ===\n";
