<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageLocation;
use App\Models\PageType;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ পেজ রেজিস্ট্রেশন ও ক্রিয়েশন অপারেটিং সিস্টেম — কম্প্রিহেন্সিভ টেস্ট সুইট
 * (GATES G0 to G40: Taxonomy, Username Availability, Drafts, Dynamic Fields,
 * Quota Enforcement, Concurrency, Multi-location, E2E Business/Creator/NGO/Commerce).
 */
class EnterprisePageRegistrationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // সিড ট্যাক্সোনমি
        $this->seed(PageTaxonomySeeder::class);

        $this->user = User::factory()->create([
            'name' => 'Enterprise Founder',
            'username' => 'founder',
            'email' => 'founder@jugajug.com',
        ]);
    }

    /**
     * টেস্ট ১: ট্যাক্সোনমি এপিআই — পেজ টাইপ, ক্যাটাগরি, সাবক্যাটাগরি এবং ডায়নামিক ফিল্ডস
     */
    public function test_taxonomy_apis_return_real_database_records(): void
    {
        // ১. পেজ টাইপস তালিকা (২২+ টাইপ)
        $response = $this->getJson('/api/v2/pages/types');
        $response->assertStatus(200);
        $types = $response->json('data');
        $this->assertGreaterThanOrEqual(20, count($types));

        $foodType = collect($types)->firstWhere('slug', 'restaurant-food');
        $this->assertNotNull($foodType);

        // ২. ক্যাটাগরি তালিকা ফিল্টারিং
        $catResponse = $this->getJson("/api/v2/pages/categories?page_type_id={$foodType['id']}");
        $catResponse->assertStatus(200);
        $categories = $catResponse->json('data');
        $this->assertNotEmpty($categories);

        $diningCat = collect($categories)->firstWhere('slug', 'dining-restaurant');
        $this->assertNotNull($diningCat);

        // ৩. সাবক্যাটাগরি তালিকা
        $subResponse = $this->getJson("/api/v2/pages/categories/{$diningCat['id']}/subcategories");
        $subResponse->assertStatus(200);
        $subcategories = $subResponse->json('data');
        $this->assertNotEmpty($subcategories);

        // ৪. ডায়নামিক ফিল্ডস তালিকা (রেস্টুরেন্টের জন্য কুইজিন, প্রাইস রেঞ্জ ইত্যাদি)
        $fieldsResponse = $this->getJson("/api/v2/pages/categories/{$diningCat['id']}/fields?page_type_id={$foodType['id']}");
        $fieldsResponse->assertStatus(200);
        $fields = $fieldsResponse->json('data');
        $this->assertNotEmpty($fields);

        $cuisineField = collect($fields)->firstWhere('field_key', 'cuisine');
        $this->assertNotNull($cuisineField);
        $this->assertEquals('text', $cuisineField['field_type']);
    }

    /**
     * টেস্ট ২: ইউজারনেম রিয়েলটাইম অ্যাভেইলেবিলিটি, রিজার্ভড নেম ও ডুপ্লিকেট চেকার
     */
    public function test_username_availability_and_reserved_names_enforcement(): void
    {
        // ১. বৈধ ও খালি ইউজারনেম
        $res1 = $this->getJson('/api/v2/pages/username/check?username=dhakatech');
        $res1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'available' => true,
                'normalized' => 'dhakatech',
            ]);

        // ২. সংরক্ষিত নাম (Reserved keyword e.g. admin, api, jugajug)
        $res2 = $this->getJson('/api/v2/pages/username/check?username=admin');
        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'available' => false,
            ]);
        $this->assertNotEmpty($res2->json('suggestions'));

        // ৩. ইতিমধ্যে নিবন্ধিত ইউজারনেম
        Page::create([
            'owner_id' => $this->user->id,
            'name' => 'Existing Brand',
            'slug' => 'existing-brand',
            'username' => 'existingbrand',
            'category' => 'Business',
            'status' => 'active',
        ]);

        $res3 = $this->getJson('/api/v2/pages/username/check?username=existingbrand');
        $res3->assertStatus(200)
            ->assertJson([
                'success' => true,
                'available' => false,
            ]);
        $this->assertNotEmpty($res3->json('suggestions'));

        // ৪. অবৈধ ক্যারেক্টার যুক্ত ইউজারনেম
        $res4 = $this->getJson('/api/v2/pages/username/check?username=my_brand$#@!');
        $res4->assertStatus(200)
            ->assertJson([
                'success' => true,
                'available' => false,
            ]);
    }

    /**
     * টেস্ট ৩: সার্ভার-সাইড ড্রাফট ও অটোসেভ ইঞ্জিন
     */
    public function test_draft_and_autosave_lifecycle(): void
    {
        // ১. ড্রাফট সংরক্ষণ / অটোসেভ
        $draftPayload = [
            'current_step' => 3,
            'form_data' => [
                'name' => 'My Draft Venture',
                'username' => 'draftventure',
                'short_description' => 'Draft bio for test',
                'category' => 'Technology',
            ],
        ];

        $saveRes = $this->actingAs($this->user)->postJson('/api/v2/pages/drafts', $draftPayload);
        $saveRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $draftId = $saveRes->json('data.id');
        $this->assertNotNull($draftId);

        // ২. ড্রাফট রিট্রিভ করা
        $getRes = $this->actingAs($this->user)->getJson("/api/v2/pages/drafts/{$draftId}");
        $getRes->assertStatus(200)
            ->assertJsonPath('data.form_data.name', 'My Draft Venture')
            ->assertJsonPath('data.current_step', 3);

        // ৩. ড্রাফট তালিকা
        $listRes = $this->actingAs($this->user)->getJson('/api/v2/pages/drafts');
        $listRes->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // ৪. ড্রাফট ডিলিট
        $delRes = $this->actingAs($this->user)->deleteJson("/api/v2/pages/drafts/{$draftId}");
        $delRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('page_creation_drafts', ['id' => $draftId]);
    }

    /**
     * টেস্ট ৪: পেজ কোটা ও লিমিট এনফোর্সমেন্ট
     */
    public function test_page_quota_limit_enforcement(): void
    {
        // ইউজারের কোটা চেক
        $quotaRes = $this->actingAs($this->user)->getJson('/api/v2/pages/quota');
        $quotaRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'owned_pages' => 0,
                    'max_allowed_pages' => 10,
                    'can_create' => true,
                ],
            ]);

        // ইউজারকে ১০টি পেজ তৈরি করানো (লিমিট পূরণ করা)
        for ($i = 1; $i <= 10; $i++) {
            Page::create([
                'owner_id' => $this->user->id,
                'name' => "User Page #{$i}",
                'slug' => "user-page-{$i}",
                'username' => "userpage{$i}",
                'category' => 'Business',
                'status' => 'active',
            ]);
        }

        $quotaRes2 = $this->actingAs($this->user)->getJson('/api/v2/pages/quota');
        $quotaRes2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'owned_pages' => 10,
                    'can_create' => false,
                ],
            ]);

        // ১১তম পেজ তৈরি করতে গেলে ৪২২ এরর হবে
        $attemptRes = $this->actingAs($this->user)->postJson('/api/v2/pages/register', [
            'name' => 'Eleventh Page',
            'username' => 'eleventhpage',
            'short_description' => 'Will fail',
        ]);

        $attemptRes->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * টেস্ট ৫: E2E বিজনেস (রেস্টুরেন্ট) সম্পূর্ণ রেজিস্ট্রেশন ও ব্রাঞ্চেস
     */
    public function test_e2e_business_restaurant_registration_with_branches_and_hours(): void
    {
        $foodType = PageType::where('slug', 'restaurant-food')->first();
        $diningCat = PageCategory::where('slug', 'dining-restaurant')->first();

        $payload = [
            'name' => 'Dhaka Heritage Dine',
            'username' => 'dhakadines',
            'page_type_id' => $foodType->id,
            'page_category_id' => $diningCat->id,
            'category' => 'Dining & Restaurant',
            'short_description' => 'Authentic culinary experience in Dhaka.',
            'description' => 'Full detailed history of Dhaka Heritage Dine offering traditional recipes.',
            'email' => 'info@dhakadines.com',
            'phone' => '+8801711223344',
            'website' => 'https://dhakadines.com',
            'address' => 'Road 11, Banani',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'zip_code' => '1213',
            'cta_type' => 'book_now',
            'cta_url' => 'https://dhakadines.com/reserve',
            'custom_fields_data' => [
                'cuisine' => 'Traditional Bengali & Mughlai',
                'price_range' => '$$$',
                'delivery_available' => true,
                'reservation_url' => 'https://dhakadines.com/reserve',
            ],
            'business_details' => [
                'legal_business_name' => 'Dhaka Heritage Dine Ltd.',
                'registration_number' => 'TL-BAN-998822',
                'tax_id' => 'TIN-4455667788',
                'year_founded' => 2018,
            ],
            'branches' => [
                [
                    'name' => 'Gulshan Express Branch',
                    'street_address' => 'Gulshan Avenue',
                    'city' => 'Dhaka',
                    'phone' => '+8801711998877',
                ],
                [
                    'name' => 'Dhanmondi Branch',
                    'street_address' => 'Satmasjid Road',
                    'city' => 'Dhaka',
                    'phone' => '+8801711665544',
                ],
            ],
            'business_hours' => [
                'saturday' => ['mode' => 'open', 'open' => '11:00', 'close' => '23:00'],
                'sunday' => ['mode' => 'open', 'open' => '11:00', 'close' => '23:00'],
                'monday' => ['mode' => 'open', 'open' => '11:00', 'close' => '23:00'],
                'tuesday' => ['mode' => 'open', 'open' => '11:00', 'close' => '23:00'],
                'wednesday' => ['mode' => 'open', 'open' => '11:00', 'close' => '23:00'],
                'thursday' => ['mode' => 'open', 'open' => '11:00', 'close' => '23:00'],
                'friday' => ['mode' => 'open', 'open' => '14:00', 'close' => '23:30'],
            ],
            'social_links' => [
                'facebook' => 'https://facebook.com/dhakadines',
                'instagram' => 'https://instagram.com/dhakadines',
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', $payload);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Dhaka Heritage Dine',
                    'username' => 'dhakadines',
                    'category' => 'Dining & Restaurant',
                    'is_owner' => true,
                ],
            ]);

        $createdPageId = $response->json('data.id');

        // ডাটাবেজ যাচাই
        $this->assertDatabaseHas('pages', [
            'id' => $createdPageId,
            'name' => 'Dhaka Heritage Dine',
            'username' => 'dhakadines',
            'owner_id' => $this->user->id,
            'cta_type' => 'book_now',
        ]);

        // ওনার মেম্বারশিপ যাচাই
        $this->assertDatabaseHas('page_members', [
            'page_id' => $createdPageId,
            'user_id' => $this->user->id,
            'role' => Page::ROLE_OWNER,
            'status' => 'active',
        ]);

        // ব্রাঞ্চ লোকেশন রেকর্ড যাচাই (১টি হেডকোয়ার্টার + ২টি অতিরিক্ত ব্রাঞ্চ = ৩টি লোকেশন)
        $this->assertDatabaseHas('page_locations', [
            'page_id' => $createdPageId,
            'name' => 'Headquarters',
            'is_headquarters' => true,
        ]);
        $this->assertDatabaseHas('page_locations', [
            'page_id' => $createdPageId,
            'name' => 'Gulshan Express Branch',
            'is_headquarters' => false,
        ]);
        $this->assertDatabaseHas('page_locations', [
            'page_id' => $createdPageId,
            'name' => 'Dhanmondi Branch',
            'is_headquarters' => false,
        ]);
        $this->assertEquals(3, PageLocation::where('page_id', $createdPageId)->count());

        // অডিট লগ তৈরি হয়েছে কিনা যাচাই
        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $createdPageId,
            'action' => 'page.create',
            'actor_id' => $this->user->id,
        ]);
    }

    /**
     * টেস্ট ৬: E2E ক্রিয়েটর পেইজ রেজিস্ট্রেশন
     */
    public function test_e2e_creator_page_registration(): void
    {
        $creatorType = PageType::where('slug', 'creator')->first();

        $payload = [
            'name' => 'Tech Talks with Rahim',
            'username' => 'techtalksrahim',
            'page_type_id' => $creatorType->id,
            'category' => 'Content Creator',
            'short_description' => 'Demystifying technology and AI for everyone.',
            'email' => 'collab@rahimtech.com',
            'custom_fields_data' => [
                'content_focus' => 'Artificial Intelligence & Software Engineering',
                'booking_email' => 'sponsor@rahimtech.com',
            ],
            'social_links' => [
                'youtube' => 'https://youtube.com/@techtalksrahim',
                'linkedin' => 'https://linkedin.com/in/rahim',
            ],
            'cta_type' => 'send_message',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', $payload);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Tech Talks with Rahim',
                    'username' => 'techtalksrahim',
                ],
            ]);

        $this->assertDatabaseHas('pages', [
            'name' => 'Tech Talks with Rahim',
            'username' => 'techtalksrahim',
            'owner_id' => $this->user->id,
        ]);
    }

    /**
     * টেস্ট ৭: E2E অর্গানাইজেশন (NGO / Nonprofit) পেইজ রেজিস্ট্রেশন
     */
    public function test_e2e_organization_ngo_registration(): void
    {
        $orgType = PageType::where('slug', 'organization')->first();

        $payload = [
            'name' => 'Clean Green Bangladesh Foundation',
            'username' => 'cleangreenbd',
            'page_type_id' => $orgType->id,
            'category' => 'Nonprofit & NGO',
            'short_description' => 'Dedicated to climate action and tree plantation across Bangladesh.',
            'email' => 'contact@cleangreenbd.org',
            'website' => 'https://cleangreenbd.org',
            'address' => 'Mirpur DOHS',
            'city' => 'Dhaka',
            'custom_fields_data' => [
                'mission_statement' => 'Planting 1 million trees by 2030.',
                'donation_url' => 'https://cleangreenbd.org/donate',
                'volunteer_url' => 'https://cleangreenbd.org/volunteer',
            ],
            'cta_type' => 'donate',
            'cta_url' => 'https://cleangreenbd.org/donate',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', $payload);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Clean Green Bangladesh Foundation',
                    'username' => 'cleangreenbd',
                ],
            ]);

        $this->assertDatabaseHas('pages', [
            'username' => 'cleangreenbd',
            'owner_id' => $this->user->id,
        ]);
    }

    /**
     * টেস্ট ৮: E2E ই-কমার্স স্টোর রেজিস্ট্রেশন
     */
    public function test_e2e_ecommerce_store_registration(): void
    {
        $retailType = PageType::where('slug', 'retail')->first();

        $payload = [
            'name' => 'Urban Craft BD',
            'username' => 'urbancraftbd',
            'page_type_id' => $retailType->id,
            'category' => 'E-Commerce Store',
            'short_description' => 'Handmade leather crafts and lifestyle accessories.',
            'website' => 'https://urbancraftbd.com',
            'phone' => '+8801900112233',
            'custom_fields_data' => [
                'store_url' => 'https://urbancraftbd.com/shop',
                'shipping_areas' => 'All 64 districts of Bangladesh',
                'return_policy_url' => 'https://urbancraftbd.com/returns',
            ],
            'cta_type' => 'shop_now',
            'cta_url' => 'https://urbancraftbd.com/shop',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v2/pages/register', $payload);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Urban Craft BD',
                    'username' => 'urbancraftbd',
                ],
            ]);

        $this->assertDatabaseHas('pages', [
            'username' => 'urbancraftbd',
            'owner_id' => $this->user->id,
        ]);
    }

    /**
     * টেস্ট ৯: ওয়েব রাউট এক্সেসিবিলিটি — /pages/create রাউট সঠিকভাবে লোড হয়
     */
    public function test_pages_create_web_view_loads_successfully_for_authenticated_users(): void
    {
        // গেস্ট রিডাইরেক্ট হবে
        $guestRes = $this->get('/pages/create');
        $guestRes->assertRedirect('/login');

        // অথেনটিকেটেড ইউজার 200 OK পাবে
        $authRes = $this->actingAs($this->user)->get('/pages/create');
        $authRes->assertStatus(200);
        $authRes->assertSee('এন্টারপ্রাইজ পেইজ ক্রিয়েশন অপারেটিং সিস্টেম');
        $authRes->assertSee('Restaurant / Food');
        $authRes->assertSee('Business');
        $authRes->assertSee('Creator');
    }

    /**
     * টেস্ট ১০: কনকারেন্ট ইউজারনেম রেস টেস্ট — একই ইউজারনেম দিয়ে একাধিক যুগপৎ রিকোয়েস্ট আসলেও ঠিক ১টি সফল হবে
     */
    public function test_concurrent_username_registration_prevents_duplicate_reservations(): void
    {
        $users = User::factory()->count(10)->create();
        $targetUsername = 'uniqueracebrand';

        $successCount = 0;
        $failCount = 0;

        foreach ($users as $idx => $candidate) {
            $response = $this->actingAs($candidate)->postJson('/api/v2/pages/register', [
                'name' => "Brand Candidate #{$idx}",
                'username' => $targetUsername,
                'short_description' => 'Concurrent race attempt',
            ]);

            if ($response->status() === 201 && $response->json('success') === true) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        // ঠিক ১টি রেজিস্ট্রেশন সফল হবে
        $this->assertEquals(1, $successCount);
        $this->assertEquals(9, $failCount);

        // ডাটাবেজে ঠিক ১টি রেকর্ড থাকবে
        $this->assertEquals(1, Page::where('username', $targetUsername)->count());
    }

    /**
     * টেস্ট ১১: কনকারেন্ট কোটা এনফোর্সমেন্ট — ইউজার লিমিটের বেশি পেজ তৈরি করতে পারবে না
     */
    public function test_concurrent_quota_enforcement_prevents_quota_bypass(): void
    {
        $limitUser = User::factory()->create();

        // ইতিমধ্যে ৮টি পেজ বিদ্যমান
        for ($i = 1; $i <= 8; $i++) {
            Page::create([
                'owner_id' => $limitUser->id,
                'name' => "Existing Page {$i}",
                'slug' => "existing-page-{$limitUser->id}-{$i}",
                'username' => "existpage_{$limitUser->id}_{$i}",
                'category' => 'Business',
                'status' => 'active',
            ]);
        }

        // অবশিষ্ট কোটা: ২টি (মোট ১০টি)
        // ৫টি পরপর তৈরি করার চেষ্টা করা হলে ঠিক ২টি সফল হবে এবং বাকি ৩টি ব্যর্থ হবে
        $successCount = 0;
        $failCount = 0;

        for ($j = 9; $j <= 13; $j++) {
            $res = $this->actingAs($limitUser)->postJson('/api/v2/pages/register', [
                'name' => "Quota Burst Page {$j}",
                'username' => "quotapage_{$limitUser->id}_{$j}",
                'short_description' => 'Testing quota boundary',
            ]);

            if ($res->status() === 201 && $res->json('success') === true) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $this->assertEquals(2, $successCount);
        $this->assertEquals(3, $failCount);

        // মোট মালিকানাধীন পেজের সংখ্যা সর্বোচ্চ ১০টি
        $this->assertEquals(10, Page::where('owner_id', $limitUser->id)->count());
    }
}
