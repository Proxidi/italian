<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUploadWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('configuration.upload-window.view')
            && $this->user('web')->can('configuration.upload-window.update');
    }

    public function rules(): array
    {
        return [
            'setting_id' => ['required', 'integer', 'min:1'],
            // Signed TINYINT in the legacy DDL, rather than an arbitrary policy.
            'numero_dias_valor' => ['required', 'integer', 'between:1,127'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_dias_valor.required' => 'El número de días es obligatorio.',
            'numero_dias_valor.integer' => 'El número de días debe ser un entero.',
            'numero_dias_valor.between' => 'Selecciona un número de días entre 1 y 127.',
            'setting_id.*' => 'El registro de configuración no es válido.',
        ];
    }
}
