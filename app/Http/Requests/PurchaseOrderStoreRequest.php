<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_at' => 'nullable|date|after_or_equal:today',
            'notes'       => 'nullable|string|max:500',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.buy_price'  => 'required|numeric|min:0',
        ];
    }
}
