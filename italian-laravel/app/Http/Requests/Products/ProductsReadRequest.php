<?php

namespace App\Http\Requests\Products;

use App\Queries\ProductsListing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')->can('products.catalog.view');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'family' => ['nullable', 'integer', 'exists:tbl_familia_productos,id'],
            'sort' => ['nullable', Rule::in(array_column(ProductsListing::COLUMNS, 'field'))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'format' => ['nullable', Rule::in(['xlsx', 'pdf'])],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only(['q', 'family', 'sort', 'direction', 'page']);
    }
}
