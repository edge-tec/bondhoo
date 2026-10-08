<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\UserProfile;
use App\Policies\PagePolicy;
use App\Policies\ProfileEducationPolicy;
use App\Policies\ProfileExperiencePolicy;
use App\Policies\ProfileInterestPolicy;
use App\Policies\ProfileLanguagePolicy;
use App\Policies\ProfileSkillPolicy;
use App\Policies\ProfileSocialLinkPolicy;
use App\Policies\UserProfilePolicy;
use App\Services\Contracts\CacheServiceInterface;
use App\Services\Contracts\MediaProcessingServiceInterface;
use App\Services\Contracts\MediaStorageServiceInterface;
use App\Services\Contracts\MessengerServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use App\Services\Email\SmtpConfigService;
use App\Services\MediaProcessingService;
use App\Services\MediaStorageService;
use App\Services\MessengerService;
use App\Services\NotificationService;
use App\Services\QueueService;
use App\Services\RealtimeService;
use App\Services\RedisCacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CacheServiceInterface::class, RedisCacheService::class);
        $this->app->singleton(QueueServiceInterface::class, QueueService::class);
        $this->app->singleton(MediaStorageServiceInterface::class, MediaStorageService::class);
        $this->app->singleton(MediaProcessingServiceInterface::class, MediaProcessingService::class);
        $this->app->singleton(NotificationServiceInterface::class, NotificationService::class);
        $this->app->singleton(RealtimeServiceInterface::class, RealtimeService::class);
        $this->app->singleton(MessengerServiceInterface::class, MessengerService::class);
    }

    /**
     * Bootstrap any application services.
     * অ্যাপ্লিকেশন বুটস্ট্র্যাপ করার সময় রেট লিমিটার কনফিগার করা।
     */
    public function boot(): void
    {
        if (class_exists(DevCommands::class) && config('queue.default') !== 'redis') {
            DevCommands::except('horizon');
            DevCommands::artisan('queue:listen --tries=1 --timeout=0', 'queue');
        }

        try {
            app(SmtpConfigService::class)->applyToMailer();
        } catch (\Throwable) {
            // Failsafe during initial install, migrations, or CLI cache commands
        }

        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(UserProfile::class, UserProfilePolicy::class);
        Gate::policy(ProfileEducation::class, ProfileEducationPolicy::class);
        Gate::policy(ProfileExperience::class, ProfileExperiencePolicy::class);
        Gate::policy(ProfileSkill::class, ProfileSkillPolicy::class);
        Gate::policy(ProfileInterest::class, ProfileInterestPolicy::class);
        Gate::policy(ProfileLanguage::class, ProfileLanguagePolicy::class);
        Gate::policy(ProfileSocialLink::class, ProfileSocialLinkPolicy::class);
        // ১. সাধারণ API রিকোয়েস্ট রেট লিমিট (প্রতি মিনিটে ৬০ টি)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ২. অথেনটিকেশন ও লগইন রেট লিমিট (Brute-force রোধে প্রতি মিনিটে ৫ টি প্রচেষ্টা)
        RateLimiter::for('auth', function (Request $request) {
            $identifier = $request->input('identifier') ?: $request->input('email', '');
            $key = strtolower(trim((string) $identifier)).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'খুব বেশি লগইন চেষ্টা করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
                ], 429);
            });
        });

        // অ্যাডমিন অথেনটিকেশন রেট লিমিট (প্রতি মিনিটে সর্বোচ্চ ১০ টি প্রচেষ্টা)
        RateLimiter::for('admin-auth', function (Request $request) {
            $key = $request->input('identifier', '').'|'.$request->ip();

            return Limit::perMinute(10)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'খুব বেশি অ্যাডমিন লগইন চেষ্টা করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
                ], 429);
            });
        });

        // ইমেইল ভেরিফিকেশন রিকোয়েস্ট রেট লিমিট (প্রতি মিনিটে ৫ টি)
        RateLimiter::for('email-verification', function (Request $request) {
            $key = ($request->user()?->id ?: $request->input('email', '')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'খুব বেশি ইমেইল যাচাইকরণ অনুরোধ পাঠানো হয়েছে। অনুগ্রহ করে কিছুক্ষণ অপেক্ষা করুন।',
                ], 429);
            });
        });

        // পাসওয়ার্ড রিসেট রিকোয়েস্ট রেট লিমিট (প্রতি মিনিটে ৫ টি)
        RateLimiter::for('password-reset', function (Request $request) {
            $key = strtolower(trim((string) ($request->input('identifier') ?: $request->input('email', '')))).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'খুব বেশি পাসওয়ার্ড রিসেট অনুরোধ পাঠানো হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
                ], 429);
            });
        });

        // SMTP টেস্ট ইমেইল রেট লিমিট (প্রতি মিনিটে ৫ টি)
        RateLimiter::for('smtp-test', function (Request $request) {
            $key = ($request->user()?->id ?: 'admin').'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'খুব বেশি টেস্ট ইমেইল পাঠানোর চেষ্টা করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
                ], 429);
            });
        });

        // ৩. ইউজারনেম ইনিউমারেশন প্রতিরোধে রেট লিমিট (প্রতি মিনিটে ২০ টি চেক)
        RateLimiter::for('username-check', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(20)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'খুব বেশি ইউজারনেম অনুসন্ধানের চেষ্টা করা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পর চেষ্টা করুন।',
                ], 429);
            });
        });

        // ৩. পোস্ট তৈরি করার রেট লিমিট (স্প্যামিং রোধে প্রতি মিনিটে ১৫ টি পোস্ট)
        RateLimiter::for('posts', function (Request $request) {
            return Limit::perMinute(15)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ৪. মেসেজ পাঠানোর রেট লিমিট (প্রতি মিনিটে ৬০ টি মেসেজ)
        RateLimiter::for('messages', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ৫. ফ্রেন্ড রিকোয়েস্ট স্প্যামিং রোধে রেট লিমিট (প্রতি মিনিটে ৬০ টি রিকোয়েস্ট)
        RateLimiter::for('friend-requests', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many friend requests sent. Please try again after cooldown.',
                ], 429);
            });
        });

        // ৬. রিঅ্যাকশন রেট লিমিট (প্রতি মিনিটে ১২০ টি)
        RateLimiter::for('reactions', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ৭. কমেন্ট ও রিপ্লাই রেট লিমিট (প্রতি মিনিটে ৬০ টি)
        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ৮. পোস্ট শেয়ার রেট লিমিট (প্রতি মিনিটে ৩০ টি)
        RateLimiter::for('shares', function (Request $request) {
            return Limit::perMinute(30)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ৯. রিপোর্ট সাবমিট রেট লিমিট (প্রতি মিনিটে ২০ টি)
        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinute(20)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // ১০. কনভারসেশন তৈরি রেট লিমিট (প্রতি মিনিটে ৩০ টি)
        RateLimiter::for('conversations', function (Request $request) {
            return Limit::perMinute(30)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}
