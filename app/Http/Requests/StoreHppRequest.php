<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120|unique:hpp,name',
            'unit' => 'required|string|max:80',
            'unit_cost' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama komponen HPP wajib diisi.',
            'name.unique' => 'Nama komponen HPP sudah terdaftar.',
            'unit.required' => 'Satuan komponen wajib diisi.',
            'unit_cost.required' => 'Biaya per satuan wajib diisi.',
            'unit_cost.numeric' => 'Biaya harus berupa angka.',
            'unit_cost.min' => 'Biaya tidak boleh kurang dari 0.',
        ];
    }
}
