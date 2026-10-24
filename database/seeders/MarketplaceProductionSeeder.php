<?php

namespace Database\Seeders;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReview;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketplaceProductionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories matching the mockup
        $categories = [
            ['slug' => 'electronics', 'name' => 'Electronics', 'icon' => 'smartphone', 'display_order' => 1],
            ['slug' => 'fashion', 'name' => 'Fashion', 'icon' => 'shirt', 'display_order' => 2],
            ['slug' => 'home-living', 'name' => 'Home & Living', 'icon' => 'home', 'display_order' => 3],
            ['slug' => 'sports', 'name' => 'Sports', 'icon' => 'activity', 'display_order' => 4],
            ['slug' => 'books', 'name' => 'Books', 'icon' => 'book-open', 'display_order' => 5],
            ['slug' => 'vehicles', 'name' => 'Vehicles', 'icon' => 'car', 'display_order' => 6],
            ['slug' => 'jobs', 'name' => 'Jobs', 'icon' => 'briefcase', 'display_order' => 7],
            ['slug' => 'services', 'name' => 'Services', 'icon' => 'wrench', 'display_order' => 8],
            ['slug' => 'property', 'name' => 'Property', 'icon' => 'building', 'display_order' => 9],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat['slug']] = MarketplaceCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'display_order' => $cat['display_order'],
                ]
            );
        }

        // 2. Realistic Sellers
        $sellersData = [
            [
                'name' => 'Rahim Ahmed',
                'username' => 'rahim_ahmed',
                'email' => 'rahim@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Tech enthusiast & Apple reseller. 100% genuine products guaranteed.',
            ],
            [
                'name' => 'Sadia Islam',
                'username' => 'sadia_islam',
                'email' => 'sadia@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Gadget lover, upgrading every year. Verified seller.',
            ],
            [
                'name' => 'Tanvir Hasan',
                'username' => 'tanvir_hasan',
                'email' => 'tanvir@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Audiophile & music producer. Premium gear only.',
            ],
            [
                'name' => 'Nusrat Jahan',
                'username' => 'nusrat_jahan',
                'email' => 'nusrat@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Interior decor lover & minimalist home collector.',
            ],
            [
                'name' => 'Rifat Mahmud',
                'username' => 'rifat_mahmud',
                'email' => 'rifat@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Cyclist & adventure enthusiast.',
            ],
            [
                'name' => 'Imran Hossain',
                'username' => 'imran_hossain',
                'email' => 'imran@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Sneakerhead & streetwear collector. 100% authentic.',
            ],
            [
                'name' => 'Farhana Akter',
                'username' => 'farhana_akter',
                'email' => 'farhana@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Home staging & furniture specialist.',
            ],
            [
                'name' => 'Shakil Khan',
                'username' => 'shakil_khan',
                'email' => 'shakil@bondhoo.test',
                'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
                'city' => 'Dhaka',
                'bio' => 'Professional photographer & camera equipment dealer.',
            ],
        ];

        $sellerMap = [];
        foreach ($sellersData as $data) {
            $seller = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $seller->id],
                [
                    'avatar_url' => $data['avatar'],
                    'bio' => $data['bio'],
                    'city' => $data['city'],
                    'country' => 'Bangladesh',
                ]
            );

            $sellerMap[$data['username']] = $seller;
        }

        // 3. Mockup Products
        $products = [
            [
                'title' => 'MacBook Air M2 (2022)',
                'seller_username' => 'rahim_ahmed',
                'category_slug' => 'electronics',
                'price' => 850.00,
                'condition' => 'used',
                'brand' => 'Apple',
                'model' => 'MacBook Air M2 (8GB RAM / 256GB SSD)',
                'is_boosted' => true,
                'is_negotiable' => true,
                'delivery_type' => 'both',
                'delivery_charge' => 0.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subHours(2),
                'views_count' => 142,
                'images' => [
                    'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Selling my MacBook Air M2 (Midnight, 256GB SSD, 8GB Unified Memory). Battery health 96%, only 42 cycle count. Comes with original MagSafe 3 cable and 30W USB-C power adapter in box.',
            ],
            [
                'title' => 'iPhone 14 Pro 128GB',
                'seller_username' => 'sadia_islam',
                'category_slug' => 'electronics',
                'price' => 700.00,
                'condition' => 'used',
                'brand' => 'Apple',
                'model' => 'iPhone 14 Pro Deep Purple',
                'is_boosted' => true,
                'is_negotiable' => false,
                'delivery_type' => 'pickup',
                'delivery_charge' => 0.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subHours(4),
                'views_count' => 218,
                'images' => [
                    'https://images.unsplash.com/photo-1663499482523-1c0c1bae4ce1?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'iPhone 14 Pro 128GB in Deep Purple. Flawless physical condition with tempered glass protector installed since day one. Battery health 89%. Free Spigen magnetic case included.',
            ],
            [
                'title' => 'Sony WH-1000XM5 Headphones',
                'seller_username' => 'tanvir_hasan',
                'category_slug' => 'electronics',
                'price' => 280.00,
                'condition' => 'used',
                'brand' => 'Sony',
                'model' => 'WH-1000XM5 Silver/White',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'both',
                'delivery_charge' => 5.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subHours(6),
                'views_count' => 97,
                'images' => [
                    'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Flagship industry-leading noise cancellation headphones. Purchased 4 months ago from authorized showroom. Silver colorway with carrying case, 3.5mm cable, and flight adapter.',
            ],
            [
                'title' => 'Wooden Study Chair',
                'seller_username' => 'nusrat_jahan',
                'category_slug' => 'home-living',
                'price' => 45.00,
                'condition' => 'used',
                'brand' => 'Handcrafted',
                'model' => 'Solid Teak Wood Ergonomic Chair',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'pickup',
                'delivery_charge' => 0.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subHours(8),
                'views_count' => 64,
                'images' => [
                    'https://images.unsplash.com/photo-1580481077195-c328ad45be4e?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1503602642458-232111445657?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Solid Sheesham/Teak wood ergonomic study and dining chair with cushioned seat. Durable build, perfect for home office or reading nook.',
            ],
            [
                'title' => 'Mountain Bike (21 Speed)',
                'seller_username' => 'rifat_mahmud',
                'category_slug' => 'sports',
                'price' => 150.00,
                'condition' => 'used',
                'brand' => 'Core',
                'model' => 'Core Project 21-Speed Alloy',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'pickup',
                'delivery_charge' => 0.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subHours(10),
                'views_count' => 134,
                'images' => [
                    'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Shimano Tourney 21-speed gears, mechanical disc brakes, front suspension fork. Smooth ride for city commuting and off-road trails. Serviced last week.',
            ],
            [
                'title' => 'Nike Air Force 1 (Original)',
                'seller_username' => 'imran_hossain',
                'category_slug' => 'fashion',
                'price' => 100.00,
                'condition' => 'new',
                'brand' => 'Nike',
                'model' => 'Air Force 1 07 Triple White (Size US 10)',
                'is_boosted' => false,
                'is_negotiable' => false,
                'delivery_type' => 'delivery',
                'delivery_charge' => 4.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subHours(12),
                'views_count' => 189,
                'images' => [
                    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1600185365926-3a2ce3cdb9eb?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Brand new unworn in original Nike box. Authentic Nike Air Force 1 07 Triple White. Men size US 10 / EU 44. Purchased abroad.',
            ],
            [
                'title' => 'Sofa Set (5 Seater)',
                'seller_username' => 'farhana_akter',
                'category_slug' => 'home-living',
                'price' => 350.00,
                'condition' => 'used',
                'brand' => 'Otobi',
                'model' => 'L-Shaped Fabric 5-Seater Sofa',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'pickup',
                'delivery_charge' => 0.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subDay(),
                'views_count' => 176,
                'images' => [
                    'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Modern L-shaped 5-seater sofa set in grey linen fabric. Very clean, non-smoking household. Relocating abroad, so urgent sale.',
            ],
            [
                'title' => 'Canon EOS 200D',
                'seller_username' => 'shakil_khan',
                'category_slug' => 'electronics',
                'price' => 450.00,
                'condition' => 'used',
                'brand' => 'Canon',
                'model' => 'EOS 200D DSLR with 18-55mm IS STM Lens',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'both',
                'delivery_charge' => 5.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subDay(),
                'views_count' => 205,
                'images' => [
                    'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&auto=format&fit=crop&q=80',
                    'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Canon EOS 200D (Rebel SL2) 24.2MP DSLR camera with flip touchscreen LCD and Dual Pixel AF. Includes 18-55mm IS STM kit lens, battery, charger, 64GB SD card and camera bag.',
            ],
            [
                'title' => 'Apple Watch Series 8 (45mm)',
                'seller_username' => 'sadia_islam',
                'category_slug' => 'electronics',
                'price' => 310.00,
                'condition' => 'used',
                'brand' => 'Apple',
                'model' => 'Series 8 GPS 45mm Midnight',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'both',
                'delivery_charge' => 3.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subDays(2),
                'views_count' => 112,
                'images' => [
                    'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Apple Watch Series 8 45mm Midnight Aluminum. Includes magnetic fast charger cable and Midnight Sport Band.',
            ],
            [
                'title' => 'Kindle Paperwhite 11th Gen',
                'seller_username' => 'rahim_ahmed',
                'category_slug' => 'books',
                'price' => 130.00,
                'condition' => 'used',
                'brand' => 'Amazon',
                'model' => 'Kindle Paperwhite 6.8" 16GB',
                'is_boosted' => false,
                'is_negotiable' => false,
                'delivery_type' => 'delivery',
                'delivery_charge' => 2.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subDays(2),
                'views_count' => 98,
                'images' => [
                    'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Amazon Kindle Paperwhite 11th Gen (6.8" glare-free screen with adjustable warm light). Waterproof, 10 weeks battery life. Includes magnetic flip cover.',
            ],
            [
                'title' => 'PlayStation 5 Digital Edition',
                'seller_username' => 'tanvir_hasan',
                'category_slug' => 'electronics',
                'price' => 480.00,
                'condition' => 'used',
                'brand' => 'Sony',
                'model' => 'PlayStation 5 Digital 825GB',
                'is_boosted' => false,
                'is_negotiable' => true,
                'delivery_type' => 'pickup',
                'delivery_charge' => 0.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subDays(3),
                'views_count' => 254,
                'images' => [
                    'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Sony PlayStation 5 Digital Edition with DualSense wireless controller, HDMI 2.1 cable, stand, and power cord. Barely used.',
            ],
            [
                'title' => 'Modern Ceramic Dinner Set (24 pcs)',
                'seller_username' => 'nusrat_jahan',
                'category_slug' => 'home-living',
                'price' => 75.00,
                'condition' => 'new',
                'brand' => 'Shinepukur Ceramics',
                'model' => 'Bone China 24-Piece Set',
                'is_boosted' => false,
                'is_negotiable' => false,
                'delivery_type' => 'delivery',
                'delivery_charge' => 6.00,
                'location' => 'Dhaka, Bangladesh',
                'created_at' => now()->subDays(3),
                'views_count' => 87,
                'images' => [
                    'https://images.unsplash.com/photo-1610701596007-11502861dcfa?w=800&auto=format&fit=crop&q=80',
                ],
                'description' => 'Complete 24-piece bone china luxury dinner set in original gift packaging. Microwave & dishwasher safe.',
            ],
        ];

        foreach ($products as $p) {
            $seller = $sellerMap[$p['seller_username']] ?? User::first();
            $category = $categoryMap[$p['category_slug']] ?? MarketplaceCategory::first();

            $product = MarketplaceProduct::updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'title' => $p['title'],
                ],
                [
                    'category_id' => $category->id,
                    'description' => $p['description'],
                    'price' => $p['price'],
                    'currency' => 'USD',
                    'condition' => $p['condition'],
                    'brand' => $p['brand'],
                    'model' => $p['model'],
                    'is_boosted' => $p['is_boosted'],
                    'is_negotiable' => $p['is_negotiable'],
                    'delivery_type' => $p['delivery_type'],
                    'delivery_charge' => $p['delivery_charge'],
                    'location' => $p['location'],
                    'images' => $p['images'],
                    'views_count' => $p['views_count'],
                    'status' => 'active',
                    'created_at' => $p['created_at'],
                ]
            );

            // Seed realistic reviews for sellers
            $buyerPool = array_values(array_filter($sellerMap, fn ($u) => $u->id !== $seller->id));
            if (! empty($buyerPool) && $product->reviews()->count() === 0) {
                $reviewCount = match ($p['seller_username']) {
                    'rahim_ahmed' => 5,
                    'sadia_islam' => 4,
                    'tanvir_hasan' => 6,
                    'nusrat_jahan' => 3,
                    'rifat_mahmud' => 3,
                    'imran_hossain' => 5,
                    'farhana_akter' => 4,
                    'shakil_khan' => 6,
                    default => 2,
                };

                for ($i = 0; $i < $reviewCount; $i++) {
                    $b = $buyerPool[$i % count($buyerPool)];
                    MarketplaceReview::create([
                        'product_id' => $product->id,
                        'seller_id' => $seller->id,
                        'buyer_id' => $b->id,
                        'rating' => 5,
                        'comment' => 'Excellent communication and the product condition was exactly as described! Highly recommended seller.',
                    ]);
                }
            }
        }
    }
}
