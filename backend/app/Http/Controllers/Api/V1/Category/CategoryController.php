<?php

namespace App\Http\Controllers\Api\V1\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $request->user()->categories()->latest()->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = $request->user()->categories()->create($data);

        return response()->json($category, 201);
    }

    public function show(Request $request, string $id)
    {
        $category = $request->user()->categories()->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = $request->user()->categories()->findOrFail($id);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Request $request, string $id)
    {
        $category = $request->user()->categories()->findOrFail($id);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
