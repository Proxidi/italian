<?php

namespace App\Http\Requests\Products;

use App\Queries\IngredientsListing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngredientsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('products.ingredients.view');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(array_column(IngredientsListing::COLUMNS, 'field'))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', Rule::in(['xlsx', 'pdf'])],
        ];
    }

    public function messages(): array
    {
        return [
            'q.*' => 'La búsqueda debe ser texto de hasta 100 caracteres.',
            'sort.*' => 'La columna de orden no es válida.', 'direction.*' => 'El orden no es válido.',
            'page.*' => 'La página debe ser un número entero positivo.', 'format.*' => 'El formato no es válido.',
        ];
    }
}
