<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familias_gastos', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 5)->unique();
            $table->string('nombre', 50);
        });
        Schema::create('grupos_gastos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('familia_id')->constrained('familias_gastos')->restrictOnDelete();
            $table->string('nombre', 50);
        });
        // Historical capture groups, NOT authorization roles or Spatie grants.
        foreach (['grupos_captura_gastos', 'formas_pago'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 50)->unique();
            });
        }
        Schema::create('conceptos_gastos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos_gastos')->restrictOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->foreignId('unidad_id')->constrained('medidas')->restrictOnDelete();
            $table->foreignId('grupo_captura_id')->constrained('grupos_captura_gastos')->restrictOnDelete();
            $table->string('nombre', 50);
            $table->decimal('stock_minimo', 65, 24);
            $table->decimal('stock_maximo', 65, 24);
            $table->tinyInteger('bandera_stocks');
            // Several real concepts may point to the same ingredient; do not merge.
        });
        Schema::create('gastos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('plaza_id')->constrained('plazas')->restrictOnDelete();
            $table->foreignId('concepto_gasto_id')->constrained('conceptos_gastos')->restrictOnDelete();
            $table->foreignId('forma_pago_id')->constrained('formas_pago')->restrictOnDelete();
            foreach (['cantidad', 'importe', 'nueva_cantidad', 'antigua_cantidad'] as $column) {
                $table->decimal($column, 65, 24);
            }
            $table->tinyInteger('modificacion');
            $table->date('fecha');
            $table->tinyInteger('estado');
            $table->string('notas', 300);
            $table->tinyInteger('corte');
            $table->string('archivos', 300);
            $table->string('folio', 20);
            $table->index(['plaza_id', 'fecha', 'concepto_gasto_id'], 'gastos_plaza_periodo_concepto');
            $table->index(['fecha', 'concepto_gasto_id', 'plaza_id'], 'gastos_periodo_concepto_plaza');
        });
    }

    public function down(): void
    {
        foreach (['gastos', 'conceptos_gastos', 'formas_pago', 'grupos_captura_gastos', 'grupos_gastos', 'familias_gastos'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
