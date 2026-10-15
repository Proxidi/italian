<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('access-log.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->query->has('date')) {
            $this->merge([
                'date' => now()->toDateString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'string', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => [
                'sometimes',
                Rule::in([
                    'default',
                    'date',
                    'time',
                    'plaza',
                    'username',
                    'role',
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
            'date' => $data['date'],
            'q' => $data['q'] ?? '',
            'sort' => $data['sort'] ?? 'default',
            'direction' => $data['direction'] ?? 'desc',
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'fecha',
            'q' => 'búsqueda',
            'sort' => 'orden',
            'direction' => 'dirección',
            'page' => 'página',
            'format' => 'formato',
        ];
    }
}
