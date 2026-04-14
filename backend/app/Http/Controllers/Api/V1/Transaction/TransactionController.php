<?php

namespace App\Http\Controllers\Api\V1\Transaction;

use App\Enums\TransactionPaymentMethod;
use App\Enums\TransactionSourceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    )
    {}

    public function index(Request $request)
    {
        $transactions = $request->user()
                                ->transactions()
                                ->with(['account', 'category', 'tags', 'card'])
                                ->latest()
                                ->get();

        return response()->json($transactions);
    }

    public function store(StoreTransactionRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        $transaction = $this->transactionService->create($user, $data);

        return response()->json($transaction);
    }

    public function show(Request $request, string $id)
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(UpdateTransactionRequest $request, string $id)
    {
        $data = $request->validated();

        $user = $request->user();

        $transaction = $user->transactions()->findOrFail($id);

        $this->transactionService->update($user, $transaction, $data);

        return response()->json($transaction);
    }

    public function destroy(Request $request, string $id)
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        $this->transactionService->delete($transaction);

        return response()->json([
            'message' => 'Transaction deleted successfully'
        ]);
    }
}
