<?php

namespace App\Http\Controllers\Api\V1\Tag;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->tags()->latest()->get();

        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag = $request->user()->tags()->create($data);

        return response()->json($tag, 201);
    }

    public function show(Request $request, string $id)
    {
        $tag = $request->user()->tags()->findOrFail($id);

        return response()->json($tag);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag = $request->user()->tags()->findOrFail($id);

        $tag->update($data);

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
