<?php

namespace App\Http\Controllers\Forecast;

use App\Http\Controllers\Controller;
use App\Models\Forecast\ForecastData;
use App\Models\Forecast\ForecastResult;
use App\Models\Forecast\ForecastInsight;
use App\Services\ForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForecastResultController extends Controller
{
    protected $forecastService;

    public function __construct(ForecastService $forecastService)
    {
        $this->forecastService = $forecastService;
    }

    /**
     * Generate forecast results untuk forecast data tertentu
     */
    public function generate(Request $request, ForecastData $forecastData)
    {
        // Authorize user & check Pro access
        $user = Auth::user();
        if ($forecastData->user_id !== $user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$user->hasPdfProAccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Hasil & Insights Forecast hanya tersedia untuk pengguna Paket Pro. Silakan upgrade paket Anda untuk melihat hasil prediksi otomatis.',
                'is_pro_required' => true
            ], 403);
        }

        $validated = $request->validate([
            'method' => 'sometimes|in:auto,arima,manual,exponential_smoothing',
            'forecast_months' => 'sometimes|integer|min:1|max:60',
        ]);

        $method = $validated['method'] ?? 'arima';
        $forecastMonths = $validated['forecast_months'] ?? 12;

        try {
            // Generate forecast
            $results = $this->forecastService->generateForecast($forecastData, $method, $forecastMonths);

            // Generate insights
            $insights = $this->forecastService->generateInsights($forecastData, $results);

            // Save ke database
            $this->forecastService->saveForecastResults($forecastData, $results);
            $this->forecastService->saveInsights($forecastData, $insights);

            // Calculate annual summary
            $annualSummary = $this->forecastService->calculateAnnualSummary($results);

            // Calculate yearly summary for easier multi-year analysis
            $yearlySummary = $this->forecastService->calculateYearlySummary(
                $results,
                $forecastData->year,
                $forecastData->month ?? 1
            );

            return response()->json([
                'success' => true,
                'message' => 'Forecast generated successfully',
                'data' => [
                    'forecast_data' => $forecastData,
                    'results' => $results,
                    'insights' => $insights,
                    'annual_summary' => $annualSummary,
                    'yearly_summary' => $yearlySummary,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating forecast: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get forecast results untuk forecast data tertentu
     */
    public function getResults(ForecastData $forecastData)
    {
        // Authorize user & check Pro access
        $user = Auth::user();
        if ($forecastData->user_id !== $user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$user->hasPdfProAccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Hasil & Insights Forecast hanya tersedia untuk pengguna Paket Pro.',
                'is_pro_required' => true
            ], 403);
        }

        $results = ForecastResult::where('forecast_data_id', $forecastData->id)
            ->orderBy('month')
            ->get();

        $insights = ForecastInsight::where('forecast_data_id', $forecastData->id)
            ->orderBy('severity')
            ->get();

        // Calculate annual summary
        $annualSummary = $this->forecastService->calculateAnnualSummary($results->toArray());

        // Calculate yearly summary
        $yearlySummary = $this->forecastService->calculateYearlySummary(
            $results->toArray(),
            $forecastData->year,
            $forecastData->month ?? 1
        );

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'insights' => $insights,
                'annual_summary' => $annualSummary,
                'yearly_summary' => $yearlySummary,
            ],
        ]);
    }

    /**
     * Get available years dari forecast data user
     */
    public function getAvailableYears()
    {
        $userId = Auth::user()?->id;
        $years = ForecastData::where('user_id', $userId)
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $years,
        ]);
    }

    /**
     * Compare multiple forecast scenarios
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'forecast_data_ids' => 'required|array|min:2|max:4',
            'forecast_data_ids.*' => 'exists:forecast_data,id',
        ]);

        $forecastDataIds = $validated['forecast_data_ids'];

        // Verify ownership & check Pro access
        $user = Auth::user();
        $count = ForecastData::whereIn('id', $forecastDataIds)
            ->where('user_id', $user?->id)
            ->count();

        if ($count !== count($forecastDataIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to some forecast data',
            ], 403);
        }

        if (!$user->hasPdfProAccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Perbandingan Forecast hanya tersedia untuk pengguna Paket Pro.',
                'is_pro_required' => true
            ], 403);
        }

        // Get results untuk setiap forecast
        $comparisons = [];
        foreach ($forecastDataIds as $id) {
            $forecastData = ForecastData::find($id);
            $results = ForecastResult::where('forecast_data_id', $id)
                ->orderBy('month')
                ->get();

            $comparisons[] = [
                'forecast_data' => $forecastData,
                'results' => $results,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $comparisons,
        ]);
    }
}
