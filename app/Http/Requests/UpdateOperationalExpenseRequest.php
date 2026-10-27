<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperationalExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation (clean currency formats if any).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('price')) {
            $cleanedPrice = preg_replace('/[^0-9]/', '', (string)$this->price);
            $this->merge([
                'price' => $cleanedPrice !== '' ? (float)$cleanedPrice : null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'quantity'    => 'required|integer|min:1',
            'price'       => 'required|numeric|min:0',
            'date'        => 'required|date',
            'notes'       => 'nullable|string|max:1000',
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name'     => 'Nama Pengeluaran',
            'quantity' => 'Jumlah',
            'price'    => 'Harga',
            'date'     => 'Tanggal',
            'notes'    => 'Keterangan',
        ];
    }
}
