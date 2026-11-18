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
        [$t('بنعمله معاك', 'Built with you'), $t('وبناخد رأيك في كل خطوة', 'With your input at every step')],
        [$t('سعر واضح', 'Clear price'), $t('تعرفه قبل ما تدفع', 'You know it before you pay')],
        [$t('دفع آمن', 'Safe payment'), $t('عن طريق Kashier', 'Through Kashier')],
        [$t('بتكلم المبرمج نفسه', 'You talk to the programmer'), $t('على واتساب', 'On WhatsApp')],
    ];

    $groups = [
        ['key' => 'programs', 'title' => $t('برامج للكمبيوتر', 'Computer programs'), 'body' => $t('للتسويق وجمع العملاء وإدارة المحل.', 'For marketing, finding customers and running a shop.')],
        ['key' => 'systems', 'title' => $t('أنظمة لإدارة شغلك', 'Systems to run your business'), 'body' => $t('جاهزة تشتغل عليها على طول، وبتبقى ملكك.', 'Ready to use right away, and they become yours.')],
        ['key' => 'services', 'title' => $t('شغل بنعمله لك', 'Work we do for you'), 'body' => $t('مواقع ومتاجر ولوجو وإعلانات.', 'Websites, online stores, logos and ads.')],
    ];

    // Hero pieces cut from hero_tech_stack_3d_transparent.png: place and size in % of the square,
    // listed clockwise from the top so they land one after another around the logo.
    $heroLogo = ['x' => 32.71, 'y' => 29.59, 'w' => 33.79, 'h' => 43.16];
    $heroIcons = array_map(function (array $icon) use ($heroLogo) {
        $toLogo = fn (float $start, float $size, float $logoStart, float $logoSize) => round(($logoStart + $logoSize / 2) - ($start + $size / 2), 2);

        return $icon + [
            'dx' => $toLogo($icon['x'], $icon['w'], $heroLogo['x'], $heroLogo['w']),
            'dy' => $toLogo($icon['y'], $icon['h'], $heroLogo['y'], $heroLogo['h']),
        ];
    }, [
        ['name' => 'node', 'x' => 38.77, 'y' => 15.43, 'w' => 9.77, 'h' => 10.45],
        ['name' => 'python', 'x' => 52.93, 'y' => 16.21, 'w' => 9.08, 'h' => 9.28],
        ['name' => 'java', 'x' => 66.21, 'y' => 20.51, 'w' => 7.32, 'h' => 10.25],
        ['name' => 'cpp', 'x' => 73.54, 'y' => 31.25, 'w' => 9.57, 'h' => 11.23],
        ['name' => 'go', 'x' => 78.52, 'y' => 46.39, 'w' => 9.08, 'h' => 11.62],
        ['name' => 'vue', 'x' => 72.75, 'y' => 61.13, 'w' => 10.55, 'h' => 10.06],
        ['name' => 'laravel', 'x' => 61.52, 'y' => 69.53, 'w' => 12.01, 'h' => 10.64],
        ['name' => 'd', 'x' => 41.89, 'y' => 73.83, 'w' => 14.65, 'h' => 12.79],
        ['name' => 'angular', 'x' => 29.69, 'y' => 69.92, 'w' => 11.62, 'h' => 15.92],
        ['name' => 'swift', 'x' => 17.29, 'y' => 59.67, 'w' => 10.45, 'h' => 10.74],
        ['name' => 'dark', 'x' => 12.21, 'y' => 45.51, 'w' => 9.57, 'h' => 9.08],
        ['name' => 'ruby', 'x' => 13.96, 'y' => 31.05, 'w' => 11.04, 'h' => 9.77],
        ['name' => 'react', 'x' => 24.12, 'y' => 19.34, 'w' => 11.33, 'h' => 10.45],
    ]);

    $steps = [
        [$t('احكيلنا', 'Tell us'), $t('قولنا فكرتك وعايز توصل لإيه.', 'Tell us your idea and your goal.')],
        [$t('اعرف السعر', 'Get the price'), $t('بنقولك هيتكلف كام قبل ما نبدأ.', 'We tell you the cost before we start.')],
        [$t('نبنيه معاك', 'We build it with you'), $t('خطوة بخطوة، وإنت متابع.', 'Step by step, with you following along.')],
        [$t('نكبّره', 'We help it grow'), $t('بنحاول قدر الإمكان إن مشروعك ينجح.', 'We do everything we can to help it succeed.')],
    ];

    $faq = [
        [$t('هل البرنامج هيبقى ملكي؟', 'Will the program really be mine?'), $t('السعر بتدفعه مقابل البرنامج نفسه، مش الكود. في المواقع وأنظمة الويب الكود بييجي معاك. في تطبيقات الموبايل والكمبيوتر الكود مش بيتسلم، ولو عايزه سعره 10 أضعاف سعر البرنامج.', 'The price pays for the program itself, not its code. Websites and web systems come with their code. Mobile and desktop apps do not, and if you want an app\'s code it costs 10 times the program price.')],
        [$t('الدفع آمن؟', 'Is paying safe?'), $t('بتدفع من صفحة Kashier، وإحنا مش بنشوف بيانات كارتك.', 'You pay on the Kashier page and we never see your card details.')],
        [$t('لو عايز أغيّر حاجة في البرنامج؟', 'What if I want something changed?'), $t('باقة التعديلات في الأنظمة بتشمل أي تعديل يحتاجه شغلك.', 'The changes plan for systems includes any change your business needs.')],
        [$t('لو حصلت مشكلة بعد ما استلمت؟', 'What if something goes wrong after I receive it?'), $t('ابعت رقم طلبك على واتساب ونرد عليك.', 'Send your order number on WhatsApp and we reply.')],
    ];
@endphp

@section('content')
<div class="w-full overflow-x-clip bg-white text-[#1d1d1f] antialiased">

    {{-- 1. Hero --}}
    <section class="px-4 sm:px-8 pt-12 sm:pt-16 bg-white">
        <div class="max-w-[1180px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            <div class="lg:col-span-6 text-start">
                <p class="text-[14px] font-semibold text-[#0071e3] mb-4">{{ $t('موسوفت، شركة برمجة مصرية في السويس', 'Musoftware, an Egyptian software company in Suez') }}</p>
                <h1 class="text-[36px] sm:text-[48px] lg:text-[54px] font-bold tracking-tight leading-[1.2]">
                    {{ $t('هنعملك أي برنامج أو موقع أو SaaS أو RaaS', 'We build you any program, website, SaaS or RaaS') }}
                </h1>
                <p class="mt-5 text-[18px] sm:text-[20px] leading-[1.7] text-[#515154] max-w-[520px]">
                    {{ $t('بنبنيه معاك خطوة بخطوة، وبنحاول قدر الإمكان إن مشروعك يكبر وينجح.', 'We build it with you step by step, and do everything we can to help your project grow and succeed.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap gap-3">
                    <a href="/start-project" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('ابدأ مشروعك', 'Start your project') }}</a>
                    <a href="{{ route('estimator') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-black/15 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">{{ $t('احسب مشروعك هيتكلف كام', 'Work out what your project costs') }}</a>
                    @if (config('services.anthropic.key'))
                        <button type="button" data-open-assistant class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-black/15 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors cursor-pointer">{{ $t('اسأل المساعد', 'Ask the assistant') }}</button>
                    @else
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-black/15 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
                    @endif
                </div>
                <p class="mt-4 text-[15px]"><a href="{{ route('services.index') }}" class="font-semibold text-[#0071e3] hover:underline">{{ $t('أو شوف البرامج الجاهزة وأسعارها', 'Or see our ready-made programs and prices') }}</a></p>
            </div>

            <div class="lg:col-span-6 flex items-center justify-center">
                <div id="hero-orbit" class="hero-orbit relative w-full max-w-[540px]" role="img" aria-label="{{ $t('موسوفت وأدوات البرمجة اللي بنشتغل بيها', 'Musoftware and the tools we build with') }}">
                    <img src="{{ asset('images/hero/orbit/logo.webp') }}" alt="" fetchpriority="high"
                         class="hero-orbit-logo z-10" style="left: {{ $heroLogo['x'] }}%; top: {{ $heroLogo['y'] }}%; width: {{ $heroLogo['w'] }}%; --depth: -0.8;">
                    @foreach ($heroIcons as $i => $icon)
                        <img src="{{ asset('images/hero/orbit/'.$icon['name'].'.webp') }}" alt="" loading="eager" decoding="async"
                             class="hero-orbit-icon"
                             style="left: {{ $icon['x'] }}%; top: {{ $icon['y'] }}%; width: {{ $icon['w'] }}%; --i: {{ $i }}; --dx: {{ $icon['dx'] }}cqw; --dy: {{ $icon['dy'] }}cqw; --depth: {{ 1.6 + ($i % 3) * 0.8 }};">
                    @endforeach
                </div>
                <script>
                    (function () {
                        var orbit = document.getElementById('hero-orbit');
                        try {
                            if (localStorage.getItem('hero-orbit-seen')) orbit.classList.add('is-seen');
                            else localStorage.setItem('hero-orbit-seen', '1');
                        } catch (e) {}

                        // Mouse depth: ease the pieces toward the pointer, and back to rest when it leaves.
                        var canHover = matchMedia('(hover: hover) and (pointer: fine)').matches;
                        var calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
                        if (!canHover || calm) return;

                        var target = { x: 0, y: 0 }, now = { x: 0, y: 0 }, frame = 0;
                        var step = function () {
                            now.x += (target.x - now.x) * 0.08;
                            now.y += (target.y - now.y) * 0.08;
                            orbit.style.setProperty('--mx', now.x.toFixed(3));
                            orbit.style.setProperty('--my', now.y.toFixed(3));
                            var settled = Math.abs(target.x - now.x) < 0.002 && Math.abs(target.y - now.y) < 0.002;
                            frame = settled ? 0 : requestAnimationFrame(step);
                        };
                        var follow = function (x, y) {
                            target.x = x; target.y = y;
                            if (!frame) frame = requestAnimationFrame(step);
                        };
                        orbit.addEventListener('pointermove', function (e) {
                            var box = orbit.getBoundingClientRect();
                            follow((e.clientX - box.left) / box.width * 2 - 1, (e.clientY - box.top) / box.height * 2 - 1);
                        });
                        orbit.addEventListener('pointerleave', function () { follow(0, 0); });
                    })();
                </script>
            </div>
        </div>

        <div class="max-w-[1180px] mx-auto mt-12 sm:mt-16 grid grid-cols-1 sm:grid-cols-4 border-t border-black/10">
            @foreach ($facts as [$factTitle, $factLine])
                <div class="py-5 sm:px-5 sm:border-e last:border-e-0 border-black/10">
                    <p class="text-[17px] font-bold">{{ $factTitle }}</p>
                    <p class="mt-1 text-[14px] text-[#515154]">{{ $factLine }}</p>
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
                        @php $shots = $product->galleryUrlsFor(); @endphp
                        <div id="biz-panel-{{ $i }}" role="tabpanel" aria-labelledby="biz-tab-{{ $i }}" data-index="{{ $i }}" @if ($i !== 0) hidden @endif>
                            <div class="rounded-xl border border-black/10 bg-white shadow-lg overflow-hidden">
                                <div class="flex items-center gap-1.5 px-4 h-8 bg-[#f5f5f7] border-b border-black/10" aria-hidden="true">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span>
                                </div>
                                @if (! empty($shots))
                                    <img src="{{ $shots[0] }}" alt="{{ $product->localized('name') }}" class="w-full aspect-[16/10] object-cover object-top bg-[#f5f5f7]" loading="lazy">
                                @endif
                            </div>
                            <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p class="text-[18px] font-bold">{{ $product->localized('name') }}</p>
                                    <p class="mt-1 text-[16px] font-semibold">@if ($product->hasPlans())<span class="text-[13px] font-normal text-[#86868b]">{{ __('software.from') }}</span> @endif{{ $product->money($product->startingPrice()) }} {{ $product->saleCurrency() }} <span class="text-[13px] font-normal text-[#86868b]">{{ $product->approxUsdLabel($product->startingPrice()) }} {{ $product->periodLabel() }}</span></p>
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
            <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('تبدأ إزاي؟', 'How do you start?') }}</h2>
            <ol class="mt-10 grid grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($steps as $i => [$stepTitle, $stepLine])
                    <li class="rounded-2xl bg-white border border-black/5 p-6">
                        <span class="text-[28px] font-bold text-[#0071e3] leading-none">{{ $i + 1 }}</span>
                        <h3 class="mt-3 text-[18px] font-bold">{{ $stepTitle }}</h3>
                        <p class="mt-1 text-[14px] text-[#515154]">{{ $stepLine }}</p>
                    </li>
                @endforeach
            </ol>
            <p class="mt-8 text-center"><a href="/how-we-work{{ app()->getLocale() === 'ar' ? '?lang=ar' : '' }}" class="text-sm font-semibold text-[#0071e3] hover:underline">{{ $t('الأسعار والدعم بعد التسليم بالتفصيل', 'Prices and support after delivery in detail') }}</a></p>
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
                <a href="/start-project" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('ابدأ مشروعك', 'Start your project') }}</a>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-white/30 text-[15px] font-semibold text-white hover:bg-white/10 transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
            </div>
        </div>
    </section>
</div>
@endsection
