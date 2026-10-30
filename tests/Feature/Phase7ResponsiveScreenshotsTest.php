<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class Phase7ResponsiveScreenshotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Vite::useHotFile(storage_path('framework/testing/no-hot'));
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_capture_and_verify_responsive_screenshots(): void
    {
        $user = User::factory()->create([
            'username' => 'jugajug_audit_user',
            'name' => 'Jugajug Audit User',
            'email' => 'audit@example.com',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Enterprise Social Network Administrator & UI Specialist',
            'location' => 'Dhaka, Bangladesh',
            'work' => 'Senior Architect at Edge Technologies',
            'education' => 'BUET',
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'Jugajug Enterprise Social Network Phase 7 Unified UI/UX responsive verification post!',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');
        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getContent();

        // Convert asset URLs to absolute file:// URLs so Chrome headless can load CSS locally
        $baseDir = base_path('public');
        $contentWithLocalAssets = str_replace(
            [
                'http://localhost:8000/build/',
                'http://localhost/build/',
                'href="/css/',
                'src="/build/',
                'href="/build/',
                'src="/js/',
                'href="/',
            ],
            [
                'file://'.$baseDir.'/build/',
                'file://'.$baseDir.'/build/',
                'href="file://'.$baseDir.'/css/',
                'src="file://'.$baseDir.'/build/',
                'href="file://'.$baseDir.'/build/',
                'src="file://'.$baseDir.'/js/',
                'href="file://'.$baseDir.'/',
            ],
            $content
        );

        $scratchDir = '/Users/mizanurrahman/.gemini/antigravity/brain/5d035332-14ae-4a6c-9e07-028000eeee19/scratch/screenshots';
        if (! is_dir($scratchDir)) {
            mkdir($scratchDir, 0777, true);
        }

        $feedHtmlPath = $scratchDir.'/feed.html';
        file_put_contents($feedHtmlPath, $contentWithLocalAssets);

        $chromePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
        if (! file_exists($chromePath)) {
            $this->markTestSkipped('Google Chrome not installed at '.$chromePath);

            return;
        }

        // Phase 7.3: Mandatory Viewports from Section 10
        $mandatoryViewports = [
            '320x568' => [320, 568],
            '360x640' => [360, 640],
            '375x667' => [375, 667],
            '390x844' => [390, 844],
            '393x852' => [393, 852],
            '414x896' => [414, 896],
            '430x932' => [430, 932],
            '768x1024' => [768, 1024],
            '1024x768' => [1024, 768],
            '1366x768' => [1366, 768],
            '1920x1080' => [1920, 1080],
        ];

        $captured = [];

        foreach ($mandatoryViewports as $name => [$width, $height]) {
            $screenshotPath = "{$scratchDir}/viewport_{$name}.png";

            $cmd = sprintf(
                '"%s" --headless=new --disable-gpu --no-sandbox --user-data-dir=/tmp/chrome-test-profile --crash-dumps-dir=/tmp/chrome-crash --hide-scrollbars --window-size=%d,%d --screenshot="%s" "file://%s" 2>/dev/null',
                $chromePath,
                $width,
                $height,
                $screenshotPath,
                $feedHtmlPath
            );

            exec($cmd, $out, $returnCode);

            $this->assertEquals(0, $returnCode, "Chrome failed to capture screenshot at {$name}: ".implode("\n", $out));
            $this->assertFileExists($screenshotPath, "Screenshot missing at {$name}");
            $this->assertGreaterThan(5000, filesize($screenshotPath), "Screenshot at {$name} is suspiciously empty");

            $captured[$name] = [
                'viewport' => $name,
                'width' => $width,
                'height' => $height,
                'size_bytes' => filesize($screenshotPath),
                'path' => $screenshotPath,
            ];
        }

        // Also capture drawer open scenario on mobile (375x667, 390x844, 430x932)
        $drawerHtml = str_replace(
            '</body>',
            '<script>
                window.addEventListener("DOMContentLoaded", () => {
                    setTimeout(() => {
                        const btn = document.getElementById("mobileMenuToggleBtn");
                        if (btn) btn.click();
                    }, 300);
                });
            </script></body>',
            $contentWithLocalAssets
        );
        $drawerHtmlPath = $scratchDir.'/drawer_open.html';
        file_put_contents($drawerHtmlPath, $drawerHtml);

        foreach (['375x667' => [375, 667], '390x844' => [390, 844], '430x932' => [430, 932]] as $name => [$width, $height]) {
            $drawerShotPath = "{$scratchDir}/drawer_{$name}.png";
            $cmd = sprintf(
                '"%s" --headless=new --disable-gpu --no-sandbox --user-data-dir=/tmp/chrome-test-profile --crash-dumps-dir=/tmp/chrome-crash --hide-scrollbars --window-size=%d,%d --virtual-time-budget=2000 --screenshot="%s" "file://%s" 2>/dev/null',
                $chromePath,
                $width,
                $height,
                $drawerShotPath,
                $drawerHtmlPath
            );
            exec($cmd, $out, $returnCode);
            $this->assertEquals(0, $returnCode, "Drawer screenshot failed at {$name}");
            $this->assertFileExists($drawerShotPath);
            $this->assertGreaterThan(5000, filesize($drawerShotPath));
        }

        // Also capture Profile at 375, 768, 1440
        $profileResponse = $this->actingAs($user, 'web')->get('/u/jugajug_audit_user');
        $this->assertEquals(200, $profileResponse->getStatusCode());

        $profileHtml = str_replace(
            [
                'http://localhost:8000/build/',
                'http://localhost/build/',
                'href="/css/',
                'src="/build/',
                'href="/build/',
                'src="/js/',
                'href="/',
            ],
            [
                'file://'.$baseDir.'/build/',
                'file://'.$baseDir.'/build/',
                'href="file://'.$baseDir.'/css/',
                'src="file://'.$baseDir.'/build/',
                'href="file://'.$baseDir.'/build/',
                'src="file://'.$baseDir.'/js/',
                'href="file://'.$baseDir.'/',
            ],
            $profileResponse->getContent()
        );

        $profileHtmlPath = $scratchDir.'/profile.html';
        file_put_contents($profileHtmlPath, $profileHtml);

        foreach ([375, 768, 1440] as $width) {
            $profileShotPath = "{$scratchDir}/profile_{$width}px.png";
            $cmd = sprintf(
                '"%s" --headless=new --disable-gpu --no-sandbox --user-data-dir=/tmp/chrome-test-profile --crash-dumps-dir=/tmp/chrome-crash --hide-scrollbars --window-size=%d,%d --screenshot="%s" "file://%s" 2>/dev/null',
                $chromePath,
                $width,
                900,
                $profileShotPath,
                $profileHtmlPath
            );
            exec($cmd, $out, $returnCode);
            $this->assertEquals(0, $returnCode, "Profile screenshot failed at {$width}px");
            $this->assertFileExists($profileShotPath);
            $this->assertGreaterThan(5000, filesize($profileShotPath));
        }

        $this->assertCount(11, $captured);
    }
}
