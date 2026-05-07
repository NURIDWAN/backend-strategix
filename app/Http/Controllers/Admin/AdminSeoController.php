<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminSeoController extends Controller
{
    /**
     * GET /api/admin/seo-pages
     * List all SEO page entries.
     */
    public function index()
    {
        $pages = SeoPage::orderBy('page_name')->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * PUT /api/admin/seo-pages/{id}
     * Update a single SEO page entry.
     */
    public function update(Request $request, $id)
    {
        $page = SeoPage::findOrFail($id);

        $validated = $request->validate([
            'title'            => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords'    => ['nullable', 'string', 'max:500'],
            'og_title'         => ['nullable', 'string', 'max:255'],
            'og_description'   => ['nullable', 'string', 'max:500'],
            'og_image'         => ['nullable', 'string', 'max:500'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $oldValues = $page->only([
            'title', 'meta_description', 'meta_keywords',
            'og_title', 'og_description', 'og_image', 'is_active',
        ]);

        $page->update($validated);

        ActivityLog::logAction(
            'seo.page.updated',
            "SEO halaman '{$page->page_name}' diperbarui.",
            null,
            [
                'page_identifier' => $page->page_identifier,
                'old' => $oldValues,
                'new' => $page->only([
                    'title', 'meta_description', 'meta_keywords',
                    'og_title', 'og_description', 'og_image', 'is_active',
                ]),
            ],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $page->fresh(),
            'message' => "SEO halaman '{$page->page_name}' berhasil diperbarui.",
        ]);
    }

    /**
     * PUT /api/admin/seo-pages/bulk
     * Bulk update multiple SEO pages.
     * Expected: { "pages": [ { "id": 1, "title": "...", ... }, ... ] }
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'pages'                  => ['required', 'array', 'min:1'],
            'pages.*.id'             => ['required', 'integer', 'exists:seo_pages,id'],
            'pages.*.title'          => ['nullable', 'string', 'max:255'],
            'pages.*.meta_description' => ['nullable', 'string', 'max:500'],
            'pages.*.meta_keywords'  => ['nullable', 'string', 'max:500'],
            'pages.*.og_title'       => ['nullable', 'string', 'max:255'],
            'pages.*.og_description' => ['nullable', 'string', 'max:500'],
            'pages.*.og_image'       => ['nullable', 'string', 'max:500'],
            'pages.*.is_active'      => ['nullable', 'boolean'],
        ]);

        $updatedPages = [];

        foreach ($validated['pages'] as $pageData) {
            $page = SeoPage::find($pageData['id']);
            if (!$page) continue;

            $updateData = collect($pageData)->except('id')->toArray();
            $page->update($updateData);
            $updatedPages[] = $page->page_name;
        }

        ActivityLog::logAction(
            'seo.bulk.updated',
            'SEO halaman diperbarui: ' . implode(', ', $updatedPages),
            null,
            ['updated_pages' => $updatedPages],
            $request
        );

        return response()->json([
            'success' => true,
            'data' => SeoPage::orderBy('page_name')->get(),
            'message' => count($updatedPages) . ' halaman SEO berhasil diperbarui.',
        ]);
    }

    /**
     * GET /api/seo/{pageIdentifier}
     * Public endpoint: get SEO data for a page.
     */
    public function publicShow($pageIdentifier)
    {
        $page = SeoPage::active()
            ->where('page_identifier', $pageIdentifier)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'title'            => $page->title,
                'meta_description' => $page->meta_description,
                'meta_keywords'    => $page->meta_keywords,
                'og_title'         => $page->og_title,
                'og_description'   => $page->og_description,
                'og_image'         => $page->og_image,
            ],
        ]);
    }
}
