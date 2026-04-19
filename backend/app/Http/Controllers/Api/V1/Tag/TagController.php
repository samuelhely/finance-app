<?php

namespace App\Http\Controllers\Api\V1\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->tags()->latest()->get();

        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request)
    {
        $tag = $request->user()->tags()->create($request->validated());

        return new TagResource($tag)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);

        return new TagResource($tag);
    }

    public function update(UpdateTagRequest $request, string $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);

        $tag->update($request->validated());

        return new TagResource($tag);
    }

    public function destroy(Request $request, string $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);

        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }
}
