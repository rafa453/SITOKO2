<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'shift'  => 'required|in:pagi,siang',
            'status' => 'required|in:active,inactive',
            'photo'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
