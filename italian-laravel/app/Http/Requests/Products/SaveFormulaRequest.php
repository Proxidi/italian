<?php

namespace App\Http\Requests\Products;

use App\Rules\ExactProductDecimal;

class SaveFormulaRequest extends FormulasReadRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user('web')->can($this->isMethod('POST') ? 'products.formulas.create' : 'products.formulas.update');
    }

    public function rules(): array
    {
        return [...parent::rules(),
            'cantidad' => ['bail', 'required', 'numeric', 'min:0', new ExactProductDecimal],
            'insumo_id' => $this->isMethod('POST') ? ['required', 'integer', 'exists:insumos,id'] : ['prohibited'],
            'unit_id' => ['required', 'integer', 'exists:medidas,id'],
            'producto_id' => ['prohibited'], 'unidad_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'cantidad.required' => 'La cantidad es obligatoria.', 'cantidad.numeric' => 'La cantidad debe ser numérica.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual que cero.',
            'insumo_id.required' => 'Selecciona un insumo.', 'insumo_id.exists' => 'El insumo no existe.',
            'unit_id.required' => 'Selecciona la unidad del insumo.', 'unit_id.exists' => 'La unidad no existe.',
        ];
    }
}
