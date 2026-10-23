@extends('layouts.public')

@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
    $whatsapp = 'https://wa.me/201015218548?text='.rawurlencode($t('مرحبا، عايز أستفسر عن خدمات موسوفت', 'Hello, I would like to ask about Musoftware services'));

    $groups = [
        ['key' => 'programs', 'title' => $t('برامج سطح المكتب', 'Desktop programs'), 'body' => $t('برامج ويندوز بترخيص سنوي وتحديث تلقائي. للتسويق وجمع البيانات وإدارة المحل.', 'Windows programs with a yearly license and automatic updates. For marketing, data and running a shop.')],
        ['key' => 'systems', 'title' => $t('أنظمة إدارة الأعمال', 'Business systems'), 'body' => $t('أنظمة كاملة للصيدليات والمطاعم والعيادات والمراكز وغيرها. تشتري الكود وتملكه.', 'Complete systems for pharmacies, restaurants, clinics, centers and more. You buy the code and own it.')],
        ['key' => 'services', 'title' => $t('خدمات بنفذها لك', 'Services we do for you'), 'body' => $t('مواقع ومتاجر وهوية وإعلانات وشات بوت. نطاق وسعر واضحين قبل ما تدفع.', 'Websites, stores, branding, ads and chatbots. A clear scope and price before you pay.')],
    ];

    $trust = [
        ['title' => $t('السعر قدامك قبل ما تدفع', 'The price is in front of you before you pay'), 'body' => $t('كل منتج وخدمة ليها سعر معلن وباقات واضحة. مفيش "كلمنا نقولك".', 'Every product and service has a public price and clear plans. No "contact us for a quote".'), 'link' => route('services.index'), 'cta' => $t('شوف الأسعار', 'See prices')],
        ['title' => $t('بتستلم الكود وبتملكه', 'You receive the code and own it'), 'body' => $t('في الأنظمة بتستلم الكود المصدري كامل. وفي المشاريع المخصصة الملكية بتنتقل لك بعد الدفع الكامل.', 'With the systems you receive the full source code. In custom projects ownership moves to you after full payment.'), 'link' => route('services.index').'#systems', 'cta' => $t('شوف الأنظمة', 'See the systems')],
        ['title' => $t('بنقولك اللي النظام مش بيعمله', 'We tell you what the product does not do'), 'body' => $t('في كل صفحة قسم صريح بالحدود: إيه المتاح وإيه المش متاح. عشان تقرر وانت فاهم.', 'Every page has an honest section on the limits: what is included and what is not, so you decide knowing the facts.'), 'link' => route('website-services.show', 'pharmacy-system'), 'cta' => $t('شوف مثال', 'See an example')],
        ['title' => $t('صور وفيديو من المنتج نفسه', 'Pictures and video of the real product'), 'body' => $t('اللقطات والفيديوهات اتصورت من البرنامج وهو شغال، ببيانات تجريبية واضحة. مش تصاميم مزيفة.', 'The screenshots and videos were recorded from the running product, with clearly fake demo data. They are not mock-up designs.'), 'link' => route('website-services.show', 'maps-leads-pro'), 'cta' => $t('شوف مثال', 'See an example')],
        ['title' => $t('دفع أونلاين من غير حساب', 'Pay online without an account'), 'body' => $t('الدفع من صفحة بوابة Kashier المعتمدة مش من عندنا. وبعد الدفع بتكلمنا على واتساب برقم طلبك.', 'You pay on the Kashier gateway page, not on ours. After paying you message us on WhatsApp with your order number.'), 'link' => null, 'cta' => null],
        ['title' => $t('شخص حقيقي ورا الشغل', 'A real person behind the work'), 'body' => $t('محمود أمين، مهندس برمجيات في السويس. بتكلمه على واتساب مباشرة، من غير مركز اتصالات.', 'Mahmoud Amin, a software engineer in Suez. You talk to him on WhatsApp directly, with no call center.'), 'link' => '/about/mahmoud-amin', 'cta' => $t('اعرف أكتر', 'Learn more')],
    ];

    $steps = [
        ['title' => $t('اختار اللي يناسبك', 'Choose what fits you'), 'body' => $t('افتح الصفحة، اتفرج على الصور والفيديو، واقرا الحدود والأسعار.', 'Open the page, watch the video and read the limits and prices.')],
        ['title' => $t('ادفع أو كلمنا', 'Pay or message us'), 'body' => $t('الدفع أونلاين من غير حساب. ولو محتاج حاجة مخصصة كلمنا على واتساب.', 'Pay online with no account. If you need something custom, message us on WhatsApp.')],
        ['title' => $t('استلم وشغّل', 'Receive and start'), 'body' => $t('بنسلمك المنتج أو بنبدأ شغل الخدمة، ونتفق معاك على التفاصيل على واتساب.', 'We deliver the product or start the service work, and agree the details with you on WhatsApp.')],
        ['title' => $t('نكمل معاك', 'We stay with you'), 'body' => $t('أي سؤال أو مشكلة بعد الاستلام، ابعت رقم طلبك على واتساب ونرد عليك.', 'Any question or problem after delivery, send your order number on WhatsApp and we reply.')],
    ];

    $faq = [
        ['q' => $t('هل بملك الكود فعلًا؟', 'Do I really own the code?'), 'a' => $t('في الأنظمة بتستلم الكود المصدري كامل وبتركبه على استضافتك. في الخدمات المخصصة الملكية بتنتقل لك بعد الدفع الكامل. الأدوات والمكتبات الجاهزة اللي بنستخدمها ليها رخصها الخاصة.', 'With the systems you receive the full source code and install it on your hosting. In custom services ownership moves to you after full payment. Ready-made tools and libraries we use keep their own licenses.')],
        ['q' => $t('الدفع آمن؟', 'Is paying safe?'), 'a' => $t('بتدفع من صفحة بوابة Kashier، وإحنا مش بنشوف بيانات كارتك. بعد الدفع بتوصلك صفحة بالطلب ورقمه.', 'You pay on the Kashier gateway page and we never see your card details. After paying you get a page with your order and its number.')],
        ['q' => $t('لو محتاج تعديلات على النظام؟', 'What if I need changes to a system?'), 'a' => $t('باقة الأنظمة التانية بتشمل أي تعديل برمجي يحتاجه شغلك، وأي طلب برمجي للتعديل متاح. بنتفق معاك على القائمة على واتساب.', 'The second system plan includes any programming change your business needs, and any change request is available. We agree the list with you on WhatsApp.')],
        ['q' => $t('وإيه لو حصلت مشكلة بعد الاستلام؟', 'What if there is a problem after delivery?'), 'a' => $t('ابعت رقم طلبك على واتساب ونرد عليك. شروط الدعم بعد التسليم مكتوبة في صفحة كل خدمة.', 'Send your order number on WhatsApp and we reply. The support terms after delivery are written on each service page.')],
        ['q' => $t('الأنظمة متوافقة مع الفاتورة الإلكترونية؟', 'Do the systems support the Egyptian e-receipt?'), 'a' => $t('بعض الأنظمة فيها وحدة ربط مع إيصال مصلحة الضرائب الإلكتروني مبنية حسب مواصفاتها المنشورة، لكنها لسه ما اتجربتش على الشبكة الحقيقية ومش معتمدة منا. اسأل محاسبك لو نشاطك لازم يصدر إيصال إلكتروني. وكل صفحة بتوضح حالة النظام.', 'Some systems include a connector for the Egyptian Tax Authority e-receipt, built to its published specification. It has not been tested on the live network and is not certified by us. Ask your accountant whether your business must issue e-receipts. Each page states what that system has.')],
        ['q' => $t('بتشتغلوا مع عملاء برا مصر؟', 'Do you work with customers outside Egypt?'), 'a' => $t('أيوه، التسليم أونلاين. معظم الأسعار بالجنيه المصري، وبعض الخدمات بالدولار. كلمنا على واتساب لو عايز ترتب حاجة.', 'Yes, delivery is online. Most prices are in Egyptian pounds and some services are in US dollars. Message us on WhatsApp if you want to arrange something.')],
    ];

    $portfolio = collect(\App\Services\PortfolioData::all())->only(['acelbay', 'zawdha'])->values();
@endphp

@section('content')
<div class="w-full overflow-x-clip bg-white text-[#1d1d1f] antialiased">

    {{-- 1. Hero: who we are in one sentence, and the real products --}}
    <section class="px-4 sm:px-8 pt-12 sm:pt-16 bg-white">
        <div class="max-w-[1180px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            <div class="lg:col-span-6 text-start">
                <p class="text-[14px] font-semibold text-[#0071e3] mb-4">{{ $t('موسوفت، استوديو برمجيات مصري في السويس', 'Musoftware, an Egyptian software studio in Suez') }}</p>
                <h1 class="text-[34px] sm:text-[46px] lg:text-[50px] font-bold tracking-tight leading-[1.25]">
                    {{ $t('برامج وأنظمة جاهزة لشغلك، وبسعر معلن من أول يوم', 'Ready-made programs and systems for your business, with a price you see on day one') }}
                </h1>
                <p class="mt-5 text-[17px] sm:text-[19px] leading-[1.8] text-[#515154] max-w-[560px]">
                    {{ $t('بنبيع برامج سطح مكتب وأنظمة إدارة جاهزة للصيدليات والمطاعم والعيادات والمراكز والمحلات، وبننفذ مواقع ومتاجر وتعديلات مخصصة. بتستلم الكود وبتملكه، وبتكلم المبرمج نفسه.', 'We sell ready-made desktop programs and management systems for pharmacies, restaurants, clinics, centers and shops, and we build websites, stores and custom changes. You receive the code, you own it, and you talk to the programmer himself.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('services.index') }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('شوف الأنظمة والبرامج والأسعار', 'See the systems, programs and prices') }}</a>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-black/15 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
                </div>
                <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-[14px] text-[#515154]">
                    @foreach ([$t('سعر معلن قبل الدفع', 'Public price before you pay'), $t('بتملك الكود', 'You own the code'), $t('دفع Kashier من غير حساب', 'Kashier payment, no account')] as $fact)
                        <li class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#0071e3]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $fact }}
                        </li>
                    @endforeach
                </ul>
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
                    <p class="mt-3 text-[12px] text-[#86868b]">{{ $t('لقطات حقيقية من المنتجات وهي شغالة، ببيانات تجريبية.', 'Real screenshots of the running products, with demo data.') }}</p>
                </div>
            @endif
        </div>

        <div class="max-w-[1180px] mx-auto mt-12 sm:mt-16 grid grid-cols-1 sm:grid-cols-3 border-t border-black/10">
            <div class="py-6 sm:pe-6 sm:border-e border-black/10">
                <p class="text-[32px] font-bold text-[#0071e3] leading-none">{{ array_sum($storeCounts) }}</p>
                <p class="mt-2 text-[15px] text-[#515154]">{{ $t('منتج وخدمة معروضة بسعرها', 'Products and services listed with a price') }}</p>
            </div>
            <div class="py-6 sm:px-6 sm:border-e border-black/10">
                <p class="text-[32px] font-bold text-[#0071e3] leading-none">{{ $portfolioCount }}</p>
                <p class="mt-2 text-[15px] text-[#515154]">{{ $t('مشروع في معرض الأعمال', 'Projects in our portfolio') }}</p>
            </div>
            <div class="py-6 sm:ps-6">
                <p class="text-[32px] font-bold text-[#0071e3] leading-none">10+</p>
                <p class="mt-2 text-[15px] text-[#515154]">{{ $t('سنوات خبرة المؤسس', 'Years of experience of the founder') }}</p>
            </div>
        </div>
    </section>

    {{-- 2. Why trust us: facts the visitor can check --}}
    <section class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
        <div class="max-w-[1100px] mx-auto">
            <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('ليه تثق فينا؟ اتأكد بنفسك', 'Why trust us? Check for yourself') }}</h2>
            <p class="mt-3 text-[16px] text-[#515154] max-w-[640px]">{{ $t('مش هنقولك "الأفضل" و"الرائدين". دي ست حاجات بنعملها فعلًا، وكل واحدة تقدر تشوفها على الموقع.', 'We will not say "the best" or "the leaders". These are six things we really do, and you can see each one on the site.') }}</p>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($trust as $item)
                    <div class="bg-white rounded-2xl border border-black/5 p-6 flex flex-col">
                        <h3 class="text-[18px] font-bold leading-snug">{{ $item['title'] }}</h3>
                        <p class="mt-3 text-[15px] leading-[1.8] text-[#515154] flex-1">{{ $item['body'] }}</p>
                        @if ($item['link'])
                            <a href="{{ $item['link'] }}" class="mt-4 text-[14px] font-semibold text-[#0071e3] hover:underline">{{ $item['cta'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3. What we offer --}}
    <section id="services-overview" class="px-4 sm:px-8 py-14 sm:py-20 bg-white">
        <div class="max-w-[1100px] mx-auto">
            <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('إيه اللي بنقدمه؟', 'What do we offer?') }}</h2>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($groups as $group)
                    <a href="{{ route('services.index') }}#{{ $group['key'] }}" class="rounded-2xl border border-black/10 p-6 hover:shadow-md transition-shadow flex flex-col">
                        <span class="text-[40px] font-bold text-[#0071e3] leading-none">{{ $storeCounts[$group['key']] }}</span>
                        <h3 class="mt-4 text-[20px] font-bold">{{ $group['title'] }}</h3>
                        <p class="mt-2 text-[15px] leading-[1.8] text-[#515154] flex-1">{{ $group['body'] }}</p>
                        @if (! empty($storeFrom[$group['key']]))
                            <p class="mt-4 text-[15px] font-semibold">{{ $t('من', 'From') }} {{ number_format($storeFrom[$group['key']]) }} {{ $t('جنيه', 'EGP') }}</p>
                        @endif
                        <span class="mt-2 text-[14px] font-semibold text-[#0071e3]">{{ $t('تصفح القسم', 'Browse this section') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Pick your business and see the real product --}}
    @if ($featuredProducts->isNotEmpty())
        @php
            $businessType = [
                'pharmacy-system' => $t('صيدلية', 'Pharmacy'),
                'restaurant-pos-system' => $t('مطعم أو كافيه', 'Restaurant or cafe'),
                'dental-clinic-system' => $t('عيادة أسنان', 'Dental clinic'),
                'shop-manager-pro' => $t('محل أو سوبر ماركت صغير', 'Small shop or grocery'),
                'maps-leads-pro' => $t('تسويق وجمع عملاء', 'Marketing and leads'),
                'company-website-design' => $t('موقع لشركتك', 'A website for your company'),
            ];
        @endphp
        <section id="pick-your-business" class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
            <div class="max-w-[1180px] mx-auto">
                <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('اختار نشاطك وشوف المنتج بنفسك', 'Pick your business and see the product yourself') }}</h2>
                <p class="mt-3 text-[16px] text-[#515154] max-w-[640px]">{{ $t('دي لقطات حقيقية من كل منتج وهو شغال. اضغط على نشاطك.', 'These are real screenshots of each product while it runs. Tap your type of business.') }}</p>

                <div class="mt-10 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start" data-business-switcher>
                    <div class="lg:col-span-5 flex flex-col gap-3" role="tablist" aria-label="{{ $t('نوع النشاط', 'Type of business') }}">
                        @foreach ($featuredProducts as $i => $product)
                            <button type="button" role="tab" id="biz-tab-{{ $i }}" aria-controls="biz-panel-{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" data-index="{{ $i }}"
                                    class="biz-tab text-start rounded-xl border px-5 py-4 transition-colors bg-white border-black/10 hover:border-[#0071e3] aria-selected:border-[#0071e3] aria-selected:bg-[#eef5fe]">
                                <span class="block text-[17px] font-bold">{{ $businessType[$product->slug] ?? $product->localized('name') }}</span>
                                <span class="block mt-1 text-[14px] text-[#515154]">{{ $product->localized('name') }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="lg:col-span-7">
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
                                        <p class="text-[15px] leading-[1.7] text-[#515154] max-w-[460px]">{{ $product->localized('tagline') }}</p>
                                        <p class="mt-2 text-[17px] font-semibold">@if ($product->hasPlans())<span class="text-[13px] font-normal text-[#86868b]">{{ __('software.from') }}</span> @endif{{ $product->money($product->startingPrice()) }} {{ $product->saleCurrency() }} <span class="text-[13px] font-normal text-[#86868b]">{{ $product->periodLabel() }}</span></p>
                                    </div>
                                    <a href="{{ route('website-services.show', $product->slug) }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex shrink-0 items-center justify-center px-6 py-3 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('افتح صفحة المنتج', 'Open the product page') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="mt-10"><a href="{{ route('services.index') }}" class="text-[15px] font-semibold text-[#0071e3] hover:underline">{{ $t('كل المنتجات والخدمات', 'All products and services') }}</a></p>
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

    {{-- 5. How it works --}}
    <section id="how-it-works" class="px-4 sm:px-8 py-14 sm:py-20 bg-white">
        <div class="max-w-[1100px] mx-auto">
            <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('إزاي بنشتغل معاك؟', 'How do we work with you?') }}</h2>
            <ol class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($steps as $i => $step)
                    <li class="rounded-2xl border border-black/10 p-6">
                        <span class="text-[14px] font-bold text-[#0071e3]">{{ $t('الخطوة', 'Step') }} {{ $i + 1 }}</span>
                        <h3 class="mt-2 text-[18px] font-bold">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-[15px] leading-[1.8] text-[#515154]">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 6. Founder --}}
    <section class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
        <div class="max-w-[1100px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-8">
                <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('مين ورا موسوفت؟', 'Who is behind Musoftware?') }}</h2>
                <p class="mt-5 text-[17px] leading-[1.9] text-[#515154]">
                    {{ $t('محمود أمين، مهندس برمجيات من السويس، بيبني أنظمة وبرامج للشركات من أكتر من 10 سنين. موسوفت استوديو صغير ومباشر: اللي بيكلمك هو اللي بيبرمج، فمفيش وسطاء ولا رسايل بتتوه بين أقسام.', 'Mahmoud Amin, a software engineer from Suez, has built systems and programs for businesses for more than 10 years. Musoftware is a small, direct studio: the person who talks to you is the person who programs, so there are no middlemen and no messages lost between departments.') }}
                </p>
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="/about/mahmoud-amin" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-black/15 text-[15px] font-semibold hover:bg-white transition-colors">{{ $t('اقرا قصته وخبرته', 'Read his story and experience') }}</a>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-[15px] font-semibold text-[#1d1d1f] hover:underline">{{ $t('كلمه على واتساب', 'Message him on WhatsApp') }}</a>
                </div>
            </div>
            <div class="lg:col-span-4 text-[15px] leading-[1.9] text-[#515154]">
                <p class="font-semibold text-[#1d1d1f]">{{ $t('المقر', 'Based in') }}</p>
                <p>{{ $t('السويس، مصر', 'Suez, Egypt') }}</p>
                <p class="mt-3 font-semibold text-[#1d1d1f]">{{ $t('أسرع طريق للتواصل', 'Fastest way to reach us') }}</p>
                <p>{{ $t('واتساب', 'WhatsApp') }} <span dir="ltr">+20 101 521 8548</span></p>
            </div>
        </div>
    </section>

    {{-- 7. Work you can open --}}
    @if ($portfolio->isNotEmpty())
        <section id="case-studies" class="px-4 sm:px-8 py-14 sm:py-20 bg-white">
            <div class="max-w-[1100px] mx-auto">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('شغل تقدر تفتحه بنفسك', 'Work you can open yourself') }}</h2>
                    <a href="/portfolio" class="text-[15px] font-semibold text-[#0071e3] hover:underline">{{ $t('معرض الأعمال كامل', 'The full portfolio') }} ({{ $portfolioCount }})</a>
                </div>
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach ($portfolio as $project)
                        <a href="{{ route('portfolio.show', $project['slug']) }}" class="rounded-2xl border border-black/10 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                            <img src="{{ $project['img'] }}" alt="{{ $isAr ? $project['title_ar'] : $project['title_en'] }}" class="w-full aspect-[16/9] object-cover object-top bg-[#f5f5f7]" loading="lazy">
                            <div class="p-6">
                                <h3 class="text-[18px] font-bold">{{ $isAr ? $project['title_ar'] : $project['title_en'] }}</h3>
                                <p class="mt-2 text-[14px] leading-[1.7] text-[#515154]">{{ $isAr ? $project['desc_ar'] : $project['desc_en'] }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 8. Questions --}}
    <section class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
        <div class="max-w-[1100px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            <div class="lg:col-span-4">
                <h2 class="text-[28px] sm:text-[36px] font-bold tracking-tight">{{ $t('أسئلة بتتسأل كتير', 'Common questions') }}</h2>
                <p class="mt-3 text-[16px] leading-[1.8] text-[#515154]">{{ $t('إجابات واضحة قبل ما تدفع. ولو سؤالك مش هنا، اسألنا على واتساب.', 'Clear answers before you pay. If your question is not here, ask us on WhatsApp.') }}</p>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex text-[15px] font-semibold text-[#0071e3] hover:underline">{{ $t('اسألنا على واتساب', 'Ask us on WhatsApp') }}</a>
            </div>
            <div class="lg:col-span-8 space-y-3">
                @foreach ($faq as $item)
                    <details class="group rounded-xl border border-black/10 bg-white px-5 py-4 open:border-[#0071e3]">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[17px] font-semibold [&::-webkit-details-marker]:hidden">
                            {{ $item['q'] }}
                            <span class="shrink-0 w-7 h-7 rounded-full bg-[#f5f5f7] flex items-center justify-center text-[#0071e3] text-[18px] leading-none group-open:hidden" aria-hidden="true">+</span>
                            <span class="shrink-0 w-7 h-7 rounded-full bg-[#eef5fe] items-center justify-center text-[#0071e3] text-[18px] leading-none hidden group-open:flex" aria-hidden="true">&minus;</span>
                        </summary>
                        <p class="mt-3 text-[15px] leading-[1.9] text-[#515154]">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 9. Next step --}}
    <section id="cta-hire" class="px-4 sm:px-8 py-16 sm:py-24 bg-[#1d1d1f] text-center text-white">
        <div class="max-w-[700px] mx-auto">
            <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight leading-tight">{{ $t('جاهز تبدأ؟ اختار أو كلمنا', 'Ready to start? Choose, or talk to us') }}</h2>
            <p class="mt-4 text-[17px] leading-[1.8] text-white/75">{{ $t('لو عارف عايز إيه، افتح الصفحة وادفع. ولو مش متأكد، ابعتلنا على واتساب وصف شغلك وهنقولك إيه المناسب، حتى لو مش عندنا.', 'If you know what you need, open the page and pay. If you are not sure, message us on WhatsApp, describe your business and we will tell you what fits, even if it is not ours.') }}</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('services.index') }}" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors">{{ $t('تصفح كل المنتجات', 'Browse all products') }}</a>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-white/30 text-[15px] font-semibold text-white hover:bg-white/10 transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
            </div>
        </div>
    </section>
</div>
@endsection
