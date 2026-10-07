<?php

namespace Database\Seeders;

use App\Models\PageCategory;
use App\Models\PageCategoryField;
use App\Models\PageSubcategory;
use App\Models\PageType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageTaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * সম্পূর্ণ পেজ টাইপ, ক্যাটাগরি, সাবক্যাটাগরি ও ডায়নামিক কাস্টম ফিল্ড ডাটাবেজে যুক্ত করে।
     */
    public function run(): void
    {
        $typesData = [
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Commercial enterprises, companies, local stores, and corporations.',
                'icon' => 'briefcase',
                'categories' => [
                    [
                        'name' => 'Company',
                        'slug' => 'company',
                        'subcategories' => ['Private Limited', 'Public Limited', 'Corporation', 'Conglomerate'],
                    ],
                    [
                        'name' => 'Small Business',
                        'slug' => 'small-business',
                        'subcategories' => ['Sole Proprietorship', 'Home Business', 'Local Workshop', 'Freelance Service'],
                    ],
                    [
                        'name' => 'Local Business',
                        'slug' => 'local-business',
                        'subcategories' => ['Neighborhood Shop', 'Local Repair Shop', 'Dry Cleaner', 'Laundromat'],
                    ],
                    [
                        'name' => 'Service Business',
                        'slug' => 'service-business',
                        'subcategories' => ['Plumbing', 'Electrician', 'Carpentry', 'Cleaning Service', 'Security Service'],
                    ],
                    [
                        'name' => 'Online Business',
                        'slug' => 'online-business',
                        'subcategories' => ['Digital Agency', 'Web Services', 'Affiliate Business', 'Drop Shipping'],
                    ],
                ],
            ],
            [
                'name' => 'Brand',
                'slug' => 'brand',
                'description' => 'Consumer goods, fashion, personal, and tech brands.',
                'icon' => 'award',
                'categories' => [
                    [
                        'name' => 'Consumer Brand',
                        'slug' => 'consumer-brand',
                        'subcategories' => ['FMCG', 'Household Goods', 'Beverages', 'Packaged Foods'],
                    ],
                    [
                        'name' => 'Fashion Brand',
                        'slug' => 'fashion-brand',
                        'subcategories' => ['Clothing', 'Footwear', 'Accessories', 'Jewelry', 'Luxury Goods'],
                    ],
                    [
                        'name' => 'Technology Brand',
                        'slug' => 'technology-brand',
                        'subcategories' => ['Consumer Electronics', 'Gadgets', 'Smart Home', 'Wearables'],
                    ],
                    [
                        'name' => 'Personal Brand',
                        'slug' => 'personal-brand',
                        'subcategories' => ['Executive Brand', 'Thought Leader', 'Founder Brand'],
                    ],
                ],
            ],
            [
                'name' => 'Creator',
                'slug' => 'creator',
                'description' => 'Bloggers, vloggers, influencers, podcasters, and digital content creators.',
                'icon' => 'video',
                'categories' => [
                    [
                        'name' => 'Content Creator',
                        'slug' => 'content-creator',
                        'subcategories' => ['Vlogger', 'YouTuber', 'TikTok Creator', 'Reels Creator', 'Short Film Maker'],
                    ],
                    [
                        'name' => 'Blogger',
                        'slug' => 'blogger',
                        'subcategories' => ['Tech Blogger', 'Travel Blogger', 'Food Blogger', 'Fashion Blogger', 'Lifestyle Blogger'],
                    ],
                    [
                        'name' => 'Streamer',
                        'slug' => 'streamer',
                        'subcategories' => ['Gaming Streamer', 'Live Entertainer', 'IRL Streamer', 'Music Streamer'],
                    ],
                    [
                        'name' => 'Podcaster',
                        'slug' => 'podcaster',
                        'subcategories' => ['Interview Podcast', 'Tech Podcast', 'News Podcast', 'Storytelling Podcast'],
                    ],
                ],
            ],
            [
                'name' => 'Public Figure',
                'slug' => 'public-figure',
                'description' => 'Artists, musicians, actors, athletes, authors, and public personalities.',
                'icon' => 'user-check',
                'categories' => [
                    [
                        'name' => 'Artist & Performer',
                        'slug' => 'artist-performer',
                        'subcategories' => ['Musician', 'Band', 'Singer', 'Actor', 'Comedian', 'Dancer', 'Painter'],
                    ],
                    [
                        'name' => 'Athlete & Sports Figure',
                        'slug' => 'athlete-sports',
                        'subcategories' => ['Cricketer', 'Footballer', 'Tennis Player', 'Martial Artist', 'Coach'],
                    ],
                    [
                        'name' => 'Author & Speaker',
                        'slug' => 'author-speaker',
                        'subcategories' => ['Writer', 'Journalist', 'Keynote Speaker', 'Motivational Speaker'],
                    ],
                ],
            ],
            [
                'name' => 'Organization',
                'slug' => 'organization',
                'description' => 'NGOs, non-profits, foundations, societies, and clubs.',
                'icon' => 'shield',
                'categories' => [
                    [
                        'name' => 'Nonprofit & NGO',
                        'slug' => 'nonprofit-ngo',
                        'subcategories' => ['Charity', 'Humanitarian', 'Environmental', 'Human Rights', 'Animal Welfare'],
                    ],
                    [
                        'name' => 'Foundation',
                        'slug' => 'foundation',
                        'subcategories' => ['Educational Foundation', 'Medical Foundation', 'Family Foundation'],
                    ],
                    [
                        'name' => 'Association & Club',
                        'slug' => 'association-club',
                        'subcategories' => ['Youth Club', 'Alumni Association', 'Sports Club', 'Rotary Club'],
                    ],
                ],
            ],
            [
                'name' => 'Community',
                'slug' => 'community',
                'description' => 'Local, fan, hobby, and social interest communities.',
                'icon' => 'users',
                'categories' => [
                    [
                        'name' => 'Interest Community',
                        'slug' => 'interest-community',
                        'subcategories' => ['Tech Enthusiasts', 'Book Club', 'Photography Group', 'Car Enthusiasts'],
                    ],
                    [
                        'name' => 'Local Community',
                        'slug' => 'local-community',
                        'subcategories' => ['City Community', 'Neighborhood Group', 'Village Society'],
                    ],
                ],
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Schools, colleges, universities, academies, and coaching institutes.',
                'icon' => 'book-open',
                'categories' => [
                    [
                        'name' => 'School & College',
                        'slug' => 'school-college',
                        'subcategories' => ['Primary School', 'High School', 'College', 'English Medium School'],
                    ],
                    [
                        'name' => 'University',
                        'slug' => 'university',
                        'subcategories' => ['Public University', 'Private University', 'Engineering University', 'Medical College'],
                    ],
                    [
                        'name' => 'Training & Academy',
                        'slug' => 'training-academy',
                        'subcategories' => ['IT Institute', 'Language Center', 'Coaching Center', 'Vocational Institute'],
                    ],
                ],
            ],
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'description' => 'Hospitals, clinics, doctors, diagnostic centers, and pharmacies.',
                'icon' => 'heart',
                'categories' => [
                    [
                        'name' => 'Hospital & Clinic',
                        'slug' => 'hospital-clinic',
                        'subcategories' => ['General Hospital', 'Specialized Hospital', 'Dental Clinic', 'Eye Hospital'],
                    ],
                    [
                        'name' => 'Doctor & Specialist',
                        'slug' => 'doctor-specialist',
                        'subcategories' => ['Cardiologist', 'Pediatrician', 'Dermatologist', 'Orthopedic Surgeon'],
                    ],
                    [
                        'name' => 'Pharmacy & Diagnostics',
                        'slug' => 'pharmacy-diagnostics',
                        'subcategories' => ['Retail Pharmacy', 'Diagnostic Center', 'Pathology Lab', 'Blood Bank'],
                    ],
                ],
            ],
            [
                'name' => 'Media',
                'slug' => 'media',
                'description' => 'Newspapers, TV channels, magazines, radio stations, and online portals.',
                'icon' => 'radio',
                'categories' => [
                    [
                        'name' => 'News & Publication',
                        'slug' => 'news-publication',
                        'subcategories' => ['Daily Newspaper', 'Online News Portal', 'Magazine', 'Journal'],
                    ],
                    [
                        'name' => 'Broadcasting',
                        'slug' => 'broadcasting',
                        'subcategories' => ['Television Network', 'Radio Station', 'Satellite Channel'],
                    ],
                ],
            ],
            [
                'name' => 'Restaurant / Food',
                'slug' => 'restaurant-food',
                'description' => 'Dining establishments, cafes, bakeries, fast food, and food delivery.',
                'icon' => 'coffee',
                'categories' => [
                    [
                        'name' => 'Dining & Restaurant',
                        'slug' => 'dining-restaurant',
                        'subcategories' => ['Fine Dining', 'Casual Dining', 'Traditional Cuisine', 'Buffet', 'Seafood'],
                    ],
                    [
                        'name' => 'Cafe & Bakery',
                        'slug' => 'cafe-bakery',
                        'subcategories' => ['Coffee Shop', 'Tea Lounge', 'Artisan Bakery', 'Pastry Shop'],
                    ],
                    [
                        'name' => 'Fast Food & Takeaway',
                        'slug' => 'fast-food-takeaway',
                        'subcategories' => ['Burger Joint', 'Pizza Parlor', 'Fried Chicken', 'Street Food'],
                    ],
                    [
                        'name' => 'Catering & Cloud Kitchen',
                        'slug' => 'catering-cloud-kitchen',
                        'subcategories' => ['Event Catering', 'Cloud Kitchen', 'Food Delivery Service'],
                    ],
                ],
            ],
            [
                'name' => 'Hospitality',
                'slug' => 'hospitality',
                'description' => 'Hotels, resorts, guest houses, and vacation accommodations.',
                'icon' => 'home',
                'categories' => [
                    [
                        'name' => 'Hotel & Resort',
                        'slug' => 'hotel-resort',
                        'subcategories' => ['5-Star Hotel', 'Boutique Hotel', 'Eco Resort', 'Beach Resort'],
                    ],
                    [
                        'name' => 'Guest House & Stay',
                        'slug' => 'guest-house-stay',
                        'subcategories' => ['Guest House', 'Hostel', 'Homestay', 'Vacation Rental'],
                    ],
                ],
            ],
            [
                'name' => 'Retail',
                'slug' => 'retail',
                'description' => 'Physical stores, e-commerce brands, supermarkets, and electronics.',
                'icon' => 'shopping-bag',
                'categories' => [
                    [
                        'name' => 'E-Commerce Store',
                        'slug' => 'ecommerce-store',
                        'subcategories' => ['Online Marketplace', 'Direct-to-Consumer', 'Fashion Store', 'Gadget Store'],
                    ],
                    [
                        'name' => 'Supermarket & Grocery',
                        'slug' => 'supermarket-grocery',
                        'subcategories' => ['Supermarket', 'Organic Food Store', 'Convenience Store'],
                    ],
                ],
            ],
            [
                'name' => 'Professional Services',
                'slug' => 'professional-services',
                'description' => 'Legal, accounting, marketing, consulting, and design agencies.',
                'icon' => 'file-text',
                'categories' => [
                    [
                        'name' => 'Legal & Financial',
                        'slug' => 'legal-financial',
                        'subcategories' => ['Law Firm', 'Accounting Firm', 'Tax Consultant', 'Auditing Firm'],
                    ],
                    [
                        'name' => 'Agency & Consulting',
                        'slug' => 'agency-consulting',
                        'subcategories' => ['Marketing Agency', 'Software Agency', 'Creative Design Studio', 'HR Consultancy'],
                    ],
                ],
            ],
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Software companies, SaaS startups, AI, and IT infrastructure.',
                'icon' => 'cpu',
                'categories' => [
                    [
                        'name' => 'Software & SaaS',
                        'slug' => 'software-saas',
                        'subcategories' => ['Enterprise SaaS', 'Mobile Apps', 'Fintech', 'Cloud Infrastructure'],
                    ],
                    [
                        'name' => 'IT Services & AI',
                        'slug' => 'it-services-ai',
                        'subcategories' => ['Artificial Intelligence', 'Cybersecurity', 'Web Development', 'Data Analytics'],
                    ],
                ],
            ],
            [
                'name' => 'Real Estate',
                'slug' => 'real-estate',
                'description' => 'Developers, brokers, property management, and construction firms.',
                'icon' => 'map-pin',
                'categories' => [
                    [
                        'name' => 'Property & Brokerage',
                        'slug' => 'property-brokerage',
                        'subcategories' => ['Real Estate Agency', 'Commercial Leasing', 'Apartment Sales'],
                    ],
                    [
                        'name' => 'Development & Construction',
                        'slug' => 'development-construction',
                        'subcategories' => ['Real Estate Developer', 'Construction Company', 'Interior Architecture'],
                    ],
                ],
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Travel agencies, tour operators, and transportation services.',
                'icon' => 'compass',
                'categories' => [
                    [
                        'name' => 'Tour & Travel Agency',
                        'slug' => 'tour-travel-agency',
                        'subcategories' => ['Domestic Tours', 'International Holidays', 'Hajj & Umrah Agency', 'Visa Consultancy'],
                    ],
                ],
            ],
            [
                'name' => 'Beauty & Lifestyle',
                'slug' => 'beauty-lifestyle',
                'description' => 'Salons, spas, wellness centers, cosmetics, and fitness studios.',
                'icon' => 'smile',
                'categories' => [
                    [
                        'name' => 'Salon & Spa',
                        'slug' => 'salon-spa',
                        'subcategories' => ['Beauty Parlor', 'Hair Salon', 'Luxury Spa', 'Gentlemen Grooming'],
                    ],
                    [
                        'name' => 'Fitness & Gym',
                        'slug' => 'fitness-gym',
                        'subcategories' => ['Gymnasium', 'Yoga Studio', 'CrossFit Center', 'Pilates Studio'],
                    ],
                ],
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Teams, sports clubs, training academies, and athletic federations.',
                'icon' => 'activity',
                'categories' => [
                    [
                        'name' => 'Sports Club & Team',
                        'slug' => 'sports-club-team',
                        'subcategories' => ['Cricket Club', 'Football Team', 'Athletics Academy', 'Swimming Club'],
                    ],
                ],
            ],
            [
                'name' => 'Events',
                'slug' => 'events',
                'description' => 'Event planners, wedding organizers, and festivals.',
                'icon' => 'calendar',
                'categories' => [
                    [
                        'name' => 'Event Management',
                        'slug' => 'event-management',
                        'subcategories' => ['Wedding Planner', 'Corporate Event Management', 'Concert Organizer', 'Convention Center'],
                    ],
                ],
            ],
            [
                'name' => 'Product',
                'slug' => 'product',
                'description' => 'Specific single products, product lines, and inventions.',
                'icon' => 'box',
                'categories' => [
                    [
                        'name' => 'Consumer Product',
                        'slug' => 'consumer-product',
                        'subcategories' => ['Physical Hardware', 'Software Product', 'Specialty Item'],
                    ],
                ],
            ],
            [
                'name' => 'Government / Public Service',
                'slug' => 'government-public-service',
                'description' => 'Government offices, public institutions, and civic utilities.',
                'icon' => 'flag',
                'categories' => [
                    [
                        'name' => 'Public Administration',
                        'slug' => 'public-administration',
                        'subcategories' => ['City Corporation', 'Ministry/Department', 'Public Utility', 'Embassy/Consulate'],
                    ],
                ],
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Any other legitimate business, creator, or community entity.',
                'icon' => 'more-horizontal',
                'categories' => [
                    [
                        'name' => 'General Page',
                        'slug' => 'general-page',
                        'subcategories' => ['Custom Entity', 'Unspecified Community', 'Special Project'],
                    ],
                ],
            ],
        ];

        $sortOrder = 1;
        foreach ($typesData as $tData) {
            $pageType = PageType::updateOrCreate(
                ['slug' => $tData['slug']],
                [
                    'name' => $tData['name'],
                    'description' => $tData['description'],
                    'icon' => $tData['icon'],
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]
            );

            $catOrder = 1;
            foreach ($tData['categories'] as $cData) {
                $category = PageCategory::updateOrCreate(
                    [
                        'page_type_id' => $pageType->id,
                        'slug' => $cData['slug'],
                    ],
                    [
                        'name' => $cData['name'],
                        'description' => "Category for {$cData['name']}",
                        'icon' => $tData['icon'],
                        'is_popular' => in_array($cData['slug'], ['company', 'dining-restaurant', 'ecommerce-store', 'content-creator', 'software-saas']),
                        'is_active' => true,
                        'sort_order' => $catOrder++,
                    ]
                );

                $subOrder = 1;
                foreach ($cData['subcategories'] as $subName) {
                    PageSubcategory::updateOrCreate(
                        [
                            'page_category_id' => $category->id,
                            'slug' => Str::slug($subName),
                        ],
                        [
                            'name' => $subName,
                            'description' => "Subcategory: {$subName}",
                            'icon' => 'tag',
                            'is_active' => true,
                            'sort_order' => $subOrder++,
                        ]
                    );
                }
            }
        }

        // ডায়নামিক ইন্ডাস্ট্রি-স্পেসিফিক ফিল্ডস সিড করা
        $this->seedIndustryFields();
    }

    /**
     * বিভিন্ন ইন্ডাস্ট্রির জন্য ডায়নামিক ফিল্ডস কনফিগার করা
     */
    protected function seedIndustryFields(): void
    {
        // ১. রেস্টুরেন্ট / ফুড ডায়নামিক ফিল্ডস
        $foodType = PageType::where('slug', 'restaurant-food')->first();
        if ($foodType) {
            $fields = [
                [
                    'field_key' => 'cuisine',
                    'label' => 'Cuisine Type',
                    'field_type' => 'text',
                    'placeholder' => 'e.g. Bangladeshi, Italian, Pan-Asian, Continental',
                    'is_required' => true,
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'price_range',
                    'label' => 'Price Range',
                    'field_type' => 'select',
                    'options' => ['$' => 'Budget ($)', '$$' => 'Moderate ($$)', '$$$' => 'Fine Dining ($$$)', '$$$$' => 'Luxury ($$$$)'],
                    'default_value' => '$$',
                    'is_required' => false,
                    'sort_order' => 2,
                ],
                [
                    'field_key' => 'delivery_available',
                    'label' => 'Home Delivery Available',
                    'field_type' => 'boolean',
                    'default_value' => '1',
                    'sort_order' => 3,
                ],
                [
                    'field_key' => 'reservation_url',
                    'label' => 'Table Reservation URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 4,
                ],
                [
                    'field_key' => 'menu_url',
                    'label' => 'Online Menu URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 5,
                ],
                [
                    'field_key' => 'outdoor_seating',
                    'label' => 'Outdoor Seating Available',
                    'field_type' => 'boolean',
                    'default_value' => '0',
                    'sort_order' => 6,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $foodType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $foodType->id, 'is_active' => true])
                );
            }
        }

        // ২. হোটেল / রিসোর্ট ডায়নামিক ফিল্ডস
        $hotelType = PageType::where('slug', 'hospitality')->first();
        if ($hotelType) {
            $fields = [
                [
                    'field_key' => 'star_rating',
                    'label' => 'Star Rating',
                    'field_type' => 'select',
                    'options' => ['3' => '3 Star', '4' => '4 Star', '5' => '5 Star', 'resort' => 'Luxury Resort'],
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'check_in_time',
                    'label' => 'Standard Check-in Time',
                    'field_type' => 'time',
                    'placeholder' => '14:00',
                    'sort_order' => 2,
                ],
                [
                    'field_key' => 'check_out_time',
                    'label' => 'Standard Check-out Time',
                    'field_type' => 'time',
                    'placeholder' => '12:00',
                    'sort_order' => 3,
                ],
                [
                    'field_key' => 'amenities',
                    'label' => 'Key Amenities',
                    'field_type' => 'text',
                    'placeholder' => 'Swimming Pool, Free Wi-Fi, Gym, Free Breakfast',
                    'sort_order' => 4,
                ],
                [
                    'field_key' => 'booking_url',
                    'label' => 'Direct Room Booking URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 5,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $hotelType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $hotelType->id, 'is_active' => true])
                );
            }
        }

        // ৩. এডুকেশন ডায়নামিক ফিল্ডস
        $eduType = PageType::where('slug', 'education')->first();
        if ($eduType) {
            $fields = [
                [
                    'field_key' => 'education_level',
                    'label' => 'Institution Level',
                    'field_type' => 'select',
                    'options' => ['k12' => 'School (K-12)', 'higher_sec' => 'College (HSC)', 'undergraduate' => 'University / Tertiary', 'vocational' => 'Vocational / Professional'],
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'admission_url',
                    'label' => 'Online Admission URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 2,
                ],
                [
                    'field_key' => 'principal_or_vc',
                    'label' => 'Head of Institution / Vice Chancellor',
                    'field_type' => 'text',
                    'placeholder' => 'Full Name and Title',
                    'sort_order' => 3,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $eduType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $eduType->id, 'is_active' => true])
                );
            }
        }

        // ৪. হেলথকেয়ার ডায়নামিক ফিল্ডস
        $healthType = PageType::where('slug', 'healthcare')->first();
        if ($healthType) {
            $fields = [
                [
                    'field_key' => 'emergency_service',
                    'label' => '24/7 Emergency Service Available',
                    'field_type' => 'boolean',
                    'default_value' => '1',
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'appointment_url',
                    'label' => 'Online Doctor Appointment URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 2,
                ],
                [
                    'field_key' => 'hotline_number',
                    'label' => 'Emergency Ambulance / Hotline',
                    'field_type' => 'phone',
                    'placeholder' => '+8801...',
                    'sort_order' => 3,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $healthType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $healthType->id, 'is_active' => true])
                );
            }
        }

        // ৫. ই-কমার্স / রিটেইল ডায়নামিক ফিল্ডস
        $retailType = PageType::where('slug', 'retail')->first();
        if ($retailType) {
            $fields = [
                [
                    'field_key' => 'store_url',
                    'label' => 'Online Store Website',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'shipping_areas',
                    'label' => 'Shipping & Delivery Coverage',
                    'field_type' => 'text',
                    'placeholder' => 'All over Bangladesh / Nationwide / Worldwide',
                    'sort_order' => 2,
                ],
                [
                    'field_key' => 'return_policy_url',
                    'label' => 'Return Policy URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 3,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $retailType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $retailType->id, 'is_active' => true])
                );
            }
        }

        // ৬. এনজিও / অর্গানাইজেশন ডায়নামিক ফিল্ডস
        $orgType = PageType::where('slug', 'organization')->first();
        if ($orgType) {
            $fields = [
                [
                    'field_key' => 'mission_statement',
                    'label' => 'Mission & Vision Statement',
                    'field_type' => 'textarea',
                    'placeholder' => 'Describe your core mission and community impact...',
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'donation_url',
                    'label' => 'Official Donation URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 2,
                ],
                [
                    'field_key' => 'volunteer_url',
                    'label' => 'Volunteer Sign-up URL',
                    'field_type' => 'url',
                    'placeholder' => 'https://...',
                    'sort_order' => 3,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $orgType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $orgType->id, 'is_active' => true])
                );
            }
        }

        // ৭. ক্রিয়েটর ডায়নামিক ফিল্ডস
        $creatorType = PageType::where('slug', 'creator')->first();
        if ($creatorType) {
            $fields = [
                [
                    'field_key' => 'content_focus',
                    'label' => 'Primary Content Focus',
                    'field_type' => 'text',
                    'placeholder' => 'e.g. Technology Reviews, Travel, Comedy, Fitness',
                    'sort_order' => 1,
                ],
                [
                    'field_key' => 'booking_email',
                    'label' => 'Business / Brand Sponsorship Email',
                    'field_type' => 'email',
                    'placeholder' => 'business@creator.com',
                    'sort_order' => 2,
                ],
            ];

            foreach ($fields as $f) {
                PageCategoryField::updateOrCreate(
                    ['page_type_id' => $creatorType->id, 'field_key' => $f['field_key']],
                    array_merge($f, ['page_type_id' => $creatorType->id, 'is_active' => true])
                );
            }
        }
    }
}
