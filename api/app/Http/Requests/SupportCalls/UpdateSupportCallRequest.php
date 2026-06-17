<?php

namespace App\Http\Requests\SupportCalls;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportCallRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,resolved,closed'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'O titulo deve ter no maximo 255 caracteres.',
            'priority.in' => 'A prioridade informada e invalida.',
            'status.in' => 'O status informado e invalido.',
            'responsible_user_id.integer' => 'O responsavel informado e invalido.',
            'responsible_user_id.exists' => 'O responsavel informado nao existe.',
        ];
    }
}
