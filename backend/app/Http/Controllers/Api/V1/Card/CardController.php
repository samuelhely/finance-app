<?php

namespace App\Http\Controllers\Api\V1\Card;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $cards = $request->user()->cards()->latest()->get();

        return response()->json($cards);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'last_four_digits' => 'nullable|string|max:4',
            'limit' => 'nullable|numeric|min:0',
            'closing_day' => 'nullable|date',
            'due_day' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $card = $request->user()->cards()->create($data);

        return response()->json($card, 201);
    }

    public function show(Request $request, string $id)
    {
        $card = $request->user()->cards()->findOrFail($id);

        return response()->json($card);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'brand' => 'nullable|string|max:255',
            'last_four_digits' => 'nullable|string|max:4',
            'limit' => 'nullable|numeric|min:0',
            'closing_day' => 'nullable|date',
            'due_day' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $card = $request->user()->cards()->findOrFail($id);

        $card->update($data);

        return response()->json($card);
    }

    public function destroy(Request $request, string $id)
    {
        $card = $request->user()->cards()->findOrFail($id);

        $card->delete();

        return response()->json([
            'message' => 'Card deleted successfully'
        ]);
    }
}
