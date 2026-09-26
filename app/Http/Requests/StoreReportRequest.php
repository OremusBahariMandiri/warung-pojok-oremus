<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_date'            => 'required|date',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer|exists:products,id',
            'items.*.selling_unit_id' => 'required|integer|exists:units,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.selling_price'  => 'nullable|numeric|min:0',
            'items.*.hpp'            => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'report_date.required'            => 'Tanggal laporan penjualan wajib diisi.',
            'items.required'                   => 'Minimal satu produk harus dipilih untuk laporan penjualan.',
            'items.min'                        => 'Minimal satu produk harus dipilih untuk laporan penjualan.',
            'items.*.product_id.required'      => 'Produk wajib dipilih.',
            'items.*.product_id.exists'        => 'Produk yang dipilih tidak valid.',
            'items.*.selling_unit_id.required' => 'Satuan jual wajib dipilih.',
            'items.*.selling_unit_id.exists'   => 'Satuan jual yang dipilih tidak valid.',
            'items.*.quantity.required'        => 'Jumlah penjualan wajib diisi.',
            'items.*.quantity.min'             => 'Jumlah penjualan minimal 1.',
            'items.*.selling_price.numeric'    => 'Harga jual harus berupa angka.',
            'items.*.hpp.numeric'              => 'Harga pokok / HPP harus berupa angka.',
        ];
    }
}