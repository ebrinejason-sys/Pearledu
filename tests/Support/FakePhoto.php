<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/** Tiny PNG so photo uploads do not require the GD extension. */
class FakePhoto
{
    public static function make(string $name = 'photo.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png ?: 'png');
    }
}
