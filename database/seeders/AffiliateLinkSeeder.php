<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Affiliate\AffiliateLink;
use App\Models\User;

class AffiliateLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat affiliate link untuk user yang ada.
     * Slug awalnya berupa random + username.
     */
    public function run(): void
    {
        $user = User::find(1);

        if ($user) {
            // Generate initial slug: random + username
            $initialSlug = strtolower(substr(md5(uniqid()), 0, 8)) . '-' . $user->username;

            AffiliateLink::create([
                'user_id' => $user->id,
                'slug' => $initialSlug,
                'original_slug' => $initialSlug,
                'is_custom' => false,
                'change_count' => 0,
                'max_changes' => 999, // Unlimited changes
                'is_active' => true,
            ]);
        }
    }
}
