<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'threshold' => 'required|integer|min:0',
            'expired_at' => 'nullable|date',
            'description' => 'nullable|string',
            'supplier_ids' => 'nullable|array',
            'supplier_ids.*' => 'exists:suppliers,id',
            'supplier_prices' => 'nullable|array',
            'supplier_prices.*' => 'nullable|numeric|min:0',
        ];
    }
}
