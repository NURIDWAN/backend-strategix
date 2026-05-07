<?php

namespace App\Services;

use App\Models\Affiliate\AffiliateLink;
use App\Models\Affiliate\AffiliateTrack;
use App\Models\Affiliate\AffiliateLead;
use Illuminate\Support\Str;
use JAsobczak\Agent\Agent;

class AffiliateService
{
    /**
     * Generate initial slug from user
     */
    public function generateInitialSlug($user): string
    {
        $baseSlug = Str::slug($user->name ?? 'user-' . $user->id);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure uniqueness
        while (AffiliateLink::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Validate slug format
     */
    public function validateSlugFormat($slug): bool
    {
        // Only letters, numbers, and hyphens
        // Must be 3-50 characters
        return preg_match('/^[a-z0-9][a-z0-9-]{2,48}[a-z0-9]$/', $slug) === 1;
    }

    /**
     * Check if slug is unique (excluding current affiliate link if provided)
     */
    public function isSlugUnique($slug, $excludeId = null): bool
    {
        $query = AffiliateLink::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Update affiliate slug with validation
     */
    public function updateSlug($affiliateLink, $newSlug): array
    {
        // Validate format
        if (!$this->validateSlugFormat($newSlug)) {
            return [
                'success' => false,
                'message' => 'Format slug tidak valid. Gunakan huruf, angka, dan strip (3-50 karakter).',
            ];
        }

        // Check uniqueness
        if (!$this->isSlugUnique($newSlug, $affiliateLink->id)) {
            return [
                'success' => false,
                'message' => 'Slug sudah digunakan oleh user lain.',
            ];
        }

        // Update slug
        $affiliateLink->update([
            'slug' => $newSlug,
            'is_custom' => true,
            'change_count' => $affiliateLink->change_count + 1,
        ]);

        return [
            'success' => true,
            'message' => 'Slug berhasil diubah.',
            'slug' => $newSlug,
            'remaining_changes' => $affiliateLink->max_changes - $affiliateLink->change_count,
        ];
    }

    /**
     * Track affiliate click
     */
    public function trackClick($affiliateLink, $request): AffiliateTrack
    {
        $agent = $this->parseUserAgent($request->userAgent() ?? '');

        return AffiliateTrack::create([
            'affiliate_link_id' => $affiliateLink->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $agent['device'],
            'browser' => $agent['browser'],
            'os' => $agent['os'],
            'referrer' => $request->header('referer'),
            'tracked_at' => now(),
        ]);
    }

    /**
     * Create affiliate lead
     */
    public function createLead($affiliateLink, $data, $request): AffiliateLead
    {
        $agent = $this->parseUserAgent($request->userAgent() ?? '');

        return AffiliateLead::create([
            'affiliate_link_id' => $affiliateLink->id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'interest' => $data['interest'] ?? null,
            'notes' => $data['notes'] ?? null,
            'device_type' => $agent['device'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => AffiliateLead::STATUS_NEW,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Parse user agent string to extract device, browser, OS
     */
    private function parseUserAgent($userAgent): array
    {
        // Simple parsing - you can use a library like jenssegers/agent for more accuracy
        $device = $this->detectDevice($userAgent);
        $browser = $this->detectBrowser($userAgent);
        $os = $this->detectOS($userAgent);

        return [
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * Detect device type from user agent
     */
    private function detectDevice($userAgent): string
    {
        if (preg_match('/mobile|android|iphone|ipod|windows phone/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad|android/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Detect browser from user agent
     */
    private function detectBrowser($userAgent): string
    {
        if (preg_match('/chrome/i', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/edge|edg\//i', $userAgent)) {
            return 'Edge';
        } elseif (preg_match('/opera|opr\//i', $userAgent)) {
            return 'Opera';
        }
        return 'Unknown';
    }

    /**
     * Detect OS from user agent
     */
    private function detectOS($userAgent): string
    {
        if (preg_match('/windows nt/i', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/macintosh|mac os/i', $userAgent)) {
            return 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        } elseif (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        return 'Unknown';
    }

    /**
     * Get affiliate statistics
     */
    public function getStatistics($affiliateLink): array
    {
        $totalClicks = $affiliateLink->tracks()->count();
        $totalLeads = $affiliateLink->leads()->count();
        $conversionRate = $totalClicks > 0 ? round(($totalLeads / $totalClicks) * 100, 2) : 0;

        $clicksPerMonth = $affiliateLink->tracks()
            ->selectRaw('DATE_FORMAT(tracked_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE_FORMAT(tracked_at, "%Y-%m")'))
            ->orderBy('month', 'desc')
            ->limit(12)
            ->pluck('count', 'month')
            ->toArray();

        // Device breakdown
        $deviceBreakdown = $affiliateLink->tracks()
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        return [
            'total_clicks' => $totalClicks,
            'total_leads' => $totalLeads,
            'conversion_rate' => $conversionRate,
            'clicks_per_month' => $clicksPerMonth,
            'device_breakdown' => $deviceBreakdown,
        ];
    }
}
