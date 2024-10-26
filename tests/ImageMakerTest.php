<?php

namespace ImageMaker\Tests;

use ImageMaker\ImageMaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageMakerTest extends TestCase
{
    protected $imageMaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageMaker = new ImageMaker();

        // Mock the storage disk
        Storage::fake($this->imageMaker->disk);
    }

    public function testUploadImage()
    {
        // Create a fake image file
        $file = UploadedFile::fake()->image('test-image.jpg');

        // Specify the path where the image should be uploaded
        $path = 'images';

        // Call the uploadImage method
        $filename = $this->imageMaker->uploadImage($file, $path);

        // Assert the file was stored
        Storage::disk($this->imageMaker->disk)->assertExists($path . '/' . $filename);

        // Assert that the file is of the correct type
        $this->assertStringEndsWith('.jpg', $filename);
    }

    public function testGetImageReturnsUploadedImage()
    {
        // Create a fake image file
        $file = UploadedFile::fake()->image('test-image.jpg');
        $path = 'images';

        // Upload the image first
        $filename = $this->imageMaker->uploadImage($file, $path);

        // Call getImage to retrieve the uploaded image
        $imageUrl = $this->imageMaker->getImage($path, $filename);

        // Assert the returned URL matches the stored file
        $this->assertEquals(asset('assets/' . $path . '/' . $filename), $imageUrl);
    }

    public function testGetImageReturnsPlaceholderWhenImageDoesNotExist()
    {
        // Specify a path and filename that doesn't exist
        $path = 'images';
        $filename = 'non-existing-image.jpg';

        // Call getImage to retrieve a non-existing image
        $imageUrl = $this->imageMaker->getImage($path, $filename);

        // Assert that the returned URL points to a placeholder
        $this->assertStringContainsString('placeholders/placeholder_', $imageUrl);
    }

    public function testGeneratePlaceholderImage()
    {
        // Define desired dimensions for the placeholder
        $width = 200;
        $height = 300;

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($this->imageMaker);
        $method = $reflection->getMethod('generatePlaceholderImage');
        $method->setAccessible(true);

        // Call the generatePlaceholderImage
        $placeholderUrl = $method->invokeArgs($this->imageMaker, [$width, $height]);

        // Assert that the placeholder image URL is valid
        $this->assertStringContainsString('placeholders/placeholder_' . $width . 'x' . $height, $placeholderUrl);

        // Additionally, you can check if the image file was created
        Storage::disk($this->imageMaker->disk)->assertExists('placeholders/placeholder_' . $width . 'x' . $height . '.png');
    }
}
