<?php
// app/Http/Requests/Auth/LoginRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El usuario es obligatorio.',
            'username.max' => 'El usuario no debe superar los 50 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
