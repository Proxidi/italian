<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('users.directory.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => [
                'sometimes',
                Rule::in([
                    'default',
                    'name',
                    'age',
                    'email',
                    'username',
                    'plaza',
                ]),
            ],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'format' => ['sometimes', Rule::in(['csv', 'print', 'xlsx', 'pdf'])],
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        return [
            'q' => $data['q'] ?? '',
            'sort' => $data['sort'] ?? 'default',
            'direction' => $data['direction'] ?? 'asc',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => 'búsqueda',
            'sort' => 'orden',
            'direction' => 'dirección',
            'page' => 'página',
            'format' => 'formato',
        ];
    }
}
