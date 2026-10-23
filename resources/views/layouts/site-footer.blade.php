{{-- One footer for every public page, in the language of the page. --}}
@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
    $columns = [
        [
            'title' => $t('المنتجات', 'Products'),
            'links' => [
                [$t('برامج سطح المكتب', 'Desktop programs'), route('services.index').'#programs'],
                [$t('أنظمة إدارة الأعمال', 'Business systems'), route('services.index').'#systems'],
                [$t('خدمات مواقع وتسويق', 'Websites and marketing services'), route('services.index').'#services'],
                [$t('احسب تكلفة مشروعك', 'Estimate your project'), '/estimator'],
            ],
        ],
        [
            'title' => $t('موسوفت', 'Musoftware'),
            'links' => [
                [$t('من نحن', 'About us'), '/about/mahmoud-amin'],
                [$t('أعمالنا', 'Our work'), '/portfolio'],
                [$t('كيف نعمل', 'How we work'), '/#how-it-works'],
                [$t('المقارنات والمقالات', 'Comparisons and articles'), '/compare/laravel-vs-nodejs'],
            ],
        ],
        [
            'title' => $t('تواصل معنا', 'Contact'),
            'links' => [
                [$t('واتساب مباشر', 'WhatsApp direct'), 'https://wa.me/201015218548'],
                ['admin@musoftwares.com', 'mailto:admin@musoftwares.com'],
                [$t('صفحة التواصل', 'Contact page'), '/company/contact'],
                [$t('ابدأ مشروع مخصص', 'Start a custom project'), '/start-project'],
            ],
        ],
        [
            'title' => $t('قانوني', 'Legal'),
            'links' => [
                [$t('سياسة الخصوصية', 'Privacy policy'), '/privacy-policy'],
                [$t('شروط الخدمة', 'Terms of service'), '/terms-of-service'],
            ],
        ],
    ];
@endphp
<footer class="w-full bg-[#f5f5f7] border-t border-black/[0.08] pt-12 pb-10 px-6 sm:px-12 text-[#515154] text-[14px]">
    <div class="max-w-[1100px] mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <div class="lg:col-span-4">
                <p class="text-[18px] font-bold text-[#1d1d1f]">Musoftware</p>
                <p class="mt-3 leading-[1.9]">
                    {{ $t('استوديو برمجيات مستقل في السويس، مصر. يديره ويبرمجه محمود أمين. أنظمة وبرامج جاهزة بسعر معلن، وبتستلم الكود.', 'An independent software studio in Suez, Egypt, run and programmed by Mahmoud Amin. Ready-made systems and programs with public prices, and you receive the code.') }}
                </p>
            </div>
            <div class="lg:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach ($columns as $column)
                    <div>
                        <h3 class="text-[14px] font-semibold text-[#1d1d1f]">{{ $column['title'] }}</h3>
                        <ul class="mt-3 space-y-2.5">
                            @foreach ($column['links'] as [$label, $url])
                                <li><a href="{{ $url }}" @if (str_starts_with($url, 'https://wa.me')) target="_blank" rel="noopener noreferrer" @endif class="hover:text-[#0071e3] transition-colors">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-black/10 flex flex-col md:flex-row items-center justify-between gap-3 text-[13px] text-[#86868b]">
            <p>&copy; {{ date('Y') }} Musoftware. {{ $t('جميع الحقوق محفوظة.', 'All rights reserved.') }} {{ $t('صُنع في السويس، مصر.', 'Made in Suez, Egypt.') }}</p>
            <p><a href="{{ request()->fullUrlWithQuery(['lang' => $isAr ? 'en' : 'ar']) }}" class="hover:text-[#1d1d1f] transition-colors">{{ $isAr ? 'English' : 'العربية' }}</a></p>
        </div>
    </div>
</footer>
