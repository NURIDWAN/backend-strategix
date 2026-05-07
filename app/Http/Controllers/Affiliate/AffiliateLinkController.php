<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateLink;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AffiliateLinkController extends Controller
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Get or create affiliate link for authenticated user
     */
    public function getMyLink(Request $request)
    {
        $user = $request->user();

        $affiliateLink = AffiliateLink::where('user_id', $user->id)->first();

        if (!$affiliateLink) {
            $slug = $this->affiliateService->generateInitialSlug($user);
            $affiliateLink = AffiliateLink::create([
                'user_id' => $user->id,
                'slug' => $slug,
                'original_slug' => $slug,
                'is_custom' => false,
                'change_count' => 0,
                'max_changes' => 999,
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $affiliateLink->id,
                'slug' => $affiliateLink->slug,
                'original_slug' => $affiliateLink->original_slug,
                'is_custom' => $affiliateLink->is_custom,
                'change_count' => $affiliateLink->change_count,
                'max_changes' => $affiliateLink->max_changes,
                'remaining_changes' => $affiliateLink->max_changes - $affiliateLink->change_count,
                'is_active' => $affiliateLink->is_active,
                'full_url' => $affiliateLink->full_url,

                'created_at' => $affiliateLink->created_at,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Update affiliate slug
     */
    public function updateSlug(Request $request)
    {
        $request->validate([
            'new_slug' => 'required|string|min:3|max:50',
        ]);

        $user = $request->user();
        $affiliateLink = AffiliateLink::where('user_id', $user->id)->firstOrFail();

        $result = $this->affiliateService->updateSlug($affiliateLink, $request->input('new_slug'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'slug' => $result['slug'],
                'is_custom' => true,
                'change_count' => $affiliateLink->change_count,
                'remaining_changes' => $result['remaining_changes'],
                'full_url' => $affiliateLink->full_url,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get affiliate link details
     */
    public function show(Request $request, AffiliateLink $affiliateLink)
    {
        $user = $request->user();
        if ($affiliateLink->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $affiliateLink->id,
                'slug' => $affiliateLink->slug,
                'original_slug' => $affiliateLink->original_slug,
                'is_custom' => $affiliateLink->is_custom,
                'change_count' => $affiliateLink->change_count,
                'max_changes' => $affiliateLink->max_changes,
                'remaining_changes' => $affiliateLink->max_changes - $affiliateLink->change_count,
                'is_active' => $affiliateLink->is_active,
                'full_url' => $affiliateLink->full_url,

                'created_at' => $affiliateLink->created_at,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Toggle affiliate link active status
     */
    public function toggleActive(Request $request, AffiliateLink $affiliateLink)
    {
        $user = $request->user();
        if ($affiliateLink->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], Response::HTTP_FORBIDDEN);
        }

        $affiliateLink->update([
            'is_active' => !$affiliateLink->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => $affiliateLink->is_active ? 'Link affiliate diaktifkan.' : 'Link affiliate dinonaktifkan.',
            'data' => [
                'is_active' => $affiliateLink->is_active,
            ],
        ], Response::HTTP_OK);
    }
}
