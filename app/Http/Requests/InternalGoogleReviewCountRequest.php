<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InternalGoogleReviewCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'regex:/^(0[1-9]|1[0-2])-\d{2}$/'],
            'location' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'El mes es obligatorio.',
            'month.regex' => 'El mes debe tener el formato MM-YY.',
            'location.required' => 'La delegación es obligatoria.',
            'location.string' => 'La delegación debe ser una cadena de texto.',
            'location.max' => 'La delegación no puede superar 255 caracteres.',
        ];
    }
}
