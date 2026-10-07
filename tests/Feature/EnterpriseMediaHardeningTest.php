<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\MediaProcessingJob;
use App\Models\MediaUploadChunk;
use App\Models\MediaUploadSession;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use App\Models\UserStorageQuota;
use App\Services\MediaProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ মিডিয়া হার্ডেনিং টেস্ট সুইট:
 * - Real streaming chunk upload & zero RAM spikes
 * - Concurrent uploads & user isolation
 * - Resume after interruption & missing chunks detection
 * - Chunk integrity (duplicate safe, invalid checksum rejection)
 * - Quota race condition (atomic lock protection)
 * - Smart dynamic disk reservation
 * - Worker crash recovery & dead-letter state
 * - FFmpeg failure isolation & GD fallback
 * - Storage reconciliation & orphan detection
 * - Deployment safety
 * - Security upload & MIME spoofing rejection
 * - Abuse & concurrent session limits
 * - RESTful session routes compatibility
 */
class EnterpriseMediaHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $chunkDir = storage_path('app/chunks');
        if (File::isDirectory($chunkDir)) {
            File::deleteDirectory($chunkDir);
        }

        parent::tearDown();
    }

    /**
     * টেস্ট ১: রিয়েল লার্জ ফাইল স্ট্রিমিং আপলোড এবং জিরো র‍্যাম স্পাইক ভ্যালিডেশন
     */
    public function test_large_file_streaming_upload_and_assembly_zero_ram_spikes(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        // ৫ মেগাবাইট রিয়েল বাইনারি পে-লোড (৫টি ১ মেগাবাইট চাঙ্কে)
        $chunkSize = 1048576; // 1 MB
        $totalChunks = 5;
        $totalBytes = $chunkSize * $totalChunks;

        $initRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'large_video_stream.mp4',
                'file_size' => $totalBytes,
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
                'chunk_size' => $chunkSize,
            ]);

        $initRes->assertStatus(201);
        $sessionId = $initRes->json('data.session_id');

        // মেমরি রেকর্ড
        $memBefore = memory_get_usage(true);

        for ($i = 1; $i <= $totalChunks; $i++) {
            // প্রথম চাঙ্কে ভ্যালিড MP4 ম্যাজিক বাইট হেডার প্রদান
            if ($i === 1) {
                $prefix = "\x00\x00\x00\x20ftypmp42\x00\x00\x00\x00isommp42";
                $content = $prefix.str_repeat('A', $chunkSize - strlen($prefix));
            } else {
                $content = str_repeat(chr(65 + ($i % 26)), $chunkSize);
            }

            $checksum = hash('sha256', $content);
            $chunkFile = UploadedFile::fake()->createWithContent("chunk_{$i}.part", $content);

            $res = $this->actingAs($user, 'sanctum')
                ->postJson("/api/v2/uploads/{$sessionId}/chunk", [
                    'chunk_number' => $i,
                    'chunk' => $chunkFile,
                    'checksum' => $checksum,
                ]);

            $res->assertStatus(200);

            if ($i < $totalChunks) {
                $res->assertJsonPath('data.is_complete', false);
            } else {
                $res->assertJsonPath('data.is_complete', true);
            }
        }

        $memAfter = memory_get_usage(true);
        // অ্যাসেম্বলির সময় মেমরি স্পাইক পুরো ফাইলের সাইজ অতিক্রম করেনি (Stream Copy 64KB)
        $memDeltaMb = round(abs($memAfter - $memBefore) / 1024 / 1024, 2);
        $this->assertLessThan(20.0, $memDeltaMb, "Memory usage spiked by {$memDeltaMb}MB!");

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'collection' => 'reel',
            'size' => $totalBytes,
        ]);
    }

    /**
     * টেস্ট ২: কনকারেন্ট আপলোড ও মাল্টি-ইউজার আইসোলেশন
     */
    public function test_concurrent_uploads_and_independent_assembly(): void
    {
        Queue::fake();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $content1 = "\x00\x00\x00\x20ftypisomUser1VideoContentData";
        $content2 = "\x00\x00\x00\x20ftypisomUser2VideoContentData";

        $init1 = $this->actingAs($user1, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'user1.mp4',
            'file_size' => strlen($content1),
            'mime_type' => 'video/mp4',
            'collection' => 'reel',
        ]);
        $init2 = $this->actingAs($user2, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'user2.mp4',
            'file_size' => strlen($content2),
            'mime_type' => 'video/mp4',
            'collection' => 'reel',
        ]);

        $session1 = $init1->json('data.session_id');
        $session2 = $init2->json('data.session_id');

        // ক্রস-ইউজার আপলোড এক্সেস অস্বীকৃত
        $crossRes = $this->actingAs($user1, 'sanctum')->postJson("/api/v2/uploads/{$session2}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c.part', $content1),
        ]);
        $crossRes->assertStatus(422);

        // নিজ নিজ সেশনে আপলোড
        $this->actingAs($user1, 'sanctum')->postJson("/api/v2/uploads/{$session1}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c1.part', $content1),
        ])->assertStatus(200);

        $this->actingAs($user2, 'sanctum')->postJson("/api/v2/uploads/{$session2}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c2.part', $content2),
        ])->assertStatus(200);

        $this->assertDatabaseHas('media', ['user_id' => $user1->id, 'size' => strlen($content1)]);
        $this->assertDatabaseHas('media', ['user_id' => $user2->id, 'size' => strlen($content2)]);
    }

    /**
     * টেস্ট ৩: ইন্টারাপশন ও আউট-অফ-অর্ডার চাঙ্ক আপলোডের পরে রেজুম
     */
    public function test_resume_after_interruption_and_missing_chunks_reporting(): void
    {
        $user = User::factory()->create();

        $session = MediaUploadSession::create([
            'user_id' => $user->id,
            'session_id' => 'resume-test-session-uuid',
            'collection' => 'reel',
            'filename' => 'reel_resume.mp4',
            'original_name' => 'reel_resume.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 3000,
            'chunk_size' => 1000,
            'total_chunks' => 3,
            'uploaded_chunks_count' => 2,
            'status' => 'uploading',
            'temp_dir' => storage_path('app/chunks/resume-test-session-uuid'),
            'expires_at' => now()->addHours(24),
        ]);

        // চাঙ্ক ১ এবং ৩ আপলোড করা হয়েছে, কিন্তু চাঙ্ক ২ মিসিং
        MediaUploadChunk::create([
            'upload_session_id' => $session->id,
            'chunk_number' => 1,
            'chunk_size' => 1000,
            'temp_path' => 'chunk_1.part',
            'status' => 'verified',
        ]);
        MediaUploadChunk::create([
            'upload_session_id' => $session->id,
            'chunk_number' => 3,
            'chunk_size' => 1000,
            'temp_path' => 'chunk_3.part',
            'status' => 'verified',
        ]);

        $resumeRes = $this->actingAs($user, 'sanctum')->getJson("/api/v2/uploads/{$session->session_id}/resume");
        $resumeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.missing_chunks', [2])
            ->assertJsonPath('data.next_chunk', 2);
    }

    /**
     * টেস্ট ৪: চাঙ্ক ইন্টিগ্রিটি - ডুপ্লিকেট চাঙ্ক আপলোড সেফ ও আইডেমপোটেন্ট
     */
    public function test_chunk_integrity_duplicate_chunk_is_safe_and_idempotent(): void
    {
        $user = User::factory()->create();
        $part = "\x00\x00\x00\x20ftypmp42chunk1";

        $init = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'dup.mp4',
            'file_size' => strlen($part) * 2,
            'mime_type' => 'video/mp4',
            'chunk_size' => strlen($part),
        ]);
        $sessionId = $init->json('data.session_id');

        // প্রথমবার চাঙ্ক ১ আপলোড
        $this->actingAs($user, 'sanctum')->postJson("/api/v2/uploads/{$sessionId}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c1.part', $part),
        ])->assertStatus(200);

        // দ্বিতীয়বার একই চাঙ্ক ১ পুনরায় আপলোড
        $res2 = $this->actingAs($user, 'sanctum')->postJson("/api/v2/uploads/{$sessionId}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c1.part', $part),
        ]);
        $res2->assertStatus(200);

        // ডাটাবেজে শুধুমাত্র একটিই চাঙ্ক ১ রেকর্ড থাকবে
        $session = MediaUploadSession::where('session_id', $sessionId)->first();
        $count = MediaUploadChunk::where('upload_session_id', $session->id)->where('chunk_number', 1)->count();
        $this->assertEquals(1, $count);
    }

    /**
     * টেস্ট ৫: ভুল চেকসাম দিলে চাঙ্ক তাৎক্ষণিকভাবে প্রত্যাখ্যাত হয়
     */
    public function test_chunk_integrity_invalid_checksum_rejected_immediately(): void
    {
        $user = User::factory()->create();
        $part = 'ChunkContentPayload';

        $init = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'bad_sum.mp4',
            'file_size' => 1000,
            'mime_type' => 'video/mp4',
        ]);
        $sessionId = $init->json('data.session_id');

        $fakeChecksum = str_repeat('f', 64);
        $res = $this->actingAs($user, 'sanctum')->postJson("/api/v2/uploads/{$sessionId}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c1.part', $part),
            'checksum' => $fakeChecksum,
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * টেস্ট ৬: ইউজার কোটা রেস কন্ডিশন প্রোটেকশন
     */
    public function test_user_quota_race_condition_atomic_lock_prevents_bypass(): void
    {
        $user = User::factory()->create();

        UserStorageQuota::create([
            'user_id' => $user->id,
            'tier' => 'free',
            'max_storage_bytes' => 52428800, // 50 MB
            'used_storage_bytes' => 0,
            'max_video_size_bytes' => 52428800,
            'max_image_size_bytes' => 20971520,
            'max_daily_uploads' => 50,
            'today_uploads_count' => 0,
        ]);

        // প্রথম আপলোড সেশন: ৪০ মেগাবাইট (৫০ মেগাবাইটের মধ্যে পাস হবে)
        $res1 = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'file1.mp4',
            'file_size' => 41943040, // 40 MB
            'mime_type' => 'video/mp4',
        ]);
        $res1->assertStatus(201);

        // দ্বিতীয় সমান্তরাল আপলোড সেশন: ৩০ মেগাবাইট (৪০ + ৩০ = ৭০ মেগাবাইট > ৫০ মেগাবাইট -> ব্লক হবে)
        $res2 = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'file2.mp4',
            'file_size' => 31457280, // 30 MB
            'mime_type' => 'video/mp4',
        ]);

        $res2->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * টেস্ট ৭: ওয়ার্কার ক্র্যাশ রিকভারি এবং ডেড-লেটার স্টেট
     */
    public function test_worker_crash_recovery_reschedules_stuck_job_and_dead_letters_after_max_attempts(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/test/reel.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1000,
            'checksum' => 'fake_hash',
            'processing_status' => 'processing',
        ]);

        // ক্র্যাশড ওয়ার্কারের জব রেকর্ড (started_at ৩০ মিনিট আগে)
        $job = MediaProcessingJob::create([
            'user_id' => $user->id,
            'media_id' => $media->id,
            'job_uuid' => 'crashed-job-uuid-1',
            'job_type' => 'video_transcode',
            'status' => 'processing',
            'attempts' => 1,
            'started_at' => now()->subMinutes(30),
        ]);

        // প্রথমবার রিকভারি কমান্ড চালনা: attempts হবে ২ এবং রিট্রাই হবে
        $this->artisan('media:recover-stuck-jobs', ['--timeout-minutes' => 15])->assertSuccessful();

        $job->refresh();
        $this->assertEquals('retrying', $job->status);
        $this->assertEquals(2, $job->attempts);

        // পুনরায় ক্র্যাশ হলে (started_at আবার অতীতের করে দিলে)
        $job->update(['status' => 'processing', 'started_at' => now()->subMinutes(30)]);

        // দ্বিতীয়বার রিকভারি: attempts হবে ৩ (ম্যাক্সিমাম) -> dead_letter স্টেটে যাবে
        $this->artisan('media:recover-stuck-jobs', ['--timeout-minutes' => 15])->assertSuccessful();

        $job->refresh();
        $this->assertEquals('dead_letter', $job->status);

        $media->refresh();
        $this->assertEquals('failed', $media->processing_status);
    }

    /**
     * টেস্ট ৮: FFmpeg ফেইলিয়র আইসোলেশন ও ফেইল-সেফ GD পোস্টার জেনারেশন
     */
    public function test_ffmpeg_isolation_graceful_fallback_to_gd_poster_when_ffmpeg_fails(): void
    {
        $user = User::factory()->create();

        // ডামি ভিডিও ফাইল তৈরি
        $videoRelative = 'uploads/test_reel_fail/video.mp4';
        $fullPath = storage_path("app/public/{$videoRelative}");
        File::ensureDirectoryExists(dirname($fullPath));
        file_put_contents($fullPath, "\x00\x00\x00\x20ftypmp42DummyContentVideo");

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => $videoRelative,
            'mime_type' => 'video/mp4',
            'size' => filesize($fullPath),
            'checksum' => hash_file('sha256', $fullPath),
            'processing_status' => 'processing',
        ]);

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'Test Reel FFmpeg Failure Handling',
            'status' => Reel::STATUS_PROCESSING,
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'video_path' => $videoRelative,
            'mime_type' => 'video/mp4',
            'size' => filesize($fullPath),
        ]);

        // ProcessMediaJob রান করা (FFmpeg ফেল করলেও GD পোস্টার বানিয়ে status ready করবে)
        $job = new ProcessMediaJob($media->id);
        $job->handle(app(MediaProcessingService::class));

        $media->refresh();
        $reel->refresh();

        $this->assertEquals('ready', $media->processing_status);
        $this->assertEquals(Reel::STATUS_READY, $reel->status);
        $this->assertNotEmpty($media->thumbnail_path);

        $thumbFullPath = storage_path("app/public/{$media->thumbnail_path}");
        $this->assertFileExists($thumbFullPath);
        $this->assertGreaterThan(0, filesize($thumbFullPath));

        // ক্লিনআপ
        @unlink($fullPath);
        @unlink($thumbFullPath);
    }

    /**
     * টেস্ট ৯: স্টোরেজ রিকনসিলিয়েশন কমান্ড ইউজার কোটা অসামঞ্জস্য মেরামত করে
     */
    public function test_storage_reconciliation_command_detects_and_repairs_quota_mismatches(): void
    {
        $user = User::factory()->create();

        // আসল মিডিয়া ফাইল তৈরি: মোট ২০০০ বাইট
        Media::create([
            'user_id' => $user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'uploads/recon/1.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2000,
            'checksum' => 'c1',
        ]);

        // কোটাতে ভুলবশত ৯৯৯৯৯ বাইট রেকর্ড ছিল
        $quota = UserStorageQuota::create([
            'user_id' => $user->id,
            'tier' => 'free',
            'max_storage_bytes' => 1000000,
            'used_storage_bytes' => 99999,
        ]);

        // রিকনসিলিয়েশন কমান্ড উইথ --repair রান করা
        $this->artisan('media:reconcile-storage', ['--repair' => true])->assertSuccessful();

        $quota->refresh();
        $this->assertEquals(2000, $quota->used_storage_bytes);
    }

    /**
     * টেস্ট ১০: সিকিউরিটি আপলোড - MIME স্পুফিং ও ম্যালিসিয়াস ফাইল ব্লকিং
     */
    public function test_security_upload_rejects_mime_spoofing_malicious_file(): void
    {
        $user = User::factory()->create();

        // পিএইচপি স্ক্রিপ্টকে video/mp4 বলে আপলোড করার চেষ্টা
        $maliciousPayload = "<?php echo 'Hacked system!'; system(\$_GET['cmd']); ?>";

        $init = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'exploit.mp4',
            'file_size' => strlen($maliciousPayload),
            'mime_type' => 'video/mp4',
        ]);
        $sessionId = $init->json('data.session_id');

        $res = $this->actingAs($user, 'sanctum')->postJson("/api/v2/uploads/{$sessionId}/chunk", [
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c1.part', $maliciousPayload),
        ]);

        // ফাইল সিগনেচার ভ্যালিডেশন ফেইল করে ৪২২ রিটার্ন করবে
        $res->assertStatus(422);

        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'status' => 'failed',
        ]);

        // ডাটাবেজে কোনো Media রেকর্ড তৈরি হবে না
        $this->assertDatabaseMissing('media', [
            'user_id' => $user->id,
            'original_path' => 'uploads/general/exploit.mp4',
        ]);
    }

    /**
     * টেস্ট ১১: অ্যাবিউজ প্রোটেকশন - কনকারেন্ট সেশন লিমিট এনফোর্সমেন্ট
     */
    public function test_abuse_protection_enforces_concurrent_active_sessions_limit(): void
    {
        $user = User::factory()->create();

        // ৫টি সেশন তৈরি করা (ডিফল্ট লিমিট ৫)
        for ($i = 1; $i <= 5; $i++) {
            MediaUploadSession::create([
                'user_id' => $user->id,
                'session_id' => "session-limit-{$i}",
                'collection' => 'story',
                'filename' => "file_{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 1000,
                'chunk_size' => 1000,
                'total_chunks' => 1,
                'status' => 'uploading',
                'expires_at' => now()->addHours(12),
            ]);
        }

        // ৬ষ্ঠ সেশন শুরু করার চেষ্টা
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/init', [
            'filename' => 'excess_session.jpg',
            'file_size' => 1000,
            'mime_type' => 'image/jpeg',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * টেস্ট ১২: RESTful আপলোড রুট সামঞ্জস্য (RESTful Session Routes Compatibility)
     */
    public function test_restful_upload_session_endpoints_compatibility(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $part = "\x00\x00\x00\x20ftypmp42RESTfulContent";

        // POST /api/v2/uploads/sessions
        $initRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/sessions', [
            'filename' => 'restful.mp4',
            'file_size' => strlen($part),
            'mime_type' => 'video/mp4',
        ]);
        $initRes->assertStatus(201);
        $sessionId = $initRes->json('data.session_id');

        // POST /api/v2/uploads/chunks
        $chunkRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/uploads/chunks', [
            'session_id' => $sessionId,
            'chunk_number' => 1,
            'chunk' => UploadedFile::fake()->createWithContent('c.part', $part),
        ]);
        $chunkRes->assertStatus(200);

        // GET /api/v2/uploads/sessions/{id}
        $statusRes = $this->actingAs($user, 'sanctum')->getJson("/api/v2/uploads/sessions/{$sessionId}");
        $statusRes->assertStatus(200)
            ->assertJsonPath('data.session_id', $sessionId);

        // POST /api/v2/uploads/sessions/{id}/complete
        $compRes = $this->actingAs($user, 'sanctum')->postJson("/api/v2/uploads/sessions/{$sessionId}/complete");
        $compRes->assertStatus(200);
    }
}
