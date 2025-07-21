<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    private function canAccessArticle(Article $article): bool
    {
        return Auth::user()->isAdmin() || $article->author_id === Auth::id();
    }

    private function canManageArticles(): bool
    {
        return Auth::user()->isAdmin() || Auth::user()->isAuthor();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Article::with(['author', 'categories']);

        if (Auth::user()->isAuthor()) {
            $query->where('author_id', Auth::id());
        }

        if ($request->has('status') && in_array($request->status, ['draft', 'published', 'archived'])) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_ids')) {
            $categoryIds = is_array($request->category_ids) ? $request->category_ids : [$request->category_ids];
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 10);
        $perPage = min(max($perPage, 1), 100);

        $articles = $query->paginate($perPage);

        $articles->appends($request->all());

        return response()->json([
            'data' => $articles->items(),
            'pagination' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
                'from' => $articles->firstItem(),
                'to' => $articles->lastItem(),
            ],
            'filters_applied' => $request->only([
                'status', 'category_ids', 'category_id', 'date_from', 'date_to', 'per_page'
            ])
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $categoryIds = $request->has('category_ids') ? (is_array($request->category_ids) ? $request->category_ids : [$request->category_ids]) : [];
        if (!empty($categoryIds)) {
            $existingIds = \App\Models\Category::whereIn('id', $categoryIds)->pluck('id')->toArray();
            $invalidCategoryIds = array_values(array_diff($categoryIds, $existingIds));
            if (!empty($invalidCategoryIds)) {
                return response()->json([
                    'message' => 'Category is not available or invalid.',
                    'invalid_category_ids' => $invalidCategoryIds
                ], 422);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'published_date' => 'nullable|date',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if (!Auth::user()->isAuthor() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Only authors and admins can create articles.'], 403);
        }

        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'status' => $request->status,
            'published_date' => $request->published_date,
            'author_id' => Auth::id(),
        ]);

        if ($request->has('category_ids')) {
            $article->categories()->attach($request->category_ids);
        }

        \App\Jobs\GenerateArticleSlugAndSummary::dispatch($article)->onConnection('sync');

        $article->load(['author', 'categories']);

        return response()->json($article, 201);
    }

    public function show($id): JsonResponse
    {
        $article = Article::find($id);
        
        if (!$article) {
            return response()->json([
                'message' => 'Article not found',
                'error' => 'The article with ID ' . $id . ' does not exist'
            ], 404);
        }

        if (Auth::user()->isAuthor() && $article->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only view your own articles.'], 403);
        }

        $article->load(['author', 'categories']);
        return response()->json($article);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $article = Article::find($id);
        
        if (!$article) {
            return response()->json([
                'message' => 'Article not found',
                'error' => 'The article with ID ' . $id . ' does not exist'
            ], 404);
        }

        if (Auth::user()->isAuthor() && $article->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only update your own articles.'], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,published,archived',
            'published_date' => 'nullable|date',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $article->update($request->only(['title', 'content', 'status', 'published_date']));

        if ($request->has('category_ids')) {
            $article->categories()->sync($request->category_ids);
        }

        \App\Jobs\GenerateArticleSlugAndSummary::dispatch($article)->onConnection('sync');

        $article->load(['author', 'categories']);

        return response()->json($article);
    }

    public function destroy($id): JsonResponse
    {
        $article = Article::find($id);
        
        if (!$article) {
            return response()->json([
                'message' => 'Article not found',
                'error' => 'The article with ID ' . $id . ' does not exist'
            ], 404);
        }

        if (Auth::user()->isAuthor() && $article->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only delete your own articles.'], 403);
        }

        $article->delete();
        return response()->json(['message' => 'Article deleted successfully']);
    }

} 
