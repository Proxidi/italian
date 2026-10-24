<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_corte_dlc', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('numero_dias');
            $table->string('descripcion', 50);
        });
        Schema::create('configuracion_notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha_inicial');
            $table->string('descripcion', 50);
        });
        // Cardinality is checked under lock by the application/importer. No
        // default rows or invented historical timestamps are generated here.
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_notificaciones');
        Schema::dropIfExists('configuracion_corte_dlc');
    }
};
