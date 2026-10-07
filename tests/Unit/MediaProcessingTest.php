<?php

namespace Tests\Unit;

use App\Services\MediaProcessingService;
use App\Services\MediaStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaProcessingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function test_media_processing_generates_webp_variants_and_metadata(): void
    {
        $storageService = new MediaStorageService('public');
        $processingService = new MediaProcessingService($storageService);

        // Create a real fake PNG image
        $file = UploadedFile::fake()->image('banner.png', 1000, 800);
        $path = 'test_uploads/banner.png';
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        // Extract metadata
        $meta = $processingService->extractMetadata($path);
        $this->assertEquals(1000, $meta['width']);
        $this->assertEquals(800, $meta['height']);
        $this->assertNotEmpty($meta['checksum']);

        // Process image variants
        $variants = $processingService->processImage($path);
        $this->assertArrayHasKey('thumbnail_path', $variants);
        $this->assertArrayHasKey('medium_path', $variants);
        $this->assertArrayHasKey('large_path', $variants);

        // Verify variants exist in storage and have webp extension
        $this->assertTrue(Storage::disk('public')->exists($variants['thumbnail_path']));
        $this->assertTrue(Storage::disk('public')->exists($variants['medium_path']));
        $this->assertTrue(Storage::disk('public')->exists($variants['large_path']));
        $this->assertStringEndsWith('.webp', $variants['thumbnail_path']);
    }
}
