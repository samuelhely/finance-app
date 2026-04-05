<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()->accounts()->latest()->get();

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $account = $request->user()->accounts()->create($data);

        return response()->json($account, 201);
    }

    public function show(Request $request, string $id)
    {
        $account = $request->user()->accounts()->findOrFail($id);

        return response()->json($account);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $account = $request->user()->accounts()->findOrFail($id);

        $account->update($data);

        return response()->json($account);
    }

    public function destroy(Request $request, string $id)
    {
        $account = $request->user()->accounts()->findOrFail($id);

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}
