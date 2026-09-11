@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'منصات الويب والضيافة الفاخرة' : 'Luxury Hospitality & Real Estate Web Platform' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'واجهة رقمية فاخرة تعكس سحر الوجهة الساحلية وتدير الحجوزات' : 'Immersive Digital Experience Showcasing Beachfront Living & Direct Reservations' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'منصة آسيل باي (Acel Bay) هي واجهة رقمية فخمة لمنتجع ووجهة ساحلية وسكنية فريدة على شواطئ مصر. طُوّرت المنصة بتصميم بصري غامر ومؤثرات تفاعلية عالية السلاسة، تتيح للزوار استكشاف الشواطئ والمطاعم والوحدات الفندقية والسكنية، مع تكامل مباشر للاستفسارات والحجوزات الفورية.'
                    : 'Acel Bay is a bespoke luxury web platform engineered for an exclusive coastal resort and residential destination in Egypt. Crafted with fluid micro-interactions and high-performance visual storytelling, it enables prospective guests and residents to explore luxury beachfront units, boutique hotels, world-class amenities, and reserve stays directly.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات منصة Acel Bay' : 'ACEL BAY PLATFORM SPECS' }}</span>
                <span class="text-zinc-500">LIVE IN PRODUCTION</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'تصميم بصري متقدم ومؤثرات حركية تفاعلية تعكس فخامة المنتجع' : 'Luxury UI/UX aesthetics with buttery-smooth scroll animations.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'استعراض تفاعلي للوحدات الفندقية والفلل الشاطئية والمرافق' : 'Interactive multi-category showcase for boutique stays and dining.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'بوابة حجز إلكترونية وتواصل مباشر وسريع مع فريق المبيعات والضيافة' : 'Direct reservation gateway & inquiry dispatch pipeline.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'أداء فائق السرعة وتحميل متجاوب بالكامل لكافة الهواتف والشاشات' : 'Ultra-fast Core Web Vitals optimized for mobile-first visitors.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
