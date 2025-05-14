<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ForgotPasswordRequest extends FormRequest
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
            'input' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL) && !preg_match('/^\d+$/', $value)) {
                        $fail('Input phải là email hoặc số điện thoại hợp lệ.');
                    }

                    if (!DB::table('mdl_user')
                        ->where('email', $value)
                        ->orWhere('phone1', $value)
                        ->exists()
                    ) {
                        $fail('Email hoặc số điện thoại không tồn tại trong hệ thống.');
                    }
                },
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'input.required' => 'Vui lòng nhập email hoặc số điện thoại.',
            'input.string' => 'Input phải là chuỗi ký tự.',
        ];
    }
}
