<?php

namespace App\Http\Requests\Products;

use Closure;

class SaveFormulaRequest extends FormulasReadRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user('web')->can($this->isMethod('POST') ? 'products.formulas.create' : 'products.formulas.update');
    }

    public function rules(): array
    {
        return [...parent::rules(),
            'cantidad' => ['bail', 'required', 'numeric', 'min:0', function (string $attribute, mixed $value, Closure $fail): void {
                $number = (float) $value;
                $stored = unpack('f', pack('f', $number))[1];
                if (! is_finite($number) || ! is_finite($stored) || ($number > 0 && $stored == 0)) {
                    $fail('La cantidad debe ser finita y representable en la columna FLOAT actual.');
                }
            }],
            'insumo_id' => $this->isMethod('POST') ? ['required', 'integer', 'exists:tbl_insumos,id'] : ['prohibited'],
            'unit_id' => ['required', 'integer', 'exists:tbl_unidades,id'],
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
