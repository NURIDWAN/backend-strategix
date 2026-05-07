<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MarketAnalysis;
use App\Models\MarketAnalysisCompetitor;
use App\Models\BusinessBackground;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MarketAnalysisController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('Fetching market analyses with business background', [
                'user_id' => $request->user_id,
                'request_all' => $request->all()
            ]);

            // Gunakan eager loading dengan cara yang lebih eksplisit
            $query = MarketAnalysis::with(['businessBackground', 'competitors'])
                ->where('user_id', Auth::id());

            $analyses = $query->get();

            // DEBUG: Manual load business background jika eager loading gagal
            foreach ($analyses as $analysis) {
                if (!$analysis->relationLoaded('businessBackground')) {
                    Log::warning('Business background not loaded for analysis: ' . $analysis->id);
                    $analysis->load('businessBackground');
                }

                // Debug setiap analysis
                Log::info('Analysis debug', [
                    'id' => $analysis->id,
                    'business_background_id' => $analysis->business_background_id,
                    'has_business_background' => !is_null($analysis->businessBackground),
                    'business_background_data' => $analysis->businessBackground ? [
                        'id' => $analysis->businessBackground->id,
                        'name' => $analysis->businessBackground->name,
                        'category' => $analysis->businessBackground->category
                    ] : null
                ]);
            }

            Log::info('Final analyses data', [
                'total' => $analyses->count(),
                'with_business_background' => $analyses->where('businessBackground', '!=', null)->count()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $analyses
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching market analyses: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch market analyses: ' . $e->getMessage()
            ], 500);
        }
    }

    // SHOW
    public function show($id)
    {
        try {
            $analysis = MarketAnalysis::with(['businessBackground', 'competitors'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$analysis) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Market analysis not found or unauthorized'
                ], 404);
            }

            // Manual load jika masih gagal
            if (!$analysis->relationLoaded('businessBackground')) {
                $analysis->load('businessBackground');
            }

            return response()->json([
                'status' => 'success',
                'data' => $analysis
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching market analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch market analysis'
            ], 500);
        }
    }

    // STORE
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'target_market' => 'nullable|string',
            'market_size' => 'nullable|string|max:255',
            'market_trends' => 'nullable|string',
            'main_competitors' => 'nullable|string',
            'competitor_strengths' => 'nullable|string',
            'competitor_weaknesses' => 'nullable|string',
            'competitive_advantage' => 'nullable|string',

            // REVISI: Validasi field baru
            'tam_total' => 'nullable|numeric|min:0',
            'sam_percentage' => 'nullable|numeric|min:0|max:100',
            'sam_total' => 'nullable|numeric|min:0',
            'som_percentage' => 'nullable|numeric|min:0|max:100',
            'som_total' => 'nullable|numeric|min:0',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'opportunities' => 'nullable|string',
            'threats' => 'nullable|string',

            // Validasi untuk competitors array
            'competitors' => 'nullable|array',
            'competitors.*.competitor_name' => 'required|string|max:255',
            'competitors.*.type' => 'required|in:ownshop,competitor',
            'competitors.*.code' => 'nullable|string|max:50',
            'competitors.*.address' => 'nullable|string',
            'competitors.*.annual_sales_estimate' => 'nullable|numeric|min:0',
            'competitors.*.selling_price' => 'nullable|numeric|min:0',
            'competitors.*.strengths' => 'nullable|string',
            'competitors.*.weaknesses' => 'nullable|string',
            'competitors.*.sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create main market analysis
            $analysis = MarketAnalysis::create([
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'target_market' => $request->target_market,
                'market_size' => $request->market_size,
                'market_trends' => $request->market_trends,
                'main_competitors' => $request->main_competitors,
                'competitor_strengths' => $request->competitor_strengths,
                'competitor_weaknesses' => $request->competitor_weaknesses,
                'competitive_advantage' => $request->competitive_advantage,
                // REVISI: Field baru
                'tam_total' => $request->tam_total,
                'sam_percentage' => $request->sam_percentage,
                'sam_total' => $request->sam_total,
                'som_percentage' => $request->som_percentage,
                'som_total' => $request->som_total,
                'strengths' => $request->strengths,
                'weaknesses' => $request->weaknesses,
                'opportunities' => $request->opportunities,
                'threats' => $request->threats,
            ]);

            // REVISI: Create competitors jika ada
            if ($request->has('competitors') && is_array($request->competitors)) {
                foreach ($request->competitors as $competitorData) {
                    MarketAnalysisCompetitor::create([
                        'market_analysis_id' => $analysis->id,
                        'competitor_name' => $competitorData['competitor_name'],
                        'type' => $competitorData['type'],
                        'code' => $competitorData['code'] ?? null,
                        'address' => $competitorData['address'] ?? null,
                        'annual_sales_estimate' => $competitorData['annual_sales_estimate'] ?? null,
                        'selling_price' => $competitorData['selling_price'] ?? null,
                        'strengths' => $competitorData['strengths'] ?? null,
                        'weaknesses' => $competitorData['weaknesses'] ?? null,
                        'sort_order' => $competitorData['sort_order'] ?? 0,
                    ]);
                }
            }

            // Load relationships
            $analysis->load(['businessBackground', 'competitors']);

            DB::commit();

            Log::info('Market analysis created successfully', [
                'id' => $analysis->id,
                'business_background_id' => $analysis->business_background_id,
                'business_background_name' => $analysis->businessBackground ? $analysis->businessBackground->name : 'NOT FOUND',
                'competitors_count' => $analysis->competitors->count()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $analysis
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating market analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create market analysis'
            ], 500);
        }
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $analysis = MarketAnalysis::find($id);

        if (!$analysis) {
            return response()->json([
                'status' => 'error',
                'message' => 'Market analysis not found'
            ], 404);
        }

        if ($analysis->user_id != Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: You cannot access this data'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'target_market' => 'nullable|string',
            'market_size' => 'nullable|string|max:255',
            'market_trends' => 'nullable|string',
            'main_competitors' => 'nullable|string',
            'competitor_strengths' => 'nullable|string',
            'competitor_weaknesses' => 'nullable|string',
            'competitive_advantage' => 'nullable|string',

            // 🔥 REVISI: Validasi field baru
            'tam_total' => 'nullable|numeric|min:0',
            'sam_percentage' => 'nullable|numeric|min:0|max:100',
            'sam_total' => 'nullable|numeric|min:0',
            'som_percentage' => 'nullable|numeric|min:0|max:100',
            'som_total' => 'nullable|numeric|min:0',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'opportunities' => 'nullable|string',
            'threats' => 'nullable|string',

            // Validasi untuk competitors array
            'competitors' => 'nullable|array',
            'competitors.*.id' => 'nullable|exists:market_analysis_competitors,id',
            'competitors.*.competitor_name' => 'required|string|max:255',
            'competitors.*.type' => 'required|in:ownshop,competitor',
            'competitors.*.code' => 'nullable|string|max:50',
            'competitors.*.address' => 'nullable|string',
            'competitors.*.annual_sales_estimate' => 'nullable|numeric|min:0',
            'competitors.*.selling_price' => 'nullable|numeric|min:0',
            'competitors.*.strengths' => 'nullable|string',
            'competitors.*.weaknesses' => 'nullable|string',
            'competitors.*.sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update main market analysis
            $analysis->update([
                'business_background_id' => $request->business_background_id,
                'target_market' => $request->target_market,
                'market_size' => $request->market_size,
                'market_trends' => $request->market_trends,
                'main_competitors' => $request->main_competitors,
                'competitor_strengths' => $request->competitor_strengths,
                'competitor_weaknesses' => $request->competitor_weaknesses,
                'competitive_advantage' => $request->competitive_advantage,
                // REVISI: Field baru
                'tam_total' => $request->tam_total,
                'sam_percentage' => $request->sam_percentage,
                'sam_total' => $request->sam_total,
                'som_percentage' => $request->som_percentage,
                'som_total' => $request->som_total,
                'strengths' => $request->strengths,
                'weaknesses' => $request->weaknesses,
                'opportunities' => $request->opportunities,
                'threats' => $request->threats,
            ]);

            // REVISI: Sync competitors
            if ($request->has('competitors')) {
                $existingCompetitorIds = [];

                foreach ($request->competitors as $competitorData) {
                    if (isset($competitorData['id'])) {
                        // Update existing competitor
                        $competitor = MarketAnalysisCompetitor::where('id', $competitorData['id'])
                            ->where('market_analysis_id', $analysis->id)
                            ->first();

                        if ($competitor) {
                            $competitor->update([
                                'competitor_name' => $competitorData['competitor_name'],
                                'type' => $competitorData['type'],
                                'code' => $competitorData['code'] ?? null,
                                'address' => $competitorData['address'] ?? null,
                                'annual_sales_estimate' => $competitorData['annual_sales_estimate'] ?? null,
                                'selling_price' => $competitorData['selling_price'] ?? null,
                                'strengths' => $competitorData['strengths'] ?? null,
                                'weaknesses' => $competitorData['weaknesses'] ?? null,
                                'sort_order' => $competitorData['sort_order'] ?? 0,
                            ]);
                            $existingCompetitorIds[] = $competitor->id;
                        }
                    } else {
                        // Create new competitor
                        $newCompetitor = MarketAnalysisCompetitor::create([
                            'market_analysis_id' => $analysis->id,
                            'competitor_name' => $competitorData['competitor_name'],
                            'type' => $competitorData['type'],
                            'code' => $competitorData['code'] ?? null,
                            'address' => $competitorData['address'] ?? null,
                            'annual_sales_estimate' => $competitorData['annual_sales_estimate'] ?? null,
                            'selling_price' => $competitorData['selling_price'] ?? null,
                            'strengths' => $competitorData['strengths'] ?? null,
                            'weaknesses' => $competitorData['weaknesses'] ?? null,
                            'sort_order' => $competitorData['sort_order'] ?? 0,
                        ]);
                        $existingCompetitorIds[] = $newCompetitor->id;
                    }
                }

                // Delete competitors that are not in the request
                MarketAnalysisCompetitor::where('market_analysis_id', $analysis->id)
                    ->whereNotIn('id', $existingCompetitorIds)
                    ->delete();
            } else {
                // If no competitors in request, delete all existing ones
                MarketAnalysisCompetitor::where('market_analysis_id', $analysis->id)->delete();
            }

            // Load relationships
            $analysis->load(['businessBackground', 'competitors']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Market analysis updated successfully',
                'data' => $analysis
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating market analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update market analysis'
            ], 500);
        }
    }

    // DESTROY
    public function destroy(Request $request, $id)
    {
        $analysis = MarketAnalysis::find($id);

        if (!$analysis) {
            return response()->json([
                'status' => 'error',
                'message' => 'Market analysis not found'
            ], 404);
        }

        if ($analysis->user_id != Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: You cannot access this data'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Delete competitors first (foreign key constraint)
            MarketAnalysisCompetitor::where('market_analysis_id', $analysis->id)->delete();

            // Then delete the analysis
            $analysis->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Market analysis deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting market analysis: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete market analysis'
            ], 500);
        }
    }

    // REVISI: Method untuk menghitung TAM, SAM, SOM otomatis
    public function calculateMarketSize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'potential_customers' => 'required|numeric|min:0',
            'average_annual_revenue' => 'required|numeric|min:0',
            'serviceable_percentage' => 'required|numeric|min:0|max:100',
            'achievable_percentage' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $potentialCustomers = $request->potential_customers;
            $averageAnnualRevenue = $request->average_annual_revenue;
            $serviceablePercentage = $request->serviceable_percentage;
            $achievablePercentage = $request->achievable_percentage;

            // Calculate TAM
            $tam = $potentialCustomers * $averageAnnualRevenue;

            // Calculate SAM
            $samPercentage = $serviceablePercentage;
            $sam = $potentialCustomers * ($serviceablePercentage / 100) * $averageAnnualRevenue;

            // Calculate SOM
            $somPercentage = $achievablePercentage;
            $som = $potentialCustomers * ($serviceablePercentage / 100) * ($achievablePercentage / 100) * $averageAnnualRevenue;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'tam_total' => round($tam, 2),
                    'sam_percentage' => round($samPercentage, 2),
                    'sam_total' => round($sam, 2),
                    'som_percentage' => round($somPercentage, 2),
                    'som_total' => round($som, 2),
                    'calculations' => [
                        'tam_formula' => "{$potentialCustomers} × {$averageAnnualRevenue}",
                        'sam_formula' => "{$potentialCustomers} × ({$serviceablePercentage}%) × {$averageAnnualRevenue}",
                        'som_formula' => "{$potentialCustomers} × ({$serviceablePercentage}%) × ({$achievablePercentage}%) × {$averageAnnualRevenue}",
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error calculating market size: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate market size'
            ], 500);
        }
    }
}
