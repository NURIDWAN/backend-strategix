<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OperationalPlan;

class OperationalPlanSeeder extends Seeder
{
    public function run(): void
    {
        OperationalPlan::create([
            'user_id' => 1,
            'business_background_id' => 1,

            // LOKASI USAHA - DETAILED
            'business_location' => 'Ruko Kampus, Jalan Kalimantan No. 45, Tegalboto, Jember, Jawa Timur 68121',

            'location_description' => 'DESKRIPSI LOKASI LENGKAP:\n\n1. AREA & LAYOUT:\n   • Total Area: 100 m² (2 lantai @ 50 m²)\n   • Lantai 1 (Main Floor - 50 m²):\n     - Coffee bar & brewing station: 8 m²\n     - Kitchen area: 6 m²\n     - Customer seating: 28 m² (20 seats)\n     - Cashier & POS: 3 m²\n     - Restroom: 3 m²\n     - Storage: 2 m²\n\n   • Lantai 2 (Upper Floor - 50 m²):\n     - Additional seating: 25 m² (15 seats)\n     - Meeting room: 15 m² (capacity 8 people)\n     - Private booths: 10 m² (2 booths for 4 people each)\n\n2. STRATEGIC ADVANTAGES:\n   • Hanya 50 meter dari gerbang utama Universitas Jember\n   • High foot traffic area (500+ pedestrians/day)\n   • Visible dari jalan raya (excellent signage visibility)\n   • Surrounded by student housing (kos-kosan area)\n   • Near public transportation stops (angkot, ojek online)\n   • Ample parking space (6 motor, 2 mobil)\n   • Safe & well-lit area (security 24/7)\n\n3. ACCESSIBILITY:\n   • Wheelchair accessible (ramp available)\n   • Easy access untuk delivery drivers\n   • Ground floor entrance (no stairs barrier)\n   • Clear signage & wayfinding\n\n4. NEIGHBORHOOD PROFILE:\n   • 15+ student boarding houses dalam radius 200m\n   • 3 mini markets within walking distance\n   • Campus facilities: Library (100m), Faculty buildings (50-200m)\n   • Mixed commercial area: food stalls, stationery shops, laundry services\n\n5. INFRASTRUCTURE:\n   • Reliable electricity (PLN with backup generator)\n   • Clean water supply (PDAM + filtration system)\n   • High-speed internet (Fiber optic 100 Mbps)\n   • Good drainage system\n   • Waste management access\n\n6. LEASE TERMS:\n   • Lease duration: 5 years (2023-2028)\n   • Renewable with negotiation\n   • Security deposit: 3 months rent (Rp 7.5 jt)\n   • Annual increment: Max 10%\n   • Landlord responsibilities: Structural maintenance, property tax\n   • Tenant responsibilities: Utilities, interior maintenance',

            'location_type' => 'rented',
            'location_size' => 100.00, // Updated to 100 sqm total
            'rent_cost' => 2500000, // Rp 2.5 jt per month

            // KARYAWAN - DETAILED STAFFING PLAN
            'employees' => [
                [
                    'name' => 'Rizky Pratama',
                    'role' => 'Store Manager',
                    'schedule' => 'Full-time, 09:00 - 18:00 (Mon-Sat), Rotating Sunday',
                    'salary' => 'Rp 4.500.000/month',
                    'responsibilities' => 'Overall operations, staff management, inventory, financial reporting, customer relations',
                    'experience' => '5 years F&B management'
                ],
                [
                    'name' => 'Andi Setiawan',
                    'role' => 'Head Barista',
                    'schedule' => 'Full-time, 08:00 - 17:00 (Mon-Sat)',
                    'salary' => 'Rp 3.800.000/month',
                    'responsibilities' => 'Coffee quality control, barista training, menu development, equipment maintenance',
                    'experience' => 'Certified barista, 4 years experience'
                ],
                [
                    'name' => 'Siti Nurhaliza',
                    'role' => 'Senior Barista',
                    'schedule' => 'Full-time, 09:00 - 18:00 (Tue-Sun)',
                    'salary' => 'Rp 3.200.000/month',
                    'responsibilities' => 'Coffee preparation, customer service, training junior barista, opening/closing duties',
                    'experience' => '3 years barista experience'
                ],
                [
                    'name' => 'Dina Cahyani',
                    'role' => 'Barista',
                    'schedule' => 'Full-time, 10:00 - 19:00 (Wed-Mon)',
                    'salary' => 'Rp 2.800.000/month',
                    'responsibilities' => 'Coffee & beverage preparation, POS operation, basic customer service',
                    'experience' => '1 year barista experience'
                ],
                [
                    'name' => 'Budi Santoso',
                    'role' => 'Barista',
                    'schedule' => 'Full-time, 11:00 - 20:00 (Mon-Sat)',
                    'salary' => 'Rp 2.800.000/month',
                    'responsibilities' => 'Coffee & beverage preparation, cleaning, stock replenishment',
                    'experience' => '1 year F&B experience'
                ],
                [
                    'name' => 'Lina Mardiana',
                    'role' => 'Kitchen Staff / Food Preparation',
                    'schedule' => 'Full-time, 08:00 - 17:00 (Mon-Sat)',
                    'salary' => 'Rp 3.000.000/month',
                    'responsibilities' => 'Food preparation, kitchen hygiene, inventory kitchen supplies, food quality control',
                    'experience' => '3 years kitchen experience'
                ],
                [
                    'name' => 'Ahmad Hidayat',
                    'role' => 'Service Staff / Waiter',
                    'schedule' => 'Full-time, 10:00 - 19:00 (Tue-Sun)',
                    'salary' => 'Rp 2.500.000/month',
                    'responsibilities' => 'Table service, order taking, customer assistance, cleaning',
                    'experience' => '2 years hospitality experience'
                ],
                [
                    'name' => 'Rina Oktaviani',
                    'role' => 'Cashier / Admin',
                    'schedule' => 'Full-time, 09:00 - 18:00 (Mon-Sat)',
                    'salary' => 'Rp 2.700.000/month',
                    'responsibilities' => 'Cashier operations, daily reporting, admin tasks, customer inquiries',
                    'experience' => '2 years retail/cashier experience'
                ],
                [
                    'name' => 'Hendra Wijaya',
                    'role' => 'Part-time Barista (Evening Shift)',
                    'schedule' => 'Part-time, 17:00 - 21:00 (Mon-Sat)',
                    'salary' => 'Rp 1.500.000/month',
                    'responsibilities' => 'Evening coffee service, closing duties, basic cleaning',
                    'experience' => 'Student, 6 months training'
                ],
                [
                    'name' => 'Eka Putri',
                    'role' => 'Part-time Service Staff (Weekend)',
                    'schedule' => 'Part-time, 10:00 - 20:00 (Sat-Sun)',
                    'salary' => 'Rp 1.000.000/month',
                    'responsibilities' => 'Weekend support, table service, customer service',
                    'experience' => 'Student, 4 months training'
                ],
                [
                    'name' => 'Ricky Gunawan',
                    'role' => 'Marketing & Social Media Specialist',
                    'schedule' => 'Full-time, Flexible hours (primarily digital)',
                    'salary' => 'Rp 3.500.000/month',
                    'responsibilities' => 'Social media management, content creation, influencer relations, event coordination',
                    'experience' => '2 years digital marketing'
                ],
                [
                    'name' => 'Bagas Nurrahman',
                    'role' => 'Cleaning & Maintenance Staff',
                    'schedule' => 'Full-time, 06:00 - 14:00 (Every day)',
                    'salary' => 'Rp 2.300.000/month',
                    'responsibilities' => 'Deep cleaning, equipment maintenance, waste management, opening preparation',
                    'experience' => '5 years cleaning/maintenance'
                ]
            ],

            // JAM OPERASIONAL - OPTIMIZED SCHEDULE
            'operational_hours' => [
                'monday' => '08:00 - 21:00 (13 hours)',
                'tuesday' => '08:00 - 21:00 (13 hours)',
                'wednesday' => '08:00 - 21:00 (13 hours)',
                'thursday' => '08:00 - 21:00 (13 hours)',
                'friday' => '08:00 - 21:00 (13 hours)',
                'saturday' => '09:00 - 22:00 (13 hours) - Weekend extended hours',
                'sunday' => '09:00 - 22:00 (13 hours) - Weekend extended hours',
                'public_holidays' => 'Special hours: 10:00 - 20:00 (may vary)',
                'notes' => 'Extended hours during exam periods (07:00 - 23:00). Last order 30 mins before closing.'
            ],

            // SUPPLIER - COMPREHENSIVE SUPPLIER LIST
            'suppliers' => [
                [
                    'name' => 'Koperasi Petani Kopi Ijen',
                    'type' => 'Coffee Beans - Primary Supplier',
                    'contact' => '+62 812-3456-7890',
                    'products' => 'Arabica beans, Single Origin Ijen',
                    'delivery' => 'Bi-weekly, 10kg per order',
                    'payment_terms' => 'Net 30, Direct Bank Transfer',
                    'price' => 'Rp 180K/kg wholesale'
                ],
                [
                    'name' => 'CV Bondowoso Coffee Roastery',
                    'type' => 'Coffee Beans - Secondary Supplier',
                    'contact' => '+62 813-4567-8901',
                    'products' => 'Arabica Bondowoso, Robusta Jember',
                    'delivery' => 'On-demand, minimum 5kg',
                    'payment_terms' => 'Cash on Delivery',
                    'price' => 'Arabica Rp 170K/kg, Robusta Rp 85K/kg'
                ],
                [
                    'name' => 'PT Greenfields Indonesia',
                    'type' => 'Dairy Products',
                    'contact' => '+62 314-567-890',
                    'products' => 'Fresh milk, UHT milk',
                    'delivery' => '3x per week (Mon, Wed, Fri)',
                    'payment_terms' => 'Net 14',
                    'price' => 'Rp 16K/liter bulk'
                ],
                [
                    'name' => 'Oatly Indonesia Distributor',
                    'type' => 'Alternative Milk',
                    'contact' => '+62 821-5678-9012',
                    'products' => 'Oat milk, Almond milk',
                    'delivery' => 'Weekly',
                    'payment_terms' => 'Prepayment',
                    'price' => 'Oat milk Rp 35K/liter'
                ],
                [
                    'name' => 'Toko Roti Barokah',
                    'type' => 'Pastries & Baked Goods',
                    'contact' => '+62 819-6789-0123',
                    'products' => 'Croissants, muffins, cookies',
                    'delivery' => 'Daily (early morning)',
                    'payment_terms' => 'Weekly payment',
                    'price' => 'Variable, consignment 40% margin'
                ],
                [
                    'name' => 'CV Fresh Vegetables Jember',
                    'type' => 'Fresh Produce',
                    'contact' => '+62 817-7890-1234',
                    'products' => 'Vegetables, fruits, herbs',
                    'delivery' => '3x per week',
                    'payment_terms' => 'Cash on Delivery',
                    'price' => 'Market price +10%'
                ],
                [
                    'name' => 'PT Ayam Cemerlang',
                    'type' => 'Chicken & Meat',
                    'contact' => '+62 816-8901-2345',
                    'products' => 'Chicken breast, thigh, minced',
                    'delivery' => '2x per week (Tue, Fri)',
                    'payment_terms' => 'Net 7',
                    'price' => 'Rp 40K/kg'
                ],
                [
                    'name' => 'Sinar Beras Jember',
                    'type' => 'Rice & Grains',
                    'contact' => '+62 815-9012-3456',
                    'products' => 'Japanese rice, local rice',
                    'delivery' => 'Bi-weekly, 25kg per order',
                    'payment_terms' => 'Cash on Delivery',
                    'price' => 'Japanese rice Rp 18K/kg'
                ],
                [
                    'name' => 'PT Paper Cup Indonesia',
                    'type' => 'Packaging & Disposables',
                    'contact' => '+62 314-012-3456',
                    'products' => 'Paper cups, lids, straws, food containers',
                    'delivery' => 'Monthly bulk order',
                    'payment_terms' => 'Net 30',
                    'price' => 'Rp 600/cup (12oz), Rp 200/lid'
                ],
                [
                    'name' => 'CV Digital Solution Jember',
                    'type' => 'Technology & Software',
                    'contact' => '+62 812-3456-7891',
                    'products' => 'POS system, IT support, WiFi service',
                    'delivery' => 'On-site support',
                    'payment_terms' => 'Monthly subscription',
                    'price' => 'POS subscription Rp 500K/month'
                ],
                [
                    'name' => 'Toko Mesin Kopi Surabaya',
                    'type' => 'Equipment & Maintenance',
                    'contact' => '+62 313-123-4567',
                    'products' => 'Espresso machines, grinders, spare parts',
                    'delivery' => 'On-demand, same-day service',
                    'payment_terms' => 'Per transaction',
                    'price' => 'Variable, service call Rp 500K'
                ]
            ],

            // DAILY WORKFLOW - COMPREHENSIVE SOP
            'daily_workflow' => 'WORKFLOW OPERASIONAL HARIAN - STANDARD OPERATING PROCEDURES:

=== PAGI (06:00 - 08:00) - OPENING PROCEDURES ===

06:00 - Maintenance Staff Arrival:
   [*] Unlock premises, disable security alarm
   [*] Turn on all lights and AC units
   [*] Deep cleaning: floors, tables, chairs, restrooms
   [*] Window cleaning, entrance area tidying
   [*] Waste disposal from previous day
   [*] Restroom supply check (tissue, soap, hand sanitizer)

07:00 - Kitchen Staff Arrival:
   [*] Kitchen hygiene check and cleaning
   [*] Equipment warming (ovens, grills)
   [*] Inventory check: food supplies
   [*] Receive fresh produce delivery
   [*] Food prep: washing vegetables, marinating proteins
   [*] Prepare daily specials

07:30 - Head Barista & Manager Arrival:
   [*] Coffee machine warm-up and calibration
   [*] Grinder setting check and adjustment
   [*] Espresso quality test (2 test shots)
   [*] Milk steaming practice for staff
   [*] Coffee bean inventory check
   [*] Pastry/food display setup
   [*] Menu board update (daily specials, sold-out items)

07:45 - Opening Staff Briefing (10 minutes):
   [*] Review yesterdays performance & feedback
   [*] Todays specials & promotions announcement
   [*] Assign stations & responsibilities
   [*] Safety & hygiene reminder
   [*] Motivational team huddle

08:00 - GRAND OPENING:
   [*] Unlock doors, flip OPEN sign
   [*] Background music on (morning playlist)
   [*] All staff at stations
   [*] Welcome first customers dengan warm greeting

=== OPERASIONAL PAGI (08:00 - 12:00) ===

* Barista Station:
  - Prepare beverages sesuai order
  - Maintain quality standards (taste test every hour)
  - Keep workspace clean & organized
  - Refill supplies as needed
  - Latte art untuk every milk-based drink

* Kitchen Station:
  - Cook food orders promptly (target: <10 mins)
  - Plate presentation sesuai standard
  - Food safety compliance (temperature, hygiene)
  - Communicate dengan waiters untuk special requests

* Service Staff:
  - Greet & seat customers
  - Take orders accurately (POS system)
  - Serve food & beverages promptly
  - Table clearing & resetting within 5 minutes
  - Handle customer inquiries & complaints

* Cashier:
  - Process payments accurately
  - Issue receipts
  - Handle cash & digital transactions
  - Track daily sales in POS system
  - Customer loyalty program enrollment

* Manager:
  - Floor supervision
  - Customer relationship management
  - Handle escalations
  - Monitor inventory levels
  - Social media check & response

=== SIANG (12:00 - 15:00) - PEAK HOURS ===

12:00 - Lunch Rush Preparation:
   [*] All hands on deck
   [*] Increase kitchen production
   [*] Expedite service (target: serve within 7 minutes)
   [*] Manager on floor untuk assist

* Priority: Speed + Quality Balance
* Communication: Kitchen to counter coordination
* Crowd Management: Queue system, online orders prioritization
* Upselling: Suggest combos & bundles

13:00 - Staff Lunch Break Rotation (30 mins each):
   [*] Ensure minimum coverage at all times
   [*] Staff meal preparation

=== SORE (15:00 - 18:00) - AFTERNOON OPERATIONS ===

15:00 - Post-Lunch Slowdown:
   [*] Deep cleaning during quiet period
   [*] Restock & reorganize
   [*] Equipment cleaning & maintenance
   [*] Prepare untuk evening crowd

* Study Hour Support:
  - Maintain quiet ambiance
  - Offer table extension untuk students
  - Refill water & check pada long-staying customers

16:00 - Shift Change:
   [*] Evening staff arrival
   [*] Handover briefing (15 mins)
   [*] Day shift departure (some staff)

17:00 - Happy Hour Start:
   [*] Promote happy hour deals
   [*] Social media story update
   [*] Increase beverage production

=== MALAM (18:00 - 21:00) - EVENING OPERATIONS ===

* Ambiance Adjustment:
  - Dim lighting slightly (cozy atmosphere)
  - Switch to evening playlist
  - Turn on decorative lighting

* Services:
  - Maintain quality standards
  - Evening snacks & light meals focus
  - Social media engagement (post daily highlights)

20:30 - Pre-Closing Procedures:
   [*] Last order announcement (30 mins before closing)
   [*] Begin kitchen cleaning
   [*] Reduce AC gradually

=== CLOSING (21:00 - 22:00) - CLOSING PROCEDURES ===

21:00 - Customer Departure:
   [*] Politely inform remaining customers
   [*] Lock entrance door (exit only)
   [*] Flip to CLOSED sign

21:00-21:30 - Equipment Shutdown:
   [*] Espresso machine: Backflush, clean group heads
   [*] Grinder: Empty hopper, clean burrs (daily)
   [*] Blenders & equipment: Wash thoroughly
   [*] Kitchen appliances: Turn off, clean

21:30-21:45 - Cleaning:
   [*] Mop all floors
   [*] Wipe all tables & chairs
   [*] Clean restrooms thoroughly
   [*] Wash all dishes, utensils
   [*] Take out garbage

21:45-22:00 - Administrative Closing:
   [*] Cash count & reconciliation
   [*] Daily sales report generation
   [*] Inventory update (items sold out, running low)
   [*] Manager review & approval
   [*] Prepare tomorrows order list
   [*] Lock all windows & doors
   [*] Activate security alarm
   [*] Final lights off

=== WEEKLY TASKS ===

Monday:
  - Deep cleaning behind equipment
  - Marketing meeting (plan weeks content)
  - Review last weeks performance

Wednesday:
  - Equipment maintenance check
  - Supplier payment processing
  - Mid-week inventory audit

Friday:
  - Staff meeting & training (30 mins)
  - Weekend preparation & stocking
  - Social media content scheduling

Sunday:
  - Deep cleaning day
  - Equipment descaling (coffee machine monthly)
  - Prepare for new week

=== MONTHLY TASKS ===

  - Full inventory count
  - Financial reporting
  - Equipment servicing
  - Staff performance reviews
  - Menu evaluation & updates
  - Deep cleaning (behind fridges, storage areas)
  - Supplier performance review
  - Marketing strategy review

=== QUALITY CONTROL CHECKPOINTS ===

Every Hour:
  [v] Espresso taste test
  [v] Milk quality check
  [v] Food temperature check
  [v] Restroom cleanliness check

Every 2 Hours:
  [v] Ice bin refill
  [v] Syrup & sauce levels
  [v] Pastry display refresh
  [v] Table cleanliness tour

=== EMERGENCY PROCEDURES ===

* Equipment Breakdown: Call technician, switch to backup equipment, inform customers
* Power Outage: Generator activation, manual POS backup, LED lighting
* Customer Complaint: Manager handle immediately, offer solution, follow-up
* Health/Safety Issue: First aid kit available, emergency numbers posted
* Fire: Evacuation procedure, fire extinguishers accessible

=== PERFORMANCE METRICS TRACKING ===

Daily:
  - Total sales
  - Transaction count
  - Average transaction value
  - Customer count
  - Waste/spoilage tracking

Weekly:
  - Sales by category
  - Popular items
  - Slow-moving items
  - Customer feedback summary

Monthly:
  - Financial performance vs budget
  - Staff performance
  - Inventory turnover
  - Customer satisfaction score',

            // Workflow diagram (optional - can be null)
            'workflow_diagram' => null,
            'workflow_image_path' => null,

            // EQUIPMENT - COMPREHENSIVE EQUIPMENT LIST
            'equipment_needs' => "PERALATAN OPERASIONAL LENGKAP:\n\n=== COFFEE EQUIPMENT (Investment: Rp 85 juta) ===\n\n1. Espresso Machine:\n   - Brand: La Marzocco Linea Classic 2 Group\n   - Price: Rp 60 juta\n   - Function: Espresso extraction, milk steaming\n   - Maintenance: Daily cleaning, monthly descaling\n\n2. Coffee Grinder (Primary):\n   - Brand: Mahlkönig E65S\n   - Price: Rp 15 juta\n   - Function: Fresh grinding untuk espresso\n   - Maintenance: Daily cleaning, burr replacement yearly\n\n3. Coffee Grinder (Secondary/Filter):\n   - Brand: Baratza Forte BG\n   - Price: Rp 8 juta\n   - Function: Filter coffee, cold brew grinding\n\n4. Cold Brew System:\n   - Brand: Toddy Commercial System\n   - Price: Rp 2 juta\n   - Function: Cold brew production (12-24 hours steeping)\n\n=== KITCHEN EQUIPMENT (Investment: Rp 35 juta) ===\n\n5. Commercial Refrigerator (2 units):\n   - Price: Rp 10 juta (Rp 5 juta each)\n   - Function: Food & beverage ingredient storage\n\n6. Freezer:\n   - Price: Rp 4 juta\n   - Function: Frozen ingredients, ice cream storage\n\n7. Ice Machine:\n   - Price: Rp 8 juta\n   - Capacity: 50 kg/day\n   - Function: Ice production untuk iced beverages\n\n8. Blender (Commercial - 2 units):\n   - Price: Rp 6 juta (Rp 3 juta each)\n   - Function: Smoothies, blended drinks\n\n9. Microwave Oven:\n   - Price: Rp 2 juta\n   - Function: Reheating food\n\n10. Toaster Oven:\n    - Price: Rp 3 juta\n    - Function: Toasting bread, heating pastries\n\n11. Rice Cooker (Commercial):\n    - Price: Rp 2 juta\n    - Capacity: 10 liter\n\n=== FURNITURE & FIXTURES (Investment: Rp 40 juta) ===\n\n12. Tables & Chairs:\n    - 15 tables (2-4 seater): Rp 15 juta\n    - 40 chairs: Rp 12 juta\n    - 2 private booths: Rp 8 juta\n\n13. Coffee Bar Counter:\n    - Custom-built: Rp 5 juta\n\n=== POS & TECHNOLOGY (Investment: Rp 25 juta) ===\n\n14. POS System:\n    - Hardware: Touchscreen register, receipt printer, cash drawer\n    - Software: Cloud-based POS (subscription)\n    - Investment: Rp 8 juta\n\n15. WiFi Router & Infrastructure:\n    - Commercial-grade router: Rp 3 juta\n    - 100 Mbps fiber connection\n    - Backup connection\n\n16. Laptops (2 units):\n    - For admin & marketing: Rp 20 juta\n\n17. CCTV System:\n    - 8 cameras: Rp 6 juta\n    - DVR recording system\n\n18. Sound System:\n    - Speakers & amplifier: Rp 4 juta\n\n=== SMALL EQUIPMENT & TOOLS (Investment: Rp 15 juta) ===\n\n- Milk pitchers (10 pcs): Rp 1 juta\n- Tampers, knock boxes, cleaning tools: Rp 500K\n- Scales (precision): Rp 1 juta\n- Thermometers: Rp 300K\n- Pourover equipment: Rp 500K\n- Serving trays: Rp 300K\n- Utensils, cutlery: Rp 2 juta\n- Glassware & cups: Rp 3 juta\n- Cleaning equipment: Rp 1 juta\n- Kitchen knives & tools: Rp 1.5 juta\n- Miscellaneous: Rp 4 juta\n\n=== TOTAL EQUIPMENT INVESTMENT: Rp 200 JUTA ===\n\n(Depreciation: 5 years straight line untuk major equipment)",

            // TECHNOLOGY STACK
            'technology_stack' => "TECHNOLOGY INFRASTRUCTURE:\n\n=== OPERATIONAL TECHNOLOGY ===\n\n1. POS System:\n   - Platform: Moka POS (Cloud-based)\n   - Features: Sales tracking, inventory management, customer database, reporting\n   - Subscription: Rp 500K/month\n   - Mobile app untuk staff\n\n2. Accounting Software:\n   - Platform: Jurnal.id\n   - Features: Invoicing, expense tracking, financial reports\n   - Subscription: Rp 300K/month\n\n3. Internet & WiFi:\n   - Provider: Indihome Fiber 100 Mbps\n   - Cost: Rp 500K/month\n   - Backup: Unlimited mobile hotspot\n\n4. Security System:\n   - CCTV: 8 cameras dengan cloud recording\n   - Service: Rp 200K/month\n\n=== CUSTOMER-FACING TECHNOLOGY ===\n\n5. Online Ordering:\n   - Platforms: GoFood, GrabFood, ShopeeFood\n   - Commission: 20-25% per order\n\n6. Payment Systems:\n   - Cash register\n   - EDC machines: BCA, Mandiri\n   - E-wallet: GoPay, OVO, Dana, ShopeePay\n   - QRIS universal\n\n7. Customer WiFi:\n   - Separate network dari operational\n   - Speed: 50 Mbps dedicated\n   - Landing page dengan social media integration\n\n=== MARKETING TECHNOLOGY ===\n\n8. Social Media Management:\n   - Platform: Later or Hootsuite\n   - Content calendar & scheduling\n   - Analytics tracking\n\n9. WhatsApp Business:\n   - Automated responses\n   - Broadcast lists\n   - Catalog integration\n\n10. Website:\n    - Platform: WordPress\n    - Hosting: Niagahoster\n    - Features: Menu, online ordering, blog, event calendar\n    - Cost: Rp 500K/year\n\n11. Email Marketing:\n    - Platform: Mailchimp (free tier initially)\n    - Newsletter distribution\n\n=== PRODUCTIVITY TOOLS ===\n\n12. Project Management:\n    - Google Workspace for collaboration\n    - Google Drive untuk file storage\n\n13. Design Tools:\n    - Canva Pro untuk social media graphics\n    - Subscription: Rp 150K/month\n\n=== FUTURE TECHNOLOGY ROADMAP ===\n\nYear 2:\n  - Mobile app development (ordering, loyalty program)\n  - Advanced CRM system\n  - Inventory automation system\n\nYear 3:\n  - AI-powered demand forecasting\n  - Automated marketing campaigns\n  - Customer behavior analytics",

            // STATUS
            'status' => 'completed'
        ]);
    }
}
