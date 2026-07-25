<?php

namespace App\Helpers;

use Intervention\Image\Facades\Image;

class ImageHelper
{
    private static function makeImage(mixed $data)
    {
        return Image::make($data);
    }

    public static function compressBase64ImagesRegexGD(string $htmlContent, int $quality = 6): string
    {
        // Regular expression to match <img> tags with base64 src
        $pattern = '/<img[^>]+src="data:image\/(png|jpeg|jpg);base64,([^"]+)"[^>]*>/i';

        // Callback function to process each matched <img> tag
        $callback = function ($matches) {
            $imageType = $matches[1]; // e.g., png, jpeg, jpg
            $base64Data = $matches[2]; // The base64 data part

            // Decode the base64 image
            $decodedImage = base64_decode($base64Data);

            // Create an image instance using Intervention Image
            $image = self::makeImage($decodedImage);

            // Resize the image to a width of 700 while maintaining aspect ratio
            $image->resize(700, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            // Encode the image back to the original format with quality 70%
            $compressedImage = $image->encode($imageType, 70);

            // Get the new base64 representation of the compressed image
            $newBase64 = 'data:image/'.$imageType.';base64,'.base64_encode($compressedImage);

            // Return the updated <img> tag with the new base64 src
            return str_replace($matches[2], base64_encode($compressedImage), $matches[0]);
        };

        // Replace all matched <img> tags with the processed ones
        return preg_replace_callback($pattern, $callback, $htmlContent);
    }

    /**
     * Create a web-optimized thumbnail using Laravel Intervention Image.
     */
    public static function createThumbnail(string $fullPath, string $thumbFullPath, int $maxWidth = 600, int $maxHeight = 400, int $quality = 80): bool
    {
        if (!file_exists($fullPath)) {
            return false;
        }

        try {
            $dir = dirname($thumbFullPath);
            if (!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }

            $img = self::makeImage($fullPath);
            $img->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($thumbFullPath, $quality);

            return true;
        } catch (\Throwable $e) {
            return @copy($fullPath, $thumbFullPath);
        }
    }
}
