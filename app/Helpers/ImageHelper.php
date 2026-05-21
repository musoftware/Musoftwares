<?php

namespace App\Helpers;

use App\Models\AdminSettings;
use App\Models\Currency;
use App\Models\User;
use App\Services\GameArterService;
use App\Services\GameMonetizeService;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class ImageHelper
{
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
            $image = Image::make($decodedImage);

            // Resize the image to a width of 700 while maintaining aspect ratio
            $image->resize(700, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            // Encode the image back to the original format with quality 70%
            $compressedImage = $image->encode($imageType, 70);

            // Get the new base64 representation of the compressed image
            $newBase64 = 'data:image/' . $imageType . ';base64,' . base64_encode($compressedImage);

            // Return the updated <img> tag with the new base64 src
            return str_replace($matches[2], base64_encode($compressedImage), $matches[0]);
        };

        // Replace all matched <img> tags with the processed ones
        return preg_replace_callback($pattern, $callback, $htmlContent);
    }




}
