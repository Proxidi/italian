<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostOfSaleReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('products.cost-of-sale.view');
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(['orders', 'remittances'])],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-08', 'before_or_equal:9999-12-24'],
            'plaza' => ['nullable', 'integer', 'exists:plazas,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['id', 'Nombre', 'Plaza', 'Cost'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only(['mode', 'date', 'plaza', 'q', 'sort', 'direction']);
    }
}
