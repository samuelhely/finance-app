<?php

use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('tag_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tag::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Transaction::class)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tag_id', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('tag_transaction');
    }
};
