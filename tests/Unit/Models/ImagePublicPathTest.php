<?php

namespace Tests\Unit\Models;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagePublicPathTest extends TestCase
{
    public function test_uploaded_image_uses_the_storage_public_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/uploaded.png', 'image contents');

        $image = new Image;
        $image->filename = 'uploaded.png';

        $this->assertSame('storage/images/uploaded.png', $image->publicUrlPath());
    }

    public function test_image_path_never_falls_back_to_the_legacy_public_directory(): void
    {
        Storage::fake('public');

        $image = new Image;
        $image->filename = 'legacy.png';

        $this->assertSame('storage/images/legacy.png', $image->publicUrlPath());
    }
}
