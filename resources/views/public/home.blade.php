@extends('layouts.public')

@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
    $whatsapp = 'https://wa.me/201015218548?text='.rawurlencode($t('مرحبا، عايز أستفسر عن خدمات موسوفت', 'Hello, I would like to ask about Musoftware services'));

    $businessType = [
        'pharmacy-system' => $t('صيدلية', 'Pharmacy'),
        'restaurant-pos-system' => $t('مطعم أو كافيه', 'Restaurant'),
        'dental-clinic-system' => $t('عيادة أسنان', 'Dental clinic'),
        'shop-manager-pro' => $t('محل صغير', 'Small shop'),
        'maps-leads-pro' => $t('تسويق وعملاء', 'Marketing'),
        'company-website-design' => $t('موقع شركة', 'Company site'),
    ];

    $facts = [
        [$t('سعر معلن', 'Public price'), $t('تشوفه قبل ما تدفع', 'You see it before you pay')],
        [$t('الكود بتاعك', 'The code is yours'), $t('بتستلمه وبتملكه', 'You receive it and own it')],
        [$t('دفع آمن', 'Safe payment'), $t('عن طريق Kashier', 'Through Kashier')],
        [$t('مبرمج حقيقي', 'A real programmer'), $t('بتكلمه على واتساب', 'You talk to him on WhatsApp')],
    ];

    $groups = [
        ['key' => 'programs', 'title' => $t('برامج سطح المكتب', 'Desktop programs'), 'body' => $t('للتسويق وجمع العملاء وإدارة المحل.', 'For marketing, leads and running a shop.')],
        ['key' => 'systems', 'title' => $t('أنظمة إدارة الأعمال', 'Business systems'), 'body' => $t('كاملة وجاهزة، وبتملك الكود.', 'Complete and ready, and you own the code.')],
        ['key' => 'services', 'title' => $t('خدمات بنفذها لك', 'Services we do for you'), 'body' => $t('مواقع ومتاجر وهوية وإعلانات.', 'Websites, stores, branding and ads.')],
    ];

    $steps = [
        [$t('اختار', 'Choose'), $t('شوف الصور والسعر.', 'See the pictures and the price.')],
        [$t('ادفع', 'Pay'), $t('أونلاين من غير حساب.', 'Online, no account.')],
        [$t('كلمنا', 'Message us'), $t('ابعت رقم طلبك على واتساب.', 'Send your order number on WhatsApp.')],
        [$t('استلم', 'Receive'), $t('وشغّل من أول يوم.', 'And start from day one.')],
    ];

    $faq = [
        [$t('هل بملك الكود فعلًا؟', 'Do I really own the code?'), $t('أيوه. في الأنظمة بتستلم الكود كامل، وفي الخدمات المخصصة الملكية بتنتقل لك بعد الدفع الكامل.', 'Yes. With the systems you receive the full code, and in custom services ownership moves to you after full payment.')],
        [$t('الدفع آمن؟', 'Is paying safe?'), $t('بتدفع من صفحة Kashier، وإحنا مش بنشوف بيانات كارتك.', 'You pay on the Kashier page and we never see your card details.')],
        [$t('لو محتاج تعديلات؟', 'What if I need changes?'), $t('باقة التعديلات في الأنظمة بتشمل أي تعديل برمجي يحتاجه شغلك.', 'The changes plan for systems includes any programming change your business needs.')],
        [$t('لو حصلت مشكلة بعد الاستلام؟', 'What if something goes wrong after delivery?'), $t('ابعت رقم طلبك على واتساب ونرد عليك.', 'Send your order number on WhatsApp and we reply.')],
    ];
@endphp

@section('content')
<div class="w-full overflow-x-clip bg-white text-[#1d1d1f] antialiased">

    {{-- 1. Hero --}}
    <section class="px-4 sm:px-8 pt-12 sm:pt-16 bg-white">
        <div class="max-w-[1180px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            <div class="lg:col-span-6 text-start">
                <p class="text-[14px] font-semibold text-[#0071e3] mb-4">{{ $t('موسوفت، استوديو برمجيات مصري في السويس', 'Musoftware, an Egyptian software studio in Suez') }}</p>
                <h1 class="text-[36px] sm:text-[48px] lg:text-[54px] font-bold tracking-tight leading-[1.2]">
                    {{ $t('برامج وأنظمة جاهزة لشغلك', 'Ready-made programs and systems for your business') }}
                </h1>
                <p class="mt-5 text-[18px] sm:text-[20px] leading-[1.7] text-[#515154] max-w-[520px]">
                    {{ $t('بسعر معلن من أول يوم. بتستلم الكود وبتملكه.', 'With a price you see on day one. You receive the code and own it.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('services.index') }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('شوف المنتجات والأسعار', 'See products and prices') }}</a>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-black/15 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
                </div>
            </div>

            @if ($heroShots->isNotEmpty())
                @php $mainShot = $heroShots->first(); $sideShots = $heroShots->slice(1); @endphp
                <div class="lg:col-span-6">
                    <a href="{{ route('website-services.show', $mainShot['product']->slug) }}" class="block rounded-xl border border-black/10 bg-white shadow-xl overflow-hidden" aria-label="{{ $mainShot['product']->localized('name') }}">
                        <div class="flex items-center gap-1.5 px-4 h-8 bg-[#f5f5f7] border-b border-black/10" aria-hidden="true">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span>
                        </div>
                        <img src="{{ $mainShot['image'] }}" alt="{{ $mainShot['product']->localized('name') }}" class="w-full aspect-[16/10] object-cover object-top bg-[#f5f5f7]" fetchpriority="high">
                    </a>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        @foreach ($sideShots as $shot)
                            <a href="{{ route('website-services.show', $shot['product']->slug) }}" class="block rounded-lg border border-black/10 bg-white shadow-md overflow-hidden" aria-label="{{ $shot['product']->localized('name') }}">
                                <img src="{{ $shot['image'] }}" alt="{{ $shot['product']->localized('name') }}" class="w-full aspect-[16/10] object-cover object-top bg-[#f5f5f7]" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="max-w-[1180px] mx-auto mt-12 sm:mt-16 grid grid-cols-1 sm:grid-cols-4 border-t border-black/10">
            @foreach ($facts as [$title, $line])
                <div class="py-5 sm:px-5 sm:border-e last:border-e-0 border-black/10">
                    <p class="text-[17px] font-bold">{{ $title }}</p>
                    <p class="mt-1 text-[14px] text-[#515154]">{{ $line }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 2. Pick your business and see the real product --}}
    @if ($featuredProducts->isNotEmpty())
        <section id="pick-your-business" class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
            <div class="max-w-[1180px] mx-auto" data-business-switcher>
                <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('اختار نشاطك وشوف المنتج', 'Pick your business and see the product') }}</h2>

                <div class="mt-8 grid grid-cols-3 lg:grid-cols-6 gap-3" role="tablist" aria-label="{{ $t('نوع النشاط', 'Type of business') }}">
                    @foreach ($featuredProducts as $i => $product)
                        <button type="button" role="tab" id="biz-tab-{{ $i }}" aria-controls="biz-panel-{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" data-index="{{ $i }}"
                                class="biz-tab rounded-xl border bg-white border-black/10 px-3 py-4 flex flex-col items-center gap-2 transition-colors hover:border-[#0071e3] aria-selected:border-[#0071e3] aria-selected:bg-[#eef5fe]">
                            <img src="{{ $product->imageUrl() }}" alt="" class="w-9 h-9 object-contain">
                            <span class="text-[14px] font-semibold text-center leading-snug">{{ $businessType[$product->slug] ?? $product->localized('name') }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-8 max-w-[860px] mx-auto">
                    @foreach ($featuredProducts as $i => $product)
                        @php $shots = $product->galleryUrls(); @endphp
                        <div id="biz-panel-{{ $i }}" role="tabpanel" aria-labelledby="biz-tab-{{ $i }}" data-index="{{ $i }}" @if ($i !== 0) hidden @endif>
                            <div class="rounded-xl border border-black/10 bg-white shadow-lg overflow-hidden">
                                <div class="flex items-center gap-1.5 px-4 h-8 bg-[#f5f5f7] border-b border-black/10" aria-hidden="true">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span>
                                </div>
                                @if (! empty($shots))
                                    <img src="{{ $shots[min(1, count($shots) - 1)] }}" alt="{{ $product->localized('name') }}" class="w-full aspect-[16/10] object-cover object-top bg-[#f5f5f7]" loading="lazy">
                                @endif
                            </div>
                            <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p class="text-[18px] font-bold">{{ $product->localized('name') }}</p>
                                    <p class="mt-1 text-[16px] font-semibold">@if ($product->hasPlans())<span class="text-[13px] font-normal text-[#86868b]">{{ __('software.from') }}</span> @endif{{ $product->money($product->startingPrice()) }} {{ $product->saleCurrency() }} <span class="text-[13px] font-normal text-[#86868b]">{{ $product->periodLabel() }}</span></p>
                                </div>
                                <a href="{{ route('website-services.show', $product->slug) }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex shrink-0 items-center justify-center px-6 py-3 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('افتح صفحة المنتج', 'Open the product page') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <script>
            (function () {
                var root = document.querySelector('[data-business-switcher]');
                if (!root) return;
                var tabs = root.querySelectorAll('.biz-tab');
                var panels = root.querySelectorAll('[role="tabpanel"]');
                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        var index = tab.getAttribute('data-index');
                        tabs.forEach(function (other) { other.setAttribute('aria-selected', other === tab ? 'true' : 'false'); });
                        panels.forEach(function (panel) { panel.hidden = panel.getAttribute('data-index') !== index; });
                    });
                });
            })();
        </script>
    @endif

    {{-- 3. What we offer --}}
    <section id="services-overview" class="px-4 sm:px-8 py-14 sm:py-20 bg-white">
        <div class="max-w-[1180px] mx-auto">
            <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('إيه اللي بنقدمه؟', 'What do we offer?') }}</h2>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($groups as $group)
                    <a href="{{ route('services.index') }}#{{ $group['key'] }}" class="rounded-2xl border border-black/10 p-7 hover:shadow-md transition-shadow flex flex-col">
                        <h3 class="text-[21px] font-bold">{{ $group['title'] }}</h3>
                        <p class="mt-2 text-[15px] text-[#515154] flex-1">{{ $group['body'] }}</p>
                        <div class="mt-6 flex items-center justify-between">
                            <span class="text-[15px] font-semibold">@if (! empty($storeFrom[$group['key']])){{ $t('من', 'From') }} {{ number_format($storeFrom[$group['key']]) }} {{ $t('جنيه', 'EGP') }}@endif</span>
                            <span class="text-[14px] font-semibold text-[#0071e3]">{{ $storeCounts[$group['key']] }} {{ $t('منتج', 'items') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. How it works --}}
    <section id="how-it-works" class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
        <div class="max-w-[1180px] mx-auto">
            <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('إزاي بنشتغل؟', 'How do we work?') }}</h2>
            <ol class="mt-10 grid grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($steps as $i => [$title, $line])
                    <li class="rounded-2xl bg-white border border-black/5 p-6">
                        <span class="text-[28px] font-bold text-[#0071e3] leading-none">{{ $i + 1 }}</span>
                        <h3 class="mt-3 text-[18px] font-bold">{{ $title }}</h3>
                        <p class="mt-1 text-[14px] text-[#515154]">{{ $line }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 5. Founder, short --}}
    <section class="px-4 sm:px-8 py-12 sm:py-16 bg-white">
        <div class="max-w-[820px] mx-auto text-center">
            <p class="text-[20px] sm:text-[24px] leading-[1.7] font-semibold">{{ $t('محمود أمين، مهندس برمجيات من السويس. بيبني أنظمة من أكتر من 10 سنين، وهو اللي بيكلمك وهو اللي بيبرمج.', 'Mahmoud Amin, a software engineer from Suez. He has built systems for more than 10 years, and he is the one who talks to you and programs.') }}</p>
            <a href="/about/mahmoud-amin" class="mt-4 inline-flex text-[15px] font-semibold text-[#0071e3] hover:underline">{{ $t('اقرا عنه', 'Read about him') }}</a>
        </div>
    </section>

    {{-- 6. Questions --}}
    <section class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
        <div class="max-w-[820px] mx-auto">
            <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('أسئلة سريعة', 'Quick questions') }}</h2>
            <div class="mt-8 space-y-3">
                @foreach ($faq as [$question, $answer])
                    <details class="group rounded-xl border border-black/10 bg-white px-5 py-4 open:border-[#0071e3]">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-semibold [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <span class="shrink-0 w-7 h-7 rounded-full bg-[#f5f5f7] flex items-center justify-center text-[#0071e3] text-[18px] leading-none group-open:hidden" aria-hidden="true">+</span>
                            <span class="shrink-0 w-7 h-7 rounded-full bg-[#eef5fe] items-center justify-center text-[#0071e3] text-[18px] leading-none hidden group-open:flex" aria-hidden="true">&minus;</span>
                        </summary>
                        <p class="mt-3 text-[15px] leading-[1.8] text-[#515154]">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 7. Next step --}}
    <section id="cta-hire" class="px-4 sm:px-8 py-16 sm:py-20 bg-[#1d1d1f] text-center text-white">
        <div class="max-w-[700px] mx-auto">
            <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight leading-tight">{{ $t('جاهز تبدأ؟', 'Ready to start?') }}</h2>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('services.index') }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('تصفح المنتجات', 'Browse products') }}</a>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-white/30 text-[15px] font-semibold text-white hover:bg-white/10 transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
            </div>
        </div>
    </section>
</div>
@endsection
