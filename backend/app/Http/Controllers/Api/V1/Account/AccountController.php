<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $request->user()->accounts()->latest()->get();

        return AccountResource::collection($accounts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $account = $request->user()->accounts()->create($data);

        return new AccountResource($account)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $account = $request->user()->accounts()->findOrFail($id);

        return new AccountResource($account);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $account = $request->user()->accounts()->findOrFail($id);

        $account->update($data);

        return new AccountResource($account)->response();
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
