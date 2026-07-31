<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadValidationTest extends FeatureTestCase
{
    /** A PHP upload must never keep its executable extension. */
    public function test_safe_extension_neutralizes_executable_types(): void
    {
        foreach (['shell.php', 'evil.phtml', 'x.phar', 'page.html', 'icon.svg'] as $name) {
            $file = UploadedFile::fake()->create($name, 4);
            $ext  = safeUploadExtension($file);

            $this->assertNotContains($ext, ['php', 'phtml', 'phar', 'html', 'svg'],
                "Extension for {$name} should be neutralised, got .{$ext}");
        }
    }

    public function test_safe_extension_keeps_real_images(): void
    {
        $this->assertSame('jpg', safeUploadExtension(UploadedFile::fake()->image('a.jpg')));
        $this->assertSame('png', safeUploadExtension(UploadedFile::fake()->image('b.png')));
    }

    /** Private uploads must land on the private disk with a random name. */
    public function test_private_upload_stores_off_the_public_disk(): void
    {
        Storage::fake('local');

        $name = uploadPrivateFile(UploadedFile::fake()->image('id.png'), 'kyc');

        Storage::disk('local')->assertExists('kyc/' . $name);
        $this->assertStringEndsWith('.png', $name);
        $this->assertStringNotContainsString('id', $name); // randomised, not the original name
    }
}
