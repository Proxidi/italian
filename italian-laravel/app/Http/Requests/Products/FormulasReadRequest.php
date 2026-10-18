<?php

namespace App\Http\Requests\Products;

class FormulasReadRequest extends ProductsReadRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user('web')->can('products.formulas.view');
    }
}
