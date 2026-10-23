<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fresh Laravel database only; this is not an upgrade of tbl_usuarios.
        Schema::create('plazas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('acronimo', 20);
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 25);
            $table->string('apellido_paterno', 25);
            $table->string('apellido_materno', 25);
            $table->tinyInteger('edad');
            $table->char('sexo', 1);
            $table->string('email', 75)->unique();
            $table->string('usuario', 50)->unique();
            $table->string('password');
            $table->foreignId('plaza_id')->index()->constrained('plazas')->restrictOnDelete();
            $table->rememberToken();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('plazas');
    }
};
