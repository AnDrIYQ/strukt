<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'is_public' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Перебільшено довжину імені',
            'password.min' => 'Пароль повинен містити мінімум 8 символів',
            'password.confirmed' => 'Паролі не співпадають',
            'file.max' => 'Файл занадто великий',
        ];
    }
}
