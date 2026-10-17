<?php

// routes/web.php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
