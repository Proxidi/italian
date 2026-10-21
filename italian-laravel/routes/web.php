<?php

// routes/web.php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CostOfSaleController;
use App\Http\Controllers\IngredientsController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProductFormulasController;
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
    Route::middleware('can:products.catalog.view')->scopeBindings()->group(function () {
        Route::get('/Productos/list', [ProductCatalogController::class, 'index'])->name('products.catalog.index');
        Route::get('/Productos/list/export', [ProductCatalogController::class, 'export'])->name('products.catalog.export');
        Route::get('/Productos/list/nuevo', [ProductCatalogController::class, 'index'])
            ->middleware('can:products.catalog.create')->name('products.catalog.create');
        Route::post('/Productos/list', [ProductCatalogController::class, 'store'])
            ->middleware('can:products.catalog.create')->name('products.catalog.store');
        Route::middleware('can:products.formulas.view')->group(function () {
            Route::get('/Productos/{product}/formulas', [ProductFormulasController::class, 'index'])
                ->whereNumber('product')->name('products.formulas.index');
            Route::get('/Productos/{product}/formulas/{formula}/editar', [ProductFormulasController::class, 'index'])
                ->middleware('can:products.formulas.update')->whereNumber(['product', 'formula'])->name('products.formulas.edit');
            Route::post('/Productos/{product}/formulas', [ProductFormulasController::class, 'store'])
                ->middleware('can:products.formulas.create')->whereNumber('product')->name('products.formulas.store');
            Route::put('/Productos/{product}/formulas/{formula}', [ProductFormulasController::class, 'update'])
                ->middleware('can:products.formulas.update')->whereNumber(['product', 'formula'])->name('products.formulas.update');
        });
    });
    Route::middleware('can:products.units.view')->group(function () {
        Route::get('/Medidas', [ProductsController::class, 'units'])->name('products.units.index');
        Route::get('/Medidas/export', [ProductsController::class, 'export'])->name('products.units.export');
        Route::post('/Medidas', [ProductsController::class, 'store'])
            ->middleware('can:products.units.create')->name('products.units.store');
        Route::put('/Medidas/{unit}', [ProductsController::class, 'update'])
            ->middleware('can:products.units.update')->whereNumber('unit')->name('products.units.update');
    });

    Route::middleware('can:products.ingredients.view')->scopeBindings()->group(function () {
        Route::get('/Insumos', [IngredientsController::class, 'index'])->name('products.ingredients.index');
        Route::get('/Insumos/export', [IngredientsController::class, 'export'])->name('products.ingredients.export');
        Route::get('/Insumos/{ingredient}/conversiones', [IngredientsController::class, 'index'])
            ->whereNumber('ingredient')->name('products.ingredients.conversions');
        Route::put('/Insumos/{ingredient}/conversiones/{conversion}', [IngredientsController::class, 'update'])
            ->middleware('can:products.ingredients.conversions.update')->whereNumber(['ingredient', 'conversion'])
            ->name('products.ingredients.conversions.update');
    });

    Route::middleware('can:products.cost-of-sale.view')->group(function () {
        Route::get('/Insumos/getInsumosCostoVenta', [CostOfSaleController::class, 'index'])->name('products.cost-of-sale.index');
        Route::get('/Insumos/costo-venta/{ingredient}/editar', [CostOfSaleController::class, 'index'])
            ->middleware('can:products.cost-of-sale.update')->whereNumber('ingredient')->name('products.cost-of-sale.edit');
        Route::put('/Insumos/costo-venta/{ingredient}', [CostOfSaleController::class, 'update'])
            ->middleware('can:products.cost-of-sale.update')->whereNumber('ingredient')->name('products.cost-of-sale.update');
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
