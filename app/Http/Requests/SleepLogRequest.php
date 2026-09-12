<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SleepLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sleep_hours' => ['required', 'numeric', 'between:0,24'],
            'sleep_quality' => ['required', 'integer', 'between:1,5'],
            'stress_level' => ['required', 'integer', 'between:1,10'],
            'logged_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
