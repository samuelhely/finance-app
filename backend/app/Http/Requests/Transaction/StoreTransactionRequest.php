<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionPaymentMethod;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'sometimes|exists:categories,id',
            'category_name' => 'sometimes|string|max:255',
            'card_id' => 'sometimes|exists:cards,id',
            'type' => ['required', Rule::enum(TransactionType::class)],
            'payment_method' => ['required', Rule::enum(TransactionPaymentMethod::class)],
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'ocurrence_status' => ['required', Rule::enum(TransactionStatus::class)],
            'source_type' => ['required', Rule::enum(TransactionSourceType::class)],
        ];
    }
}
