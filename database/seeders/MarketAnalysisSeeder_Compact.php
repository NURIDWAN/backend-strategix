<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketAnalysis;

class MarketAnalysisSeeder extends Seeder
{
    public function run(): void
    {
        $analysis = MarketAnalysis::create([
            'user_id' => 1,
            'business_background_id' => 1,

            'target_market' => 'Mahasiswa (70%), Young Professionals (20%), Komunitas & Event Organizers (10%)',

            'market_size' => 'Market size Jember: ±68.000 potential customers. TAM: Rp 6.68 Miliar/tahun. Heavy users 6K, Medium users 9K, Light users 15K orang.',

            'market_trends' => 'Coffee consumption tumbuh 8%/tahun. Specialty coffee 15%/tahun. Gen Z prefer sustainable brands. Work-from-cafe trend. Study cafe popularity meningkat. Digital transformation & cashless payment. Single-use plastic reduction movement.',

            'main_competitors' => '1. Kopi Kenangan (national chain) 2. Janji Jiwa (franchise) 3. Fore Coffee (tech-enabled) 4. Kopi Tuku (regional) 5-7. Local shops: Kopi Corner, Study Hub, Cafe Anak Kampus 8. Indirect: Bubble tea shops',

            'competitor_strengths' => 'National chains: Brand kuat, marketing budget besar, standardized SOP, economies of scale. Local shops: Community connection, agility, lower cost, unique positioning.',

            'competitor_weaknesses' => 'National chains: High price (20-30% mahal), limited space, impersonal service, inconsistent quality. Local shops: Limited resources, weak branding, scalability issues, operational inconsistencies.',

            'competitive_advantage' => 'Quality-price balance 15-20% lebih murah. Perfect third place (WiFi 100Mbps, spacious, long-stay friendly). Community-first approach. 100% local authenticity. Sustainability leadership. Service excellence. Versatile space. Technology-integrated.',

            'tam_total' => 6680000000,
            'sam_percentage' => 35.00,
            'sam_total' => 2338000000,
            'som_percentage' => 8.50,
            'som_total' => 198730000,

            'strengths' => 'Premium specialty coffee. Strategic location (50m dari UNEJ). Spacious modern cafe (100m2). Experienced team. Competitive pricing. Growing loyal customer base. Good margins (60%+).',

            'weaknesses' => 'New brand (1.5 tahun). Limited marketing budget. Single location = high risk. Limited production capacity. Basic tech/POS. No mobile app. Limited delivery presence.',

            'opportunities' => 'Market growth (8% per year). Specialty segment (15% growth). Student population increasing. Tech enablement. Corporate catering. Product expansion (beans retail, training). Sustainability movement. Franchise potential.',

            'threats' => 'Aggressive national chains. Price wars. Staff turnover. Coffee price volatility. Economic recession. Regulatory changes. Competitor copying. Climate change. Supply chain disruption.',
        ]);
    }
}
