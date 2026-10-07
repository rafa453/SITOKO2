<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierReturnStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'reason'            => 'nullable|string|max:500',
            'items'             => 'required|array|min:1',
            'items.*.po_item_id'    => 'required|exists:purchase_order_items,id',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.qty_returned'  => 'required|integer|min:1',
            'items.*.buy_price'     => 'required|numeric|min:0',
        ];
    }
}
