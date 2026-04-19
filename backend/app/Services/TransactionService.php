<?php

namespace App\Services;

use App\Enums\TransactionPaymentMethod;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function create($user, $data): Transaction
    {
        return DB::transaction(function() use ($user, $data) {
            $account = $user->accounts()->findOrFail($data['account_id']);

            // validate category
            abort_if(!isset($data['category_id']) && !isset($data['category_name']),
                422, 'Please select an existing category or create a new one'
            );

            $data['category_id'] = $this->resolveCategory($user, $data);

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
            $tagIds = $this->resolveTags($user, $data['tags'] ?? []);

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
                'occurrence_status' => $data['occurrence_status'],
                'source_type' => $data['source_type'],
            ]);

            // validate transaction type
            $account = $user->accounts()->findOrFail($data['account_id']);
            $this->applyBalance($account, $transaction->type, $transaction->amount);

            $card = $user->cards()->findOrFail($transaction->card_id);
            $this->applyCardLimit($card, $transaction->payment_method, $transaction->amount);

            if (!empty($tagIds)) {
                 $transaction->tags()->sync($tagIds);
            }

            return $transaction->load(['account', 'card', 'tags', 'category']);
        });
    }

    public function update($user, $transaction, $data): Transaction
    {
        return DB::transaction(function () use ($user, $transaction, $data) {
            $oldAccount = $transaction->account;
            $oldAmount = $transaction->amount;
            $oldType = $transaction->type;
            $oldCard = $transaction->card;
            $oldPaymentMethod = $transaction->payment_method;

            // validate category
            abort_if(!isset($data['category_id']) && !isset($data['category_name']),
                422, 'Please select an existing category or create a new one'
            );

            $data['category_id'] = $this->resolveCategory($user, $data);

            if ($data['payment_method'] === TransactionPaymentMethod::Credit->value || $data['payment_method'] === TransactionPaymentMethod::Debit->value) {
                abort_if(empty($data['card_id']),
                    422, 'A card is required for card payment method'
                );

                $card = $user->cards()->findOrFail($data['card_id']);
                $data['card_id'] = $card->id;
            } else {
                $data['card_id'] = null;
            }

            $tagIds = $this->resolveTags($user, $data['tags'] ?? []);

            $this->reverseBalance($oldAccount, $oldType, $oldAmount);

            $this->reverseCardLimit($oldCard, $oldPaymentMethod, $oldAmount);

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
                'occurrence_status' => $data['occurrence_status'] ?? $transaction->occurrence_status,
                'source_type' => $data['source_type'] ?? $transaction->source_type,
            ]);

            if (!empty($tagIds)) {
                $transaction->tags()->sync($tagIds);
            }

            $newAccount = $transaction->account()->first();
            $this->applyBalance($newAccount, $transaction->type, $transaction->amount);

            $card = $transaction->card()->first();
            $this->applyCardLimit($card, $transaction->payment_method, $transaction->amount);

            return $transaction->load(['account', 'category', 'card', 'tags']);
        });
    }

    public function delete($transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $account = $transaction->account()->first();

            $this->reverseBalance($account, $transaction->type, $transaction->amount);

            $card = $transaction->card()->first();
            $this->reverseCardLimit($card, $transaction->payment_method, $transaction->amount);

            $transaction->tags()->detach();

            $transaction->delete();
        });
    }

    private function resolveCategory($user, $data)
    {
        if (!empty($data['category_id'])) {
            $category = $user->categories()->findOrFail($data['category_id']);
            return $category->id;
        } else if (!empty($data['category_name'])) {
            $category = $user->categories()->where('name', $data['category_name'])->first();
            if ($category) {
                return $category->id;
            } else {
                $category = $user->categories()->create(['name' => $data['category_name']]);
                return $category->id;
            }
        }
    }

    private function resolveTags($user, array $tags): array
    {
        if (!empty($tags)) {
            $normalizedTagNames = collect($tags)
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

            return $tagIds;
        }

        return [];
    }

    private function applyBalance($account, TransactionType $type, $amount): void
    {
        if ($type === TransactionType::Income) {
            $account->increment('balance', $amount);
        } else if ($type === TransactionType::Expense) {
            $account->decrement('balance', $amount);
        }
    }

    private function reverseBalance($account, TransactionType $type, $amount): void
    {
        if ($type === TransactionType::Income) {
            $account->decrement('balance', $amount);
        } else if ($type === TransactionType::Expense) {
            $account->increment('balance', $amount);
        }
    }

    private function applyCardLimit($card, TransactionPaymentMethod $payment_method, $amount): void
    {
        if ($card && $payment_method === TransactionPaymentMethod::Credit) {
            $card->decrement('limit', $amount);
        }
    }

    private function reverseCardLimit($card, TransactionPaymentMethod $payment_method, $amount): void
    {
        if ($card && $payment_method === TransactionPaymentMethod::Credit) {
            $card->increment('limit', $amount);
        }
    }
}
