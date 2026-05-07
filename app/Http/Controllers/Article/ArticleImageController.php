<?php

namespace App\Http\Controllers\Article;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ArticleImageController extends Controller
{
    /**
     * POST /api/admin/articles/upload-image
     * Upload image for TipTap editor (inline images).
     * Auto-resize to max 1200px and convert to WebP.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB (client compresses first)
        ]);

        $file = $request->file('image');
        $filename = Str::uuid() . '.webp';

        $image = \Intervention\Image\Laravel\Facades\Image::read($file);

        // Auto resize if too large
        if ($image->width() > 1200) {
            $image->scaleDown(width: 1200);
        }

        $encoded = $image->toWebp(quality: 80);

        Storage::disk('public')->put("articles/content/{$filename}", (string) $encoded);

        $url = Storage::disk('public')->url("articles/content/{$filename}");

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    /**
     * POST /api/admin/articles/upload-gallery
     * Upload multiple images at once.
     */
    public function uploadGallery(Request $request)
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'image|max:10240',
        ]);

        $urls = [];

        foreach ($request->file('images') as $file) {
            $filename = Str::uuid() . '.webp';

            $image = \Intervention\Image\Laravel\Facades\Image::read($file);

            if ($image->width() > 1200) {
                $image->scaleDown(width: 1200);
            }

            $encoded = $image->toWebp(quality: 80);

            Storage::disk('public')->put("articles/content/{$filename}", (string) $encoded);

            $urls[] = Storage::disk('public')->url("articles/content/{$filename}");
        }

        return response()->json([
            'success' => true,
            'urls' => $urls,
        ]);
    }
}
