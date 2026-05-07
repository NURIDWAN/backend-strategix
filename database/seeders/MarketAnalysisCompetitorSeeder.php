<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketAnalysis;
use App\Models\MarketAnalysisCompetitor;

class MarketAnalysisCompetitorSeeder extends Seeder
{
    public function run(): void
    {
        $analysis = MarketAnalysis::first(); // Ambil market analysis pertama (ID = 1)

        // OWNSHOP
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Kedai Kopi Pandu',
            'type' => 'ownshop',
            'code' => 'OWN-01',
            'address' => 'Ruko Kampus, Jalan Kalimantan No. 45, Tegalboto, Jember',
            'annual_sales_estimate' => 198730000, // Sesuai dengan SOM calculation
            'selling_price' => 18000,
            'strengths' => 'KEUNGGULAN KOMPETITIF:\n\n• Quality-Price Balance: Premium specialty coffee dengan harga 15-20% lebih murah dari national chains\n• Perfect Third Place: Space design yang ideal untuk study, work, dan socialize\n• Community Integration: Deep connection dengan student community melalui regular events dan partnerships\n• Sustainability Leadership: First zero single-use plastic coffee shop di Jember\n• Service Excellence: Trained barista, friendly staff, consistent quality\n• Versatile Space: Meeting room, event hosting capability, multiple ambiance zones\n• Local Authenticity: 100% Indonesian beans, fair trade dengan petani lokal\n• Student-Centric: Long sitting tolerance, fast WiFi, affordable pricing',
            'weaknesses' => 'AREA PENGEMBANGAN:\n\n• Brand Awareness: Masih relatif baru (1.5 tahun), competing dengan established brands\n• Financial Resources: Limited working capital, tidak bisa aggressive expansion\n• Single Location: High business risk, no backup facility\n• Technology: No proprietary app, basic POS system, manual inventory\n• Marketing Budget: Limited dibanding national chains\n• Supply Chain: Limited supplier options, vulnerability to price fluctuation\n• Market Reach: Primarily walk-in customers, limited delivery presence',
            'sort_order' => 0,
        ]);

        // NATIONAL CHAIN COMPETITORS

        // Competitor 1: Kopi Kenangan (Market Leader)
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Kopi Kenangan',
            'type' => 'competitor',
            'code' => 'COMP-01',
            'address' => 'Roxy Square Jember, Jl. Gajah Mada No. 201',
            'annual_sales_estimate' => 1200000000, // Rp 1.2 Miliar per tahun
            'selling_price' => 22000,
            'strengths' => 'KEKUATAN:\n\n• Brand Power: National brand dengan massive awareness, unicorn startup status\n• Marketing Budget: Large-scale advertising campaigns, celebrity endorsements\n• Technology: Sophisticated mobile app dengan loyalty program\n• Operational Excellence: Standardized processes, efficient supply chain\n• Economies of Scale: Lower COGS karena bulk purchasing\n• Multiple Locations: 2 outlets di Jember (Roxy Square, Lippo Plaza)\n• Strong Social Media: 100K+ Instagram followers\n• Consistent Product: Standardized recipes across outlets\n• Fast Service: Optimized untuk high-volume takeaway',
            'weaknesses' => 'KELEMAHAN:\n\n• Price Premium: 25-30% lebih mahal dari local competitors\n• Limited Seating: Small footprint, focus on takeaway, tidak ideal untuk study sessions\n• Impersonal Service: High staff turnover, transactional interactions\n• Space Constraints: No meeting rooms, cramped during peak hours\n• One-Size-Fits-All: Tidak customized untuk local preferences\n• Queue Issues: Long waiting time during peak hours\n• Quality Variance: Inconsistency across locations dan shifts',
            'sort_order' => 1,
        ]);

        // Competitor 2: Janji Jiwa
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Janji Jiwa',
            'type' => 'competitor',
            'code' => 'COMP-02',
            'address' => 'Jl. Kalimantan No. 88, dekat kampus UNEJ (3 lokasi)',
            'annual_sales_estimate' => 1000000000, // Rp 1 Miliar per tahun (3 outlets combined)
            'selling_price' => 18000,
            'strengths' => 'KEKUATAN:\n\n• Wide Franchise Network: 3 outlets di strategic locations\n• Affordable Pricing: Competitive price point untuk students\n• Menu Variety: Extensive menu dengan seasonal specials\n• Brand Recognition: Well-known national brand\n• Promo Frequency: Regular discounts dan bundle offers\n• Delivery Presence: Available di semua major platforms\n• Strategic Locations: High foot traffic areas\n• Proven Business Model: Successful franchise system',
            'weaknesses' => 'KELEMAHAN:\n\n• Quality Inconsistency: Taste varies by location, barista skill level tidak uniform\n• Cramped Space: Limited seating, primarily takeaway focus\n• Service Speed: Slow during peak hours, inefficient workflow\n• No Study-Friendly Environment: Not designed for long stays\n• Generic Ambiance: Cookie-cutter interior design\n• Limited Community Engagement: Transactional relationship dengan customers\n• Staff Training: Basic training, high turnover\n• No Premium Options: Focus on volume over quality',
            'sort_order' => 2,
        ]);

        // Competitor 3: Fore Coffee
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Fore Coffee',
            'type' => 'competitor',
            'code' => 'COMP-03',
            'address' => 'Lippo Plaza Jember, Lt. 1',
            'annual_sales_estimate' => 500000000, // Rp 500 Juta per tahun
            'selling_price' => 25000,
            'strengths' => 'KEKUATAN:\n\n• Tech-First Approach: Advanced mobile app dengan AI recommendations\n• Premium Positioning: High-quality specialty coffee\n• Modern Branding: Sleek design, Instagram-worthy\n• Cashless Only: Efficient, data-driven operations\n• Quality Consistency: Strict quality control standards\n• Innovative Menu: Unique signature drinks\n• Delivery Focus: Strong online presence',
            'weaknesses' => 'KELEMAHAN:\n\n• High Price Point: Premium pricing alienates budget-conscious students\n• Limited Seating: Very small space, mostly takeaway\n• Single Location: Only 1 outlet, limited accessibility\n• No Cash Payment: Barrier untuk some customer segments\n• Not Student-Friendly: No study amenities, discourages long stays\n• Corporate Feel: Lacks warmth dan personal touch\n• New Market: Still building brand awareness di Jember',
            'sort_order' => 3,
        ]);

        // Competitor 4: Kopi Tuku
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Kopi Tuku',
            'type' => 'competitor',
            'code' => 'COMP-04',
            'address' => 'Jl. Letjen Suprapto (dekat Alun-alun)',
            'annual_sales_estimate' => 400000000, // Rp 400 Juta per tahun
            'selling_price' => 20000,
            'strengths' => 'KEKUATAN:\n\n• Signature Products: Famous untuk Es Kopi Susu Tetangga\n• Premium Ingredients: High-quality dairy dan coffee\n• Strong Brand Identity: Distinctive branding\n• Social Media Presence: Active Instagram marketing\n• Consistent Quality: Well-trained barista\n• Loyal Following: Cult-like fanbase',
            'weaknesses' => 'KELEMAHAN:\n\n• Limited Menu: Focused on few signature items\n• Small Space: Minimal seating, mostly takeaway\n• Single Location: Accessibility limited\n• No Food Options: Only beverages\n• High Price: Not budget-friendly untuk daily consumption\n• Location: Not close to campus',
            'sort_order' => 4,
        ]);

        // LOCAL INDEPENDENT COMPETITORS

        // Competitor 5: Kopi Corner
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Kopi Corner',
            'type' => 'competitor',
            'code' => 'COMP-05',
            'address' => 'Jl. Kalimantan, seberang UNEJ',
            'annual_sales_estimate' => 180000000,
            'selling_price' => 12000,
            'strengths' => 'Lowest price, strategic location, fast service, local favorite',
            'weaknesses' => 'Basic quality, limited space, no WiFi, cash only',
            'sort_order' => 5,
        ]);

        // Competitor 6: Study Hub Café
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Study Hub Café',
            'type' => 'competitor',
            'code' => 'COMP-06',
            'address' => 'Jl. Mastrip, kompleks ruko mahasiswa',
            'annual_sales_estimate' => 240000000,
            'selling_price' => 15000,
            'strengths' => 'Study-focused design, good WiFi, affordable pricing, long hours, food options available',
            'weaknesses' => 'Average coffee quality, worn out interior, inconsistent service, weak online marketing',
            'sort_order' => 6,
        ]);

        // Competitor 7: Café Anak Kampus
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Café Anak Kampus',
            'type' => 'competitor',
            'code' => 'COMP-07',
            'address' => 'Jl. Kalimantan Gang 5',
            'annual_sales_estimate' => 150000000,
            'selling_price' => 10000,
            'strengths' => 'Budget-friendly, generous portions, casual vibe, no time limit, student discounts',
            'weaknesses' => 'Poor coffee quality, dated interior, limited space, inconsistent cleanliness, weak WiFi',
            'sort_order' => 7,
        ]);

        // INDIRECT COMPETITORS

        // Competitor 8: Xing Fu Tang (Bubble Tea)
        MarketAnalysisCompetitor::create([
            'market_analysis_id' => $analysis->id,
            'competitor_name' => 'Xing Fu Tang',
            'type' => 'competitor',
            'code' => 'COMP-08',
            'address' => 'Mall Jember, Lt. Ground',
            'annual_sales_estimate' => 600000000,
            'selling_price' => 28000,
            'strengths' => 'Trendy product, premium quality, Instagram-worthy, strong brand, high foot traffic mall location',
            'weaknesses' => 'Different category, high price point, limited seating, not accessible for daily campus visit',
            'sort_order' => 8,
        ]);
    }
}
