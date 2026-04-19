<?php

namespace App\Http\Controllers\Api\V1\Card;

use App\Http\Controllers\Controller;
use App\Http\Requests\Card\StoreCardRequest;
use App\Http\Requests\Card\UpdateCardRequest;
use App\Http\Resources\CardResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $cards = $request->user()->cards()->latest()->get();

        return CardResource::collection($cards);
    }

    public function store(StoreCardRequest $request)
    {
        $card = $request->user()->cards()->create($request->validated());

        return new CardResource($card)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $card = $request->user()->cards()->findOrFail($id);

        return new CardResource($card);
    }

    public function update(UpdateCardRequest $request, string $id)
    {
        $card = $request->user()->cards()->findOrFail($id);

        $card->update($request->validated());

        return new CardResource($card);
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
