<?php

namespace App\Http\Requests\SupportCalls;

use Illuminate\Foundation\Http\FormRequest;

class IndexSupportCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:open,in_progress,resolved,closed'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'sort_by' => ['sometimes', 'string'],
            'sort_direction' => ['sometimes', 'string'],
            'per_page' => ['sometimes'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'O status informado e invalido.',
            'priority.in' => 'A prioridade informada e invalida.',
        ];
    }
}
