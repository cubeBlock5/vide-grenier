<?php

namespace Tests\Utility;

use App\Utility\Upload;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    public function testUploadFileThrowsWhenExtensionIsNotAllowed(): void
    {
        $file = [
            'size' => 1000,
            'tmp_name' => '/tmp/does-not-matter',
            'name' => 'document.pdf',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This file extension is not allowed. Please upload a JPEG or PNG file');

        Upload::uploadFile($file, 'my-picture');
    }

    public function testUploadFileThrowsWhenExtensionIsNotAllowedForGifFile(): void
    {
        $file = [
            'size' => 1000,
            'tmp_name' => '/tmp/does-not-matter',
            'name' => 'animated.gif',
        ];

        $this->expectException(\Exception::class);

        Upload::uploadFile($file, 'my-picture');
    }

    public function testUploadFileThrowsWhenFileExceedsMaximumSize(): void
    {
        $file = [
            'size' => 4000001,
            'tmp_name' => '/tmp/does-not-matter',
            'name' => 'photo.jpg',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File exceeds maximum size (4MB)');

        Upload::uploadFile($file, 'my-picture');
    }

    public function testUploadFileAllowsExactlyMaximumSize(): void
    {
        // Size is at the allowed boundary, so it passes both validations and
        // falls through to move_uploaded_file(), which always fails in the
        // CLI/test SAPI (there is no real HTTP upload here) -- so we assert
        // on that downstream exception instead of a successful return. The
        // success path isn't reachable without a real HTTP upload request.
        $file = [
            'size' => 4000000,
            'tmp_name' => '/tmp/does-not-matter',
            'name' => 'photo.jpg',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('An error occurred. Please contact the administrator.');

        Upload::uploadFile($file, 'my-picture');
    }

    /**
     * @dataProvider allowedExtensionsProvider
     */
    public function testUploadFileWithValidExtensionAndSizeFailsAtMoveStepInTestEnvironment(string $extension): void
    {
        $file = [
            'size' => 1000,
            'tmp_name' => '/tmp/does-not-matter',
            'name' => 'photo.' . $extension,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('An error occurred. Please contact the administrator.');

        Upload::uploadFile($file, 'my-picture');
    }

    public static function allowedExtensionsProvider(): array
    {
        return [
            'jpeg' => ['jpeg'],
            'jpg' => ['jpg'],
            'png' => ['png'],
        ];
    }
}
