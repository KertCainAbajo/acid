<?php

namespace App\Http\Requests;

use App\Models\FoodLog;
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
            'category' => ['required', 'string', Rule::in(FoodLog::CATEGORIES)],
            'time' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
