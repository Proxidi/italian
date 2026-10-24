<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['medidas', 'categorias_insumos', 'familias_productos'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $column = $table->string('nombre', 50);
                if ($name === 'familias_productos') {
                    $column->unique();
                }
            });
        }
        Schema::create('insumos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->string('clave_pedido', 45);
            $table->foreignId('categorias_insumos_id')->constrained('categorias_insumos')->restrictOnDelete();
            foreach (['unidad_id', 'unidad_pedido', 'unidad_final'] as $column) {
                $table->foreignId($column)->constrained('medidas')->restrictOnDelete();
            }
            foreach (['inv_produccion', 'inv_fisico', 'pedido', 'pedido_pasteleria', 'analisis_cv'] as $column) {
                $table->tinyInteger($column);
            }
            // External catalog IDs retained for future migrations, no invented FK.
            $table->smallInteger('categoria_pedido_id');
            $table->tinyInteger('categoria_quebranto_id');
            $table->decimal('costo_venta', 65, 24);
            $table->smallInteger('orden_captura_inventario');
        });
        Schema::create('conversiones_insumos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->string('nombre', 150);
            foreach (['bodega' => 200, 'piso' => 200, 'total' => 50, 'dlc' => 50, 'pedido' => 50, 'cvi' => 20, 'cvg' => 20] as $column => $length) {
                $table->string($column, $length);
            }
            // Legacy permits multiple rows. Preserve them and disable ambiguous edits.
        });
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('clave', 50)->unique();
            $table->foreignId('familia_id')->constrained('familias_productos')->restrictOnDelete();
            $table->tinyInteger('estado')->default(1);
        });
        Schema::create('productos_insumos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->decimal('cantidad', 65, 24);
            $table->index(['producto_id', 'insumo_id']);
            // Six duplicate pairs exist in the available dump. Preserve IDs/rows;
            // writers serialize on the parent and refuse ambiguous pairs.
        });
    }

    public function down(): void
    {
        foreach (['productos_insumos', 'productos', 'conversiones_insumos', 'insumos', 'familias_productos', 'categorias_insumos', 'medidas'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
