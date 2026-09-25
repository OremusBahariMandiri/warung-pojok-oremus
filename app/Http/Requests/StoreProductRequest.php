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
        $configs = $this->configurations ?? $this->components;
        if ($configs && is_array($configs)) {
            $filtered = array_values(array_filter($configs, function ($comp) {
                return !empty($comp['selling_unit_id']);
            }));
            $this->merge(['configurations' => $filtered]);
        }
    }

    public function rules(): array
    {
        return [
            'prod_name'                          => 'required|string|max:255',
            'prod_code'                          => 'nullable|string|max:50|unique:products,prod_code',
            'slug'                               => 'nullable|string|max:255|unique:products,slug',
            'unit_id'                            => 'required|integer|exists:units,id',
            'hpp_method'                         => 'required|in:MANUAL,CALCULATED',
            'initial_stock'                      => 'nullable|integer|min:0',
            'current_stock'                      => 'nullable|integer|min:0',
            'min_stock'                          => 'required|integer|min:0',
            'description'                        => 'nullable|string',
            'thumbnail'                          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'configurations'                     => 'nullable|array',
            'configurations.*.selling_unit_id'   => 'required_with:configurations|exists:units,id',
            'configurations.*.hpp_id'            => 'nullable|exists:hpp,id',
            'configurations.*.selling_price'     => 'required_with:configurations|numeric|min:0',
            'configurations.*.current_hpp'       => 'required_with:configurations|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'prod_name.required'                      => 'Nama produk wajib diisi.',
            'prod_code.unique'                        => 'Kode produk sudah digunakan.',
            'slug.unique'                             => 'Slug sudah digunakan, gunakan slug lain.',
            'unit_id.required'                        => 'Satuan dasar produk wajib dipilih.',
            'unit_id.exists'                          => 'Satuan dasar produk yang dipilih tidak valid.',
            'hpp_method.required'                     => 'Metode HPP wajib dipilih.',
            'hpp_method.in'                           => 'Metode HPP harus MANUAL atau CALCULATED.',
            'min_stock.required'                      => 'Batas minimum stok wajib diisi.',
            'thumbnail.image'                         => 'File thumbnail harus berupa gambar.',
            'thumbnail.max'                           => 'Ukuran thumbnail maksimal 2MB.',
            'configurations.*.selling_unit_id.required_with' => 'Satuan jual wajib dipilih pada konfigurasi penjualan.',
            'configurations.*.selling_unit_id.exists'        => 'Satuan jual yang dipilih tidak valid.',
            'configurations.*.selling_price.required_with'    => 'Harga jual wajib diisi pada konfigurasi penjualan.',
            'configurations.*.current_hpp.required_with'      => 'Current HPP wajib diisi pada konfigurasi penjualan.',
        ];
    }
}