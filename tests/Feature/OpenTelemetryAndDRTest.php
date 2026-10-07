<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\Page;
use App\Models\User;
use App\Services\Cache\DistributedCacheClusterService;
use App\Services\DisasterRecovery\DisasterRecoveryService;
use App\Services\Event\EventService;
use App\Services\Group\EnterpriseGroupService;
use App\Services\Messenger\EnterpriseMessengerService;
use App\Services\Microservices\ServiceMeshGateway;
use App\Services\Notification\EnterpriseNotificationService;
use App\Services\Observability\OpenTelemetryService;
use App\Services\Page\EnterprisePageService;
use App\Services\Performance\EnterprisePerformanceService;
use App\Services\Storage\ResumableMultipartStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenTelemetryAndDRTest extends TestCase
{
    use RefreshDatabase;

    public function test_opentelemetry_service_starts_and_ends_spans(): void
    {
        $otel = new OpenTelemetryService;

        $span = $otel->startSpan('ai.inference', ['model' => 'gemini-2.5-flash']);
        $this->assertNotEmpty($span['trace_id']);
        $this->assertNotEmpty($span['span_id']);

        $header = $otel->getTraceparentHeader($span);
        $this->assertStringStartsWith('00-', $header);

        $finished = $otel->endSpan($span, ['tokens' => 120]);
        $this->assertArrayHasKey('duration_ms', $finished);
        $this->assertSame(120, $finished['attributes']['tokens']);
    }

    public function test_disaster_recovery_service_validates_pitr_and_replicas(): void
    {
        $dr = new DisasterRecoveryService;

        $backup = Backup::create([
            'filename' => 'backup-full-2026.tar.gz',
            'disk' => 'local',
            'file_path' => 'backups/backup-full-2026.tar.gz',
            'size' => 1048576,
            'type' => 'full',
            'checksum' => hash('sha256', 'sample-backup-content'),
            'status' => 'completed',
        ]);

        $this->assertTrue($dr->verifyBackupIntegrity($backup));

        $replica = $dr->replicateToSecondaryRegion($backup, 'ap-southeast-1');
        $this->assertSame('synced', $replica['status']);
        $this->assertSame('ap-southeast-1', $replica['secondary_region']);

        $pitr = $dr->getPitrStatus();
        $this->assertTrue($pitr['pitr_enabled']);
        $this->assertSame(5, $pitr['rpo_minutes']);
    }

    public function test_performance_service_monitors_memory_and_audits_queries(): void
    {
        $perf = new EnterprisePerformanceService;

        $mem = $perf->checkWorkerMemoryHealth();
        $this->assertArrayHasKey('current_mb', $mem);
        $this->assertSame('healthy', $mem['status']);

        $audit = $perf->auditQueries(function () {
            User::count();
        });
        $this->assertGreaterThanOrEqual(1, $audit['query_count']);
    }

    public function test_distributed_cache_cluster_service(): void
    {
        $cacheCluster = new DistributedCacheClusterService;

        $key = $cacheCluster->formatClusterKey('feed:public', 'ranked');
        $this->assertSame('{feed:public}:ranked', $key);

        $val = $cacheCluster->rememberWithStampedeProtection($key, 60, fn () => 'cached_feed_payload');
        $this->assertSame('cached_feed_payload', $val);
    }

    public function test_enterprise_notification_service_handles_dispatch(): void
    {
        $user = User::factory()->create();
        $notifier = app(EnterpriseNotificationService::class);

        $res = $notifier->dispatch($user->id, 'post.liked', 'নতুন লাইক', 'আপনার পোস্টে একজন লাইক দিয়েছে');
        $this->assertNotEmpty($res['status']);
    }

    public function test_enterprise_page_service_manages_roles_and_insights(): void
    {
        $admin = User::factory()->create();
        $editor = User::factory()->create();
        $page = Page::create([
            'owner_id' => $admin->id,
            'name' => 'Tech News Bangladesh',
            'slug' => 'tech-news-bd',
            'category' => 'Media',
        ]);

        $service = new EnterprisePageService;
        $service->setVerifiedStatus($page, true);
        $this->assertTrue($page->fresh()->is_verified);

        $role = $service->assignTeamRole($page, $editor, 'editor');
        $this->assertSame('editor', $role['role']);

        $insights = $service->getAudienceInsights($page);
        $this->assertArrayHasKey('top_cities', $insights);
    }

    public function test_enterprise_group_service_moderation(): void
    {
        $owner = User::factory()->create();
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Laravel Developers BD',
            'slug' => 'laravel-bd',
            'privacy' => 'public',
        ]);

        $service = new EnterpriseGroupService;
        $alert = $service->checkKeywordAlerts($group, 'এখানে দ্রুত টাকা দ্বিগুণ করার সুযোগ!');

        $this->assertTrue($alert['flagged']);
        $this->assertContains('টাকা', $alert['detected_keywords']);
    }

    public function test_event_service_creates_events_and_rsvps(): void
    {
        $creator = User::factory()->create();
        $attendee = User::factory()->create();
        $service = new EventService;

        $event = $service->createEvent($creator, [
            'title' => 'Bangladesh AI Summit 2026',
            'start_time' => now()->addDays(10),
            'location_type' => 'venue',
            'address' => 'BICC, Agargaon, Dhaka',
        ]);

        $rsvp = $service->rsvp($event, $attendee, 'going');
        $this->assertNotNull($rsvp->ticket_code);
        $this->assertSame(1, $event->fresh()->going_count);

        $ics = $service->generateIcs($event);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('Bangladesh AI Summit 2026', $ics);
    }

    public function test_enterprise_messenger_service_features(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $conv = Conversation::create(['type' => 'direct']);
        $conv->participants()->createMany([
            ['user_id' => $u1->id],
            ['user_id' => $u2->id],
        ]);

        $service = app(EnterpriseMessengerService::class);

        // Secret Chat
        $secret = $service->initiateSecretChat($conv, $u1);
        $this->assertSame('AES-256-GCM', $secret['cipher']);

        // Voice Message
        $voice = $service->sendVoiceMessage($conv, $u1, 'audio/voice_1.m4a', 15);
        $this->assertSame('voice', $voice->type);
        $this->assertSame(15, $voice->metadata['duration']);

        // Call Session
        $call = $service->startCallSession($conv, $u1, 'video');
        $this->assertSame('video', $call['call_type']);
    }

    public function test_resumable_multipart_storage_and_service_mesh(): void
    {
        $user = User::factory()->create();
        $storage = new ResumableMultipartStorageService;

        $session = $storage->initiateMultipartUpload($user, 'large_video.mp4', 'video/mp4', 50 * 1024 * 1024);
        $this->assertNotEmpty($session['upload_id']);

        $part = $storage->registerPart($session['upload_id'], 1, 'etag_part_1');
        $this->assertSame(1, $part['uploaded_parts']);

        $mesh = new ServiceMeshGateway;
        $authService = $mesh->resolve('auth-service');
        $this->assertSame(50051, $authService['port']);
        $this->assertSame('grpc', $authService['protocol']);
    }
}
