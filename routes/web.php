<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Storage Proxy Route (Alternative to avoid /storage/ conflict)
Route::get('/get-image/{path}', function ($path) {
    try {
        $disk = Illuminate\Support\Facades\Storage::disk('public');

        if (!$disk->exists($path)) {
            // Return 200 with text so user sees the message
            return response("Debug: File not found. Checked path: " . $path, 200);
        }

        // Manual Mime Type Detection (Avoids fileinfo extension dependency)
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

        // Try Method 1: Get Content
        try {
            $content = $disk->get($path);
            return response($content, 200)->header('Content-Type', $contentType);
        } catch (\Throwable $e) {
            return response("Debug: Read Error. " . $e->getMessage(), 200);
        }
    } catch (\Throwable $e) {
        return response("Debug: System Error. " . $e->getMessage(), 200);
    }
})->where('path', '.*');

// Keep original storage proxy just in case
Route::get('/storage/{path}', function ($path) {
    if (Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        return response()->file(Illuminate\Support\Facades\Storage::disk('public')->path($path));
    }
    return response()->json(['message' => 'Image Not Found', 'path_checked' => $path], 404);
})->where('path', '.*');

// Debug Storage Route (Temporary)
Route::get('/debug-storage', function () {
    $disk = Illuminate\Support\Facades\Storage::disk('public');
    $root = $disk->path('');

    $checkLogos = $disk->exists('logos');
    $files = $checkLogos ? $disk->files('logos') : [];

    // Try to write a test file
    try {
        $disk->put('test_write.txt', 'Hello World');
        $writeStatus = 'Success';
    } catch (\Exception $e) {
        $writeStatus = 'Failed: ' . $e->getMessage();
    }

    return [
        'root_path' => $root,
        'logos_dir_exists' => $checkLogos,
        'files_in_logos' => array_slice($files, 0, 10), // Show first 10 files
        'write_test' => $writeStatus,
        'php_user' => get_current_user(),
    ];
});
