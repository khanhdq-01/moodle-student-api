<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }
    public function messages(): array
    {
        return [
            'username.required' => 'Vui lòng nhập tên tài khoản.',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ];
    }
}
