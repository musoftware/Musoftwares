<?php

namespace Modules\SoftwareStore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Pictures and the sample menu for the free QR menu maker.
 *
 * A menu stores picture references, never URLs: "lib:pizza-margherita" is one of our food photos
 * (public/images/qr-menu, Unsplash licence) and "up:<32 hex>" is a photo the restaurant uploaded,
 * re-encoded to WebP on our side so nothing is served the way it was sent.
 */
class QrMenuKit
{
    /** The demo menu is shown at /menu/demomenu; it is built here, not stored. */
    public const DEMO_CODE = 'demomenu';

    public const BADGES = ['popular', 'new', 'spicy', 'veg'];

    private const UPLOAD_DIR = 'qr-menus';

    private const MAX_SIDE = 1400;

    /** @var array<string, array{0: string, 1: string}> photo => [Arabic, English] */
    public const LIBRARY = [
        'cover-dining' => ['قاعة المطعم', 'Dining room'],
        'cover-interior' => ['ديكور المطعم', 'Restaurant interior'],
        'cover-plates' => ['أطباق متنوعة', 'Table of dishes'],
        'mixed-grill' => ['مشويات مشكلة', 'Mixed grill'],
        'kebab' => ['كباب على الفحم', 'Charcoal kebab'],
        'shawarma' => ['شاورما', 'Shawarma'],
        'steak-fries' => ['ستيك وبطاطس', 'Steak and fries'],
        'fried-chicken' => ['فراخ مقرمشة', 'Fried chicken'],
        'classic-burger' => ['برجر كلاسيك', 'Classic burger'],
        'smash-burger' => ['سماش برجر', 'Smash burger'],
        'burger-fries' => ['برجر وبطاطس', 'Burger and fries'],
        'pizza-margherita' => ['بيتزا مارجريتا', 'Margherita pizza'],
        'pizza-chicken' => ['بيتزا فراخ', 'Chicken pizza'],
        'penne-arrabbiata' => ['بيني أرابياتا', 'Penne arrabbiata'],
        'chicken-bowl' => ['بول فراخ', 'Chicken bowl'],
        'veggie-bowl' => ['بول خضار', 'Veggie bowl'],
        'green-salad' => ['سلطة خضراء', 'Green salad'],
        'tiramisu' => ['تيراميسو', 'Tiramisu'],
        'pancakes' => ['بان كيك', 'Pancakes'],
        'donuts' => ['دونتس', 'Donuts'],
        'cupcakes' => ['كب كيك', 'Cupcakes'],
        'coffee' => ['قهوة', 'Coffee'],
        'latte-cheers' => ['لاتيه', 'Latte'],
        'mocktails' => ['موكتيلز', 'Mocktails'],
    ];

    public static function isValidRef(?string $ref): bool
    {
        if ($ref === null || $ref === '') {
            return true;
        }
        if (preg_match('/^lib:([a-z0-9-]{2,40})$/', $ref, $m)) {
            return isset(self::LIBRARY[$m[1]]);
        }
        if (preg_match('/^up:([a-f0-9]{32})$/', $ref, $m)) {
            return Storage::disk('public_uploads')->exists(self::UPLOAD_DIR."/{$m[1]}.webp");
        }

        return false;
    }

    public static function url(?string $ref): ?string
    {
        if (! $ref) {
            return null;
        }
        if (str_starts_with($ref, 'lib:')) {
            return asset('images/qr-menu/'.substr($ref, 4).'.webp');
        }
        if (str_starts_with($ref, 'up:')) {
            return asset('uploads/'.self::UPLOAD_DIR.'/'.substr($ref, 3).'.webp');
        }

        return null;
    }

    /** @return array<int, array{ref: string, url: string, label: string}> */
    public static function library(string $lang): array
    {
        return collect(self::LIBRARY)->map(fn (array $labels, string $name) => [
            'ref' => "lib:{$name}",
            'url' => self::url("lib:{$name}"),
            'label' => $labels[$lang === 'en' ? 1 : 0],
        ])->values()->all();
    }

    /**
     * Re-encodes an uploaded photo to WebP (no larger than 1400px) and returns its reference.
     * Decoding and re-encoding drops anything that is not the picture itself.
     */
    public static function storeUpload(UploadedFile $file): ?string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $image) {
            return null;
        }

        [$width, $height] = [imagesx($image), imagesy($image)];
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        if ($scale < 1) {
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagewebp($image, null, 80);
        $webp = (string) ob_get_clean();
        imagedestroy($image);

        $id = bin2hex(random_bytes(16));
        Storage::disk('public_uploads')->put(self::UPLOAD_DIR."/{$id}.webp", $webp);

        return "up:{$id}";
    }

    /** A complete sample restaurant, so people see what a finished menu looks like. */
    public static function demo(string $lang): array
    {
        $ar = $lang !== 'en';
        $t = fn (string $arText, string $enText) => $ar ? $arText : $enText;
        $item = fn (string $nameAr, string $nameEn, float $price, ?string $photo, string $noteAr = '', string $noteEn = '', array $badges = [], bool $soldOut = false) => [
            'name' => $t($nameAr, $nameEn),
            'price' => $price,
            'note' => $t($noteAr, $noteEn) ?: null,
            'image' => $photo ? "lib:{$photo}" : null,
            'badges' => $badges,
            'sold_out' => $soldOut,
        ];

        return [
            'name' => $t('لذيذ', 'Lazeez'),
            'tagline' => $t('مشويات على الفحم، بيتزا من الفرن، وحلويات طازة كل يوم', 'Charcoal grills, oven pizza and fresh desserts every day'),
            'phone' => '+20 100 000 0000',
            'whatsapp' => '+20 100 000 0000',
            'address' => $t('١٢ شارع التسعين، التجمع الخامس، القاهرة', '12 90th Street, New Cairo'),
            'hours' => $t('يوميًا من ١٢ الظهر لـ ٢ بالليل', 'Daily 12 pm – 2 am'),
            'currency' => 'EGP',
            'color' => '#c2410c',
            'theme' => 'light',
            'lang' => $ar ? 'ar' : 'en',
            'cover' => 'lib:cover-plates',
            'logo' => null,
            'sections' => [
                ['title' => $t('المشويات', 'Grills'), 'items' => [
                    $item('مشويات مشكلة', 'Mixed grill', 420, 'mixed-grill', 'كفتة وكباب وشيش طاووق مع عيش وطحينة وسلطة', 'Kofta, kebab and shish tawook with bread, tahini and salad', ['popular']),
                    $item('كباب على الفحم', 'Charcoal kebab', 360, 'kebab', 'ربع كيلو لحم ضاني متبل', '250 g of marinated lamb', ['popular']),
                    $item('ستيك ريب آي', 'Rib-eye steak', 520, 'steak-fries', 'مع بطاطس بالأعشاب وصوص الفلفل', 'With herb fries and pepper sauce', ['new']),
                    $item('فراخ مقرمشة', 'Crispy chicken', 210, 'fried-chicken', '٤ قطع مع كول سلو وصوص الثوم', '4 pieces with coleslaw and garlic sauce', ['spicy']),
                ]],
                ['title' => $t('السندوتشات والبرجر', 'Sandwiches & burgers'), 'items' => [
                    $item('شاورما فراخ', 'Chicken shawarma', 120, 'shawarma', 'عيش صاج، ثومية، بطاطس ومخلل', 'Saj bread, garlic sauce, fries and pickles', ['popular']),
                    $item('برجر كلاسيك', 'Classic burger', 185, 'classic-burger', '٢٠٠ جرام لحم بلدي مع شيدر', '200 g beef patty with cheddar'),
                    $item('سماش برجر دبل', 'Double smash burger', 230, 'smash-burger', 'قطعتين سماش مع صوص البيت', 'Two smashed patties with house sauce', ['new']),
                    $item('وجبة برجر', 'Burger meal', 240, 'burger-fries', 'برجر وبطاطس ومشروب', 'Burger, fries and a drink'),
                ]],
                ['title' => $t('البيتزا والمكرونة', 'Pizza & pasta'), 'items' => [
                    $item('بيتزا مارجريتا', 'Margherita pizza', 190, 'pizza-margherita', 'صوص طماطم وموتزاريلا وريحان', 'Tomato, mozzarella and basil', ['veg']),
                    $item('بيتزا فراخ باربكيو', 'BBQ chicken pizza', 240, 'pizza-chicken', 'فراخ مشوية وبصل وصوص باربكيو', 'Grilled chicken, onion and BBQ sauce'),
                    $item('بيني أرابياتا', 'Penne arrabbiata', 165, 'penne-arrabbiata', 'صوص طماطم حار مع بارميزان', 'Spicy tomato sauce with parmesan', ['spicy', 'veg']),
                ]],
                ['title' => $t('السلطات والبول', 'Salads & bowls'), 'items' => [
                    $item('بول فراخ', 'Chicken bowl', 175, 'chicken-bowl', 'فراخ مشوية وخضار طازة وبيض', 'Grilled chicken, fresh veg and egg'),
                    $item('بول خضار', 'Veggie bowl', 150, 'veggie-bowl', 'حمص وأفوكادو وخضار موسمية', 'Chickpeas, avocado and seasonal veg', ['veg']),
                    $item('سلطة خضراء', 'Green salad', 95, 'green-salad', 'خس وجرجير وخيار مع صوص الليمون', 'Lettuce, rocket and cucumber with lemon dressing', ['veg']),
                ]],
                ['title' => $t('الحلويات', 'Desserts'), 'items' => [
                    $item('تيراميسو', 'Tiramisu', 120, 'tiramisu', 'وصفة إيطالية بالماسكاربوني', 'Italian recipe with mascarpone', ['popular']),
                    $item('بان كيك بالعسل', 'Honey pancakes', 110, 'pancakes', '٣ قطع مع عسل وزبدة', 'Three pancakes with honey and butter'),
                    $item('دونتس', 'Donuts', 60, 'donuts', '', '', [], true),
                ]],
                ['title' => $t('المشروبات', 'Drinks'), 'items' => [
                    $item('موكتيل فواكه', 'Fruit mocktail', 85, 'mocktails', 'مانجو وفراولة وليمون نعناع', 'Mango, strawberry and mint lemonade', ['new']),
                    $item('لاتيه', 'Latte', 70, 'latte-cheers'),
                    $item('قهوة تركي', 'Turkish coffee', 40, null, 'سادة، مظبوط، أو زيادة', 'Plain, medium or sweet'),
                    $item('مياه معدنية', 'Water', 15, null),
                ]],
            ],
        ];
    }
}
