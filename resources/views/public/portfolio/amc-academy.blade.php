@extends('public.portfolio.layout')

@section('case_study')
<div class="mt-24 pt-16 border-t border-[#222222] space-y-16">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div class="space-y-4">
            <span class="text-xs font-mono font-bold uppercase tracking-[0.2em] text-[#748660]">
                {{ $locale === 'ar' ? 'التعليم الرقمي وحماية المحتوى' : 'DRM-Protected Video & E-Learning Infrastructure' }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-bold text-white font-sans">
                {{ $locale === 'ar' ? 'بث فيديو مشفر ومحمي من التسجيل بعلامات مائية ديناميكية' : 'Adaptive Bitrate Video Delivery with Anti-Piracy Dynamic Watermarking' }}
            </h2>
            <p class="text-sm text-zinc-400 font-sans leading-relaxed">
                {{ $locale === 'ar' 
                    ? 'منصة AMC Academy هي منظومة تدريبية وتعليمية متقدمة للمعاهد والمدربين، توفر حماية فائقة للمحاضرات من التسجيل عبر علامات مائية متحركة تُظهر بيانات الطالب وعنوان الـ IP لحظياً، مع اختبارات تفاعلية وشهادات تخرج إلكترونية موثقة.'
                    : 'AMC Academy is an enterprise-grade learning management system designed to protect intellectual property. It features DRM video encryption, dynamic moving student watermarks to prevent screen recording leaks, automated timed assessments, and verifiable digital certificates.' }}
            </p>
        </div>

        <div class="bg-[#161616] border border-[#262626] p-6 space-y-4 font-mono text-xs">
            <div class="flex items-center justify-between border-b border-[#222222] pb-3">
                <span class="text-[#748660] font-bold uppercase tracking-wider">{{ $locale === 'ar' ? 'مواصفات الأمان وحماية الفيديو' : 'LMS SECURITY SPECS' }}</span>
                <span class="text-zinc-500">ANTI-LEAK WATERMARK</span>
            </div>
            <ul class="space-y-2.5 text-zinc-300">
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'علامة مائية ديناميكية متحركة تظهر اسم وهاتف والـ IP للطالب أثناء المشاهدة' : 'Floating watermark overlaying student phone and IP dynamically.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'بث فيديو تكيفي HLS يعمل بسلاسة تامة مع كافة سرعات الإنترنت' : 'HLS adaptive bitrate streaming optimized for low-speed connections.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'نظام اختبارات ذكي بالتصحيح التلقائي وتحديد وقت الإجابة وبنوك الأسئلة' : 'Automated timed quiz grading engine with randomized question banks.' }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#748660]">✓</span>
                    <span>{{ $locale === 'ar' ? 'إصدار شهادات إتمام معتمدة بباركود QR للتحقق من صحتها فوراً' : 'Verifiable digital course completion certificates with cryptographic QR.' }}</span>
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
