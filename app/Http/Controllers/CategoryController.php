<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('articles')->get();
        return response()->json($categories);
    }

    private function checkAdminAccess(): void
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $existingCategory = Category::where('name', $request->name)->first();
        if ($existingCategory) {
            return response()->json([
                'message' => 'Category name already exists',
                'error' => 'A category with the name "' . $request->name . '" already exists. Please choose a different name.',
                'existing_category' => [
                    'id' => $existingCategory->id,
                    'name' => $existingCategory->name
                ]
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json($category, 201);
    }

    public function show($id): JsonResponse
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'error' => 'The category with ID ' . $id . ' does not exist'
            ], 404);
        }

        $category->load('articles');
        return response()->json($category);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $this->checkAdminAccess();

        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'error' => 'The category with ID ' . $id . ' does not exist'
            ], 404);
        }

        if ($request->has('name') && $request->name !== $category->name) {
            $existingCategory = Category::where('name', $request->name)
                ->where('id', '!=', $category->id)
                ->first();
                
            if ($existingCategory) {
                return response()->json([
                    'message' => 'Category name already exists',
                    'error' => 'A category with the name "' . $request->name . '" already exists. Please choose a different name.',
                    'existing_category' => [
                        'id' => $existingCategory->id,
                        'name' => $existingCategory->name
                    ]
                ], 422);
            }
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $request->name ?? $category->name,
            'slug' => \Str::slug($request->name ?? $category->name),
            'description' => $request->description,
        ]);

        return response()->json($category);
    }

    public function destroy($id): JsonResponse
    {
        $this->checkAdminAccess();

        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'error' => 'The category with ID ' . $id . ' does not exist'
            ], 404);
        }

        if ($category->articles()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category. It has associated articles.',
                'articles_count' => $category->articles()->count()
            ], 422);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
} 

