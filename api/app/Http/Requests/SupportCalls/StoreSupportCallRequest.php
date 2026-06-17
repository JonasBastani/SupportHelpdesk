<?php

namespace App\Http\Requests\SupportCalls;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportCallRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'O titulo e obrigatorio.',
            'title.max' => 'O titulo deve ter no maximo 255 caracteres.',
            'description.required' => 'A descricao e obrigatoria.',
            'priority.required' => 'A prioridade e obrigatoria.',
            'priority.in' => 'A prioridade informada e invalida.',
            'responsible_user_id.integer' => 'O responsavel informado e invalido.',
            'responsible_user_id.exists' => 'O responsavel informado nao existe.',
        ];
    }
}
