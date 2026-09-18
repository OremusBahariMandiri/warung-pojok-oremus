<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit') ? ($this->route('unit') instanceof \App\Models\Unit ? $this->route('unit')->id : $this->route('unit')) : null;

        return [
            'unit_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('units', 'unit_name')->ignore($unitId),
            ],
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
