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
                return !empty($comp['hpp_id']) && !empty($comp['selling_unit_id']);
            }));
            $this->merge(['components' => $filtered]);
        }
    }

    public function rules(): array
    {
        return [
            'prod_name'                       => 'required|string|max:255',
            'prod_code'                       => 'nullable|string|max:50|unique:products,prod_code',
            'slug'                            => 'nullable|string|max:255|unique:products,slug',
            'unit_id'                         => 'required|integer|exists:units,id',
            'unit_price'                      => 'required|numeric|min:0',
            'hpp_method'                      => 'required|in:MANUAL,CALCULATED',
            'current_hpp'                     => 'nullable|numeric|min:0',
            'initial_stock'                   => 'nullable|integer|min:0',
            'current_stock'                   => 'nullable|integer|min:0',
            'min_stock'                       => 'required|integer|min:0',
            'description'                     => 'nullable|string',
            'thumbnail'                       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'components'                      => 'nullable|array',
            'components.*.hpp_id'             => 'required_with:components|exists:hpp,id',
            'components.*.selling_unit_id'    => 'required_with:components|exists:units,id',
            'components.*.selling_price'      => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'prod_name.required'                    => 'Nama produk wajib diisi.',
            'prod_code.unique'                      => 'Kode produk sudah digunakan.',
            'slug.unique'                           => 'Slug sudah digunakan, gunakan slug lain.',
            'unit_id.required'                      => 'Satuan produk wajib dipilih.',
            'unit_id.exists'                        => 'Satuan produk yang dipilih tidak valid.',
            'unit_price.required'                   => 'Harga bahan utama wajib diisi.',
            'unit_price.numeric'                    => 'Harga bahan utama harus berupa angka.',
            'hpp_method.required'                   => 'Metode HPP wajib dipilih.',
            'hpp_method.in'                         => 'Metode HPP harus MANUAL atau CALCULATED.',
            'min_stock.required'                    => 'Batas minimum stok wajib diisi.',
            'thumbnail.image'                       => 'File thumbnail harus berupa gambar.',
            'thumbnail.max'                         => 'Ukuran thumbnail maksimal 2MB.',
            'components.*.hpp_id.required_with'     => 'Komponen HPP wajib dipilih.',
            'components.*.selling_unit_id.required_with' => 'Satuan jual komponen HPP wajib dipilih.',
            'components.*.selling_unit_id.exists'   => 'Satuan jual komponen HPP tidak valid.',
        ];
    }
}