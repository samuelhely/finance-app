<?php

namespace App\Http\Controllers\Api\V1\Transaction;

use App\Enums\TransactionPaymentMethod;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = $request->user()
                                ->transactions()
                                ->with(['account', 'category', 'tags', 'card'])
                                ->latest()
                                ->get();

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'category_name' => 'nullable|string|max:255',
            'card_id' => 'nullable|exists:cards,id',
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
        ]);

        $user = $request->user();
        return DB::transaction(function() use ($user, $data) {
            $account = $user->accounts()->findOrFail($data['account_id']);

            // validate category
            abort_if(!isset($data['category_id']) && !isset($data['category_name']),
                422, 'Please select an existing category or create a new one'
            );

            if (!empty($data['category_id'])) {
                $category = $user->categories()->findOrFail($data['category_id']);
                $data['category_id'] = $category->id;
            } else if (!empty($data['category_name'])) {
                $found = $user->categories()->where('name', $data['category_name'])->first();
                if ($found) {
                    $data['category_id'] = $found->id;
                } else {
                    $newCategory = $user->categories()->create(['name' => $data['category_name']]);
                    $data['category_id'] = $newCategory->id;
                }
            }

            // validate payment method
            // validate card
            if ($data['payment_method'] === TransactionPaymentMethod::Credit->value || $data['payment_method'] === TransactionPaymentMethod::Debit->value) {
                abort_if(empty($data['card_id']),
                    422, 'A card is required for card payment method'
                );

                $card = $user->cards()->findOrFail($data['card_id']);
                $data['card_id'] = $card->id;
            } else {
                $data['card_id'] = null;
            }

            // validate tags
            $tagIds = [];
            if (!empty($data['tags'])) {
                $normalizedTagNames = collect($data['tags'])
                    ->filter(fn ($name) => filled($name))
                    ->map(fn ($name) => mb_strtolower(trim($name)))
                    ->filter(fn ($name) => $name !== '')
                    ->unique()
                    ->values();

                if ($normalizedTagNames->isEmpty()) {
                    return [];
                }

                $existingTags = $user->tags()
                                     ->whereIn('name', $normalizedTagNames->toArray())
                                     ->get()
                                     ->keyBy('name');

                foreach ($normalizedTagNames as $name) {
                    if (isset($existingTags[$name])) {
                        $tagIds[] = $existingTags[$name]->id;
                        continue;
                    }

                    $tag = $user->tags()->create(['name' => $name]);
                    $tagIds[] = $tag->id;
                }
            }

            // add transaction
            $transaction = $user->transactions()->create([
                'account_id' => $account->id,
                'category_id' => $data['category_id'] ?? null,
                'card_id' => $data['card_id'] ?? null,
                'type' => $data['type'],
                'payment_method' => $data['payment_method'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'amount' => $data['amount'],
                'date' => $data['date'],
                'ocurrence_status' => $data['ocurrence_status'],
                'source_type' => $data['source_type'],
            ]);

            // validate transaction type
            $account = $user->accounts()->findOrFail($data['account_id']);
            if ($data['type'] === TransactionType::Expense->value) {
                $account->decrement('balance', $transaction->amount);
            } else if ($data['type'] === TransactionType::Income->value) {
                $account->increment('balance', $transaction->amount);
            }

            $card = $user->cards()->findOrFail($transaction->card_id);
            if ($data['payment_method'] === TransactionPaymentMethod::Credit->value) {
                $card->decrement('limit', $transaction->amount);
            }

            if (!empty($tagIds)) {
                 $transaction->tags()->sync($tagIds);
            }

            return response()->json(
                $transaction->load(['account', 'card', 'tags', 'category'])
            );
        });
    }

    public function show(Request $request, string $id)
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'account_id' => 'required|exists:accounts,id',
            'category_id' => 'nullable|exists:categories,id',
            'category_name' => 'nullable|string|max:255',
            'card_id' => 'nullable|exists:cards,id',
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
        ]);

        $user = $request->user();
        $transaction = $user->transactions()->findOrFail($id);

        return DB::transaction(function () use ($user, $transaction, $data) {
            $oldAccount = $transaction->account;
            $oldAmount = $transaction->amount;
            $oldType = $transaction->type;
            $oldCard = $transaction->card;

            // validate category
            abort_if(!isset($data['category_id']) && !isset($data['category_name']),
                422, 'Please select an existing category or create a new one'
            );

            if (array_key_exists('category_id', $data) && !empty($data['category_id'])) {
                $category = $user->categories()->findOrFail($data['category_id']);
                $data['category_id'] = $category->id;
            } else if (array_key_exists('category_name', $data) && !empty($data['category_name'])) {
                $found = $user->categories()->where('name', $data['category_name'])->first();
                if ($found) {
                    $data['category_id'] = $found->id;
                } else {
                    $category = $user->categories()->create(['name' => $data['category_name']]);
                    $data['category_id'] = $category->id;
                }
            }

            // validate payment method and card
            if ($data['payment_method'] === TransactionPaymentMethod::Credit->value || $data['payment_method'] === TransactionPaymentMethod::Debit->value) {
                abort_if(empty($data['card_id']),
                    422, 'A card is required for card payment method'
                );

                $card = $user->cards()->findOrFail($data['card_id']);
                $data['card_id'] = $card->id;
            } else {
                $data['card_id'] = null;
            }

            // validate tags
            $tagIds = null;
            if (!empty($data['tags'])) {
                $normalizedTagNames = collect($data['tags'])
                    ->filter(fn ($name) => filled($name))
                    ->map(fn ($name) => mb_strtolower(trim($name)))
                    ->filter(fn ($name) => $name !== '')
                    ->unique()
                    ->values();

                if ($normalizedTagNames->isEmpty()) {
                    return [];
                }

                $existingTags = $user->tags()
                                     ->whereIn('name', $normalizedTagNames->toArray())
                                     ->get()
                                     ->keyBy('name');

                foreach ($normalizedTagNames as $name) {
                    if (isset($existingTags[$name])) {
                        $tagIds[] = $existingTags[$name]->id;
                        continue;
                    }

                    $tag = $user->tags()->create(['name' => $name]);
                    $tagIds[] = $tag->id;
                }
            }

            // reverse old balance
            if ($oldType === TransactionType::Income->value) {
                $oldAccount->decrement('balance', $oldAmount);
            } else if ($oldType === TransactionType::Expense->value) {
                $oldAccount->increment('balance', $oldAmount);
            }

            if ($data['payment_method'] === TransactionPaymentMethod::Credit->value) {
                $oldCard->increment('limit', $oldAmount);
            }

            $transaction->update([
                'account_id' => $transaction->account_id,
                'category_id' => $data['category_id'] ?? $transaction->account_id,
                'card_id' => $data['card_id'] ?? null,
                'type' => $data['type'] ?? $transaction->type,
                'payment_method' => $data['payment_method'] ?? $transaction->payment_method,
                'description' => $data['description'] ?? $transaction->description,
                'notes' => $data['notes'] ?? null,
                'amount' => $data['amount'] ?? $transaction->amount,
                'date' => $data['date'] ?? $transaction->date,
                'ocurrence_status' => $data['ocurrence_status'] ?? $transaction->ocurrence_status,
                'source_type' => $data['source_type'] ?? $transaction->source_type,
            ]);

            if (!empty($tagIds)) {
                $transaction->tags()->sync($tagIds);
            }

            $newAccount = $transaction->account()->first();
            if ($transaction->type === TransactionType::Income->value) {
                $newAccount->increment('balance', $transaction->amount);
            } else if ($transaction->type === TransactionType::Expense->value) {
                $newAccount->decrement('balance', $transaction->amount);
            }

            if ($data['payment_method'] === TransactionPaymentMethod::Credit->value) {
                $card = $transaction->card()->first();
                $card->decrement('limit', $transaction->amount);
            }

            return response()->json(
                $transaction->load(['account', 'category', 'card', 'tags'])
            );
        });
    }

    public function destroy(Request $request, string $id)
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        return DB::transaction(function () use ($transaction) {
            $account = $transaction->account()->first();

            if ($transaction->type === TransactionType::Income->value) {
                $account->decrement('balance', $transaction->amount);
            } else if ($transaction->type === TransactionType::Expense->value) {
                $account->increment('balance', $transaction->amount);
            }

            $transaction->tags()->detach();

            $transaction->delete();

            return response()->json([
                'message' => 'Transaction deleted successfully'
            ]);
        });
    }
}
