<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SymptomLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symptom_type' => ['required', 'string', Rule::in(['heartburn', 'bloating', 'pain'])],
            'intensity' => ['required', 'integer', 'between:1,10'],
            'time' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
