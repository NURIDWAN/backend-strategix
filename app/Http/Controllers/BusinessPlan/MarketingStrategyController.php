<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\MarketingStrategy;
use Illuminate\Support\Facades\Auth;

class MarketingStrategyController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_background_id' => 'nullable|exists:business_backgrounds,id',
            'promotion_strategy' => 'required|string',
            'media_used' => 'nullable|string',
            'pricing_strategy' => 'nullable|string',
            'monthly_target' => 'nullable|integer|min:0',
            'collaboration_plan' => 'nullable|string',
            'status' => 'nullable|in:draft,active'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = Auth::id();
        $marketing = MarketingStrategy::create($data);

        return response()->json([
            'status' => 'success',
            'data' => $marketing
        ], 201);
    }

    public function index(Request $request)
    {
        // Ambil query builder awal, sudah include relasi businessBackground
        $query = MarketingStrategy::with('businessBackground')
            ->where('user_id', Auth::id());

        // Filter berdasarkan business_background_id (opsional)
        if ($request->has('business_background_id')) {
            $query->where('business_background_id', $request->business_background_id);
        }

        // Ambil semua hasil filter
        $data = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }


    public function show($id)
    {
        // $marketing = MarketingStrategy::find($id);
        // $userId = $marketing->user_id;
        $marketing = MarketingStrategy::with('businessBackground')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$marketing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Marketing strategy not found or unauthorized'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $marketing
        ]);
    }

    public function update(Request $request, $id)
    {
        $marketing = MarketingStrategy::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$marketing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Marketing strategy not found or unauthorized'
            ], 404);
        }

        $validated = $request->validate([
            'promotion_strategy' => 'sometimes|required|string',
            'media_used' => 'nullable|string',
            'pricing_strategy' => 'nullable|string',
            'monthly_target' => 'nullable|integer|min:0',
            'collaboration_plan' => 'nullable|string',
            'status' => 'nullable|in:draft,active'
        ]);

        $marketing->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Marketing strategy updated successfully',
            'data' => $marketing
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $marketing = MarketingStrategy::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$marketing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Marketing strategy not found or unauthorized'
            ], 404);
        }

        $marketing->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Marketing strategy deleted successfully'
        ]);
    }
}
