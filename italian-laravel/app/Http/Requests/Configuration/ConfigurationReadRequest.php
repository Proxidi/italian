<?php

namespace App\Http\Requests\Configuration;

use App\Queries\ConfigurationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigurationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can($this->route('configuration_permission'));
    }

    public function rules(): array
    {
        $section = $this->route('configuration_permission') === 'configuration.upload-window.view' ? 'dates' : 'notifications';

        return [
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', Rule::in(['xlsx', 'pdf'])],
            'sort' => ['nullable', Rule::in([...array_column(ConfigurationSettings::SECTIONS[$section]['columns'], 'field'), 'id'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'q.*' => 'La búsqueda debe ser texto de hasta 100 caracteres.',
            'page.*' => 'La página debe ser un número entero positivo.',
            'format.*' => 'El formato de exportación no es válido.',
            'sort.*' => 'La columna de ordenamiento no es válida.',
            'direction.*' => 'La dirección de ordenamiento no es válida.',
        ];
    }
}
