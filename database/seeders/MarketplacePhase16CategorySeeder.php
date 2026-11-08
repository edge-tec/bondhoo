<?php

namespace Database\Seeders;

use App\Models\MarketplaceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplacePhase16CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'icon' => 'smartphone',
                'display_order' => 1,
                'subcategories' => [
                    'Smartphones',
                    'Computers & Laptops',
                    'Cameras',
                    'Gaming',
                    'Accessories',
                    'Home Electronics',
                ],
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'icon' => 'shirt',
                'display_order' => 2,
                'subcategories' => [
                    "Men's Fashion",
                    "Women's Fashion",
                    'Shoes',
                    'Watches',
                    'Bags',
                    'Fashion Accessories',
                ],
            ],
            [
                'name' => 'Vehicles',
                'slug' => 'vehicles',
                'icon' => 'car',
                'display_order' => 3,
                'subcategories' => [
                    'Cars',
                    'Motorcycles',
                    'Bicycles',
                    'Auto Parts',
                    'Commercial Vehicles',
                ],
            ],
            [
                'name' => 'Property',
                'slug' => 'property',
                'icon' => 'building',
                'display_order' => 4,
                'subcategories' => [
                    'Apartments',
                    'Houses',
                    'Land',
                    'Commercial Property',
                    'Room Rentals',
                ],
            ],
            [
                'name' => 'Home & Living',
                'slug' => 'home-living',
                'icon' => 'home',
                'display_order' => 5,
                'subcategories' => [
                    'Furniture',
                    'Kitchen Appliances',
                    'Home Decor',
                    'Household Items',
                ],
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'icon' => 'activity',
                'display_order' => 6,
                'subcategories' => [
                    'Fitness Equipment',
                    'Outdoor Equipment',
                    'Sports Accessories',
                ],
            ],
            [
                'name' => 'Books',
                'slug' => 'books',
                'icon' => 'book-open',
                'display_order' => 7,
                'subcategories' => [
                    'Academic Books',
                    'Novels',
                    'Religious Books',
                    'Professional Books',
                ],
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'icon' => 'grid',
                'display_order' => 8,
                'subcategories' => [
                    'Miscellaneous Products',
                    'Collectibles',
                    'Other Services',
                ],
            ],
        ];

        foreach ($categoriesData as $catData) {
            $category = MarketplaceCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'icon' => $catData['icon'],
                    'display_order' => $catData['display_order'],
                    'is_active' => true,
                    'parent_id' => null,
                ]
            );

            foreach ($catData['subcategories'] as $order => $subName) {
                $subSlug = Str::slug($catData['slug'].'-'.$subName);
                MarketplaceCategory::updateOrCreate(
                    ['slug' => $subSlug],
                    [
                        'parent_id' => $category->id,
                        'name' => $subName,
                        'icon' => $catData['icon'],
                        'display_order' => $order + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
