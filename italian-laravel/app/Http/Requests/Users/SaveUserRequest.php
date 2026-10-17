<?php

namespace App\Http\Requests\Users;

use App\Actions\Users\SaveUser;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? $this->user('web')->can('update', $user)
            : $this->user('web')->can('create', User::class);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $creating = ! $user instanceof User;

        return [
            'nombre' => ['required', 'string', 'max:25'],
            'apepat' => ['required', 'string', 'max:25'],
            'apemat' => ['required', 'string', 'max:25'],
            // Edad is a signed TINYINT in the temporary legacy schema.
            'edad' => ['required', 'integer', 'between:0,127'],
            'sexo' => $creating ? ['required', Rule::in(['H', 'M'])] : ['prohibited'],
            'email' => ['required', 'email', 'max:75', Rule::unique(User::class, 'Email')->ignore($user)],
            'usuario' => ['required', 'string', 'max:50', Rule::unique(User::class, 'Usuario')->ignore($user)],
            'password' => [
                $creating ? 'required' : 'nullable',
                'string',
                'max:4096',
                Rule::when($this->input('password') !== null, ['filled']),
            ],
            'plaza_id' => ['required', 'integer', Rule::exists('tbl_plazas', 'id')],
            'rol_id' => ['required', 'integer', Rule::in(array_keys(SaveUser::ROLES)), Rule::exists('tbl_roles', 'id')],
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
        return ['nombre' => 'nombre', 'apepat' => 'apellido paterno', 'apemat' => 'apellido materno',
            'edad' => 'edad', 'usuario' => 'User Name', 'plaza_id' => 'plaza', 'rol_id' => 'rol'];
    }
}
