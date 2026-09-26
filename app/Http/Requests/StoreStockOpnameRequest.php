<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opname_date'              => 'required|date',
            'status_opname'            => 'required|in:DRAFT,REVIEW,AWAITING CONFIRMATION,COMPLETED,CONFIRMED',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|integer|exists:products,id',
            'items.*.physical_stock'   => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'opname_date.required'           => 'Tanggal opname wajib diisi.',
            'status_opname.required'         => 'Status opname wajib dipilih.',
            'status_opname.in'               => 'Status opname tidak valid.',
            'items.required'                  => 'Minimal satu produk harus diisi untuk stock opname.',
            'items.min'                       => 'Minimal satu produk harus diisi untuk stock opname.',
            'items.*.product_id.required'     => 'Produk wajib dipilih.',
            'items.*.product_id.exists'       => 'Produk yang dipilih tidak valid.',
            'items.*.physical_stock.required' => 'Jumlah stok fisik wajib diisi.',
            'items.*.physical_stock.integer'  => 'Jumlah stok fisik harus berupa angka bulat.',
            'items.*.physical_stock.min'      => 'Jumlah stok fisik tidak boleh kurang dari 0.',
        ];
    }
}
