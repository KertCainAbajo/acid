<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FoodLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'food_name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::in(['acidic', 'oily', 'spicy', 'dairy', 'other'])],
            'time' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
