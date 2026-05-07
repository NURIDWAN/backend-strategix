<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\BusinessBackground;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BusinessController extends Controller
{
    /**
     * Resize image to optimal size maintaining aspect ratio
     * @param string $sourcePath
     * @param int $maxSize
     * @return string|null PNG encoded binary or null
     */
    private function resizeImage($sourcePath, $maxSize = 200)
    {
        // Get image info
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }

        list($width, $height, $type) = $imageInfo;

        // Create image resource based on type
        $image = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $image = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }

        if (!$image) {
            return null;
        }

        // Calculate new dimensions
        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxSize || $height > $maxSize) {
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = intval($height * ($maxSize / $width));
            } else {
                $newHeight = $maxSize;
                $newWidth = intval($width * ($maxSize / $height));
            }
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Resize
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Get PNG output
        ob_start();
        imagepng($newImage);
        $output = ob_get_clean();

        // Note: imagedestroy() is deprecated in PHP 8.0+
        // Garbage collection will automatically handle resource cleanup

        return $output;
    }
    // BusinessBackground
    public function index()
    {
        $businesses = BusinessBackground::where('user_id', Auth::id())->get();

        return response()->json([
            'status' => 'success',
            'data' => $businesses
        ], 200);
    }

    public function show($id)
    {
        $business = BusinessBackground::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$business) {
            return response()->json([
                'status' => 'error',
                'message' => 'Business not found or unauthorized'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $business
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'required|string',
            'business_overview' => 'nullable|string',
            'business_legality' => 'nullable|string',
            'business_objectives' => 'nullable|string',
            'purpose' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'values' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi gagal pada storeBusinessBackground', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $sourcePath = $logoFile->getPathname();

                // Resize image
                $resizedImage = $this->resizeImage($sourcePath, 200);

                if ($resizedImage) {
                    // Save resized image
                    $filename = 'logo_' . time() . '_' . uniqid() . '.png';
                    $logoPath = 'logos/' . $filename;
                    Storage::disk('public')->put($logoPath, $resizedImage);
                }
            }

            $backgroundPath = null;
            if ($request->hasFile('background_image')) {
                $backgroundFile = $request->file('background_image');
                $filename = 'background_' . time() . '_' . uniqid() . '.' . $backgroundFile->getClientOriginalExtension();
                $backgroundPath = 'backgrounds/' . $filename;
                Storage::disk('public')->put($backgroundPath, file_get_contents($backgroundFile->getRealPath()));
            }

            $business = BusinessBackground::create([
                'user_id' => Auth::id(),
                'logo' => $logoPath,
                'background_image' => $backgroundPath,
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'business_overview' => $request->business_overview,
                'business_legality' => $request->business_legality,
                'business_objectives' => $request->business_objectives,
                'purpose' => $request->purpose,
                'location' => $request->location,
                'business_type' => $request->business_type,
                'start_date' => $request->start_date,
                'values' => $request->values,
                'vision' => $request->vision,
                'mission' => $request->mission,
                'contact' => $request->contact,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data latar belakang usaha berhasil disimpan.',
                'data' => $business
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error storing business: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $business = BusinessBackground::find($id);

        if (!$business) {
            return response()->json([
                'status' => 'error',
                'message' => 'Business not found'
            ], 404);
        }

        if (Auth::id() != $business->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: You cannot update this data'
            ], 403);
        }

        $validated = $request->validate([
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'background_image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'required|string',
            'business_overview' => 'nullable|string',
            'business_legality' => 'nullable|string',
            'business_objectives' => 'nullable|string',
            'purpose' => 'nullable|string',
            'location' => 'required|string|max:255',
            'business_type' => 'required|string|max:50',
            'start_date' => 'nullable|date',
            'values' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'contact' => 'nullable|string',
            'user_id' => 'nullable|integer', // Add user_id as nullable for validation
        ]);

        try {
            // Handle logo update
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $sourcePath = $logoFile->getPathname();

                // Resize image
                $resizedImage = $this->resizeImage($sourcePath, 200);

                if ($resizedImage) {
                    // Save resized image
                    $filename = 'logo_' . time() . '_' . uniqid() . '.png';
                    $path = 'logos/' . $filename;
                    Storage::disk('public')->put($path, $resizedImage);
                    $validated['logo'] = $path;
                }

                // Hapus logo lama jika ada
                if ($business->logo) {
                    Storage::disk('public')->delete($business->logo);
                }
            } elseif ($request->has('logo') && $request->input('logo') === '') {
                // Jika logo dikirim sebagai string kosong, hapus logo
                if ($business->logo) {
                    Storage::disk('public')->delete($business->logo);
                }
                $validated['logo'] = null;
            } else {
                // Jika tidak ada perubahan logo, pertahankan logo lama
                unset($validated['logo']);
            }

            // Handle background_image update
            if ($request->hasFile('background_image')) {
                $backgroundFile = $request->file('background_image');
                $filename = 'background_' . time() . '_' . uniqid() . '.' . $backgroundFile->getClientOriginalExtension();
                $path = 'backgrounds/' . $filename;
                Storage::disk('public')->put($path, file_get_contents($backgroundFile->getRealPath()));
                $validated['background_image'] = $path;

                // Hapus background lama jika ada
                if ($business->background_image) {
                    Storage::disk('public')->delete($business->background_image);
                }
            } elseif ($request->has('background_image') && $request->input('background_image') === '') {
                // Jika background_image dikirim sebagai string kosong, hapus background
                if ($business->background_image) {
                    Storage::disk('public')->delete($business->background_image);
                }
                $validated['background_image'] = null;
            } else {
                // Jika tidak ada perubahan background_image, pertahankan background lama
                unset($validated['background_image']);
            }

            // Remove user_id from validated data before update
            unset($validated['user_id']);

            $business->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Business updated successfully',
                'data' => $business
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating business: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        $business = BusinessBackground::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$business) {
            return response()->json([
                'status' => 'error',
                'message' => 'Business not found or unauthorized'
            ], 404);
        }

        try {
            // Hapus logo jika ada
            if ($business->logo) {
                Storage::disk('public')->delete($business->logo);
            }

            // Hapus background_image jika ada
            if ($business->background_image) {
                Storage::disk('public')->delete($business->background_image);
            }

            $business->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Business deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting business: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ], 500);
        }
    }
}
