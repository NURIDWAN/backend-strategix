<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\BusinessPlan\BusinessController;
use App\Http\Controllers\BusinessPlan\MarketAnalysisController;
use App\Http\Controllers\BusinessPlan\ProductServiceController;
use App\Http\Controllers\BusinessPlan\MarketingStrategyController;
use App\Http\Controllers\BusinessPlan\OperationalPlanController;
use App\Http\Controllers\BusinessPlan\TeamStructureController;
// TODO: Comment - FinancialPlan nonaktif di Business Plan
// use App\Http\Controllers\BusinessPlan\FinancialPlanController;
use App\Http\Controllers\BusinessPlan\PdfBusinessPlanController;
use App\Http\Controllers\ManagementFinancial\ManagementFinancialController;
use App\Http\Controllers\ManagementFinancial\FinancialCategoryController;
use App\Http\Controllers\ManagementFinancial\FinancialSimulationController;
use App\Http\Controllers\ManagementFinancial\FinancialSummaryController;
use App\Http\Controllers\ManagementFinancial\PdfFinancialReportController;
use App\Http\Controllers\Forecast\ForecastDataController;
use App\Http\Controllers\Forecast\ForecastResultController;
use App\Http\Controllers\Forecast\PdfForecastController;
use App\Http\Controllers\Affiliate\AffiliateLinkController;

use App\Http\Controllers\Affiliate\AffiliateCommissionController;
use App\Http\Controllers\Affiliate\AffiliateWithdrawalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Singapay\PdfPaymentController;
use App\Http\Controllers\Singapay\WebhookController;
use App\Http\Controllers\Article\ArticleController;
use App\Http\Controllers\Article\ArticleCategoryController;
use App\Http\Controllers\Article\ArticleImageController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminAffiliateController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSeoController;
use App\Http\Controllers\Consultation\MemberConsultationController;
use App\Http\Controllers\Consultation\ConsultantDashboardController;
use App\Http\Controllers\Admin\AdminConsultationController;

// =====================================
// CORS preflight untuk semua route
// =====================================
Route::options('{any}', function () {
    $allowedOrigins = [
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:3000',
    ];

    $origin = request()->header('Origin');
    $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];

    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', $allowOrigin)
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin')
        ->header('Access-Control-Allow-Credentials', 'true');
})->where('any', '.*');

// =====================================
// Auth routes (PUBLIC)
// =====================================
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/register-consultant', 'registerConsultant');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/resend-otp', 'resendOtp');
    Route::post('/login', 'login');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/verify-reset-otp', 'verifyResetOtp');
    Route::post('/reset-password', 'resetPassword');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// =====================================
// Public Payment & Package Info
// =====================================
Route::middleware(['cors'])->group(function () {
    Route::get('/payment/packages', [PdfPaymentController::class, 'packages']);
});



// =====================================
// Protected routes (ALL AUTHENTICATED)
// =====================================
Route::middleware(['auth:sanctum', 'account.active', 'cors'])->group(function () {

    // User session
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Feature flags (for frontend feature gating)
    Route::get('/feature-flags', function (\Illuminate\Http\Request $request) {
        $packageFeatures = [];
        $user = $request->user('sanctum');

        // Check if user has active Pro access and get their package features
        if ($user && $user->hasPdfProAccess()) {
            $activePurchase = $user->pdfPurchases()->active()->first();
            if ($activePurchase && $activePurchase->premiumPdf) {
                $packageFeatures = $activePurchase->premiumPdf->features ?? [];
            } else {
                // Fallback for manual grant
                $package = \App\Models\Singapay\PremiumPdf::active()->where('package_type', $user->pdf_access_package)->first();
                if ($package) {
                    $packageFeatures = $package->features ?? [];
                }
            }
            
            // Normalize JSON output which could be null or empty string
            if (!is_array($packageFeatures)) {
                $packageFeatures = [];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                // System gated features: Enabled if globally enabled OR explicitly granted by user's active package
                'feature_forecast' => \App\Models\Setting::getValue('feature_forecast', false) || in_array('feature_forecast', $packageFeatures),
                'feature_pdf_export' => \App\Models\Setting::getValue('feature_pdf_export', false) || in_array('feature_pdf_export', $packageFeatures),
                
                // General features
                'feature_articles' => \App\Models\Setting::getValue('feature_articles', true),
                'registration_enabled' => \App\Models\Setting::getValue('registration_enabled', true),
            ],
        ]);
    });

    // Business Background
    Route::prefix('business-background')->group(function () {
        Route::post('/', [BusinessController::class, 'store']);
        Route::post('/{id}', [BusinessController::class, 'update']); // For FormData with _method=PUT
        Route::get('/', [BusinessController::class, 'index']);
        Route::get('/{id}', [BusinessController::class, 'show']);
        Route::put('/{id}', [BusinessController::class, 'update']);
        Route::delete('/{id}', [BusinessController::class, 'destroy']);
    });

    // Market Analysis
    Route::prefix('market-analysis')->group(function () {
        Route::get('/', [MarketAnalysisController::class, 'index']);
        Route::get('/{id}', [MarketAnalysisController::class, 'show']);
        Route::post('/', [MarketAnalysisController::class, 'store']);
        Route::put('/{id}', [MarketAnalysisController::class, 'update']);
        Route::delete('/{id}', [MarketAnalysisController::class, 'destroy']);
        Route::post('/calculate-market-size', [MarketAnalysisController::class, 'calculateMarketSize']);
    });

    // Product Service
    Route::prefix('product-service')->group(function () {
        Route::get('/', [ProductServiceController::class, 'index']);
        Route::get('/{id}', [ProductServiceController::class, 'show']);
        Route::post('/', [ProductServiceController::class, 'store']);
        Route::post('/{id}', [ProductServiceController::class, 'update']);
        Route::put('/{id}', [ProductServiceController::class, 'update']);
        Route::delete('/{id}', [ProductServiceController::class, 'destroy']);
        Route::post('/{id}/generate-bmc-alignment', [ProductServiceController::class, 'generateBmcAlignment']);
        Route::get('/statistics/overview', [ProductServiceController::class, 'getStatistics']);
    });

    // Marketing Strategy
    Route::prefix('marketing-strategy')->group(function () {
        Route::get('/', [MarketingStrategyController::class, 'index']);
        Route::post('/', [MarketingStrategyController::class, 'store']);
        Route::get('/{id}', [MarketingStrategyController::class, 'show']);
        Route::put('/{id}', [MarketingStrategyController::class, 'update']);
        Route::delete('/{id}', [MarketingStrategyController::class, 'destroy']);
    });

    // Operational Plan
    Route::prefix('operational-plan')->group(function () {
        Route::get('/', [OperationalPlanController::class, 'index']);
        Route::post('/', [OperationalPlanController::class, 'store']);
        Route::get('/{id}', [OperationalPlanController::class, 'show']);
        Route::put('/{id}', [OperationalPlanController::class, 'update']);
        Route::delete('/{id}', [OperationalPlanController::class, 'destroy']);
        Route::post('/{id}/generate-workflow-diagram', [OperationalPlanController::class, 'generateWorkflowDiagram']);
        Route::post('/{id}/upload-workflow-image', [OperationalPlanController::class, 'uploadWorkflowImage']);
        Route::get('/statistics/overview', [OperationalPlanController::class, 'getStatistics']);
    });

    // Team Structure
    Route::prefix('team-structure')->group(function () {
        // Salary simulation endpoints (harus di atas route /{id})
        Route::post('/check-existing-salary', [TeamStructureController::class, 'checkExistingSalary']);
        Route::get('/salary-summary', [TeamStructureController::class, 'getSalarySummary']);
        Route::post('/generate-salary', [TeamStructureController::class, 'generateSalary']);

        // Org chart endpoints (harus di atas route /{id})
        Route::post('/upload-org-chart', [TeamStructureController::class, 'uploadOrgChart']);
        Route::delete('/org-chart/{businessBackgroundId}', [TeamStructureController::class, 'deleteOrgChart']);

        // CRUD endpoints (route dengan /{id} harus paling bawah)
        Route::get('/', [TeamStructureController::class, 'index']);
        Route::get('/{id}', [TeamStructureController::class, 'show']);
        Route::post('/', [TeamStructureController::class, 'store']);
        Route::put('/{id}', [TeamStructureController::class, 'update']);
        Route::delete('/{id}', [TeamStructureController::class, 'destroy']);
        Route::post('/{id}/upload-photo', [TeamStructureController::class, 'uploadPhoto']);
    });

    // TODO: Comment - FinancialPlan nonaktif di Business Plan
    /*
    // Financial Plan
    Route::prefix('financial-plans')->group(function () {
        Route::get('/', [FinancialPlanController::class, 'index']);
        Route::post('/', [FinancialPlanController::class, 'store']);
        Route::get('/{id}', [FinancialPlanController::class, 'show']);
        Route::put('/{id}', [FinancialPlanController::class, 'update']);
        Route::delete('/{id}', [FinancialPlanController::class, 'destroy']);

        Route::get('/summary/financial', [FinancialPlanController::class, 'getFinancialSummary']);
        Route::get('/dashboard/charts', [FinancialPlanController::class, 'getDashboardCharts']);

        Route::get('/{id}/cash-flow', [FinancialPlanController::class, 'getCashFlowSimulation']);
        Route::get('/{id}/feasibility', [FinancialPlanController::class, 'getFeasibilityAnalysis']);
        Route::get('/{id}/forecast', [FinancialPlanController::class, 'getFinancialForecast']);
        Route::get('/{id}/sensitivity', [FinancialPlanController::class, 'getSensitivityAnalysis']);

        Route::get('/{id}/report', [FinancialPlanController::class, 'generateReport']);
        Route::get('/{id}/charts', [FinancialPlanController::class, 'getChartData']);
    });
    */

    // PDF Business Plan
    Route::prefix('pdf-business-plan')->group(function () {
        Route::post('/generate', [PdfBusinessPlanController::class, 'generatePdf']);
        Route::post('/executive-summary', [PdfBusinessPlanController::class, 'generateExecutiveSummary']);
        Route::get('/statistics', [PdfBusinessPlanController::class, 'getPdfStatistics']);
    });

    // Management Financial Routes
    Route::prefix('management-financial')->group(function () {

        // Dashboard Stats
        Route::get('/dashboard-stats', [ManagementFinancialController::class, 'getDashboardStats']);

        // Monthly Reports
        Route::prefix('reports')->group(function () {
            Route::get('/monthly', [\App\Http\Controllers\ManagementFinancial\MonthlyReportController::class, 'getMonthlyReport']);
        });

        // Financial Categories Routes
        Route::prefix('categories')->group(function () {
            Route::get('/', [ManagementFinancialController::class, 'indexCategories']);
            Route::get('/summary', [ManagementFinancialController::class, 'getCategoriesSummary']);
            Route::get('/{id}', [ManagementFinancialController::class, 'showCategory']);
            Route::post('/', [ManagementFinancialController::class, 'storeCategory']);
            Route::put('/{id}', [ManagementFinancialController::class, 'updateCategory']);
            Route::delete('/{id}', [ManagementFinancialController::class, 'destroyCategory']);
        });

        // Financial Summaries Routes
        Route::prefix('summaries')->group(function () {
            Route::get('/', [FinancialSummaryController::class, 'index']);
            Route::get('/statistics', [FinancialSummaryController::class, 'getStatistics']);
            Route::get('/monthly-comparison', [FinancialSummaryController::class, 'getMonthlyComparison']);
            Route::post('/generate-from-simulations', [FinancialSummaryController::class, 'generateFromSimulations']);
            Route::get('/{id}', [FinancialSummaryController::class, 'show']);
            Route::post('/', [FinancialSummaryController::class, 'store']);
            Route::put('/{id}', [FinancialSummaryController::class, 'update']);
            Route::delete('/{id}', [FinancialSummaryController::class, 'destroy']);
        });

        // Financial Simulations Routes
        Route::prefix('simulations')->group(function () {
            Route::get('/available-years', [FinancialSimulationController::class, 'getAvailableYears']);
            Route::get('/cash-flow-summary', [FinancialSimulationController::class, 'getCashFlowSummary']);
            Route::get('/monthly-comparison', [FinancialSimulationController::class, 'getMonthlyComparison']);
            Route::get('/', [FinancialSimulationController::class, 'index']);
            Route::get('/{id}', [FinancialSimulationController::class, 'show']);
            Route::post('/', [FinancialSimulationController::class, 'store']);
            Route::put('/{id}', [FinancialSimulationController::class, 'update']);
            Route::delete('/{id}', [FinancialSimulationController::class, 'destroy']);
        });

        // Financial Projections Routes
        Route::prefix('projections')->group(function () {
            Route::get('/baseline', [\App\Http\Controllers\ManagementFinancial\FinancialProjectionController::class, 'getBaselineData']);
            Route::get('/', [\App\Http\Controllers\ManagementFinancial\FinancialProjectionController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\ManagementFinancial\FinancialProjectionController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\ManagementFinancial\FinancialProjectionController::class, 'store']);
            Route::delete('/{id}', [\App\Http\Controllers\ManagementFinancial\FinancialProjectionController::class, 'destroy']);
        });

        // Financial Report PDF Routes
        Route::prefix('pdf')->middleware('feature:feature_pdf_export')->group(function () {
            Route::post('/generate', [\App\Http\Controllers\ManagementFinancial\PdfFinancialReportController::class, 'generatePdf']);
            Route::post('/generate-combined', [\App\Http\Controllers\ManagementFinancial\CombinedPdfController::class, 'generateCombinedPdf']);
            Route::get('/statistics', [\App\Http\Controllers\ManagementFinancial\PdfFinancialReportController::class, 'getStatistics']);
        });

        // Forecast Routes
        Route::prefix('forecast')->middleware('feature:feature_forecast')->group(function () {
            Route::get('/available-years', [ForecastResultController::class, 'getAvailableYears']);
            Route::get('/simulation-years', [ForecastDataController::class, 'getAvailableSimulationYears']);
            Route::post('/import-from-simulation', [ForecastDataController::class, 'importFromFinancialSimulation']);
            Route::post('/generate-from-simulation', [ForecastDataController::class, 'generateFromSimulation']);
            Route::get('/', [ForecastDataController::class, 'index']);
            Route::post('/', [ForecastDataController::class, 'store']);
            Route::get('/{forecastData}', [ForecastDataController::class, 'show']);
            Route::put('/{forecastData}', [ForecastDataController::class, 'update']);
            Route::delete('/{forecastData}', [ForecastDataController::class, 'destroy']);

            // Generate and get results
            Route::post('/{forecastData}/generate', [ForecastResultController::class, 'generate']);
            Route::get('/{forecastData}/results', [ForecastResultController::class, 'getResults']);

            // Compare scenarios
            Route::post('/compare', [ForecastResultController::class, 'compare']);

            // PDF Export Routes
            Route::post('/{forecastData}/export-pdf', [PdfForecastController::class, 'generatePdf']);
            Route::get('/export-pdf/statistics', [PdfForecastController::class, 'getPdfStatistics']);
        });
    });

    // Affiliate Routes (Authenticated)
    Route::prefix('affiliate')->group(function () {
        Route::get('/my-link', [AffiliateLinkController::class, 'getMyLink']);
        Route::put('/slug', [AffiliateLinkController::class, 'updateSlug']);
        Route::patch('/{affiliateLink}/toggle-active', [AffiliateLinkController::class, 'toggleActive']);



        Route::prefix('commissions')->group(function () {
            Route::get('/statistics', [AffiliateCommissionController::class, 'getStatistics']);
            Route::get('/history', [AffiliateCommissionController::class, 'getHistory']);
            Route::get('/withdrawable', [AffiliateCommissionController::class, 'getWithdrawableBalance']);
        });

        // Withdrawals (outside commissions prefix)
        Route::post('/withdraw', [AffiliateWithdrawalController::class, 'withdraw']);
        Route::get('/withdrawals', [AffiliateWithdrawalController::class, 'history']);
    });

    // =====================================
    // SingaPay Payment Routes (AUTHENTICATED)
    // =====================================
    Route::prefix('payment')->group(function () {
        Route::get('/subscription', [PdfPaymentController::class, 'subscription']);
        Route::get('/check-access', [PdfPaymentController::class, 'checkAccess']);

        // Purchase & Payment
        Route::post('/purchase', [PdfPaymentController::class, 'purchase']);
        Route::get('/status/{transactionCode}', [PdfPaymentController::class, 'checkStatus']);
        Route::post('/cancel/{transactionCode}', [PdfPaymentController::class, 'cancel']);

        // Transaction History
        Route::get('/history', [PdfPaymentController::class, 'history']);
    });

    // =====================================
    // Member Consultation Routes
    // =====================================
    Route::prefix('consultation')->group(function () {
        Route::get('/consultants', [MemberConsultationController::class, 'getConsultants']);
        Route::get('/available-slots', [MemberConsultationController::class, 'getAvailableSlots']);
        Route::post('/request', [MemberConsultationController::class, 'requestSession']);
        Route::get('/my-sessions', [MemberConsultationController::class, 'mySessions']);
        Route::get('/credits', [MemberConsultationController::class, 'getCredits']);
    });

    // =====================================
    // Consultant Dashboard Routes
    // =====================================
    Route::prefix('consultant')->middleware(['consultant'])->group(function () {
        Route::get('/dashboard-stats', [ConsultantDashboardController::class, 'dashboardStats']);
        Route::get('/dashboard-overview', [ConsultantDashboardController::class, 'dashboardOverview']);
        Route::get('/upcoming-sessions', [ConsultantDashboardController::class, 'upcomingSessions']);
        Route::get('/sessions', [ConsultantDashboardController::class, 'sessions']);
        Route::get('/profile', [ConsultantDashboardController::class, 'profile']);
        Route::post('/sessions/{id}', [ConsultantDashboardController::class, 'updateSession']);
        Route::post('/availability', [ConsultantDashboardController::class, 'updateAvailability']);
    });
});

// =====================================
// Admin Panel Endpoints (AUTH + ADMIN REQUIRED)
// =====================================
Route::prefix('admin')->middleware(['auth:sanctum', 'account.active', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard-stats', [AdminController::class, 'dashboardStats']);

    // User management
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/password', [UserController::class, 'updatePassword']);
    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
    Route::put('/users/{id}/role', [AdminController::class, 'updateRole']);
    Route::put('/users/{id}/grant-pro', [AdminController::class, 'grantProAccess']);
    Route::put('/users/{id}/grant-consultation', [AdminController::class, 'grantConsultationCredit']);

    // Article management
    Route::post('/articles/upload-image', [ArticleImageController::class, 'upload']);
    Route::post('/articles/upload-gallery', [ArticleImageController::class, 'uploadGallery']);
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/articles/{id}', [ArticleController::class, 'show']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::post('/articles/{id}', [ArticleController::class, 'update']); // For FormData
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

    // Article categories management
    Route::get('/article-categories', [ArticleCategoryController::class, 'index']);
    Route::post('/article-categories', [ArticleCategoryController::class, 'store']);
    Route::put('/article-categories/{id}', [ArticleCategoryController::class, 'update']);
    Route::delete('/article-categories/{id}', [ArticleCategoryController::class, 'destroy']);

    // Payment management
    Route::get('/payments/stats', [AdminPaymentController::class, 'stats']);
    Route::get('/payments', [AdminPaymentController::class, 'index']);
    Route::get('/payments/{id}', [AdminPaymentController::class, 'show']);
    Route::put('/payments/{id}/status', [AdminPaymentController::class, 'updateStatus']);
    Route::get('/packages', [AdminPaymentController::class, 'packages']);
    Route::post('/packages', [AdminPaymentController::class, 'storePackage']);
    Route::put('/packages/{id}', [AdminPaymentController::class, 'updatePackage']);
    Route::delete('/packages/{id}', [AdminPaymentController::class, 'destroyPackage']);

    // Affiliate management
    Route::get('/affiliates/stats', [AdminAffiliateController::class, 'stats']);
    Route::get('/affiliates/commissions', [AdminAffiliateController::class, 'commissions']);
    Route::put('/affiliates/commissions/{id}/status', [AdminAffiliateController::class, 'updateCommissionStatus']);
    Route::get('/affiliates/withdrawals', [AdminAffiliateController::class, 'withdrawals']);
    Route::put('/affiliates/withdrawals/{id}/status', [AdminAffiliateController::class, 'updateWithdrawalStatus']);
    Route::get('/affiliates', [AdminAffiliateController::class, 'index']);
    Route::get('/affiliates/{userId}', [AdminAffiliateController::class, 'show']);

    // Activity logs
    Route::get('/logs/stats', [AdminLogController::class, 'stats']);
    Route::get('/logs', [AdminLogController::class, 'index']);

    // System settings
    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::put('/settings', [AdminSettingController::class, 'update']);
    Route::get('/settings/{group}', [AdminSettingController::class, 'showGroup']);

    // SEO Pages management
    Route::put('/seo-pages/{id}', [AdminSeoController::class, 'update']);

    // Consultation management
    Route::prefix('consultations')->group(function () {
        Route::get('/consultants', [AdminConsultationController::class, 'getConsultants']);
        Route::post('/assign-consultant', [AdminConsultationController::class, 'assignConsultantRole']);
        Route::get('/all-sessions', [AdminConsultationController::class, 'allSessions']);
    });
});

// =====================================
// Public SEO Routes (NO AUTH REQUIRED)
// =====================================
Route::get('/seo/{pageIdentifier}', [AdminSeoController::class, 'publicShow']);

// =====================================
// Public Article Routes (NO AUTH REQUIRED)
// =====================================
Route::prefix('articles')->middleware('feature:feature_articles')->group(function () {
    Route::get('/', [ArticleController::class, 'publicIndex']);
    Route::get('/categories', [ArticleCategoryController::class, 'index']);
    Route::get('/{slug}', [ArticleController::class, 'publicShow']);
});

// =====================================
// Public Affiliate Routes (NO AUTH REQUIRED)
// =====================================


// =====================================
// SingaPay Webhook Routes (PUBLIC - NO AUTH)
// Rate Limited: 60 requests per minute per IP
// =====================================
Route::prefix('webhook/singapay')->group(function () {
    // 🔒 Rate limiting: 60 webhooks/minute untuk mencegah DoS/spam
    Route::post('/payment', [WebhookController::class, 'handlePayment'])
        ->middleware('throttle:60,1');

    Route::post('/virtual-account', [WebhookController::class, 'handleVirtualAccount'])
        ->middleware('throttle:60,1');

    Route::post('/qris', [WebhookController::class, 'handleQris'])
        ->middleware('throttle:60,1');

    // Disbursement webhook (for withdrawal/payout status updates)
    Route::post('/disbursement', [WebhookController::class, 'handleDisbursement'])
        ->middleware('throttle:60,1');

    // Test webhook (mock mode only) - Strict rate limit
    Route::post('/test', [WebhookController::class, 'test'])
        ->middleware('throttle:10,1'); // Lebih ketat untuk testing
});

// =====================================
// Test Singapay Routes (DEBUG MODE ONLY)
// Only accessible when APP_DEBUG=true
// =====================================
Route::prefix('test/singapay')->group(function () {
    Route::get('/token', [\App\Http\Controllers\TestSingapayController::class, 'testAccessToken']);
    Route::get('/config', [\App\Http\Controllers\TestSingapayController::class, 'testConfig']);
    Route::post('/clear-cache', [\App\Http\Controllers\TestSingapayController::class, 'clearCache']);
    Route::post('/payment', [\App\Http\Controllers\TestSingapayController::class, 'createTestPayment']);
});

// =====================================
// Test Faspay Routes (DEBUG MODE ONLY)
// Only accessible when APP_DEBUG=true
// =====================================
Route::prefix('test/faspay')->group(function () {
    Route::get('/config', [\App\Http\Controllers\TestFaspayController::class, 'testConfig']);
    Route::post('/payment', [\App\Http\Controllers\TestFaspayController::class, 'createTestPayment']);
    Route::get('/status/{billNo}', [\App\Http\Controllers\TestFaspayController::class, 'checkStatus']);
});
