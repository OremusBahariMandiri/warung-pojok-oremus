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
            'prod_name'                                  => 'required|string|max:255',
            'prod_code'                                  => ['nullable', 'string', 'max:50', Rule::unique('products', 'prod_code')->ignore($productId)],
            'slug'                                       => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'unit_price'                                 => 'nullable|numeric|min:0',
            'unit_id'                                    => 'nullable|integer|exists:units,id',
            'initial_stock'                              => 'nullable|integer|min:0',
            'current_stock'                              => 'nullable|integer|min:0',
            'min_stock'                                  => 'required|integer|min:0',
            'description'                                => 'nullable|string',
            'thumbnail'                                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_flexible_product' => 'nullable|boolean',

            'selling_configs'                            => 'nullable|array',
            'selling_configs.*.selling_unit_id'          => 'nullable|integer|exists:units,id',
            'selling_configs.*.hpp_method'               => 'nullable|in:MANUAL,CALCULATED',
            'selling_configs.*.selling_price'            => 'nullable|numeric|min:0',
            'selling_configs.*.current_hpp'              => 'nullable|numeric|min:0',
            'selling_configs.*.components'               => 'nullable|array',
            'selling_configs.*.components.*.hpp_id'      => 'nullable|exists:hpp,id',
            'selling_configs.*.components.*.cost'        => 'nullable|numeric|min:0',

            'configurations'                             => 'nullable|array',
            'configurations.*.selling_unit_id'           => 'nullable|integer|exists:units,id',
            'configurations.*.hpp_method'                => 'nullable|in:MANUAL,CALCULATED',
            'configurations.*.selling_price'             => 'nullable|numeric|min:0',
            'configurations.*.current_hpp'               => 'nullable|numeric|min:0',

            // Legacy fallback single fields
            'hpp_method'                                 => 'nullable|in:MANUAL,CALCULATED',
            'selling_price'                              => 'nullable|numeric|min:0',
            'current_hpp'                                => 'nullable|numeric|min:0',
            'components'                                 => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'prod_name.required'                      => 'Nama produk wajib diisi.',
            'prod_code.unique'                        => 'Kode produk sudah digunakan produk lain.',
            'slug.unique'                             => 'Slug sudah digunakan produk lain.',
            'min_stock.required'                      => 'Batas minimum stok wajib diisi.',
            'thumbnail.image'                         => 'File thumbnail harus berupa gambar.',
            'thumbnail.max'                           => 'Ukuran thumbnail maksimal 2MB.',
        ];
    }
}