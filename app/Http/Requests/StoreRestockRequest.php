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
            'invoice_number'          => 'nullable|string|max:100',
            'supplier_name'           => 'required|string|max:180',
            'restock_date'            => 'required|date',
            'status_restock'          => 'required|in:DRAFT,CONFIRMED',
            'discount'                => 'nullable|numeric|min:0',
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer|exists:products,id',
            'items.*.restock_unit_id' => 'required|integer|exists:units,id',
            'items.*.quantity'        => 'required|integer|min:1',
            'items.*.purchase_price'  => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.required'           => 'Nama supplier wajib diisi.',
            'restock_date.required'            => 'Tanggal restock wajib diisi.',
            'status_restock.required'          => 'Status transaksi wajib dipilih (DRAFT atau CONFIRMED).',
            'status_restock.in'                => 'Status transaksi harus DRAFT atau CONFIRMED.',
            'items.required'                   => 'Minimal satu produk harus dipilih untuk direstock.',
            'items.min'                        => 'Minimal satu produk harus dipilih untuk direstock.',
            'items.*.product_id.required'      => 'Produk wajib dipilih.',
            'items.*.product_id.exists'        => 'Produk yang dipilih tidak valid.',
            'items.*.restock_unit_id.required' => 'Satuan pembelian wajib dipilih.',
            'items.*.restock_unit_id.exists'   => 'Satuan pembelian yang dipilih tidak valid.',
            'items.*.quantity.required'        => 'Jumlah restock wajib diisi.',
            'items.*.quantity.min'             => 'Jumlah restock minimal 1.',
            'items.*.purchase_price.required'  => 'Harga beli wajib diisi.',
            'items.*.purchase_price.numeric'   => 'Harga beli harus berupa angka.',
            'items.*.purchase_price.min'       => 'Harga beli tidak boleh kurang dari 0.',
        ];
    }
}
