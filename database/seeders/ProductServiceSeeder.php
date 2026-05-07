<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductService;

class ProductServiceSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ========== COFFEE BEVERAGES - SIGNATURE SERIES ==========
            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Signature Caramel Latte',
                'description' => 'Best seller kami! Espresso double shot dari biji Arabica Ijen, dikombinasikan dengan fresh milk dan caramel sauce homemade yang creamy. Disajikan dengan latte art yang cantik. Perfect balance antara sweetness dan coffee boldness. Available hot or iced.',
                'price' => 25000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Caramel sauce dibuat sendiri tanpa artificial flavoring\n• Espresso berkualitas tinggi dari single origin Ijen\n• Consistency guaranteed dengan standard recipe\n• Instagram-worthy presentation\n• Rasa smooth, tidak terlalu manis\n• Popular choice (30% dari total coffee sales)\n• Great untuk coffee beginners dan enthusiasts',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Seasonal Variant: Salted Caramel (winter), Coconut Caramel (summer)\n2. Size Options: Small (Rp 20K), Regular (Rp 25K), Large (Rp 30K)\n3. Bundling: Caramel Latte + Pastry combo (Rp 40K)\n4. Retail Product: Bottled caramel sauce untuk dijual terpisah\n5. Caramel Latte Workshop: Monthly event untuk teach customers\n6. Instagram Campaign: #PanduCaramelMoment untuk user-generated content',
                'bmc_alignment' => [
                    'customer_segment' => 'Mahasiswa (18-24), pekerja muda (25-35), pecinta kopi pemula hingga enthusiast',
                    'value_proposition' => 'Premium quality dengan harga affordable, consistent taste, Instagram-worthy',
                    'channels' => 'Dine-in, takeaway, delivery apps (GoFood, GrabFood, ShopeeFood), pre-order via WhatsApp',
                    'customer_relationships' => 'Loyalty program (buy 10 get 1 free), personalized service, customer feedback loop',
                    'revenue_streams' => 'Direct sales (70%), delivery (25%), catering/event (5%)',
                    'key_resources' => 'Premium Arabica beans, skilled barista, homemade caramel recipe, espresso machine',
                    'key_activities' => 'Daily fresh caramel production, quality control, barista training, social media content',
                    'key_partnerships' => 'Koperasi Petani Kopi Ijen, delivery platforms, Instagram influencers',
                    'cost_structure' => 'COGS 35% (coffee beans 15%, milk 10%, caramel ingredients 7%, cup/packaging 3%), labor 15%'
                ],
                'status' => 'launched',
            ],

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Pandu Specialty Espresso',
                'description' => 'Pure double shot espresso dari single origin Arabica Bondowoso yang disangrai medium. Notes of chocolate, caramel, dan subtle fruitiness. Untuk true coffee lovers yang appreciate simplicity dan quality. Served dengan complimentary sparkling water untuk cleanse palate.',
                'price' => 18000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• 100% Arabica single origin dari Bondowoso\n• Roasted fresh setiap minggu\n• Extraction time 25-30 detik untuk optimal flavor\n• Crema yang thick dan golden\n• Complex flavor profile: chocolate, caramel, fruity notes\n• Perfect untuk evaluate kualitas kopi\n• Barista dapat custom grind size berdasarkan preferensi',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Single Origin Series: Rotasi bulanan dari berbagai daerah (Aceh, Toraja, Papua)\n2. Tasting Flight: 3 espresso shots dari origins berbeda (Rp 45K)\n3. Espresso-Based Workshop: Teach customers about extraction, grind size\n4. Barista Competition: Quarterly espresso brewing competition\n5. Espresso Subscription: Monthly subscription untuk regulars (10 cups = Rp 150K)\n6. Partnership: Kolaborasi dengan coffee farmers untuk farm visit',
                'bmc_alignment' => [
                    'customer_segment' => 'Coffee enthusiasts, barista, foodies, age 22-40',
                    'value_proposition' => 'Authentic specialty espresso, traceable origin, barista expertise',
                    'channels' => 'Primarily dine-in (85%), takeaway (15%)',
                    'customer_relationships' => 'Educational approach, tasting notes sharing, personalized recommendations',
                    'revenue_streams' => 'Direct sales, coffee bean retail, workshop fees',
                    'key_resources' => 'Direct trade relationship dengan farmers, professional espresso machine, trained barista',
                    'key_activities' => 'Quality control, cupping sessions, barista training, farmer collaboration',
                    'key_partnerships' => 'Coffee farmers, roaster, specialty coffee community',
                    'cost_structure' => 'COGS 40% (premium beans 35%, packaging 5%), labor 15%, equipment maintenance 5%'
                ],
                'status' => 'launched',
            ],

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Iced Vietnamese Coffee',
                'description' => 'Strong Vietnamese-style coffee dibuat dengan drip method, mixed dengan condensed milk dan served over ice. Robust, sweet, dan incredibly refreshing. Perfect untuk hot Jember weather. Our unique twist: menggunakan local Robusta dari Jember untuk authenticity.',
                'price' => 22000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Authentic Vietnamese brewing method\n• Strong caffeine kick (Robusta beans)\n• Perfect sweetness balance\n• Very refreshing untuk cuaca panas\n• Unique di market (competitor tidak punya)\n• Local Robusta dari Jember = support local\n• Great profit margin (COGS rendah)',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Vietnamese Coffee Flight: Original, Coconut, Egg Coffee (Rp 55K)\n2. DIY Kit: Jual Vietnamese dripper + beans + condensed milk (Rp 150K)\n3. Seasonal Variant: Mango Vietnamese Coffee, Avocado Vietnamese Coffee\n4. Education: Instagram reels showing brewing process\n5. Partnership: Kolaborasi dengan Vietnamese restaurant untuk cross-promo\n6. Loyalty: Vietnamese Coffee Club - exclusive deals untuk regulars',
                'bmc_alignment' => [
                    'customer_segment' => 'Students (harga affordable), young workers, coffee explorers',
                    'value_proposition' => 'Unique experience, authentic taste, affordable premium',
                    'channels' => 'Dine-in (60%), delivery (35%), takeaway (5%)',
                    'customer_relationships' => 'Story-telling (Vietnamese coffee culture), brewing demonstrations',
                    'revenue_streams' => 'Beverage sales, DIY kit sales',
                    'key_resources' => 'Vietnamese dripper, local Robusta beans, condensed milk supplier',
                    'key_activities' => 'Brewing demonstration, content creation, sourcing local Robusta',
                    'key_partnerships' => 'Local Robusta farmers, condensed milk supplier, Vietnamese community',
                    'cost_structure' => 'COGS 30% (Robusta cheap 10%, condensed milk 15%, ice/packaging 5%), labor 10%'
                ],
                'status' => 'launched',
            ],

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Cold Brew Tonic',
                'description' => 'Trendy beverage yang refreshing! Cold brew coffee yang smooth di-pour over tonic water dan fresh lemon. Effervescent, slightly sweet, dengan coffee acidity yang balanced. Layered presentation yang stunning. Perfect afternoon pick-me-up.',
                'price' => 28000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Unik dan trendy (viral di social media)\n• Refreshing alternative untuk yang bosan dengan standard coffee\n• Low acidity karena cold brew method\n• Instagram-worthy layered presentation\n• Sparkling sensation yang berbeda\n• High margin product\n• Dapat jadi signature drink',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Flavor Variants: Passion Fruit Tonic, Lychee Tonic, Yuzu Tonic\n2. Seasonal: Summer exclusive untuk maximize demand\n3. Marketing: Focus pada visual content (TikTok, Instagram Reels)\n4. Workshop: Cold brew making workshop untuk customers\n5. Wholesale: Supply ke gym, yoga studios (health-conscious market)\n6. Batch Production: Pre-made cold brew untuk efficiency',
                'bmc_alignment' => [
                    'customer_segment' => 'Gen Z, health-conscious millennials, Instagram influencers',
                    'value_proposition' => 'Trendy, refreshing, low-acid, Instagram-worthy, unique experience',
                    'channels' => 'Dine-in (primary), delivery, social media pre-order',
                    'customer_relationships' => 'Social media engagement, influencer collaboration, photo opportunities',
                    'revenue_streams' => 'Direct sales, wholesale to fitness centers',
                    'key_resources' => 'Cold brew equipment, tonic water supplier, fresh fruits',
                    'key_activities' => 'Cold brew production (12-24 hours), content creation, influencer management',
                    'key_partnerships' => 'Tonic water distributor, local fruit supplier, fitness influencers',
                    'cost_structure' => 'COGS 35% (cold brew coffee 15%, tonic 12%, fruits/garnish 5%, special glass 3%), marketing 10%'
                ],
                'status' => 'launched',
            ],

            // ========== NON-COFFEE BEVERAGES ==========

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Premium Matcha Latte',
                'description' => 'Japanese ceremonial grade matcha dari Kyoto, whisked traditionally dan mixed dengan fresh milk. Creamy, smooth, dengan natural sweetness. Rich dalam antioxidants. Option: oat milk, almond milk, atau regular milk. Available hot or iced.',
                'price' => 30000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Ceremonial grade matcha (highest quality)\n• Health benefits: antioxidants, metabolism boost, calm energy\n• No coffee jitters\n• Traditionally prepared (bamboo whisk)\n• Vibrant green color (Instagram-worthy)\n• Plant-based milk options available\n• Popular among health-conscious customers (25% of non-coffee sales)',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Matcha Series: Matcha Strawberry, Matcha Cookies & Cream, Dirty Matcha (matcha + espresso shot)\n2. Matcha Food Pairing: Bundle dengan matcha-flavored pastries\n3. Matcha Ceremony Experience: Monthly traditional matcha ceremony event\n4. Retail: Sell matcha powder dan bamboo whisk untuk home brewing\n5. Collaboration: Partner dengan Japanese restaurant atau culture center\n6. Membership: Matcha Lovers Club dengan special discount',
                'bmc_alignment' => [
                    'customer_segment' => 'Health-conscious millennials, Gen Z, non-coffee drinkers, wellness enthusiasts',
                    'value_proposition' => 'Premium quality, health benefits, authentic preparation, alternative to coffee',
                    'channels' => 'Dine-in, delivery, retail (matcha powder)',
                    'customer_relationships' => 'Educational content (health benefits), matcha preparation tutorials',
                    'revenue_streams' => 'Beverage sales (80%), retail products (15%), workshop fees (5%)',
                    'key_resources' => 'Ceremonial grade matcha supplier, bamboo whisk, trained staff',
                    'key_activities' => 'Staff training (proper matcha preparation), content creation, sourcing quality matcha',
                    'key_partnerships' => 'Japanese matcha supplier, health/wellness influencers, yoga studios',
                    'cost_structure' => 'COGS 45% (matcha premium 30%, milk 12%, packaging 3%), labor 15%'
                ],
                'status' => 'launched',
            ],

            // ========== FOOD ITEMS ==========

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Chicken Teriyaki Rice Bowl',
                'description' => 'Comfort food yang filling! Grilled chicken teriyaki dengan homemade sauce, served over Japanese rice dengan mixed vegetables (edamame, carrot, corn), soft-boiled egg, dan sesame seeds. Portion yang generous, perfect untuk lunch atau dinner.',
                'price' => 32000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Porsi besar, mengenyangkan\n• Balanced nutrition: protein, carbs, vegetables\n• Homemade teriyaki sauce (no MSG)\n• Fresh daily preparation\n• Value for money\n• Quick serve time (8-10 menit)\n• Popular lunch option (40% food sales)',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Bowl Variants: Salmon Teriyaki Bowl, Beef Teriyaki Bowl, Tofu Bowl (vegan)\n2. Spice Level Options: Original, Spicy, Extra Spicy\n3. Meal Subscription: Daily lunch subscription untuk office workers (5 days = Rp 140K)\n4. Bundle: Rice Bowl + Drink combo (Rp 45K)\n5. Catering: Untuk campus events, office lunch\n6. Delivery Optimization: Partner dengan multiple platforms',
                'bmc_alignment' => [
                    'customer_segment' => 'Students (budget-conscious), office workers (convenience), health-conscious eaters',
                    'value_proposition' => 'Affordable, filling, healthy, quick service, consistent quality',
                    'channels' => 'Dine-in (50%), delivery (40%), catering (10%)',
                    'customer_relationships' => 'Subscription program, bundle deals, loyalty stamps',
                    'revenue_streams' => 'Direct sales, subscription, catering contracts',
                    'key_resources' => 'Kitchen facility, suppliers (chicken, rice, vegetables), trained cook',
                    'key_activities' => 'Daily food prep, quality control, inventory management, kitchen hygiene',
                    'key_partnerships' => 'Chicken supplier, vegetable supplier, rice distributor, delivery platforms',
                    'cost_structure' => 'COGS 40% (chicken 18%, rice 8%, vegetables 8%, egg 3%, sauce/seasoning 3%), labor 20%, kitchen overhead 10%'
                ],
                'status' => 'launched',
            ],

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'product',
                'name' => 'Croissant Sandwich Selection',
                'description' => 'Buttery croissant kami yang flaky (baked fresh daily) dengan pilihan filling: Chicken Mayo, Tuna Melt, Smoked Beef & Cheese, atau Egg & Avocado. Served dengan side salad dan special sauce. Perfect untuk breakfast atau light meal.',
                'price' => 28000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Fresh baked daily croissant\n• Multiple filling options\n• Premium ingredients\n• Perfect untuk breakfast/brunch\n• Light yet satisfying\n• Pairs well dengan coffee\n• Good profit margin',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Breakfast Set: Croissant + Coffee combo (Rp 40K) - most popular\n2. Vegan Option: Vegan croissant dengan plant-based fillings\n3. Seasonal Flavors: Limited edition monthly specials\n4. Retail: Sell plain croissants untuk takeaway (6 pcs package)\n5. Workshop: Croissant baking class untuk customers (weekend)\n6. Partnership: Supply ke nearby office buildings (morning delivery)',
                'bmc_alignment' => [
                    'customer_segment' => 'Morning customers, breakfast lovers, office workers, brunch enthusiasts',
                    'value_proposition' => 'Fresh daily baking, premium quality, perfect breakfast pairing dengan coffee',
                    'channels' => 'Dine-in (60%), takeaway (30%), corporate delivery (10%)',
                    'customer_relationships' => 'Breakfast club membership, corporate contracts, morning regular rewards',
                    'revenue_streams' => 'Sandwich sales, retail croissants, breakfast combos, workshop fees',
                    'key_resources' => 'Bakery equipment, skilled baker, quality ingredients (butter, flour)',
                    'key_activities' => 'Early morning baking, quality control, ingredient sourcing, workshop hosting',
                    'key_partnerships' => 'Butter supplier (premium), flour supplier, filling ingredient suppliers',
                    'cost_structure' => 'COGS 38% (flour/butter 20%, fillings 12%, packaging 6%), labor 18%, bakery overhead 8%'
                ],
                'status' => 'launched',
            ],

            // ========== SERVICES ==========

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'service',
                'name' => 'Event Space Rental',
                'description' => 'Sewa space kami yang cozy untuk berbagai acara: study group, organizational meeting, birthday party, workshop, product launch, atau gathering. Capacity hingga 30 orang. Include: WiFi, projector, whiteboard, AC, sound system basic. Minimum 3 hours rental.',
                'price' => 200000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Strategic location (dekat kampus)\n• Full AC, comfortable seating\n• Complete facilities (WiFi, projector, sound system, whiteboard)\n• Flexible space arrangement\n• Photo-friendly interior\n• Dedicated event staff\n• F&B can be arranged (additional)\n• Competitive pricing vs hotel meeting rooms',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Package Deals:\n   - Basic Package: Space only (Rp 200K/3hrs)\n   - Standard Package: Space + 20 drinks (Rp 400K)\n   - Premium Package: Space + full F&B + decoration (Rp 800K)\n2. Partnership: Dengan student organizations (discount 20%)\n3. Recurring Events: Monthly booking gets discount 15%\n4. Corporate Program: Partnership dengan nearby offices\n5. Weekend Special: Lower rate untuk weekend bookings\n6. Add-ons: Professional photography, decorations, upgraded sound system',
                'bmc_alignment' => [
                    'customer_segment' => 'Student organizations, startups, freelancer community, small businesses, birthday celebrants',
                    'value_proposition' => 'Affordable, strategic location, complete facilities, flexible, intimate setting',
                    'channels' => 'Direct booking (WhatsApp, website), student organization partnerships, corporate outreach',
                    'customer_relationships' => 'Dedicated event coordinator, customization support, follow-up for repeat booking',
                    'revenue_streams' => 'Space rental, F&B upselling, add-on services, recurring bookings',
                    'key_resources' => 'Event space (30 pax capacity), AV equipment, trained event staff, F&B capability',
                    'key_activities' => 'Space management, equipment maintenance, event coordination, marketing to organizations',
                    'key_partnerships' => 'Student organizations, event organizers, decoration vendors, photographer',
                    'cost_structure' => 'Variable costs: staffing (30K/3hrs), utilities (20K), wear-tear (10K), marketing (10K)'
                ],
                'status' => 'launched',
            ],

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'service',
                'name' => 'Barista Training Program',
                'description' => 'Program training profesional untuk belajar espresso extraction, milk steaming, latte art, dan coffee knowledge. 2 levels: Basic (1 day, Rp 300K) dan Advanced (2 days, Rp 500K). Include: materials, practice session, certificate, dan take-home coffee beans. Limited to 6 participants per batch.',
                'price' => 300000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Taught by certified barista\n• Hands-on practice (not just theory)\n• Small class size (max 6) untuk quality learning\n• Professional certificate\n• Take-home coffee beans (250gr)\n• Can practice dengan professional equipment\n• Networking opportunity\n• Good revenue per event (Rp 1.8M gross revenue per batch)',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Curriculum Expansion:\n   - Level 3: Competition Preparation (Latte Art, Brewers Cup)\n   - Specialized: Home Brewing Masterclass\n   - Business Track: How to Start Coffee Business\n2. Corporate Training: Team building program untuk companies\n3. University Partnership: Elective course atau student activity\n4. Online Component: Hybrid model dengan video lessons\n5. Alumni Network: Create barista community, job placement assistance\n6. Certification Path: Partner dengan coffee associations untuk recognized cert',
                'bmc_alignment' => [
                    'customer_segment' => 'Coffee enthusiasts, aspiring baristas, F&B workers, entrepreneurs, hobbyists',
                    'value_proposition' => 'Professional training, hands-on experience, certified instructor, career opportunity',
                    'channels' => 'Social media ads, university bulletin boards, coffee community groups, word-of-mouth',
                    'customer_relationships' => 'Alumni network, ongoing mentorship, job referrals, community events',
                    'revenue_streams' => 'Training fees, advanced level upsell, corporate training contracts, equipment sales',
                    'key_resources' => 'Certified trainer, professional equipment, training curriculum, practice materials',
                    'key_activities' => 'Curriculum development, marketing to target audience, trainer scheduling, materials preparation',
                    'key_partnerships' => 'Coffee associations, universities, F&B businesses (for job placement), equipment suppliers',
                    'cost_structure' => 'Trainer fee (30%), materials/beans (20%), marketing (15%), certificate printing (5%), overhead (10%)'
                ],
                'status' => 'launched',
            ],

            [
                'user_id' => 1,
                'business_background_id' => 1,
                'type' => 'service',
                'name' => 'Coffee Subscription Box',
                'description' => 'Monthly subscription box untuk coffee lovers! Setiap bulan receive: 500gr freshly roasted beans (rotating origins), tasting notes card, brewing guide, exclusive merchandise, dan special discount voucher. Perfect gift atau untuk personal enjoyment. Subscription commitment: minimum 3 months.',
                'price' => 150000,
                'image_path' => null,
                'advantages' => 'KEUNGGULAN:\n• Recurring revenue model\n• Customer loyalty builder\n• Predictable cash flow\n• Introduce customers ke different coffee origins\n• Gift option (prepaid 3/6/12 months)\n• Exclusive member benefits\n• Direct relationship dengan customers\n• High lifetime value',
                'development_strategy' => 'STRATEGI PENGEMBANGAN:\n1. Tier System:\n   - Basic: 500gr beans + tasting notes (Rp 150K/month)\n   - Premium: 1kg beans + brewing equipment + merchandise (Rp 280K/month)\n   - Ultimate: 1kg beans + exclusive limited editions + priority access (Rp 400K/month)\n2. Gift Subscriptions: Prepaid packages untuk gifts\n3. Corporate Subscriptions: Office coffee supply (bulk discount)\n4. Referral Program: Refer friend get 1 month free\n5. Community: Exclusive subscribers-only events, farm visits\n6. Add-ons: Brewing equipment, grinders, accessories',
                'bmc_alignment' => [
                    'customer_segment' => 'Coffee enthusiasts, home brewers, gift givers, offices/co-working spaces',
                    'value_proposition' => 'Convenience, discovery, quality, education, exclusive access, perfect gift',
                    'channels' => 'Website subscription, social media marketing, gift registries, corporate sales',
                    'customer_relationships' => 'Subscription model, exclusive community, personalized recommendations, ongoing education',
                    'revenue_streams' => 'Monthly subscriptions, prepaid gift packages, corporate contracts, add-on sales',
                    'key_resources' => 'Coffee bean supply, roasting capacity, packaging materials, logistics partner',
                    'key_activities' => 'Sourcing beans, roasting, packing, shipping, content creation, community management',
                    'key_partnerships' => 'Coffee farmers, roaster, logistics/courier, packaging supplier, merchandise vendor',
                    'cost_structure' => 'COGS 50% (beans 30%, packaging 10%, shipping 10%), marketing 10%, ops 15%'
                ],
                'status' => 'in_development',
            ],
        ];

        foreach ($products as $product) {
            ProductService::create($product);
        }
    }
}
