<?php

namespace App\Http\Controllers\Article;

use App\Http\Controllers\Controller;
use App\Models\Article\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * GET /api/admin/articles
     * Admin: List all articles with search/filter/pagination.
     */
    public function index(Request $request)
    {
        $query = Article::with(['author:id,name', 'category:id,name,slug']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('article_category_id', $request->category_id);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['title', 'status', 'published_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $articles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    /**
     * POST /api/admin/articles
     * Admin: Create a new article.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'article_category_id' => 'nullable|exists:article_categories,id',
            'excerpt' => 'nullable|string',
            'body' => 'required|string',
            'featured_image' => 'nullable|image|max:5120',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'status' => 'in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $article = new Article();
        $article->user_id = $request->user()->id;
        $article->title = $validated['title'];
        $article->slug = $slug;
        $article->article_category_id = $validated['article_category_id'] ?? null;
        $article->excerpt = $validated['excerpt'] ?? null;
        $article->body = $validated['body'];
        $article->meta_title = $validated['meta_title'] ?? null;
        $article->meta_description = $validated['meta_description'] ?? null;
        $article->status = $validated['status'] ?? 'draft';

        // Handle published_at
        if ($article->status === 'published' && empty($validated['published_at'])) {
            $article->published_at = now();
        } else {
            $article->published_at = $validated['published_at'] ?? null;
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $article->featured_image = $this->processAndStoreImage($request->file('featured_image'), 'articles/featured');
        }

        $article->save();
        $article->load(['author:id,name', 'category:id,name,slug']);

        return response()->json([
            'success' => true,
            'data' => $article,
            'message' => 'Artikel berhasil dibuat.',
        ], 201);
    }

    /**
     * GET /api/admin/articles/{id}
     * Admin: Show article detail.
     */
    public function show($id)
    {
        $article = Article::with(['author:id,name', 'category:id,name,slug'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }

    /**
     * PUT /api/admin/articles/{id}
     * POST /api/admin/articles/{id} (for FormData with _method=PUT)
     * Admin: Update an article.
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'article_category_id' => 'nullable|exists:article_categories,id',
            'excerpt' => 'nullable|string',
            'body' => 'sometimes|required|string',
            'featured_image' => 'nullable|image|max:5120',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'status' => 'in:draft,published,archived',
            'published_at' => 'nullable|date',
            'slug' => 'nullable|string|max:280',
        ]);

        if (isset($validated['title']) && $validated['title'] !== $article->title) {
            $slug = isset($validated['slug']) && !empty($validated['slug'])
                ? Str::slug($validated['slug'])
                : Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Article::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $article->slug = $slug;
        } elseif (isset($validated['slug']) && !empty($validated['slug'])) {
            $slug = Str::slug($validated['slug']);
            $originalSlug = $slug;
            $counter = 1;
            while (Article::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $article->slug = $slug;
        }

        $article->fill(collect($validated)->except(['featured_image', 'slug'])->toArray());

        // Handle published_at when publishing
        if (isset($validated['status']) && $validated['status'] === 'published' && !$article->published_at) {
            $article->published_at = $validated['published_at'] ?? now();
        } elseif (isset($validated['published_at'])) {
            $article->published_at = $validated['published_at'];
        }

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $article->featured_image = $this->processAndStoreImage($request->file('featured_image'), 'articles/featured');
        }

        $article->save();
        $article->load(['author:id,name', 'category:id,name,slug']);

        return response()->json([
            'success' => true,
            'data' => $article,
            'message' => 'Artikel berhasil diperbarui.',
        ]);
    }

    /**
     * DELETE /api/admin/articles/{id}
     * Admin: Delete an article.
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        // Delete featured image
        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Artikel berhasil dihapus.',
        ]);
    }

    /**
     * GET /api/articles (PUBLIC)
     * Public: List published articles.
     */
    public function publicIndex(Request $request)
    {
        $query = Article::published()
            ->with(['author:id,name', 'category:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'article_category_id', 'user_id', 'published_at', 'created_at']);

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 12), 50);
        $articles = $query->orderBy('published_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    /**
     * GET /api/articles/{slug} (PUBLIC)
     * Public: Show published article by slug.
     */
    public function publicShow($slug)
    {
        $article = Article::published()
            ->with(['author:id,name', 'category:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Get related articles (same category, exclude current)
        $related = Article::published()
            ->with(['category:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at'])
            ->where('id', '!=', $article->id)
            ->when($article->article_category_id, function ($q) use ($article) {
                $q->where('article_category_id', $article->article_category_id);
            })
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $article,
            'related' => $related,
        ]);
    }

    /**
     * Process and store image with Intervention Image.
     * Auto-resize to max 1200px width and convert to WebP.
     */
    private function processAndStoreImage($file, $directory)
    {
        $filename = Str::uuid() . '.webp';

        $image = \Intervention\Image\Laravel\Facades\Image::read($file);

        // Auto resize if too large
        if ($image->width() > 1200) {
            $image->scaleDown(width: 1200);
        }

        $encoded = $image->toWebp(quality: 80);

        Storage::disk('public')->put("{$directory}/{$filename}", (string) $encoded);

        return "{$directory}/{$filename}";
    }
}
