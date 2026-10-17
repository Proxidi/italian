<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('configuration.notifications.view')
            && $this->user('web')->can('configuration.notifications.update');
    }

    public function rules(): array
    {
        return [
            'setting_id' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.*' => 'Selecciona una fecha válida entre 1000-01-01 y 9999-12-31.',
            'setting_id.*' => 'El registro de configuración no es válido.',
        ];
    }
}
