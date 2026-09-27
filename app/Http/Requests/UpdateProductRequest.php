<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product') instanceof \App\Models\Products
            ? $this->route('product')->id
            : $this->route('product');

        return [
            'prod_name'                          => 'required|string|max:255',
            'prod_code'                          => ['nullable', 'string', 'max:50', Rule::unique('products', 'prod_code')->ignore($productId)],
            'slug'                               => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'unit_id'                            => 'required|integer|exists:units,id',
            'hpp_method'                         => 'nullable|in:MANUAL,CALCULATED',
            'current_stock'                      => 'nullable|integer|min:0',
            'min_stock'                          => 'required|integer|min:0',
            'unit_price'                         => 'nullable|numeric|min:0',
            'selling_price'                      => 'required|numeric|min:0',
            'current_hpp'                        => 'nullable|numeric|min:0',
            'description'                        => 'nullable|string',
            'thumbnail'                          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'components'                         => 'nullable|array',
            'components.*.hpp_id'                => 'nullable|exists:hpp,id',
            'components.*.cost'                  => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'prod_name.required'     => 'Nama produk wajib diisi.',
            'prod_code.unique'       => 'Kode produk sudah digunakan produk lain.',
            'slug.unique'            => 'Slug sudah digunakan produk lain.',
            'unit_id.required'       => 'Satuan dasar produk wajib dipilih.',
            'unit_id.exists'         => 'Satuan dasar produk yang dipilih tidak valid.',
            'hpp_method.required'    => 'Metode HPP wajib dipilih.',
            'hpp_method.in'          => 'Metode HPP harus MANUAL atau CALCULATED.',
            'min_stock.required'     => 'Batas minimum stok wajib diisi.',
            'selling_price.required' => 'Harga jual konsumen wajib diisi.',
            'selling_price.numeric'  => 'Harga jual konsumen harus berupa angka.',
            'thumbnail.image'        => 'File thumbnail harus berupa gambar.',
            'thumbnail.max'          => 'Ukuran thumbnail maksimal 2MB.',
        ];
    }
}