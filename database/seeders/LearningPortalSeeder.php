<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LearningCategory;
use App\Models\LearningCertificate;
use App\Models\LearningCourse;
use App\Models\LearningCourseLesson;
use App\Models\LearningCourseProgress;
use App\Models\LearningCourseSection;
use App\Models\LearningEnrollment;
use App\Models\LearningInstructor;
use App\Models\LearningLessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LearningPortalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure instructors exist
        $instructorUser = User::firstOrCreate(
            ['email' => 'instructor.tanvir@bondhoo.com'],
            [
                'name' => 'Dr. Tanvir Ahmed',
                'username' => 'dr_tanvir_ahmed',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        $instructor = LearningInstructor::firstOrCreate(
            ['user_id' => $instructorUser->id],
            [
                'headline' => 'Principal Software Architect & Technology Director',
                'biography' => 'Specializing in reactive mobile architectures, high-performance distributed systems, and modern full-stack development.',
                'expertise' => ['Laravel', 'Kotlin', 'System Design', 'AI Engineering'],
                'status' => 'approved',
                'is_verified' => true,
                'total_students' => 4500,
                'total_courses' => 5,
                'rating_avg' => 4.92,
                'approved_at' => now(),
            ]
        );

        // 2. Categories
        $categoriesData = [
            [
                'name' => 'Software Engineering',
                'slug' => 'software-engineering',
                'icon' => 'code',
                'description' => 'Modern backend, frontend, API design, and mobile development.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Artificial Intelligence',
                'slug' => 'artificial-intelligence',
                'icon' => 'psychology',
                'description' => 'Machine learning, large language models, agentic systems, and neural networks.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Cloud & DevOps',
                'slug' => 'cloud-devops',
                'icon' => 'cloud',
                'description' => 'Docker, Kubernetes, CI/CD, cloud orchestration, and site reliability.',
                'sort_order' => 3,
            ],
            [
                'name' => 'UI/UX & Product Design',
                'slug' => 'ui-ux-design',
                'icon' => 'palette',
                'description' => 'Design systems, prototyping in Figma, user research, and interaction design.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Business & Leadership',
                'slug' => 'business-leadership',
                'icon' => 'trending_up',
                'description' => 'Tech management, product strategy, startup execution, and agile practices.',
                'sort_order' => 5,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $cat = LearningCategory::updateOrCreate(['slug' => $data['slug']], $data);
            $categories[$data['slug']] = $cat;
        }

        // 3. Courses
        $coursesData = [
            [
                'instructor_id' => $instructorUser->id,
                'category_id' => $categories['software-engineering']->id,
                'title' => 'Full-Stack Laravel 12 & Jetpack Compose Architecture',
                'slug' => 'laravel-jetpack-compose-mastery',
                'subtitle' => 'Master production REST & WebSocket APIs, repository patterns, and reactive Android UI.',
                'description' => 'A comprehensive, end-to-end masterclass covering enterprise Laravel 12 API architecture, Reverb WebSockets for real-time messaging, and clean Jetpack Compose MVI/MVVM design on Android.',
                'level' => 'intermediate',
                'language' => 'en',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=800&q=80',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'status' => LearningCourse::STATUS_PUBLISHED,
                'duration_minutes' => 420,
                'total_sections' => 3,
                'total_lessons' => 9,
                'enrollments_count' => 1420,
                'rating_avg' => 4.92,
                'rating_count' => 185,
                'is_featured' => true,
                'published_at' => now(),
                'sections' => [
                    [
                        'title' => 'Module 1: Architecture & Project Setup',
                        'lessons' => [
                            ['title' => '1.1 Domain-Driven Design in Modern Laravel', 'duration' => 1800],
                            ['title' => '1.2 Multi-Module Android Compose Project Setup', 'duration' => 2400],
                            ['title' => '1.3 Dependency Injection with Dagger Hilt', 'duration' => 2100],
                        ],
                    ],
                    [
                        'title' => 'Module 2: Real-Time Networking & WebSockets',
                        'lessons' => [
                            ['title' => '2.1 Laravel Reverb Server Configuration', 'duration' => 2700],
                            ['title' => '2.2 OkHttp WebSocket Client & Flow Management', 'duration' => 3000],
                            ['title' => '2.3 Handling Presence & Private Channel Authorizations', 'duration' => 2400],
                        ],
                    ],
                    [
                        'title' => 'Module 3: Production Testing & Deployment',
                        'lessons' => [
                            ['title' => '3.1 Automated Feature Tests with PHPUnit', 'duration' => 1800],
                            ['title' => '3.2 Compose UI & ViewModel Unit Testing', 'duration' => 2100],
                            ['title' => '3.3 Zero-Downtime Deployment & ProGuard Optimization', 'duration' => 2400],
                        ],
                    ],
                ],
            ],
            [
                'instructor_id' => $instructorUser->id,
                'category_id' => $categories['artificial-intelligence']->id,
                'title' => 'Building Production LLM & Multi-Agent Systems',
                'slug' => 'production-llm-multi-agent-systems',
                'subtitle' => 'From Retrieval-Augmented Generation (RAG) to autonomous multi-agent orchestration.',
                'description' => 'Hands-on guide to designing enterprise AI agents, semantic vector search with pgvector, tool execution sandboxes, and structured JSON generation.',
                'level' => 'intermediate',
                'language' => 'en',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'status' => LearningCourse::STATUS_PUBLISHED,
                'duration_minutes' => 360,
                'total_sections' => 2,
                'total_lessons' => 6,
                'enrollments_count' => 2150,
                'rating_avg' => 4.95,
                'rating_count' => 310,
                'is_featured' => true,
                'published_at' => now(),
                'sections' => [
                    [
                        'title' => 'Module 1: Vector Embeddings & RAG Foundations',
                        'lessons' => [
                            ['title' => '1.1 Semantic Chunking & Vector Databases', 'duration' => 2400],
                            ['title' => '1.2 Hybrid Search with BM25 & Cosine Similarity', 'duration' => 2700],
                            ['title' => '1.3 Context Window Optimization & Re-ranking', 'duration' => 2100],
                        ],
                    ],
                    [
                        'title' => 'Module 2: Autonomous Agents & Tool Use',
                        'lessons' => [
                            ['title' => '2.1 Function Calling & Schema Validation', 'duration' => 2400],
                            ['title' => '2.2 Agentic Memory & Multi-Step Reasoning', 'duration' => 2700],
                            ['title' => '2.3 Human-in-the-Loop Guardrails & Safety Audits', 'duration' => 2400],
                        ],
                    ],
                ],
            ],
            [
                'instructor_id' => $instructorUser->id,
                'category_id' => $categories['ui-ux-design']->id,
                'title' => 'Design Systems & High-Fidelity UI/UX in Figma',
                'slug' => 'design-systems-figma-mastery',
                'subtitle' => 'Design scalable UI components, design tokens, responsive layouts, and handoff workflows.',
                'description' => 'Learn industry-standard product design workflows. Master Figma variables, component variants, auto-layout 5.0, accessibility compliance, and developer documentation.',
                'level' => 'all_levels',
                'language' => 'en',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=800&q=80',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'status' => LearningCourse::STATUS_PUBLISHED,
                'duration_minutes' => 300,
                'total_sections' => 2,
                'total_lessons' => 6,
                'enrollments_count' => 1100,
                'rating_avg' => 4.88,
                'rating_count' => 140,
                'is_featured' => false,
                'published_at' => now(),
                'sections' => [
                    [
                        'title' => 'Module 1: Foundations of Design Tokens',
                        'lessons' => [
                            ['title' => '1.1 Color Palettes, Typography Scales & Spacing', 'duration' => 1800],
                            ['title' => '1.2 Building Accessible Dark & Light Modes', 'duration' => 2100],
                            ['title' => '1.3 Iconography & Micro-interactions', 'duration' => 1800],
                        ],
                    ],
                    [
                        'title' => 'Module 2: Reusable Components & Architecture',
                        'lessons' => [
                            ['title' => '2.1 Complex Input Forms & Validation States', 'duration' => 2400],
                            ['title' => '2.2 Navigation Patterns for Mobile & Web', 'duration' => 2100],
                            ['title' => '2.3 Design System Governance & Token Sync', 'duration' => 2400],
                        ],
                    ],
                ],
            ],
            [
                'instructor_id' => $instructorUser->id,
                'category_id' => $categories['cloud-devops']->id,
                'title' => 'Kubernetes, Docker & Cloud Pipeline Engineering',
                'slug' => 'kubernetes-docker-cicd-pipeline',
                'subtitle' => 'Production container orchestration, zero-trust security, and automated GitOps.',
                'description' => 'Deep dive into containerizing scalable applications, writing Helm charts, configuring automated GitHub Actions CI/CD pipelines, and zero-downtime rolling upgrades.',
                'level' => 'intermediate',
                'language' => 'en',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1667372393119-3d4c48d07fc9?auto=format&fit=crop&w=800&q=80',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'status' => LearningCourse::STATUS_PUBLISHED,
                'duration_minutes' => 480,
                'total_sections' => 2,
                'total_lessons' => 6,
                'enrollments_count' => 890,
                'rating_avg' => 4.91,
                'rating_count' => 95,
                'is_featured' => false,
                'published_at' => now(),
                'sections' => [
                    [
                        'title' => 'Module 1: Containerization Best Practices',
                        'lessons' => [
                            ['title' => '1.1 Multi-stage Docker Builds for PHP & Node', 'duration' => 2400],
                            ['title' => '1.2 Non-root Containers & Security Hardening', 'duration' => 2100],
                            ['title' => '1.3 Local Kubernetes Orchestration with Minikube', 'duration' => 2700],
                        ],
                    ],
                    [
                        'title' => 'Module 2: CI/CD & Production GitOps',
                        'lessons' => [
                            ['title' => '2.1 Automated Testing & Image Registry Scanning', 'duration' => 2400],
                            ['title' => '2.2 Ingress Controllers, TLS & Load Balancing', 'duration' => 3000],
                            ['title' => '2.3 Observability: Prometheus & Grafana Dashboards', 'duration' => 2400],
                        ],
                    ],
                ],
            ],
            [
                'instructor_id' => $instructorUser->id,
                'category_id' => $categories['business-leadership']->id,
                'title' => 'Tech Leadership & Engineering Management',
                'slug' => 'engineering-management-leadership',
                'subtitle' => 'Effective 1-on-1s, technical hiring, cross-functional alignment, and team culture.',
                'description' => 'Guidance for senior engineers transitioning to Tech Leads and Engineering Managers. Master sprint planning, managing technical debt, and building resilient high-velocity engineering cultures.',
                'level' => 'intermediate',
                'language' => 'en',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=800&q=80',
                'type' => 'free',
                'price' => 0.00,
                'currency' => 'USD',
                'status' => LearningCourse::STATUS_PUBLISHED,
                'duration_minutes' => 240,
                'total_sections' => 2,
                'total_lessons' => 6,
                'enrollments_count' => 740,
                'rating_avg' => 4.89,
                'rating_count' => 88,
                'is_featured' => false,
                'published_at' => now(),
                'sections' => [
                    [
                        'title' => 'Module 1: The Transition to Leadership',
                        'lessons' => [
                            ['title' => '1.1 Coding vs Managing: Mindset Shifts', 'duration' => 1800],
                            ['title' => '1.2 Conducting Impactful 1-on-1 Conversations', 'duration' => 2100],
                            ['title' => '1.3 Delegation & Psychological Safety', 'duration' => 1800],
                        ],
                    ],
                    [
                        'title' => 'Module 2: High-Performance Team Execution',
                        'lessons' => [
                            ['title' => '2.1 Balancing Tech Debt with Product Deliverables', 'duration' => 2100],
                            ['title' => '2.2 Technical Interview Design & Fair Evaluation', 'duration' => 2400],
                            ['title' => '2.3 Leading Through Organizational Change', 'duration' => 2100],
                        ],
                    ],
                ],
            ],
        ];

        $createdCourses = [];
        foreach ($coursesData as $courseItem) {
            $sectionsData = $courseItem['sections'];
            unset($courseItem['sections']);

            $course = LearningCourse::updateOrCreate(['slug' => $courseItem['slug']], $courseItem);
            $createdCourses[$courseItem['slug']] = $course;

            // Create sections & lessons
            $secOrder = 1;
            foreach ($sectionsData as $secItem) {
                $section = LearningCourseSection::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => $secItem['title'],
                    ],
                    [
                        'sort_order' => $secOrder++,
                        'is_published' => true,
                    ]
                );

                $lesOrder = 1;
                foreach ($secItem['lessons'] as $lesItem) {
                    LearningCourseLesson::updateOrCreate(
                        [
                            'course_id' => $course->id,
                            'section_id' => $section->id,
                            'title' => $lesItem['title'],
                        ],
                        [
                            'slug' => Str::slug($lesItem['title']),
                            'type' => 'video',
                            'video_duration_seconds' => $lesItem['duration'],
                            'sort_order' => $lesOrder++,
                            'is_preview' => ($secOrder === 2 && $lesOrder === 2),
                            'is_mandatory' => true,
                            'is_published' => true,
                            'content' => 'Comprehensive technical walkthrough and code examples for ' . $lesItem['title'] . '.',
                        ]
                    );
                }
            }

            // Update category courses count
            LearningCategory::where('id', $course->category_id)->increment('courses_count');
        }

        // 4. Seed user enrollments and certificates for User 1 (Mizanur Rahman)
        $targetUser = User::first();
        if ($targetUser) {
            // Course 1: In progress (65%)
            $course1 = $createdCourses['laravel-jetpack-compose-mastery'] ?? null;
            if ($course1) {
                $enrollment1 = LearningEnrollment::updateOrCreate(
                    [
                        'course_id' => $course1->id,
                        'user_id' => $targetUser->id,
                    ],
                    [
                        'status' => 'active',
                        'price_paid' => 0.00,
                        'currency' => 'USD',
                        'enrolled_at' => now()->subDays(10),
                        'last_accessed_at' => now(),
                    ]
                );

                LearningCourseProgress::updateOrCreate(
                    ['enrollment_id' => $enrollment1->id],
                    [
                        'course_id' => $course1->id,
                        'user_id' => $targetUser->id,
                        'completed_lessons_count' => 6,
                        'total_lessons_count' => 9,
                        'percentage' => 66.67,
                        'is_completed' => false,
                    ]
                );

                // Mark some lessons completed
                $lessons = LearningCourseLesson::where('course_id', $course1->id)->take(6)->get();
                foreach ($lessons as $lesson) {
                    LearningLessonProgress::updateOrCreate(
                        [
                            'user_id' => $targetUser->id,
                            'lesson_id' => $lesson->id,
                        ],
                        [
                            'enrollment_id' => $enrollment1->id,
                            'course_id' => $course1->id,
                            'is_completed' => true,
                            'watch_time_seconds' => $lesson->video_duration_seconds,
                            'last_position_seconds' => $lesson->video_duration_seconds,
                            'completed_at' => now()->subDays(2),
                        ]
                    );
                }
            }

            // Course 2: Completed (100%) with Certificate!
            $course2 = $createdCourses['design-systems-figma-mastery'] ?? null;
            if ($course2) {
                $enrollment2 = LearningEnrollment::updateOrCreate(
                    [
                        'course_id' => $course2->id,
                        'user_id' => $targetUser->id,
                    ],
                    [
                        'status' => 'completed',
                        'price_paid' => 0.00,
                        'currency' => 'USD',
                        'enrolled_at' => now()->subDays(20),
                        'completed_at' => now()->subDays(3),
                        'last_accessed_at' => now()->subDays(3),
                    ]
                );

                LearningCourseProgress::updateOrCreate(
                    ['enrollment_id' => $enrollment2->id],
                    [
                        'course_id' => $course2->id,
                        'user_id' => $targetUser->id,
                        'completed_lessons_count' => 6,
                        'total_lessons_count' => 6,
                        'percentage' => 100.00,
                        'is_completed' => true,
                        'completed_at' => now()->subDays(3),
                    ]
                );

                // Issue certificate
                LearningCertificate::updateOrCreate(
                    [
                        'course_id' => $course2->id,
                        'user_id' => $targetUser->id,
                    ],
                    [
                        'certificate_code' => 'BND-CERT-2026-UIUX-7741',
                        'verification_token' => Str::random(64),
                        'student_name' => $targetUser->name,
                        'course_title' => $course2->title,
                        'instructor_name' => 'Dr. Tanvir Ahmed',
                        'issued_at' => now()->subDays(3),
                        'is_revoked' => false,
                    ]
                );
            }
        }
    }
}
