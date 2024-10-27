<?php

namespace ImageMaker;

use Illuminate\Support\Facades\Storage;
use Exception;

class ImageMaker
{
    protected $disk; // This will hold the disk name

    public function __construct($disk = 'public') {
        $this->disk = $disk; // Set the default disk
    }

    /**
     * Upload an image to the specified path.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @param string|null $size
     * @param string|null $thumb
     * @param string|null $old
     * @return string
     * @throws Exception
     */
    public function uploadImage($file, $path, $size = null, $thumb = null, $old = null)
    {
        // Ensure the target directory exists
        $this->ensureDirectoryExists($path);

        // Validate the uploaded file
        if (!$file->isValid() || !$this->isImage($file)) {
            throw new Exception('Invalid image file.');
        }

        // Generate a unique filename
        $filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();

        // Move the uploaded file to the desired location
        Storage::disk($this->disk)->putFileAs($path, $file, $filename);

        // Get the full file path for resizing and thumbnail creation
        $filePath = Storage::disk($this->disk)->path($path . '/' . $filename);

        // Resize the image if a size is provided
        if ($size) {
            $this->resizeImage($filePath, $size);
        }

        // Create a thumbnail if required
        if ($thumb) {
            $this->createThumbnail($filePath, $thumb, $path, $filename);
        }

        // Remove old image if specified
        if ($old) {
            $this->removeOldImage($path, $old);
        }

        return $filename;
    }

    /**
     * Resize the image to the specified size.
     *
     * @param string $filePath
     * @param string $size
     */
    protected function resizeImage($filePath, $size)
    {
        list($width, $height) = explode('x', $size);
        $src = imagecreatefromstring(file_get_contents($filePath));

        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));

        // Save the resized image based on its original type
        imagejpeg($newImage, $filePath);
        imagedestroy($src);
        imagedestroy($newImage);
    }

    /**
     * Create a thumbnail of the image.
     *
     * @param string $filePath
     * @param string $thumb
     * @param string $path
     * @param string $filename
     */
    protected function createThumbnail($filePath, $thumb, $path, $filename)
    {
        list($width, $height) = explode('x', $thumb);
        $src = imagecreatefromstring(file_get_contents($filePath));

        $thumbImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumbImage, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));

        // Save the thumbnail based on its original type
        $thumbnailPath = Storage::disk($this->disk)->path($path . '/thumb_' . $filename);
        imagejpeg($thumbImage, $thumbnailPath);
        imagedestroy($src);
        imagedestroy($thumbImage);
    }

    /**
     * Ensure the directory exists for storing images.
     *
     * @param string $path
     */
    protected function ensureDirectoryExists($path)
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->makeDirectory($path);
        }
    }

    /**
     * Validate if the uploaded file is an image.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return bool
     */
    protected function isImage($file)
    {
        $validMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getClientMimeType(), $validMimeTypes);
    }

    /**
     * Remove the old image from storage.
     *
     * @param string $path
     * @param string $filename
     * @return bool
     */
    protected function removeOldImage($path, $filename)
    {
        $filePath = Storage::disk($this->disk)->path($path . '/' . $filename);

        // Check if the image file exists
        if (Storage::disk($this->disk)->exists($path . '/' . $filename)) {
            // Delete the main image file
            Storage::disk($this->disk)->delete($path . '/' . $filename);

            // Optionally, delete the thumbnail if it exists
            $thumbnailPath = $path . '/thumb_' . $filename;
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                Storage::disk($this->disk)->delete($thumbnailPath);
            }

            return true; // Indicate success
        }

        return false; // Indicate failure due to file not found
    }

    /**
     * Get the image URL, falling back to a placeholder if not found.
     *
     * @param string $path
     * @param string $filename
     * @param string $size
     * @param bool $thumb
     * @return string
     */
    public function getImage($path, $filename, $size = '100x100', $thumb = false)
    {
        // Construct the storage path
        $storagePath = $path . '/' . $filename;

        // Check if the main image exists
        if (Storage::disk($this->disk)->exists($storagePath)) {
            return Storage::url($storagePath); // Generate the URL for the main image
        }

        // Check if the thumbnail image exists, if $thumb is true
        if ($thumb) {
            $thumbnailPath = $path . '/thumb_' . $filename;
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                return Storage::url($thumbnailPath); // Generate the URL for the thumbnail image
            }
        }

        // If no image exists, generate a placeholder image
        list($width, $height) = explode('x', $size);
        return $this->generatePlaceholderImage((int)$width, (int)$height);
    }

   /**
 * Generate a placeholder image with specified dimensions and optional background color.
 *
 * @param int $width
 * @param int $height
 * @param array $color
 * @return string
 * @throws Exception
 */
    public function generatePlaceholderImage($width, $height, $color = [200, 200, 200])
    {
        // Create a blank image
        $image = imagecreatetruecolor($width, $height);

        // Allocate a color for the placeholder
        $bgColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);

        // Fill the image with the background color
        imagefill($image, 0, 0, $bgColor);

        // Define the text to display
        $text = "{$width}x{$height}"; // Display the dimensions
        $fontPath = __DIR__ . '/ttfonts/font.ttf'; // Ensure this is the correct path

        // Check if the font file exists
        if (!file_exists($fontPath)) {
            throw new Exception('Font file not found at path: ' . $fontPath);
        }

        // Set font size and color
        $fontSize = 12; // Font size
        $textColor = imagecolorallocate($image, 0, 0, 0); // Black color

        // Calculate the bounding box of the text
        $boundingBox = imagettfbbox($fontSize, 0, $fontPath, $text);

        // Calculate the width and height of the text
        $textWidth = abs($boundingBox[4] - $boundingBox[0]);
        $textHeight = abs($boundingBox[5] - $boundingBox[1]);

        // Calculate the x and y coordinates to center the text
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2 + $textHeight;

        // Write the text to the image
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);

        // Define the placeholder directory and ensure it exists
        $placeholderDir = 'placeholders';
        if (!Storage::disk($this->disk)->exists($placeholderDir)) {
            Storage::disk($this->disk)->makeDirectory($placeholderDir);
        }

        // Save the placeholder image
        $placeholderPath = "{$placeholderDir}/{$width}x{$height}.png"; // Save it in the placeholders directory
        imagepng($image, Storage::disk($this->disk)->path($placeholderPath));

        // Free up memory
        imagedestroy($image);

        return Storage::url($placeholderPath);
    }
}
