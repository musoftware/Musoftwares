<?php

return [
    'name' => env('SHOP_NAME', 'دكّانة'),
    'name_en' => env('SHOP_NAME_EN', 'Dokkana'),
    'tagline' => env('SHOP_TAGLINE', 'تسوق أونلاين وادفع عند الاستلام'),
    'announcement' => env('SHOP_ANNOUNCEMENT', 'الدفع عند الاستلام والتوصيل لمعظم محافظات مصر'),
    'currency' => 'EGP',
    'currency_symbol' => 'ج.م',
    'shipping_cost' => (float) env('SHOP_SHIPPING_COST', 0),
    'twitter_site' => env('SHOP_TWITTER_SITE', '@MusoftwareUno'),
    'per_page' => 24,
    'nav_items' => 5,
    'max_quantity' => 20,

    'governorates' => [
        'القاهرة', 'الجيزة', 'الإسكندرية', 'القليوبية', 'الدقهلية', 'الشرقية', 'الغربية',
        'المنوفية', 'البحيرة', 'كفر الشيخ', 'دمياط', 'بورسعيد', 'الإسماعيلية', 'السويس',
        'الفيوم', 'بني سويف', 'المنيا', 'أسيوط', 'سوهاج', 'قنا', 'الأقصر', 'أسوان',
        'البحر الأحمر', 'الوادي الجديد', 'مطروح', 'شمال سيناء', 'جنوب سيناء',
    ],

    'angazny' => [
        'base_url' => 'https://api.angazny.com',
        'token' => env('ANGAZNY_TOKEN'),
        // Stays off until one real test order has been checked in Angazny.
        'forward_orders' => (bool) env('ANGAZNY_FORWARD_ORDERS', false),
        'image_host' => 'angazny.fra1.cdn.digitaloceanspaces.com',
        'depot_id' => 1,
        'shipping' => 80,
        'max_profit_ratio' => 0.30,
        'min_profit' => 50,
        'price_ending' => 9,
        'drive_max_images' => 40,

        'brands' => [
            'balenciaga', 'nike', 'adidas', 'puma', 'zara', 'gucci', 'dior', 'louis vuitton', 'hermes', 'lacoste',
            'ralph lauren', 'tommy hilfiger', 'calvin klein', 'north face', 'new balance', 'skechers', 'converse',
            'jordan', 'burberry', 'prada', 'versace', 'fendi', 'armani', 'levis', 'hugo boss', 'american eagle',
            'بالنسياجا', 'بلنسياجا', 'نايك', 'نايكي', 'اديداس', 'أديداس', 'زارا', 'جوتشي', 'جوشي', 'ديور',
            'لويس فيتون', 'هيرميس', 'بوما', 'لاكوست', 'رالف لورين', 'تومي', 'كالفن', 'جوردن', 'بربري', 'برادا',
            'فيرساتشي', 'فندي', 'ارماني', 'أرماني', 'ليفايز',
            'diesel', 'ديزل', 'fred perry', 'فريد بيري', 'under armour', 'اندر ارمور', 'أندر آرمور', 'امريكان ايجل', 'أمريكان ايجل',
            'philip plain', 'philipp plein', 'فيليب بلين', 'casio', 'كاسيو', 'silver crest', 'silvercrest', 'سيلفر كرست', 'stwd', 'الأهلي', 'الزمالك',
        ],

        // Products whose name is clean but whose photos show a brand or another store's sign.
        'blocked_ids' => [2884, 2889, 2976, 3050],

        'category_names' => [
            'Men Shoes' => 'أحذية رجالي', 'Women Shoes' => 'أحذية حريمي', 'Home and Office' => 'البيت والمكتب',
            'Health Care & Cosmetics' => 'صحة وعناية', 'Cosmetics' => 'تجميل', 'Mobile Accessories' => 'إكسسوارات موبايل',
            'Watch' => 'ساعات', 'Bags' => 'شنط', 'Fashion' => 'ملابس', 'Women Fashion' => 'ملابس حريمي', 'Gifts' => 'هدايا',
            'Vehicles Accessories' => 'إكسسوارات عربيات', 'Accessories' => 'إكسسوارات', 'Home & Kitchen' => 'البيت والمطبخ',
            'Baby Toys & Accessories' => 'أطفال ولعب', 'Glasses Department' => 'نظارات', 'تـصفيات' => 'تصفيات',
        ],

        // Angazny's own city ids. It does not deliver to any governorate left out of this list.
        'cities' => [
            'الإسكندرية' => 1, 'أسوان' => 2, 'أسيوط' => 3, 'البحيرة' => 4, 'بني سويف' => 5, 'القاهرة' => 6, 'الدقهلية' => 7,
            'دمياط' => 8, 'الفيوم' => 9, 'الغربية' => 10, 'الجيزة' => 11, 'الإسماعيلية' => 12, 'كفر الشيخ' => 13, 'المنيا' => 15,
            'المنوفية' => 16, 'بورسعيد' => 19, 'القليوبية' => 20, 'قنا' => 21, 'البحر الأحمر' => 22, 'الشرقية' => 23,
            'سوهاج' => 24, 'السويس' => 26, 'الأقصر' => 27,
        ],
    ],
];
