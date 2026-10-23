<?php

namespace Database\Factories;

use App\Models\Plaza;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nombre' => mb_substr(fake()->firstName(), 0, 25),
            'apellido_paterno' => mb_substr(fake()->lastName(), 0, 25),
            'apellido_materno' => mb_substr(fake()->lastName(), 0, 25),
            'edad' => 30, 'sexo' => 'H',
            'email' => fake()->unique()->safeEmail(), 'usuario' => fake()->unique()->userName(),
            'password' => Hash::make('synthetic-password'),
            'plaza_id' => fn () => Plaza::create(['nombre' => 'Plaza sintética', 'acronimo' => 'TEST'])->id,
        ];
    }
}
