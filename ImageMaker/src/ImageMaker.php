<?php

namespace ImageMaker;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Exception;

class ImageMaker
{
    public $disk = 'public';

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
        $this->ensureDirectoryExists($path);

        if (!$file->isValid() || !$this->isImage($file)) {
            throw new Exception('Invalid image file.');
        }

        $filename = uniqid() . time() . '.' . $file->getClientOriginalExtension();
        $filePath = Storage::disk($this->disk)->path($path . '/' . $filename);

        // Move the uploaded file to the desired location
        $file->move(Storage::disk($this->disk)->path($path), $filename);

        // Resize image if size is provided
        if ($size) {
            $this->resizeImage($filePath, $size);
        }

        // Create thumbnail if required
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

        // Save the resized image
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

        // Save the thumbnail
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
        } else {
            return false; // Indicate failure due to file not found
        }
    }

    /**
     * Get the image URL, falling back to a placeholder if not found.
     *
     * @param string $path
     * @param string $filename
     * @return string
     */
    public function getImage($path, $filename)
    {
        $baseUrl = $this->getBaseUrl();

        if (Storage::disk($this->disk)->exists($path . '/' . $filename)) {
            return $baseUrl . '/' . $path . '/' . $filename;
        } elseif (Storage::disk($this->disk)->exists($path . '/thumb_' . $filename)) {
            return $baseUrl . '/' . $path . '/thumb_' . $filename;
        } else {
            return $this->generatePlaceholderImage(200, 300);
        }
    }

    /**
     * Get the base URL for assets.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        $assetFolder = Config::get('filesystems.asset_folder', 'assets');
        return asset($assetFolder);
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

        // Check if font file exists
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
        $y = ($height - $textHeight) / 2 + $textHeight; // Add text height for vertical centering

        // Add text to the image
        if (!imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text)) {
            throw new Exception('Error rendering text with TTF font.');
        }

        // Save the image to the specified path
        $placeholderPath = Storage::disk($this->disk)->path('placeholders/placeholder_' . $width . 'x' . $height . '.png');

        // Create the directory if it doesn't exist
        if (!file_exists(dirname($placeholderPath))) {
            mkdir(dirname($placeholderPath), 0777, true);
        }

        // Save the image as PNG
        imagepng($image, $placeholderPath);

        // Free up memory
        imagedestroy($image);

        return $this->getImage('placeholders', 'placeholder_' . $width . 'x' . $height . '.png');
    }
}
