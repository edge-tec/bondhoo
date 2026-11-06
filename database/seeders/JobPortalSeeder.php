<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\JobCategory;
use App\Models\JobPosting;
use App\Models\JobSkill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobPortalSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first() ?? User::factory()->create([
            'name' => 'BONDHOO Admin',
            'email' => 'admin@bondhoo.com',
            'username' => 'bondhooadmin',
        ]);

        // Categories
        $categories = [
            ['name' => 'Software & Engineering', 'slug' => 'software-engineering'],
            ['name' => 'Product & Design', 'slug' => 'product-design'],
            ['name' => 'Marketing & Growth', 'slug' => 'marketing-growth'],
            ['name' => 'Data Science & AI', 'slug' => 'data-ai'],
            ['name' => 'Customer Success & HR', 'slug' => 'hr-customer-success'],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['slug']] = JobCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['name'].' career tracks',
                    'status' => 'active',
                ]
            );
        }

        // Company
        $company = Company::firstOrCreate(
            ['slug' => 'bondhoo-technologies'],
            [
                'owner_user_id' => $admin->id,
                'name' => 'BONDHOO Technologies Ltd.',
                'industry' => 'Software & Social Technology',
                'company_size' => '51-200',
                'headquarters' => 'Gulshan 2, Dhaka, Bangladesh',
                'website' => 'https://bondhoo.com',
                'description' => 'BONDHOO (বন্ধু) is Bangladesh\'s premier professional networking and community platform.',
                'verification_status' => 'verified',
                'visibility' => 'public',
            ]
        );

        CompanyMember::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $admin->id],
            [
                'role' => CompanyMember::ROLE_OWNER,
                'invitation_status' => CompanyMember::STATUS_ACCEPTED,
                'joined_at' => now(),
            ]
        );

        // Job Postings
        $jobs = [
            [
                'title' => 'Senior Full-Stack Engineer (Laravel & Vue.js)',
                'company_id' => $company->id,
                'category_id' => $categoryModels['software-engineering']->id,
                'company_name' => $company->name,
                'location' => 'Dhaka, Bangladesh',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'location_type' => 'hybrid',
                'workplace_type' => 'hybrid',
                'employment_type' => 'full_time',
                'experience_level' => 'senior',
                'salary_min' => 120000,
                'salary_max' => 180000,
                'salary_currency' => 'BDT',
                'salary_negotiable' => false,
                'vacancies' => 2,
                'description' => 'We are seeking an experienced Senior Full-Stack Engineer to lead development of core social architecture and real-time messaging engines at BONDHOO.',
                'responsibilities' => '• Design high-throughput REST & WebSocket APIs in Laravel 11.'."\n".'• Build reactive web interfaces with Vue 3 and Tailwind CSS.'."\n".'• Architect PostgreSQL database schemas and Redis caching clusters.',
                'requirements' => '• 5+ years building production applications with modern PHP/Laravel.'."\n".'• Strong proficiency with Vue.js 3, Inertia.js, and TypeScript.'."\n".'• Experience in MySQL/PostgreSQL indexing, query optimization, and background queue workers.',
                'benefits' => '• Competitive monthly compensation with bi-annual performance bonuses.'."\n".'• Comprehensive health & life insurance.'."\n".'• 2 days work-from-home weekly.'."\n".'• Annual learning and conference stipend.',
                'deadline' => now()->addDays(45)->toDateString(),
                'status' => 'active',
                'skills' => ['Laravel', 'Vue.js', 'PostgreSQL', 'Tailwind CSS', 'Redis'],
            ],
            [
                'title' => 'Lead Android Engineer (Kotlin & Jetpack Compose)',
                'company_id' => $company->id,
                'category_id' => $categoryModels['software-engineering']->id,
                'company_name' => $company->name,
                'location' => 'Gulshan, Dhaka',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'location_type' => 'onsite',
                'workplace_type' => 'onsite',
                'employment_type' => 'full_time',
                'experience_level' => 'lead',
                'salary_min' => 140000,
                'salary_max' => 200000,
                'salary_currency' => 'BDT',
                'salary_negotiable' => true,
                'vacancies' => 1,
                'description' => 'Shape the future of the BONDHOO Android ecosystem. Lead the mobile architecture using 100% Jetpack Compose, Kotlin Coroutines, and Clean Architecture.',
                'responsibilities' => '• Architect clean, scalable multi-module Android codebase.'."\n".'• Build fluid Material 3 Compose UI with zero frame drops.'."\n".'• Optimize WebRTC calling, real-time Reverb push, and offline database synchronization.',
                'requirements' => '• 4+ years of dedicated Android engineering experience.'."\n".'• Deep expertise in Kotlin, Jetpack Compose, Flow, and Hilt dependency injection.'."\n".'• Strong understanding of Android Target SDK 36 security requirements and Play Store release guidelines.',
                'benefits' => '• Top-tier market compensation and equity sharing.'."\n".'• Latest flagship development hardware provided.'."\n".'• Catered lunch and snacks.'."\n".'• Flexible hours and generous PTO.',
                'deadline' => now()->addDays(30)->toDateString(),
                'status' => 'active',
                'skills' => ['Kotlin', 'Jetpack Compose', 'Coroutines', 'Hilt', 'WebRTC'],
            ],
            [
                'title' => 'Product UI/UX Designer',
                'company_id' => $company->id,
                'category_id' => $categoryModels['product-design']->id,
                'company_name' => $company->name,
                'location' => 'Remote, Bangladesh',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'location_type' => 'remote',
                'workplace_type' => 'remote',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'salary_min' => 80000,
                'salary_max' => 130000,
                'salary_currency' => 'BDT',
                'salary_negotiable' => false,
                'vacancies' => 1,
                'description' => 'Join BONDHOO design team to craft beautiful, intuitive experiences for millions of social network and business users across Android, iOS, and Web.',
                'responsibilities' => '• Create design systems, component libraries, and interactive prototypes in Figma.'."\n".'• Conduct user interviews and usability research.'."\n".'• Collaborate closely with frontend and mobile teams for pixel-perfect implementation.',
                'requirements' => '• Strong portfolio showcasing modern web and mobile apps.'."\n".'• Mastery of Figma, auto-layout, design tokens, and micro-interactions.'."\n".'• Understanding of Material 3 and iOS Human Interface Guidelines.',
                'benefits' => '• 100% remote work flexibility from anywhere in Bangladesh.'."\n".'• Monthly home-office allowance.'."\n".'• Creative subscription stipends (Figma, Adobe, Mobbin).',
                'deadline' => now()->addDays(60)->toDateString(),
                'status' => 'active',
                'skills' => ['Figma', 'UI/UX Design', 'Design Systems', 'User Research'],
            ],
            [
                'title' => 'DevSecOps & Cloud Infrastructure Architect',
                'company_id' => $company->id,
                'category_id' => $categoryModels['software-engineering']->id,
                'company_name' => $company->name,
                'location' => 'Remote / Worldwide',
                'country' => 'Bangladesh',
                'city' => 'Dhaka',
                'location_type' => 'remote',
                'workplace_type' => 'remote',
                'employment_type' => 'contract',
                'experience_level' => 'senior',
                'salary_min' => 150000,
                'salary_max' => 220000,
                'salary_currency' => 'BDT',
                'salary_negotiable' => true,
                'vacancies' => 1,
                'description' => 'Oversee high-availability cloud infrastructure, automated CI/CD pipelines, container orchestration, and security compliance for the BONDHOO ecosystem.',
                'responsibilities' => '• Maintain Docker containerization and Kubernetes cluster deployment.'."\n".'• Automate zero-downtime deployment pipelines with GitHub Actions.'."\n".'• Implement rigorous vulnerability scanning, DDoS mitigation, and log observability.',
                'requirements' => '• Experience with Linux administration, Docker, Nginx, and cloud providers.'."\n".'• Strong background in application security and SSL/TLS certificate management.'."\n".'• Knowledge of Redis cluster replication and PostgreSQL high availability.',
                'benefits' => '• High-tier hourly or monthly contract compensation.'."\n".'• Async workflow with flexible timezone scheduling.',
                'deadline' => now()->addDays(40)->toDateString(),
                'status' => 'active',
                'skills' => ['Docker', 'Kubernetes', 'CI/CD', 'Linux', 'Cloudflare'],
            ],
        ];

        foreach ($jobs as $jobData) {
            $skills = $jobData['skills'] ?? [];
            unset($jobData['skills']);

            $jobData['user_id'] = $admin->id;
            $jobData['slug'] = Str::slug($jobData['title']).'-'.Str::random(5);
            $jobData['published_at'] = now();

            $job = JobPosting::firstOrCreate(
                ['title' => $jobData['title'], 'company_id' => $jobData['company_id']],
                $jobData
            );

            foreach ($skills as $skillName) {
                $skill = JobSkill::firstOrCreate(
                    ['slug' => Str::slug($skillName)],
                    ['name' => $skillName]
                );
                $job->skills()->syncWithoutDetaching([$skill->id]);
            }
        }
    }
}
