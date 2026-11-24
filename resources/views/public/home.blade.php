@extends('layouts.public')

@php
    $isAr = app()->getLocale() === 'ar';
    $t = fn (string $ar, string $en) => $isAr ? $ar : $en;
    $whatsapp = \App\Support\SiteNav::whatsAppChatUrl();

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

    $primaryButton = 'inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-[15px] font-semibold hover:bg-[#0077ed] transition-colors';
    $secondaryButton = 'inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-black/15 text-[15px] font-semibold text-[#1d1d1f] hover:bg-[#f5f5f7] transition-colors';
@endphp

@section('content')
<div class="w-full overflow-x-clip bg-white text-[#1d1d1f] antialiased">

    {{-- 1. Hero: what we do in one sentence, one main step, and a real program as the picture --}}
    <section class="px-4 sm:px-8 pt-12 sm:pt-16 bg-white">
        <div class="max-w-[1180px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            <div class="lg:col-span-6 text-start">
                <p class="text-[14px] font-semibold text-[#0071e3] mb-4">{{ $t('موسوفت، شركة برمجة مصرية في السويس', 'Musoftware, an Egyptian software company in Suez') }}</p>
                <h1 class="text-[34px] sm:text-[46px] lg:text-[52px] font-bold tracking-tight leading-[1.25]">
                    {{ $t('بنعملك البرنامج أو الموقع أو التطبيق اللي شغلك محتاجه', 'We build the program, website or app your business needs') }}
                </h1>
                <p class="mt-5 text-[18px] sm:text-[20px] leading-[1.7] text-[#515154] max-w-[520px]">
                    {{ $t('قولنا شغلك بيعمل إيه، وإحنا نبنيه معاك خطوة بخطوة، بسعر تعرفه قبل ما نبدأ.', 'Tell us what your business does, and we build it with you step by step, at a price you know before we start.') }}
                </p>
                <div class="mt-8 flex flex-col sm:flex-row sm:flex-wrap gap-3">
                    <a href="/start-project" style="background-color: #0071e3; color: #ffffff;" class="{{ $primaryButton }}">{{ $t('ابدأ مشروعك', 'Start your project') }}</a>
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="{{ $secondaryButton }}">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
                </div>
                @if (! empty($clientWork))
                    {{-- A real client project right under the main message, so the promise has proof next to it --}}
                    <p class="mt-6 text-[15px] text-[#515154]">
                        {{ $t('من شغلنا لعملاء:', 'From our client work:') }}
                        <a href="/portfolio/{{ $clientWork[0]['slug'] }}" class="font-semibold text-[#0071e3] hover:underline">{{ $isAr ? $clientWork[0]['title_ar'] : $clientWork[0]['title_en'] }}</a>
                    </p>
                @endif
            </div>

            @if ($heroShot)
                <div class="lg:col-span-6">
                    <figure class="rounded-xl border border-black/10 bg-white shadow-xl overflow-hidden">
                        <div class="flex items-center gap-1.5 px-4 h-8 bg-[#f5f5f7] border-b border-black/10" aria-hidden="true">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span><span class="w-2.5 h-2.5 rounded-full bg-[#d2d2d7]"></span>
                        </div>
                        <img src="{{ $heroShot['image'] }}" alt="{{ $heroShot['product']->localized('name') }}" fetchpriority="high" class="w-full aspect-[16/10] object-cover object-top bg-[#f5f5f7]">
                    </figure>
                    <p class="mt-3 text-[13px] text-[#86868b] text-center">{{ $t('من برامجنا', 'One of our programs') }}: {{ $heroShot['product']->localized('name') }}</p>
                </div>
            @endif
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

    {{-- 2. Work we did for real clients --}}
    @if (! empty($clientWork))
        <section id="our-work" class="px-4 sm:px-8 py-14 sm:py-20 bg-[#f5f5f7]">
            <div class="max-w-[1180px] mx-auto">
                <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('شغل عملناه لعملاء حقيقيين', 'Work we did for real clients') }}</h2>
                <p class="mt-3 text-[16px] text-[#515154] text-center">{{ $t('مواقع ومتاجر وأنظمة، كل واحد معمول على مقاس شغل صاحبه.', 'Websites, stores and systems, each one made to fit its owner\'s business.') }}</p>
                <ul class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($clientWork as $project)
                        <li>
                            <a href="/portfolio/{{ $project['slug'] }}" class="group flex h-full flex-col rounded-2xl bg-white border border-black/5 overflow-hidden hover:shadow-md transition-shadow">
                                <img src="{{ $project['img'] }}" alt="" loading="lazy" class="w-full aspect-[16/10] object-cover object-top bg-[#f5f5f7]">
                                <div class="flex flex-1 flex-col p-5">
                                    <h3 class="text-[17px] font-bold leading-snug">{{ $isAr ? $project['title_ar'] : $project['title_en'] }}</h3>
                                    <p class="mt-2 text-[14px] leading-[1.7] text-[#515154] line-clamp-2">{{ $isAr ? $project['desc_ar'] : $project['desc_en'] }}</p>
                                    <span class="mt-4 text-[14px] font-semibold text-[#0071e3] group-hover:underline">{{ $t('شوف التفاصيل', 'See the details') }}</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-8 text-center"><a href="{{ route('portfolio') }}" class="text-[15px] font-semibold text-[#0071e3] hover:underline">{{ $t('شوف كل أعمالنا', 'See all our work') }}</a></p>
            </div>
        </section>
    @endif

    {{-- 3. Ready programs as samples: pick your business and see one --}}
    <section id="pick-your-business" class="px-4 sm:px-8 py-14 sm:py-20 bg-white">
        <div class="max-w-[1180px] mx-auto" data-business-switcher>
                <h2 class="text-[28px] sm:text-[38px] font-bold tracking-tight text-center">{{ $t('عندنا برامج جاهزة لنشاطات كتير', 'We have ready programs for many businesses') }}</h2>
                <p class="mt-3 text-[16px] text-[#515154] text-center">{{ $t('اختار نشاطك وشوف البرنامج. تقدر تبدأ بيه، ونعدّله على مقاس شغلك.', 'Pick your business and see the program. You can start with it, and we adjust it to fit your work.') }}</p>

                @if ($featuredProducts->isNotEmpty())

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
                                <a href="{{ route('website-services.show', $product->slug) }}" class="{{ $secondaryButton }} shrink-0">{{ $t('افتح صفحة البرنامج', 'Open the program page') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
                <p class="mt-8 text-center"><a href="{{ route('services.index') }}" class="text-[15px] font-semibold text-[#0071e3] hover:underline">{{ $t('شوف كل البرامج الجاهزة وأسعارها', 'See all ready programs and prices') }}</a></p>
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
            <p class="mt-8 text-center"><a href="/how-we-work{{ $isAr ? '?lang=ar' : '' }}" class="text-sm font-semibold text-[#0071e3] hover:underline">{{ $t('الأسعار والدعم بعد التسليم بالتفصيل', 'Prices and support after delivery in detail') }}</a></p>
        </div>
    </section>

    {{-- 5. Founder, short --}}
    <section class="px-4 sm:px-8 py-12 sm:py-16 bg-white">
        <div class="max-w-[820px] mx-auto text-center">
            <p class="text-[20px] sm:text-[24px] leading-[1.7] font-semibold">{{ $t('محمود أمين، مؤسس موسوفت ومصمم الأنظمة، من السويس. بيبني أنظمة من أكتر من 10 سنين، وهو اللي بيكلمك وهو اللي بيبرمج.', 'Mahmoud Amin, Musoftware\'s founder and system designer, from Suez. He has built systems for more than 10 years, and he is the one who talks to you and programs.') }}</p>
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
            <p class="mt-3 text-[16px] text-white/70">{{ $t('احكيلنا عن شغلك، ونقولك هنعمله إزاي وبكام.', 'Tell us about your business, and we tell you how we would build it and what it costs.') }}</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/start-project" style="background-color: #0071e3; color: #ffffff;" class="{{ $primaryButton }}">{{ $t('ابدأ مشروعك', 'Start your project') }}</a>
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-white/30 text-[15px] font-semibold text-white hover:bg-white/10 transition-colors">{{ $t('كلمنا على واتساب', 'Message us on WhatsApp') }}</a>
            </div>
        </div>
    </section>
</div>
@endsection
