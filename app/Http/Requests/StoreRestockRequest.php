<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => 'required|string|max:180',
            'restock_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.required' => 'Nama supplier / sumber restock wajib diisi.',
            'items.required' => 'Minimal satu produk harus dipilih untuk direstock.',
            'items.min' => 'Minimal satu produk harus dipilih untuk direstock.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak valid.',
            'items.*.quantity.required' => 'Jumlah restock wajib diisi.',
            'items.*.quantity.min' => 'Jumlah restock minimal 1.',
            'items.*.unit_price.numeric' => 'Harga beli/bahan harus berupa angka.',
            'items.*.unit_price.min' => 'Harga beli/bahan tidak boleh kurang dari 0.',
        ];
    }
}
