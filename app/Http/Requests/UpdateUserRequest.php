<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? ($this->route('user') instanceof \App\Models\User ? $this->route('user')->id : $this->route('user')) : null;

        return [
            'nrk' => 'required|string|max:50',
            'employee_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'nullable|string|min:6',
            'is_admin' => 'nullable|boolean',
            'accesses' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'nrk.required' => 'NRK wajib diisi.',
            'employee_name.required' => 'Nama karyawan wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 6 karakter.',
        ];
    }
}
