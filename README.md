# ImageMaker Package

The **ImageMaker** package provides a convenient way to handle image uploads, resizing, thumbnail creation, and generating placeholder images in your Laravel application. 
It abstracts the complexities of image handling, making it easy to integrate image functionality into your projects.

## Features

- **Image Uploading**: Easily upload images to the specified storage disk.
- **Image Resizing**: Resize images to specific dimensions upon upload.
- **Thumbnail Creation**: Automatically generate thumbnails for images.
- **Old Image Removal**: Optionally remove old images when new ones are uploaded.
- **Image Retrieval**: Retrieve image URLs with fallback to a placeholder if the image is not found.
- **Placeholder Generation**: Create placeholder images with specified dimensions and custom background colors.
- **Configurable Storage Disk**: Set the storage disk used for saving images during instantiation.

## Installation

To install the `ImageMaker` package, include it in your `composer.json` file or run:

```bash
composer require imagemaker/imagemaker

## Usage

### Basic Setup

To use the `ImageMaker` package, first, ensure you have it installed. Then, instantiate the `ImageMaker` class, specifying the desired storage disk:

```php
use ImageMaker\ImageMaker;

// Instantiate the ImageMaker class with the desired disk (default is 'public')
$imageMaker = new ImageMaker('public');

## Methods

### 1. `__construct($disk = 'public')`

#### Description
Initializes a new instance of the `ImageMaker` class.

#### Parameters
- **`$disk`** *(string, optional)*: The name of the storage disk to use. Defaults to `'public'`.

---

### 2. `uploadImage($file, $path, $size = null, $thumb = null, $old = null)`

#### Description
Uploads an image to the specified path, optionally resizing it and creating a thumbnail. It also removes an old image if specified.

#### Parameters
- **`$file`** *(\Illuminate\Http\UploadedFile)*: The uploaded file instance.
- **`$path`** *(string)*: The directory path where the image will be stored.
- **`$size`** *(string|null, optional)*: The size to which the image should be resized (format: `widthxheight`).
- **`$thumb`** *(string|null, optional)*: The size for the thumbnail (format: `widthxheight`).
- **`$old`** *(string|null, optional)*: The filename of the old image to be removed.

#### Returns
- **string**: The filename of the uploaded image.

#### Throws
- **Exception**: If the uploaded file is invalid or not an image.

---

### 3. `getImage($path, $filename, $size = '100x100', $thumb = false)`

#### Description
Retrieves the URL of the uploaded image. If the image is not found, it can return a placeholder or a thumbnail if specified.

#### Parameters
- **`$path`** *(string)*: The directory path where the image is stored.
- **`$filename`** *(string)*: The name of the uploaded image file.
- **`$size`** *(string, optional)*: The size for the fallback placeholder image (format: `widthxheight`). Defaults to `'100x100'`.
- **`$thumb`** *(bool, optional)*: A boolean to specify whether to return the thumbnail if available. Defaults to `false`.

#### Returns
- **string**: The URL of the uploaded image or placeholder image.

---

### 4. `generatePlaceholderImage($width, $height, $color = [200, 200, 200])`

#### Description
Generates a placeholder image with specified dimensions and an optional background color.

#### Parameters
- **`$width`** *(int)*: The width of the placeholder image.
- **`$height`** *(int)*: The height of the placeholder image.
- **`$color`** *(array, optional)*: An array representing the RGB values for the background color. Defaults to `[200, 200, 200]`.

#### Returns
- **string**: The URL of the generated placeholder image.

#### Throws
- **Exception**: If the font file required for text rendering is not found.

---

### 5. `removeOldImage($path, $filename)`

#### Description
Removes the old image from storage if it exists, along with its thumbnail.

#### Parameters
- **`$path`** *(string)*: The directory path where the image is stored.
- **`$filename`** *(string)*: The name of the old image file to be removed.

#### Returns
- **bool**: Returns `true` if the image was successfully removed, `false` if the file was not found.

---

### 6. `ensureDirectoryExists($path)`

#### Description
Ensures that the specified directory exists for storing images. If the directory does not exist, it creates it.

#### Parameters
- **`$path`** *(string)*: The directory path to check or create.

#### Returns
- **void**

---

### 7. `isImage($file)`

#### Description
Validates if the uploaded file is an image based on its MIME type.

#### Parameters
- **`$file`** *(\Illuminate\Http\UploadedFile)*: The uploaded file instance.

#### Returns
- **bool**: Returns `true` if the file is a valid image type, otherwise `false`.

---

### 8. `resizeImage($filePath, $size)`

#### Description
Resizes the image to the specified dimensions.

#### Parameters
- **`$filePath`** *(string)*: The full file path of the image to be resized.
- **`$size`** *(string)*: The size to which the image should be resized (format: `widthxheight`).

#### Returns
- **void**

---

### 9. `createThumbnail($filePath, $thumb, $path, $filename)`

#### Description
Creates a thumbnail of the uploaded image at the specified dimensions.

#### Parameters
- **`$filePath`** *(string)*: The full file path of the original image.
- **`$thumb`** *(string)*: The size for the thumbnail (format: `widthxheight`).
- **`$path`** *(string)*: The directory path where the thumbnail will be stored.
- **`$filename`** *(string)*: The name of the original uploaded file.

#### Returns
- **void**

---

This structure gives users a clear understanding of each method's purpose, parameters, and return values, making it easier for them to integrate and use the `ImageMaker` package effectively.


