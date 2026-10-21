<?php

namespace App\Http\Requests\Products;

class UpdateCostOfSaleRequest extends CostOfSaleReadRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user('web')->can('products.cost-of-sale.update');
    }

    public function rules(): array
    {
        return [...parent::rules(), 'cost' => ['required', 'numeric', function ($attribute, $value, $fail): void {
            if (! is_finite((float) $value)) {
                $fail('El costo debe ser un número finito representable.');
            }
        }]];
    }
}
