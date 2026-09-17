<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('components') && is_array($this->components)) {
            $filtered = array_values(array_filter($this->components, function ($comp) {
                return !empty($comp['hpp_id']);
            }));
            $this->merge(['components' => $filtered]);
        }
    }

    public function rules(): array
    {
        return [
            'prod_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'satuan' => 'required|string|max:50',
            'selling_price' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'hpp_method' => 'required|in:manual,calculated',
            'current_hpp' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'components' => 'nullable|array',
            'components.*.hpp_id' => 'required_with:components|exists:hpp,id',
            'components.*.cost' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'prod_name.required' => 'Nama produk wajib diisi.',
            'satuan.required' => 'Satuan produk wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.numeric' => 'Harga jual harus berupa angka.',
            'unit_price.required' => 'Harga bahan utama wajib diisi.',
            'unit_price.numeric' => 'Harga bahan utama harus berupa angka.',
            'hpp_method.required' => 'Metode HPP wajib dipilih.',
            'hpp_method.in' => 'Metode HPP harus manual atau calculated.',
            'min_stock.required' => 'Batas minimum stok wajib diisi.',
            'thumbnail.image' => 'File thumbnail harus berupa gambar.',
            'thumbnail.max' => 'Ukuran thumbnail maksimal 2MB.',
        ];
    }
}
