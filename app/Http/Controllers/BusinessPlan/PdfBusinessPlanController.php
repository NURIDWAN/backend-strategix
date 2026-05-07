<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessBackground;
use App\Models\MarketAnalysis;
use App\Models\ProductService;
use App\Models\MarketingStrategy;
use App\Models\OperationalPlan;
use App\Models\TeamStructure;
use App\Models\FinancialPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\WorkflowDiagramService;

class PdfBusinessPlanController extends Controller
{
    /**
     * Generate PDF Business Plan
     */
    public function generatePdf(Request $request)
    {
        try {
            Log::info('PDF Generation Started', [
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'mode' => $request->mode,
                'has_charts' => !empty($request->charts)
            ]);

            $userId = Auth::id();
            $businessBackgroundId = $request->business_background_id;
            $mode = $request->mode ?? 'free';
            $charts = $request->charts ?? null;

            // Validasi input
            if (!$businessBackgroundId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background ID is required'
                ], 422);
            }

            // Ambil semua data business plan
            $businessData = $this->getBusinessPlanData($userId, $businessBackgroundId);

            if (!$businessData['business_background']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background not found'
                ], 404);
            }

            $executiveSummary = $this->createExecutiveSummary($businessData);

            // Generate workflow diagrams dari backend
            $workflows = $this->generateWorkflowDiagrams($businessData['operational_plans']);

            // Log data yang ditemukan
            Log::info('Business Data Found', [
                'business_name' => $businessData['business_background']->name,
                'market_analysis_count' => $businessData['market_analysis'] ? 1 : 0,
                'products_services_count' => count($businessData['products_services']),
                'marketing_strategies_count' => count($businessData['marketing_strategies']),
                'operational_plans_count' => count($businessData['operational_plans']),
                'team_structures_count' => count($businessData['team_structures']),
                'financial_plans_count' => count($businessData['financial_plans'])
            ]);

            // Jika preview mode, return data saja
            if ($request->has('preview') && $request->preview) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'preview_data' => $businessData,
                        'filename' => "business-plan-" . Str::slug($businessData['business_background']->name) . "-" . now()->format('Y-m-d') . ".pdf",
                        'mode' => $mode
                    ],
                    'message' => 'Preview data generated successfully'
                ]);
            }

            // Generate PDF
            $pdf = PDF::loadView('pdf.business-plan', [
                'data' => $businessData,
                'mode' => $mode,
                'executiveSummary' => $executiveSummary,
                'charts' => $charts,
                'workflows' => $workflows,
                'generated_at' => now()->format('d F Y H:i:s')
            ]);

            // Konfigurasi PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'chroot' => [public_path(), storage_path()],
                'enable_php' => true,
            ]);

            $businessName = Str::slug($businessData['business_background']->name);
            $filename = "business-plan-{$businessName}-" . now()->format('Y-m-d') . ".pdf";

            // Encode PDF ke base64
            $pdfContent = $pdf->output();
            $pdfBase64 = base64_encode($pdfContent);

            Log::info('PDF Generated Successfully', [
                'filename' => $filename,
                'file_size' => strlen($pdfContent),
                'base64_size' => strlen($pdfBase64)
            ]);

            // Return sebagai JSON dengan base64 PDF
            return response()->json([
                'status' => 'success',
                'data' => [
                    'filename' => $filename,
                    'pdf_base64' => $pdfBase64,
                    'file_size' => strlen($pdfContent),
                    'mime_type' => 'application/pdf'
                ],
                'message' => 'PDF generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id ?? 'null'
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all business plan data dengan error handling
     */
    private function getBusinessPlanData($userId, $businessBackgroundId)
    {
        try {
            $businessBackground = BusinessBackground::with('user')
                ->where('user_id', $userId)
                ->where('id', $businessBackgroundId)
                ->first();

            // Jika business background tidak ditemukan, return empty array
            if (!$businessBackground) {
                return ['business_background' => null];
            }

            return [
                'business_background' => $businessBackground,

                'market_analysis' => MarketAnalysis::with(['businessBackground', 'competitors'])
                    ->where('user_id', $userId)
                    ->where('business_background_id', $businessBackgroundId)
                    ->first(),

                'products_services' => ProductService::with(['businessBackground', 'user'])
                    ->where('user_id', $userId)
                    ->where('business_background_id', $businessBackgroundId)
                    ->get(),

                'marketing_strategies' => MarketingStrategy::with('businessBackground')
                    ->where('user_id', $userId)
                    ->where('business_background_id', $businessBackgroundId)
                    ->get(),

                'operational_plans' => OperationalPlan::with(['businessBackground', 'user'])
                    ->where('user_id', $userId)
                    ->where('business_background_id', $businessBackgroundId)
                    ->get(),

                'team_structures' => TeamStructure::with(['businessBackground', 'user', 'operationalPlan'])
                    ->where('user_id', $userId)
                    ->where('business_background_id', $businessBackgroundId)
                    ->orderBy('sort_order')
                    ->get(),

                'financial_plans' => FinancialPlan::with(['businessBackground', 'user'])
                    ->where('user_id', $userId)
                    ->where('business_background_id', $businessBackgroundId)
                    ->get()
            ];
        } catch (\Exception $e) {
            Log::error('Error getting business plan data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate Executive Summary
     */
    public function generateExecutiveSummary(Request $request)
    {
        try {
            $userId = Auth::id();
            $businessBackgroundId = $request->business_background_id;

            if (!$businessBackgroundId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background ID is required'
                ], 422);
            }

            $businessData = $this->getBusinessPlanData($userId, $businessBackgroundId);

            if (!$businessData['business_background']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background not found'
                ], 404);
            }

            // Generate executive summary text
            $executiveSummary = $this->createExecutiveSummary($businessData);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'executive_summary' => $executiveSummary,
                    'business_name' => $businessData['business_background']->name
                ],
                'message' => 'Executive summary generated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating executive summary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate executive summary'
            ], 500);
        }
    }

    /**
     * Create executive summary from business data
     */
    private function createExecutiveSummary($businessData)
    {
        $business = $businessData['business_background'];
        $marketAnalysis = $businessData['market_analysis'];
        $financialPlan = $businessData['financial_plans']->first();

        $summary = "RINGKASAN EKSEKUTIF\n\n";
        $summary .= "{$business->name} adalah bisnis dalam kategori {$business->category} ";
        $summary .= "yang berfokus pada {$business->description}.\n\n";

        if ($marketAnalysis) {
            $summary .= "Bisnis ini menargetkan pasar: " . ($marketAnalysis->target_market ?? 'Belum ditentukan') . ". ";

            if ($marketAnalysis->tam_total) {
                $summary .= "Dengan analisis pasar yang menunjukkan Total Addressable Market (TAM) sebesar Rp " . number_format($marketAnalysis->tam_total, 0, ',', '.');
            }

            if ($marketAnalysis->competitive_advantage) {
                $summary .= " Keunggulan kompetitif utama: {$marketAnalysis->competitive_advantage}.\n\n";
            }
        }

        if ($financialPlan) {
            $summary .= "Dari aspek keuangan, bisnis ini memproyeksikan pendapatan bulanan sebesar Rp " .
                number_format($financialPlan->total_monthly_income ?? 0, 0, ',', '.') .
                " dengan ROI " . ($financialPlan->roi_percentage ?? 0) . "%.\n\n";
        }

        if ($business->vision) {
            $summary .= "Visi: {$business->vision}\n\n";
        }

        if ($business->mission) {
            $summary .= "Misi: {$business->mission}\n\n";
        }

        $summary .= "Dokumen business plan ini menyajikan analisis komprehensif mengenai strategi, operasional, ";
        $summary .= "dan rencana keuangan untuk mewujudkan visi bisnis tersebut.";

        return $summary;
    }

    /**
     * Get PDF statistics and history
     */
    public function getPdfStatistics(Request $request)
    {
        try {
            $userId = Auth::id();

            // Hitung jumlah business plan yang sudah dibuat PDF
            $businessBackgrounds = BusinessBackground::where('user_id', $userId)->count();
            $recentPlans = BusinessBackground::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(['id', 'name', 'updated_at']);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_business_plans' => $businessBackgrounds,
                    'recent_plans' => $recentPlans,
                    'pdf_usage' => [
                        'generated_today' => 0,
                        'generated_this_month' => 0
                    ]
                ],
                'message' => 'PDF statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting PDF statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get PDF statistics'
            ], 500);
        }
    }

    /**
     * Generate workflow diagrams for operational plans
     */
    private function generateWorkflowDiagrams($operationalPlans)
    {
        $workflowService = new WorkflowDiagramService();
        $workflows = [];

        foreach ($operationalPlans as $plan) {
            if ($plan->workflow_diagram) {
                $diagram = is_string($plan->workflow_diagram)
                    ? json_decode($plan->workflow_diagram, true)
                    : $plan->workflow_diagram;

                $image = $workflowService->generateWorkflowSvg($diagram);
                if ($image) {
                    $workflows[$plan->id] = $image;
                }
            }
        }

        return $workflows;
    }
}
