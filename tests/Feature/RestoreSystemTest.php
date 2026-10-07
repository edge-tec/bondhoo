<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RestoreSystemTest — ব্যাকআপ রিস্টোর ও অখণ্ডতা ভ্যালিডেশন টেস্ট
 */
class RestoreSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * সঠিক চেকসামযুক্ত ব্যাকআপ ফাইল রিস্টোর ভেরিফিকেশন পাস করে।
     */
    public function test_backup_restore_verification_succeeds_for_valid_backup(): void
    {
        $service = app(BackupService::class);
        $backup = $service->createBackup('db', 'local');

        $result = $service->verifyRestore($backup);

        $this->assertTrue($result['verified']);
        $this->assertTrue($result['checksum_match']);
        $this->assertStringContainsString('সফলভাবে যাচাইকৃত', $result['message']);
    }

    /**
     * ব্যর্থ বা অপূর্ণাঙ্গ ব্যাকআপের ক্ষেত্রে রিস্টোর ভ্যালিডেশন ব্যর্থ হওয়া যাচাই।
     */
    public function test_backup_restore_verification_fails_for_incomplete_backup(): void
    {
        $service = app(BackupService::class);
        $backup = $service->createBackup('full', 'local');

        // স্ট্যাটাস ফেইল্ড করে দিলে ভেরিফিকেশন ফেইল করবে
        $backup->update(['status' => 'failed']);

        $result = $service->verifyRestore($backup);

        $this->assertFalse($result['verified']);
        $this->assertFalse($result['checksum_match']);
    }
}
