<?php

namespace App\Http\Requests\Users;

use App\Models\Usuario;
use App\Support\CorePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof Usuario
            ? $this->user('web')->can('update', $user)
            : $this->user('web')->can('create', Usuario::class);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $creating = ! $user instanceof Usuario;

        return [
            'nombre' => ['required', 'string', 'max:25'],
            'apellido_paterno' => ['required', 'string', 'max:25'],
            'apellido_materno' => ['required', 'string', 'max:25'],
            'edad' => ['required', 'integer', 'between:0,127'],
            'sexo' => $creating ? ['required', Rule::in(['H', 'M'])] : ['prohibited'],
            'email' => ['required', 'email', 'max:75', Rule::unique(Usuario::class, 'email')->ignore($user)],
            'usuario' => ['required', 'string', 'max:50', Rule::unique(Usuario::class, 'usuario')->ignore($user)],
            'password' => [
                $creating ? 'required' : 'nullable',
                'string',
                'max:4096',
                Rule::when($this->input('password') !== null, ['filled']),
            ],
            'plaza_id' => ['required', 'integer', Rule::exists('plazas', 'id')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')->whereIn('name', array_keys(CorePermissions::labels()))],
            'rol_id' => ['prohibited'],
            'Password' => ['prohibited'],
            'Hash' => ['prohibited'],
            'sha1_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'filled' => 'El campo :attribute no puede contener solamente espacios.',
            'string' => 'El campo :attribute debe ser texto.',
            'max.string' => 'El campo :attribute no debe superar :max caracteres.',
            'email.email' => 'El email debe tener un formato válido.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'edad.between' => 'La edad debe estar entre 0 y 127.',
            'unique' => 'El campo :attribute ya está registrado. Email y Usuario deben ser únicos.',
            'exists' => 'La selección de :attribute no existe.',
            'in' => 'La selección de :attribute no es válida.',
            'prohibited' => 'El campo :attribute no puede modificarse en este formulario.',
        ];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre', 'apellido_paterno' => 'apellido paterno', 'apellido_materno' => 'apellido materno',
            'edad' => 'edad', 'usuario' => 'User Name', 'plaza_id' => 'plaza', 'role_id' => 'rol'];
    }
}
