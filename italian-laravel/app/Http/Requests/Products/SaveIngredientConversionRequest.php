<?php

namespace App\Http\Requests\Products;

use App\Rules\CompatibleConversion;

class SaveIngredientConversionRequest extends IngredientsReadRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('products.ingredients.view')
            && $this->user('web')->can('products.ingredients.conversions.update');
    }

    public function rules(): array
    {
        return [...parent::rules(),
            'bodega' => ['bail', 'required', 'string', 'max:200', new CompatibleConversion(3)],
            'pedido' => ['bail', 'required', 'string', 'max:50', new CompatibleConversion(1)],
        ];
    }

    public function messages(): array
    {
        return [...parent::messages(), 'bodega.required' => 'Bodega es obligatoria.', 'pedido.required' => 'Pedido es obligatorio.',
            'bodega.string' => 'Bodega debe ser texto.', 'pedido.string' => 'Pedido debe ser texto.',
            'bodega.max' => 'Bodega admite hasta 200 caracteres.', 'pedido.max' => 'Pedido admite hasta 50 caracteres.'];
    }
}
