<?php

namespace App\Http\Requests\Card;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'last_four_digits' => 'nullable|string|max:4',
            'limit' => 'nullable|numeric|min:0',
            'closing_day' => 'nullable|date',
            'due_day' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }
}
