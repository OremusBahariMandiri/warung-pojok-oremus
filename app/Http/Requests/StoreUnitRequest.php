<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_name' => 'required|string|max:120|unique:units,unit_name',
            'type' => 'required|string|max:120',
            'short_name' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'unit_name.required' => 'Nama satuan wajib diisi.',
            'unit_name.max' => 'Nama satuan maksimal 120 karakter.',
            'unit_name.unique' => 'Nama satuan sudah terdaftar di sistem.',
            'type.required' => 'Tipe atau kategori satuan wajib diisi.',
            'type.max' => 'Tipe satuan maksimal 120 karakter.',
            'short_name.required' => 'Singkatan satuan wajib diisi.',
            'short_name.max' => 'Singkatan satuan maksimal 50 karakter.',
        ];
    }
}
