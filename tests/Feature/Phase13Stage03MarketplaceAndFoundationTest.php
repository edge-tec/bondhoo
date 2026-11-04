<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LearningCategory;
use App\Models\LearningCourse;
use App\Models\LearningInstructor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13Stage03MarketplaceAndFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $student;

    protected User $admin;

    protected LearningCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create([
            'name' => 'Prof. Alan Turing',
            'email' => 'turing@connectly.app',
        ]);

        $this->otherInstructor = User::factory()->create([
            'name' => 'Dr. Grace Hopper',
            'email' => 'grace@connectly.app',
        ]);

        $this->student = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@connectly.app',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'LMS Super Admin',
            'email' => 'admin@connectly.app',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Administrator']);
        $this->admin->roles()->attach($adminRole->id);

        $this->category = LearningCategory::create([
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'icon' => 'code',
            'description' => 'Algorithms, architecture, and programming.',
        ]);
    }

    /**
     * Test 1: Category Listing & Details & Admin Management
     */
    public function test_category_management_and_admin_crud(): void
    {
        // 1. Authenticated user can view categories
        $res = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/categories');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Computer Science');

        // 2. View specific category details
        $catRes = $this->actingAs($this->student, 'sanctum')->getJson("/api/v1/learning/categories/{$this->category->slug}");
        $catRes->assertOk()
            ->assertJsonPath('data.name', 'Computer Science');

        // 3. Admin creates new category
        $createRes = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/learning/admin/categories', [
                'name' => 'Data Science',
                'description' => 'Machine learning and big data.',
                'icon' => 'database',
            ]);
        $createRes->assertStatus(201)
            ->assertJsonPath('data.slug', 'data-science');

        $dataScienceId = $createRes->json('data.id');

        // 4. Admin updates category
        $updateRes = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/learning/admin/categories/{$dataScienceId}", [
                'name' => 'Data Science & AI',
            ]);
        $updateRes->assertOk()
            ->assertJsonPath('data.name', 'Data Science & AI');

        // 5. Non-admin cannot delete or update category
        $unauthRes = $this->actingAs($this->student, 'sanctum')
            ->deleteJson("/api/v1/learning/admin/categories/{$dataScienceId}");
        $unauthRes->assertStatus(403);

        // 6. Admin deletes empty category
        $delRes = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/learning/admin/categories/{$dataScienceId}");
        $delRes->assertOk();
    }

    /**
     * Test 2: Course Marketplace Discovery, Filtering, Featured & Popular Endpoints
     */
    public function test_course_marketplace_discovery_and_filters(): void
    {
        // Create instructor profile
        $instructorProfile = LearningInstructor::create([
            'user_id' => $this->instructor->id,
            'headline' => 'Computer Scientist',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        // Create published course
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Introduction to Algorithms',
            'slug' => 'intro-to-algorithms',
            'subtitle' => 'Foundational algorithmic concepts',
            'description' => 'Sorting, searching, and graphs.',
            'level' => 'beginner',
            'type' => 'paid',
            'price' => 1500,
            'status' => LearningCourse::STATUS_PUBLISHED,
            'is_featured' => true,
            'enrollments_count' => 120,
            'rating_avg' => 4.90,
            'published_at' => now(),
        ]);

        // Create draft course (should not show in public marketplace)
        $draftCourse = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Secret Quantum Computing Draft',
            'slug' => 'quantum-draft',
            'description' => 'Unreleased draft.',
            'status' => LearningCourse::STATUS_DRAFT,
        ]);

        // 1. Marketplace listing only shows published courses
        $marketRes = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/courses');
        $marketRes->assertOk()
            ->assertJsonPath('success', true);
        $slugs = collect($marketRes->json('data'))->pluck('slug')->all();
        $this->assertContains('intro-to-algorithms', $slugs);
        $this->assertNotContains('quantum-draft', $slugs);

        // 2. Filter by search query
        $searchRes = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/courses?q=Algorithms');
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('data'));

        // 3. Filter by level
        $levelRes = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/courses?level=expert');
        $levelRes->assertOk();
        $this->assertCount(0, $levelRes->json('data'));

        // 4. Featured courses endpoint
        $featuredRes = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/courses/featured');
        $featuredRes->assertOk()
            ->assertJsonPath('data.0.slug', 'intro-to-algorithms');

        // 5. Popular courses endpoint
        $popularRes = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/learning/courses/popular');
        $popularRes->assertOk()
            ->assertJsonPath('data.0.slug', 'intro-to-algorithms');

        // 6. Course details endpoint
        $detailRes = $this->getJson('/api/v1/learning/courses/intro-to-algorithms');
        $detailRes->assertOk()
            ->assertJsonPath('data.course.title', 'Introduction to Algorithms')
            ->assertJsonPath('data.is_enrolled', false);
    }

    /**
     * Test 3: Instructor Application, Verification, and Profile Endpoints
     */
    public function test_instructor_application_flow_and_profile(): void
    {
        // 1. Instructor submits application
        $applyRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/api/v1/learning/instructors/apply', [
                'headline' => 'Distributed Systems Lead',
                'biography' => '10 years experience building scalable platforms.',
                'expertise' => ['Kotlin', 'Microservices', 'Distributed Databases'],
            ]);
        $applyRes->assertStatus(201)
            ->assertJsonPath('data.headline', 'Distributed Systems Lead');

        // 2. Duplicate pending application is rejected
        $dupRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/api/v1/learning/instructors/apply', [
                'headline' => 'Duplicate Application',
            ]);
        $dupRes->assertStatus(422)
            ->assertJsonValidationErrors('application');

        // 3. Instructor views own profile
        $meRes = $this->actingAs($this->instructor, 'sanctum')
            ->getJson('/api/v1/learning/instructors/me');
        $meRes->assertOk()
            ->assertJsonPath('data.headline', 'Distributed Systems Lead');

        $instructorRecord = LearningInstructor::where('user_id', $this->instructor->id)->first();

        // 4. Authenticated user views instructor profile
        $showRes = $this->actingAs($this->student, 'sanctum')->getJson("/api/v1/learning/instructors/{$instructorRecord->id}");
        $showRes->assertOk()
            ->assertJsonPath('data.headline', 'Distributed Systems Lead');
    }

    /**
     * Test 4: Instructor Course Creation, Updating, Ownership & Deletion Guards
     */
    public function test_instructor_course_crud_and_ownership_guards(): void
    {
        // 1. Instructor creates course
        $createRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson('/api/v1/learning/instructor/courses', [
                'title' => 'Fullstack Laravel & Compose',
                'description' => 'Complete fullstack masterclass.',
                'category_id' => $this->category->id,
                'level' => 'intermediate',
                'type' => 'paid',
                'price' => 2000,
            ]);
        $createRes->assertStatus(201);
        $courseId = $createRes->json('data.id');

        // 2. Instructor lists their courses
        $listRes = $this->actingAs($this->instructor, 'sanctum')
            ->getJson('/api/v1/learning/instructor/courses');
        $listRes->assertOk();
        $this->assertCount(1, $listRes->json('data'));

        // 3. Other instructor CANNOT edit or update this course (Ownership policy guard)
        $unauthUpdate = $this->actingAs($this->otherInstructor, 'sanctum')
            ->putJson("/api/v1/learning/instructor/courses/{$courseId}", [
                'title' => 'Hacked Title by Other Instructor',
                'description' => 'Attempting unauthorized update.',
            ]);
        $unauthUpdate->assertStatus(403);

        // 4. Other instructor CANNOT delete this course
        $unauthDelete = $this->actingAs($this->otherInstructor, 'sanctum')
            ->deleteJson("/api/v1/learning/instructor/courses/{$courseId}");
        $unauthDelete->assertStatus(403);

        // 5. Course owner updates course
        $authUpdate = $this->actingAs($this->instructor, 'sanctum')
            ->putJson("/api/v1/learning/instructor/courses/{$courseId}", [
                'title' => 'Fullstack Laravel & Compose (Updated)',
                'description' => 'Complete fullstack masterclass (Updated).',
            ]);
        $authUpdate->assertOk()
            ->assertJsonPath('data.title', 'Fullstack Laravel & Compose (Updated)');

        // 6. Course owner publishes course
        $publishRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$courseId}/publish");
        $publishRes->assertOk()
            ->assertJsonPath('data.status', LearningCourse::STATUS_PUBLISHED);

        // 7. Course owner deletes course
        $delRes = $this->actingAs($this->instructor, 'sanctum')
            ->deleteJson("/api/v1/learning/instructor/courses/{$courseId}");
        $delRes->assertOk();
    }

    /**
     * Test 5: Curriculum Sections and Lessons Management
     */
    public function test_curriculum_sections_and_lessons_lifecycle(): void
    {
        $course = LearningCourse::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $this->category->id,
            'title' => 'Kotlin Coroutines Deep Dive',
            'slug' => 'kotlin-coroutines-deep-dive',
            'description' => 'Master async programming in Kotlin.',
            'status' => LearningCourse::STATUS_DRAFT,
        ]);

        // 1. Instructor creates section
        $secRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/courses/{$course->id}/sections", [
                'title' => 'Section 1: Coroutine Builders',
                'sort_order' => 1,
            ]);
        $secRes->assertStatus(201)
            ->assertJsonPath('data.title', 'Section 1: Coroutine Builders');
        $sectionId = $secRes->json('data.id');

        // 2. Other instructor CANNOT add lessons to this section
        $unauthLesson = $this->actingAs($this->otherInstructor, 'sanctum')
            ->postJson("/api/v1/learning/sections/{$sectionId}/lessons", [
                'title' => 'Unauthorized Lesson',
                'type' => 'video',
            ]);
        $unauthLesson->assertStatus(403);

        // 3. Instructor adds lesson to section
        $lessonRes = $this->actingAs($this->instructor, 'sanctum')
            ->postJson("/api/v1/learning/sections/{$sectionId}/lessons", [
                'title' => 'Lesson 1.1: launch vs async',
                'type' => 'video',
                'video_url' => 'https://storage.connectly.com/videos/launch-async.mp4',
                'video_duration_seconds' => 720,
                'is_preview' => true,
                'sort_order' => 1,
            ]);
        $lessonRes->assertStatus(201)
            ->assertJsonPath('data.title', 'Lesson 1.1: launch vs async');
        $lessonId = $lessonRes->json('data.id');

        // 4. Retrieve curriculum
        $curriculumRes = $this->getJson("/api/v1/learning/courses/{$course->id}/curriculum");
        $curriculumRes->assertOk()
            ->assertJsonPath('data.0.title', 'Section 1: Coroutine Builders')
            ->assertJsonPath('data.0.lessons.0.title', 'Lesson 1.1: launch vs async');

        // 5. Update lesson
        $upLesson = $this->actingAs($this->instructor, 'sanctum')
            ->putJson("/api/v1/learning/lessons/{$lessonId}", [
                'title' => 'Lesson 1.1: launch vs async (Revised)',
            ]);
        $upLesson->assertOk()
            ->assertJsonPath('data.title', 'Lesson 1.1: launch vs async (Revised)');

        // 6. Delete lesson
        $delLesson = $this->actingAs($this->instructor, 'sanctum')
            ->deleteJson("/api/v1/learning/lessons/{$lessonId}");
        $delLesson->assertOk();

        // 7. Delete section
        $delSection = $this->actingAs($this->instructor, 'sanctum')
            ->deleteJson("/api/v1/learning/sections/{$sectionId}");
        $delSection->assertOk();
    }
}
