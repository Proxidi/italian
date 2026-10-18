<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class SaveUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('products.units.view')
            && $this->user('web')->can($this->isMethod('POST') ? 'products.units.create' : 'products.units.update');
    }

    public function rules(): array
    {
        // The legacy table has no unique constraint on names. Do not invent one.
        return ['nombre' => ['required', 'string', 'max:50']];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la unidad.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no debe exceder 50 caracteres.',
        ];
    }
}
