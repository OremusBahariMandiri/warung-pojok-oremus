<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bersihkan input sebelum validasi dijalankan
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('nrk')) {
            $this->merge([
                'nrk' => trim($this->nrk),
            ]);
        }
        $this->merge([
            'remember' => $this->boolean('remember'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nrk' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nrk.required' => 'NRK wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }
}