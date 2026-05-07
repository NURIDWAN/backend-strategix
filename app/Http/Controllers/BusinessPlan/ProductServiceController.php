<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductServiceController extends Controller
{
    public function store(Request $request)
    {
        // Decode bmc_alignment dari JSON string ke array sebelum validasi
        $requestData = $request->all();

        if ($request->has('bmc_alignment') && is_string($request->bmc_alignment)) {
            try {
                $requestData['bmc_alignment'] = json_decode($request->bmc_alignment, true);
            } catch (\Exception $e) {
                Log::error('Error decoding bmc_alignment JSON: ' . $e->getMessage());
                $requestData['bmc_alignment'] = [];
            }
        }

        $validator = Validator::make($requestData, [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'type' => 'required|in:product,service',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'advantages' => 'nullable|string',
            'development_strategy' => 'nullable|string',
            'bmc_alignment' => 'nullable|array',
            'status' => 'nullable|in:draft,in_development,launched'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imagePath = null;
            if ($request->hasFile('image_path')) {
                $imagePath = $request->file('image_path')->store('product_images', 'public');
                Log::info('Image uploaded successfully', [
                    'original_name' => $request->file('image_path')->getClientOriginalName(),
                    'stored_path' => $imagePath,
                    'full_url' => asset('storage/' . $imagePath)
                ]);
            }

            $productData = [
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'image_path' => $imagePath,
                'advantages' => $request->advantages,
                'development_strategy' => $request->development_strategy,
                'status' => $request->status ?? 'draft',
            ];

            // Handle BMC Alignment - gunakan data yang sudah di-decode
            if (isset($requestData['bmc_alignment']) && is_array($requestData['bmc_alignment'])) {
                $productData['bmc_alignment'] = $requestData['bmc_alignment'];
            }

            $product = ProductService::create($productData);

            // Generate BMC alignment otomatis jika tidak disediakan
            if (!isset($requestData['bmc_alignment']) || empty($requestData['bmc_alignment'])) {
                $product->generateBmcAlignment();
                $product->save();
            }

            // Load relationships
            $product->load(['businessBackground', 'user']);

            // Format response dengan full image URL dan BMC alignment
            $formattedProduct = $this->formatProductResponse($product);

            return response()->json([
                'status' => 'success',
                'message' => 'Product/service created successfully',
                'data' => $formattedProduct
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating product/service: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product/service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = ProductService::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product or service not found'
                ], 404);
            }

            // Check ownership
            if (Auth::id() != $product->user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: You cannot update this data'
                ], 403);
            }

            // Decode bmc_alignment dari JSON string ke array sebelum validasi
            $requestData = $request->all();

            if ($request->has('bmc_alignment') && is_string($request->bmc_alignment)) {
                try {
                    $requestData['bmc_alignment'] = json_decode($request->bmc_alignment, true);
                } catch (\Exception $e) {
                    Log::error('Error decoding bmc_alignment JSON: ' . $e->getMessage());
                    $requestData['bmc_alignment'] = [];
                }
            }

            $validator = Validator::make($requestData, [
                'business_background_id' => 'required|exists:business_backgrounds,id',
                'type' => 'required|in:product,service',
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'nullable|numeric|min:0',
                'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'advantages' => 'nullable|string',
                'development_strategy' => 'nullable|string',
                'bmc_alignment' => 'nullable|array',
                'status' => 'nullable|in:draft,in_development,launched'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'business_background_id' => $request->business_background_id,
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'advantages' => $request->advantages,
                'development_strategy' => $request->development_strategy,
                'status' => $request->status ?? 'draft',
            ];

            // Handle price
            if ($request->has('price') && $request->price !== '') {
                $updateData['price'] = $request->price;
            } else {
                $updateData['price'] = null;
            }

            // Handle BMC Alignment - gunakan data yang sudah di-decode
            if (isset($requestData['bmc_alignment']) && is_array($requestData['bmc_alignment'])) {
                $updateData['bmc_alignment'] = $requestData['bmc_alignment'];
            } else {
                // Regenerate BMC alignment jika data penting berubah
                $importantFieldsChanged = $request->has('name') || $request->has('description') ||
                    $request->has('advantages') || $request->has('development_strategy');

                if ($importantFieldsChanged) {
                    $product->generateBmcAlignment();
                    $updateData['bmc_alignment'] = $product->bmc_alignment;
                }
            }

            // Handle image upload
            if ($request->hasFile('image_path')) {
                // Delete old image if exists
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }

                // Simpan file baru
                $updateData['image_path'] = $request->file('image_path')->store('product_images', 'public');

                Log::info('Image updated successfully', [
                    'new_path' => $updateData['image_path'],
                    'full_url' => asset('storage/' . $updateData['image_path'])
                ]);
            } elseif ($request->has('remove_image') && $request->remove_image) {
                // Handle image removal
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $updateData['image_path'] = null;
            }

            $product->update($updateData);

            // Reload dengan relationship
            $product->load(['businessBackground', 'user']);

            // Format response dengan full image URL
            $formattedProduct = $this->formatProductResponse($product);

            return response()->json([
                'status' => 'success',
                'message' => 'Product/service updated successfully',
                'data' => $formattedProduct
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product/service: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product/service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ... (method lainnya tetap sama)

    public function index(Request $request)
    {
        try {
            $query = ProductService::with(['businessBackground', 'user'])
                ->where('user_id', Auth::id());

            // User filter removed as it is now enforced via Auth::id()

            // Filter berdasarkan business_background_id
            if ($request->business_background_id) {
                $query->where('business_background_id', $request->business_background_id);
            }

            // Filter berdasarkan type
            if ($request->type) {
                $query->where('type', $request->type);
            }

            // Filter berdasarkan status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->search) {
                $query->search($request->search);
            }

            // Sorting
            $sortBy = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $data = $query->get();

            // Format semua produk dengan full image URL
            $formattedData = $data->map(function ($product) {
                return $this->formatProductResponse($product);
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedData,
                'meta' => [
                    'total' => $data->count(),
                    'products_count' => $data->where('type', 'product')->count(),
                    'services_count' => $data->where('type', 'service')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching products/services: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products/services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = ProductService::with(['businessBackground', 'user'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product or service not found'
                ], 404);
            }

            // Format response dengan full image URL
            $formattedProduct = $this->formatProductResponse($product);

            return response()->json([
                'status' => 'success',
                'data' => $formattedProduct
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product/service: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch product/service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $product = ProductService::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product or service not found'
                ], 404);
            }

            // Check ownership
            if (Auth::id() != $product->user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: You cannot delete this data'
                ], 403);
            }

            // Delete image if exists
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product/service deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting product/service: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product/service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate BMC alignment untuk product/service tertentu
     */
    public function generateBmcAlignment($id)
    {
        try {
            $product = ProductService::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product or service not found'
                ], 404);
            }

            $bmcAlignment = $product->generateBmcAlignment();
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'BMC alignment generated successfully',
                'data' => [
                    'bmc_alignment' => $bmcAlignment,
                    'product' => $this->formatProductResponse($product)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating BMC alignment: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate BMC alignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics untuk products/services
     */
    public function getStatistics(Request $request)
    {
        try {
            $userId = $request->user_id;

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID is required'
                ], 422);
            }

            $stats = ProductService::where('user_id', Auth::id())
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN type = "product" THEN 1 ELSE 0 END) as products_count,
                    SUM(CASE WHEN type = "service" THEN 1 ELSE 0 END) as services_count,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = "in_development" THEN 1 ELSE 0 END) as in_development_count,
                    SUM(CASE WHEN status = "launched" THEN 1 ELSE 0 END) as launched_count,
                    SUM(CASE WHEN image_path IS NOT NULL THEN 1 ELSE 0 END) as with_images_count
                ')
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product/service statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format product response dengan full image URL dan BMC alignment
     */
    private function formatProductResponse($product)
    {
        $formatted = $product->toArray();

        // Tambahkan full image URL jika ada image_path
        if ($product->image_path) {
            $formatted['image_url'] = asset('storage/' . $product->image_path);
        } else {
            $formatted['image_url'] = null;
        }

        // Pastikan BMC alignment terformat dengan baik
        if (!isset($formatted['bmc_alignment']) || !$formatted['bmc_alignment']) {
            $formatted['bmc_alignment'] = $product->bmc_alignment;
        }

        return $formatted;
    }
}
