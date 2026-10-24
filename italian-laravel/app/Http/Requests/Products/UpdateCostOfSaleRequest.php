<?php

namespace App\Http\Requests\Products;

use App\Rules\ExactProductDecimal;

class UpdateCostOfSaleRequest extends CostOfSaleReadRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->input('mode', 'orders') !== 'remittances'
            && $this->user('web')->can('products.cost-of-sale.update');
    }

    public function rules(): array
    {
        return [...parent::rules(), 'cost' => ['bail', 'required', 'numeric', new ExactProductDecimal]];
    }
}
