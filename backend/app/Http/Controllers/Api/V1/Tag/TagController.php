<?php

namespace App\Http\Controllers\Api\V1\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->tags()->latest()->get();

        return response()->json($tags);
    }

    public function store(StoreTagRequest $request)
    {
        $tag = $request->user()->tags()->create($request->validated());

        return response()->json($tag, 201);
    }

    public function show(Request $request, string $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);

        return response()->json($tag);
    }

    public function update(UpdateTagRequest $request, string $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);

        $tag->update($request->validated());

        return response()->json($tag);
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
