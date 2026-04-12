<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'card_id',
        'type',
        'payment_method',
        'description',
        'notes',
        'amount',
        'date',
        'ocurrence_status',
        'source_type',
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
