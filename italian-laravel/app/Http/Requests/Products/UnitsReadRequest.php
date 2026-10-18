<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('products.units.view');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', Rule::in(['xlsx', 'pdf'])],
        ];
    }

    public function messages(): array
    {
        return [
            'q.*' => 'La búsqueda debe ser texto de hasta 100 caracteres.',
            'direction.*' => 'El orden no es válido.',
            'page.*' => 'La página debe ser un número entero positivo.',
            'format.*' => 'El formato de exportación no es válido.',
        ];
    }
}
