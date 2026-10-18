<?php

namespace App\Http\Requests\Products;

class SaveProductRequest extends ProductsReadRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->user('web')->can('products.catalog.create');
    }

    public function rules(): array
    {
        return [...parent::rules(),
            'nombre' => ['required', 'string', 'max:100', 'unique:tbl_productos,Nombre'],
            'clave' => ['required', 'string', 'max:50', 'unique:tbl_productos,Clave'],
            'familia_id' => ['required', 'integer', 'exists:tbl_familia_productos,id'],
            'Estado' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.', 'nombre.max' => 'El nombre admite hasta 100 caracteres.',
            'nombre.unique' => 'El nombre ya está registrado.', 'clave.required' => 'La clave es obligatoria.',
            'clave.max' => 'La clave admite hasta 50 caracteres.', 'clave.unique' => 'La clave ya está registrada.',
            'familia_id.required' => 'Selecciona una familia.', 'familia_id.exists' => 'La familia no existe.',
        ];
    }
}
