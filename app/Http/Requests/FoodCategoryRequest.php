<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FoodCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('foodCategory')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('food_categories', 'name')->ignore($categoryId)],
            'type' => ['required', 'string', Rule::in(['safe', 'avoid'])],
        ];
    }
}
