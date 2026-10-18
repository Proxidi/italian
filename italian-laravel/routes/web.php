<?php

// routes/web.php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\UsersReadController;
use App\Http\Controllers\UsersWriteController;
use Illuminate\Support\Facades\Route;

// web.php utiliza sesiones nativas y protección CSRF.
// Sin Passport ni Sanctum.

Route::get('/', function () {
    return auth('web')->check()
        ? redirect()->route('inicio')
        : redirect()->route('login');
});

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth:web')->group(function () {
    Route::get('/Inicio', [AuthenticatedSessionController::class, 'inicio'])
        ->name('inicio');

    // El formulario de cierre de sesión debe incluir @csrf.
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/Opciones', [ConfigurationController::class, 'index'])
        ->middleware('can:configuration.view')->name('configuration.index');

    Route::get('/Productos', [ProductsController::class, 'index'])
        ->middleware('can:products.view')->name('products.index');
    Route::middleware('can:products.units.view')->group(function () {
        Route::get('/Medidas', [ProductsController::class, 'units'])->name('products.units.index');
        Route::get('/Medidas/export', [ProductsController::class, 'export'])->name('products.units.export');
        Route::post('/Medidas', [ProductsController::class, 'store'])
            ->middleware('can:products.units.create')->name('products.units.store');
        Route::put('/Medidas/{unit}', [ProductsController::class, 'update'])
            ->middleware('can:products.units.update')->whereNumber('unit')->name('products.units.update');
    });

    Route::middleware('can:configuration.upload-window.view')->group(function () {
        Route::get('/Opciones/fechas', [ConfigurationController::class, 'dates'])
            ->defaults('configuration_permission', 'configuration.upload-window.view')->name('configuration.dates');
        Route::get('/Opciones/fechas/export', [ConfigurationController::class, 'exportDates'])
            ->defaults('configuration_permission', 'configuration.upload-window.view')->name('configuration.dates.export');
        Route::put('/Opciones/fechas', [ConfigurationController::class, 'updateDates'])
            ->middleware('can:configuration.upload-window.update')->name('configuration.dates.update');
    });

    Route::middleware('can:configuration.notifications.view')->group(function () {
        Route::get('/Usuarios/notificaciones', [ConfigurationController::class, 'notifications'])
            ->defaults('configuration_permission', 'configuration.notifications.view')->name('configuration.notifications');
        Route::get('/Usuarios/notificaciones/export', [ConfigurationController::class, 'exportNotifications'])
            ->defaults('configuration_permission', 'configuration.notifications.view')->name('configuration.notifications.export');
        Route::put('/Usuarios/notificaciones', [ConfigurationController::class, 'updateNotifications'])
            ->middleware('can:configuration.notifications.update')->name('configuration.notifications.update');
    });

    Route::middleware('can:users.directory.view')->group(function () {
        Route::get('/Usuarios', [UsersReadController::class, 'directory'])
            ->name('usuarios.index');

        Route::get('/Usuarios/export', [UsersReadController::class, 'exportDirectory'])
            ->name('usuarios.export');
    });

    Route::post('/Usuarios', [UsersWriteController::class, 'store'])
        ->middleware('can:create,App\\Models\\User')->name('usuarios.store');
    Route::get('/Usuarios/{user}/editar', [UsersWriteController::class, 'edit'])
        ->middleware('can:update,user')->whereNumber('user')->name('usuarios.edit');
    Route::put('/Usuarios/{user}', [UsersWriteController::class, 'update'])
        ->middleware('can:update,user')->whereNumber('user')->name('usuarios.update');

    Route::middleware('can:access-log.view')->group(function () {
        Route::get(
            '/Usuarios/listaAccesos',
            [UsersReadController::class, 'accessLog'],
        )->name('usuarios.accesos');

        Route::get(
            '/Usuarios/listaAccesos/export',
            [UsersReadController::class, 'exportAccessLog'],
        )->name('usuarios.accesos.export');
    });
});
