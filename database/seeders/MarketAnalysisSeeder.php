<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketAnalysis;

class MarketAnalysisSeeder extends Seeder
{
    public function run(): void
    {
        MarketAnalysis::create([
            'user_id' => 1,
            'business_background_id' => 1,

            'target_market' => 'Primary 70%: Students (18-24) & grad students (24-28) seeking study space, Rp 30-80K/visit. Secondary 20%: Young professionals & freelancers, Rp 80-150K. Tertiary 10%: Dosen & community.',

            'market_size' => 'Primary market 0-1km: 42K population (UNEJ 35K students, residential 5K, office 2K). Secondary 1-3km: 26K (schools 8K, homes 15K, office 3K). Total addressable: 68K. Potential coffee drinkers: 30K.',

            'market_trends' => 'Indonesia coffee +8% annually. Specialty coffee +15% YoY. Work-from-cafe trend, study cafe boom. Gen Z: 60% prefer sustainable brands. Digital payment: 70% adoption. Social media: 85% influence purchase.',

            'main_competitors' => 'Kopi Kenangan 25% (Rp 1.2B). Janji Jiwa 20% (Rp 1B). Fore Coffee 10% (Rp 500M). Kopi Tuku 8% (Rp 400M). Local shops 20%. Indirect: warung kopi, fast food, convenience stores.',

            'competitor_strengths' => 'Chains: National brand, consistent quality, SOPs, capital, marketing power. Locals: Community bonds, agility, unique concepts, low costs. Our advantage: Quality + affordable price + perfect study space.',

            'competitor_weaknesses' => 'Chains: Impersonal, 20-30% price premium, small spaces, inconsistent. Locals: Limited resources, weak brands, operation issues, hard to scale. Common: Poor food options, weak loyalty, no sustainability.',

            'competitive_advantage' => '1) 15-20% cheaper premium coffee 2) Perfect third-place (100sqm, 40+ seats, fast WiFi, power) 3) Community-first (events, loyalty) 4) 100% local fair-trade beans 5) Zero-plastic sustainability 6) Consistent quality 7) Instagram venue 8) Tech integration (QR, mobile order)',

            'tam_total' => 6680000000,
            'sam_percentage' => 35.00,
            'sam_total' => 2338000000,
            'som_percentage' => 8.50,
            'som_total' => 198730000,

            'strengths' => 'Premium coffee quality. Perfect location near UNEJ. 100sqm modern facility, 40+ seats. Experienced barista team. Competitive pricing, 60%+ margin. Growing customer base, 4.8/5 reviews.',

            'weaknesses' => 'New brand (1.5 yrs). Limited marketing budget. Single location risk. Limited capital. No mobile app. Limited suppliers. Geographic walk-in only.',

            'opportunities' => 'Coffee market +8-15% annually. Work-from-cafe trend. Delivery platforms. Student partnerships. Expand products: retail beans, training. Franchise opportunities.',

            'threats' => 'Chain expansion, price wars, coffee volatility, consumer shifts, rising costs, wages, regulations, equipment breakdown, rental increases, negative reviews.',
        ]);
    }
}
