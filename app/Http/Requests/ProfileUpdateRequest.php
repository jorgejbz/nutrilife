<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * users.email es UNIQUE y la regla 'lowercase' rechazaba correos con
     * mayúsculas; se normaliza antes de validar (igual que en el registro).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /** Mensajes en español (APP_LOCALE=en). */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre completo.',
            'name.max' => 'El nombre no puede superar 255 caracteres.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Escribe un correo válido.',
            'email.max' => 'El correo no puede superar 255 caracteres.',
            'email.unique' => 'Ese correo ya pertenece a otra cuenta.',
        ];
    }
}
