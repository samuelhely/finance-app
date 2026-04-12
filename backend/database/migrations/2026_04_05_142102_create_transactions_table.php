<?php

use App\Enums\TransactionPaymentMethod;
use App\Enums\TransactionType;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Card::class)->nullable()->constrained()->cascadeOnDelete();

            $table->enum('type', TransactionType::cases());
            $table->enum('payment_method', TransactionPaymentMethod::cases());
            $table->string('description');
            $table->string('notes')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('date');

            $table->enum('ocurrence_status', TransactionStatus::cases());
            $table->enum('source_type', TransactionSourceType::cases());
            $table->timestamps();

            // TODO: $table->foreignIdFor('installment_plan_id');
            // TODO: $table->foreignIdFor('recurrence_rule_id');
            // TODO: $table->unsignedTinyInteger('installment_number');
            // TODO: $table->unsignedTinyInteger('installment_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
