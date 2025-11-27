<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\Validator as ValidationValidator;
use Tymon\JWTAuth\Validators\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\ApiResponseService;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Kata sandi wajid diisi',
            'password.string' => 'Kata sandi wajib berupa teks'
        ];
    }

    protected function failedValidation(ValidationValidator $validator)
    {
        throw new HttpResponseException(ApiResponseService::validation($validator->errors()));
    }
}
