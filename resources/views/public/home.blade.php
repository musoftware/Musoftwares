@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isAr = $locale === 'ar';
@endphp

{{-- ============================================================ --}}
{{-- CUSTOM NAVBAR (Matching image.png navbar)                    --}}
{{-- Left: Musoftware Studio Logo                                  --}}
{{-- Center: About us | Services | Case Studies | Blog | How it Works | Hire --}}
{{-- Right: Solid Purple "Contact us" Button                      --}}
{{-- ============================================================ --}}
@section('custom_header')
<header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
    <div class="max-w-[1240px] mx-auto flex items-center justify-between px-6 sm:px-10 h-20">
        
        <!-- Logo Left -->
        <a href="/" class="flex items-center gap-2.5 group focus:outline-none" title="Musoftware Studio">
            <svg class="w-6 h-6 fill-[#0071e3] transition-transform group-hover:scale-105" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg">
                <path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/>
            </svg>
            <span class="text-[20px] font-bold tracking-tight text-[#1d1d1f]">
                Musoftware
            </span>
        </a>

        <!-- Center Links (Matching image.png navbar) -->
        <nav class="hidden md:flex items-center gap-8 text-[14px] font-medium text-[#515154]">
            <a href="/about/mahmoud-amin" class="hover:text-[#0071e3] transition-colors">{{ $isAr ? 'من نحن' : 'About us' }}</a>
            <a href="#services-overview" class="hover:text-[#0071e3] transition-colors">{{ $isAr ? 'الخدمات' : 'Services' }}</a>
            <a href="#case-studies" class="hover:text-[#0071e3] transition-colors">{{ $isAr ? 'أعمالنا' : 'Case Studies' }}</a>
            <a href="/compare/laravel-vs-nodejs" class="hover:text-[#0071e3] transition-colors">{{ $isAr ? 'المدونة' : 'Blog' }}</a>
            <a href="#how-it-works" class="hover:text-[#0071e3] transition-colors">{{ $isAr ? 'كيف نعمل' : 'How it Works' }}</a>
            <a href="#cta-hire" class="hover:text-[#0071e3] transition-colors">{{ $isAr ? 'التوظيف' : 'Hire' }}</a>
        </nav>

        <!-- Right Solid Royal Blue CTA Button (Matching Apple musoftwares.com) -->
        <div class="flex items-center gap-4">
            <a href="/company/contact" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg text-white text-[14px] font-medium shadow-sm hover:bg-[#0077ed] transition">
                <span>{{ $isAr ? 'تواصل معنا' : 'Contact us' }}</span>
            </a>
        </div>

    </div>
</header>
@endsection

@section('content')
<div class="min-h-screen bg-white text-[#1d1d1f] antialiased font-sans overflow-x-clip relative">

    <!-- ============================================================ -->
    <!-- SECTION 1: HERO (Matching image.png & Apple Simplicity)      -->
    <!-- Headline with Apple Blue Accent + Royal Blue Button          -->
    <!-- ============================================================ -->
    <section style="min-height: calc(100vh - 80px); min-height: calc(100dvh - 80px);" class="relative flex flex-col justify-between pt-6 md:pt-10 pb-4 px-6 sm:px-10 bg-white">
        <div class="max-w-[1240px] w-full mx-auto my-auto py-2">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                
                <!-- Left Column: Headline, Subtitle, CTA Button -->
                <div class="lg:col-span-6 space-y-6 text-start">
                    <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-bold tracking-tight leading-[1.15] text-[#1d1d1f]">
                        @if($isAr)
                            منتج برمجي <span style="color: #0071e3;">عظيم</span> يُبنى <br>
                            بواسطة <span style="color: #1d1d1f;">فريق محترف</span>
                        @else
                            Great <span style="color: #0071e3;">Product</span> is <br>
                            built by great <span style="color: #1d1d1f;">teams</span>
                        @endif
                    </h1>

                    <p class="text-[16px] sm:text-[18px] leading-[1.65] text-[#515154] font-normal max-w-[480px]">
                        {{ $isAr 
                            ? 'نساعدك في بناء وإدارة فريق هندسي محترف لتحويل رؤيتك إلى واقع برمجي متكامل مع تسليم كامل للسورس كود والداتا.' 
                            : 'We help build and manage a team of world-class developers to bring your vision to life.' }}
                    </p>

                    <div class="pt-2">
                        <a href="/start-project" style="background-color: #0071e3; color: #ffffff;" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-md text-white text-[15px] font-medium shadow-md hover:bg-[#0077ed] transition duration-200">
                            <span>{{ $isAr ? 'ابدأ مشروعك الآن!' : "Let's get started!" }}</span>
                        </a>
                    </div>
                </div>

                <!-- Right Column: Vector Illustration (Clean transparent illustration) -->
                <div class="lg:col-span-6 flex justify-center items-center">
                    <div class="w-full max-w-[580px] flex justify-center">
                        <img src="/images/hero_team_illustration.png" alt="Great Product is built by great teams" class="w-full max-h-[460px] h-auto object-contain block">
                    </div>
                </div>

            </div>
        </div>

        <!-- Decorative Subtle Pill below Hero -->
        <div class="flex justify-center pb-2">
            <div style="background-color: #d2d2d7;" class="w-10 h-3 rounded-full opacity-70"></div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 2: SERVICES WE OFFER (Matching image.png & Estimator) -->
    <!-- 5 Cards backed by ProjectEstimatorDataService + Slider UI     -->
    <!-- ============================================================ -->
    <section id="services-overview" class="py-16 md:py-24 px-6 sm:px-10 bg-[#fbfbfe] overflow-hidden relative">
        <div class="max-w-[1280px] mx-auto">
            
            <!-- Section Header -->
            <div class="text-center max-w-[650px] mx-auto mb-14">
                <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight">
                    {{ $isAr ? 'خدماتنا البرمجية' : 'Services we offer' }}
                </h2>
                <p class="text-[14px] sm:text-[15px] text-[#6e6e73] mt-2">
                    {{ $isAr ? 'تسعير شفاف بالوحدة والشاشة مستمد مباشرة من حاسبة تقدير المشاريع (Estimator)' : 'Transparent unit-based pricing powered directly by our Project Estimator' }}
                </p>
            </div>

            <!-- 5 Estimator Cards Horizontal Slider Track (Matching image.png) -->
            <div id="estimator-services-track" class="flex gap-6 overflow-x-auto pb-6 pt-2 no-scrollbar scroll-smooth snap-x snap-mandatory items-stretch">
                
                <!-- Card 1: Web Design & Development (FEATURED / ACTIVE) -->
                <div id="service-card-0" style="width: 285px; min-width: 285px; max-width: 285px; border: 2px solid #0071e3;" class="service-slide-card shrink-0 snap-start p-7 rounded-2xl bg-white shadow-lg flex flex-col justify-between relative transform -translate-y-1 transition-all duration-300">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div style="background-color: #eff6ff; color: #0071e3; border: 1px solid #bfdbfe;" class="w-12 h-12 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            </div>
                            <span style="background-color: #eff6ff; color: #0071e3; border: 1px solid #bfdbfe;" class="text-[11px] font-bold px-2.5 py-1 rounded-full">
                                {{ $isAr ? '500 ج.م / صفحة ($10)' : '$10 / Page' }}
                            </span>
                        </div>
                        <h3 style="color: #0071e3;" class="text-[17px] font-bold mb-3">
                            {{ $isAr ? 'تصميم وتطوير مواقع الويب' : 'Web Design & Development' }}
                        </h3>
                        <p class="text-[13px] text-[#6e6e73] leading-relaxed mb-4">
                            {{ $isAr 
                                ? 'منصات سحابية SaaS وتطبيقات ويب متجاوبة للشركات وبوابات العملاء بمعمارية معزولة وآمنة وقابلة للتوسع.' 
                                : 'Responsive web applications, corporate websites, customer portals, and SaaS platforms built with scalable architecture.' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">{{ $isAr ? 'الوحدة: صفحة' : 'Unit: Page' }}</span>
                        <a href="/estimator" style="color: #0071e3;" class="text-[12px] font-bold hover:underline inline-flex items-center gap-1">
                            <span>{{ $isAr ? 'احسب في الـ Estimator' : 'Calculate in Estimator' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>

                <!-- Card 2: Mobile App Development -->
                <div id="service-card-1" style="width: 285px; min-width: 285px; max-width: 285px;" class="service-slide-card shrink-0 snap-start p-7 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-full border border-blue-200 text-blue-500 bg-blue-50/50 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100">
                                {{ $isAr ? '750 ج.م / شاشة ($15)' : '$15 / Screen' }}
                            </span>
                        </div>
                        <h3 class="text-[17px] font-bold text-[#1d1d1f] mb-3">
                            {{ $isAr ? 'تطوير تطبيقات الهواتف' : 'Mobile App Development' }}
                        </h3>
                        <p class="text-[13px] text-[#6e6e73] leading-relaxed mb-4">
                            {{ $isAr 
                                ? 'تطبيقات هواتف ذكية أصلية لنظامي iOS وAndroid مع تتبع حي للطلبات عبر الخرائط وإشعارات فورية وتجربة مستخدم عالية السرعة.' 
                                : 'Cross-platform native mobile applications for iOS and Android with high performance, GPS live tracking, and instant alerts.' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">{{ $isAr ? 'الوحدة: شاشة' : 'Unit: Screen' }}</span>
                        <a href="/estimator" style="color: #0071e3;" class="text-[12px] font-bold hover:underline inline-flex items-center gap-1">
                            <span>{{ $isAr ? 'احسب في الـ Estimator' : 'Calculate in Estimator' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>

                <!-- Card 3: Software Testing & QA -->
                <div id="service-card-2" style="width: 285px; min-width: 285px; max-width: 285px;" class="service-slide-card shrink-0 snap-start p-7 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-full border border-slate-200 text-[#0071e3] bg-blue-50/50 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $isAr ? 'فحص شامل وضمان جودة' : 'Full Lifecycle QA' }}
                            </span>
                        </div>
                        <h3 class="text-[17px] font-bold text-[#1d1d1f] mb-3">
                            {{ $isAr ? 'اختبار وفحص جودة البرمجيات' : 'Software Testing Service' }}
                        </h3>
                        <p class="text-[13px] text-[#6e6e73] leading-relaxed mb-4">
                            {{ $isAr 
                                ? 'فحص أمني واختبارات ضغط وتحميل تلقائية تضمن خلو الأنظمة من الثغرات البرمجية واستقرارها بنسبة 99.9% تحت أقصى حمل.' 
                                : 'End-to-end automated testing, load benchmarks, security audits, and bug fixing to guarantee 99.9% uptime.' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">{{ $isAr ? 'ضمان أمان وتوافق' : 'Security & Load' }}</span>
                        <a href="/estimator" style="color: #0071e3;" class="text-[12px] font-bold hover:underline inline-flex items-center gap-1">
                            <span>{{ $isAr ? 'احسب في الـ Estimator' : 'Calculate in Estimator' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>

                <!-- Card 4: Desktop Software & POS -->
                <div id="service-card-3" style="width: 285px; min-width: 285px; max-width: 285px;" class="service-slide-card shrink-0 snap-start p-7 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-full border border-teal-200 text-teal-600 bg-teal-50/50 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-teal-50 text-teal-600 border border-teal-100">
                                {{ $isAr ? '1,250 ج.م / شاشة ($25)' : '$25 / Screen' }}
                            </span>
                        </div>
                        <h3 class="text-[17px] font-bold text-[#1d1d1f] mb-3">
                            {{ $isAr ? 'برمجيات سطح المكتب ونقاط البيع' : 'Desktop Software & POS' }}
                        </h3>
                        <p class="text-[13px] text-[#6e6e73] leading-relaxed mb-4">
                            {{ $isAr 
                                ? 'أنظمة سطح مكتب للويندوز تعمل أوفلاين 100% مع إدارة طابعات الفواتير وقارئ الباركود والموازين الإلكترونية وربط السحابة.' 
                                : 'Offline-first desktop software for Windows, POS cashier terminals, receipt printing, barcode scanners, and local hardware sync.' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">{{ $isAr ? 'الوحدة: شاشة' : 'Unit: Screen' }}</span>
                        <a href="/estimator" style="color: #0071e3;" class="text-[12px] font-bold hover:underline inline-flex items-center gap-1">
                            <span>{{ $isAr ? 'احسب في الـ Estimator' : 'Calculate in Estimator' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>

                <!-- Card 5: AI & WhatsApp Automation -->
                <div id="service-card-4" style="width: 285px; min-width: 285px; max-width: 285px;" class="service-slide-card shrink-0 snap-start p-7 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-full border border-sky-200 text-sky-600 bg-sky-50/50 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-sky-50 text-sky-600 border border-sky-100">
                                {{ $isAr ? 'من 1,750 ج.م ($35)' : 'From $35 / Module' }}
                            </span>
                        </div>
                        <h3 class="text-[17px] font-bold text-[#1d1d1f] mb-3">
                            {{ $isAr ? 'الذكاء الاصطناعي وأتمتة الواتساب' : 'AI & WhatsApp Automation' }}
                        </h3>
                        <p class="text-[13px] text-[#6e6e73] leading-relaxed mb-4">
                            {{ $isAr 
                                ? 'ربط رسمي عبر Meta Cloud API لأتمتة رسائل واتساب والتحقق برمز OTP مع روبوتات محادثة ذكية وتنبيهات فورية لطلبات المتجر.' 
                                : 'Official Meta WhatsApp Cloud API bots, OTP alerts, 24/7 AI customer service assistants, and automated multi-channel messaging.' }}
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-50 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">{{ $isAr ? 'الوحدة: إضافة' : 'Unit: Module' }}</span>
                        <a href="/estimator" style="color: #0071e3;" class="text-[12px] font-bold hover:underline inline-flex items-center gap-1">
                            <span>{{ $isAr ? 'احسب في الـ Estimator' : 'Calculate in Estimator' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Carousel Pagination Slider Controls (Matching image.png) -->
            <div class="flex items-center justify-between mt-8 pt-4">
                <!-- Left Nav Arrows -->
                <div class="flex items-center gap-2">
                    <button id="services-prev-arrow" type="button" onclick="slideServices(-1)" style="border: 1px solid #0071e3; color: #0071e3;" class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-blue-50 transition cursor-pointer" aria-label="Previous service">
                        <span class="rtl:rotate-180">←</span>
                    </button>
                    <button id="services-next-arrow" type="button" onclick="slideServices(1)" style="background-color: #0071e3; color: #ffffff;" class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-[#0077ed] transition cursor-pointer" aria-label="Next service">
                        <span class="rtl:rotate-180">→</span>
                    </button>
                </div>

                <!-- Center 5 Dots -->
                <div class="flex items-center gap-2">
                    <button id="service-dot-0" type="button" onclick="goToService(0)" class="service-carousel-dot w-4 h-2.5 rounded-full transition-all duration-300 cursor-pointer" style="background-color: #0071e3;" aria-label="Go to service 1"></button>
                    <button id="service-dot-1" type="button" onclick="goToService(1)" class="service-carousel-dot w-2.5 h-2.5 rounded-full bg-slate-300 transition-all duration-300 cursor-pointer" aria-label="Go to service 2"></button>
                    <button id="service-dot-2" type="button" onclick="goToService(2)" class="service-carousel-dot w-2.5 h-2.5 rounded-full bg-slate-300 transition-all duration-300 cursor-pointer" aria-label="Go to service 3"></button>
                    <button id="service-dot-3" type="button" onclick="goToService(3)" class="service-carousel-dot w-2.5 h-2.5 rounded-full bg-slate-300 transition-all duration-300 cursor-pointer" aria-label="Go to service 4"></button>
                    <button id="service-dot-4" type="button" onclick="goToService(4)" class="service-carousel-dot w-2.5 h-2.5 rounded-full bg-slate-300 transition-all duration-300 cursor-pointer" aria-label="Go to service 5"></button>
                </div>

                <!-- Right Page Number (01 — 05 matching image.png) -->
                <div class="flex justify-end items-center gap-2 text-[13px] font-mono text-slate-400">
                    <span id="service-current-page" class="text-slate-900 font-bold">01</span>
                    <span class="w-12 h-0.5 bg-slate-200"></span>
                    <span>05</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 3: LEADING COMPANIES TRUST US (Matching image.png)    -->
    <!-- 2 Columns + Play Button Badge on Photo + Meet the People Row  -->
    <!-- ============================================================ -->
    <section class="py-16 md:py-24 px-6 sm:px-10 bg-white relative">
        
        <!-- Decorative Pill on Left Margin -->
        <div style="background-color: #0071e3;" class="absolute left-6 top-24 w-8 h-3 rounded-full opacity-60 hidden lg:block"></div>

        <div class="max-w-[1240px] mx-auto">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                
                <!-- Left: Leading Companies Text -->
                <div class="lg:col-span-6 space-y-6 text-start">
                    <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mb-2"></div>
                    <h2 class="text-[34px] sm:text-[42px] font-bold text-[#1d1d1f] tracking-tight leading-[1.2]">
                        @if($isAr)
                            شركات رائدة ومؤسسات أعمال <br>
                            تعتمد علينا لتطوير برمجياتها
                        @else
                            Leading companies trust us <br>
                            to develop software
                        @endif
                    </h2>

                    <p class="text-[15px] sm:text-[16px] text-[#515154] leading-relaxed">
                        @if($isAr)
                            نحن <span style="color: #0071e3;" class="font-semibold">نبني منظومات برمجية متكاملة</span> وحلولاً رقمية متقدمة مصممة خصيصاً لدعم نمو الأعمال وتلبية أدق احتياجاتها التشغيلية. على مدار سنوات، قمنا بهندسة وتطوير أنظمة إدارة الموارد (ERP)، منصات الـ SaaS السحابية، برمجيات نقاط البيع (POS) المستقلة، وأتمتة الواتساب عبر Meta Cloud API لقطاعات متنوعة تشمل الخدمات اللوجستية، التجارة الإلكترونية، الرعاية الصحية، والمؤسسات الخدمية — مع تسليم كامل للسورس كود والداتا بملكية مطلقة وضمان استقرار وتشغيل مستمر.
                        @else
                            We <span style="color: #0071e3;" class="font-semibold">engineer custom software systems</span> and high-performance digital platforms tailored to solve complex operational challenges. Over years of hands-on delivery, we have built enterprise ERP & accounting platforms, multi-tenant cloud SaaS products, offline-first POS systems, automated Meta WhatsApp dispatch engines, and secure payment integrations across logistics, healthcare, retail, and fintech — providing end-to-end execution, total reliability, and 100% source code ownership.
                        @endif
                    </p>

                    <!-- What we worked on highlights (Subtle capability grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1 pb-1">
                        <div class="flex items-center gap-2.5 text-[13px] text-[#333336] font-medium">
                            <span class="w-2 h-2 rounded-full bg-[#0071e3] shrink-0"></span>
                            <span>{{ $isAr ? 'أنظمة ERP وحسابات ومخازن' : 'Enterprise ERP & Workflows' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-[13px] text-[#333336] font-medium">
                            <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                            <span>{{ $isAr ? 'منصات SaaS وتطبيقات سحابية' : 'Cloud SaaS & Web Platforms' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-[13px] text-[#333336] font-medium">
                            <span class="w-2 h-2 rounded-full bg-[#0071e3] shrink-0"></span>
                            <span>{{ $isAr ? 'برمجيات POS ونقاط بيع أوفلاين' : 'Offline POS & Desktop Apps' }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-[13px] text-[#333336] font-medium">
                            <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
                            <span>{{ $isAr ? 'أتمتة WhatsApp وربط البوابات' : 'Meta WhatsApp & API Integrations' }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="#case-studies" style="color: #0071e3;" class="font-semibold hover:opacity-80 inline-flex items-center gap-2 text-[15px] transition group">
                            <span>{{ $isAr ? 'استكشف مشاريعنا ودراسات الحالة' : 'See more Informations & Case Studies' }}</span>
                            <span class="rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform">➔</span>
                        </a>
                    </div>
                </div>

                <!-- Right: Team Photo with Circular Play Button (Matching image.png) -->
                <div class="lg:col-span-6 flex justify-center">
                    <div class="relative w-full max-w-[500px] rounded-3xl overflow-hidden shadow-2xl group border-4 border-white">
                        <img src="/images/team_trust_clean.png" alt="Leading companies trust us to develop software" class="w-full h-auto object-cover block">
                    </div>
                </div>

            </div>

            <!-- Sub-Row: Meet the People We are Working With (Matching image.png) -->
            <div class="mt-20 pt-8 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-[14px] text-slate-500 font-medium block">{{ $isAr ? 'تعرف على' : 'Meet the People' }}</span>
                    <h3 class="text-[24px] sm:text-[28px] font-bold text-[#1d1d1f]">
                        {{ $isAr ? 'شركاء النجاح والشركات التي عملنا معها' : 'We are Working With' }}
                    </h3>
                </div>
                <div class="flex items-center gap-3">
                    <button style="border: 1px solid #0071e3; color: #0071e3;" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-blue-50 transition cursor-pointer">
                        <span class="rtl:rotate-180">←</span>
                    </button>
                    <button style="background-color: #0071e3; color: #ffffff;" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-[#0077ed] transition cursor-pointer">
                        <span class="rtl:rotate-180">→</span>
                    </button>
                </div>
            </div>

            <!-- Centered Decorative Subtle Dot -->
            <div class="flex justify-center mt-6">
                <div style="background-color: #d2d2d7;" class="w-8 h-3 rounded-full opacity-60"></div>
            </div>

        </div>

        <!-- Centered Partner Logos (Static, 5 Per Row, Centered) -->
        <div class="mt-12 pt-10 border-t border-slate-200/60 select-none">
            <div class="max-w-[1100px] mx-auto px-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-8 sm:gap-10 items-center justify-items-center">
                    <!-- Row 1: 5 Real Clients -->
                    <!-- Logo 1: Egypt Servers -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/egy-servers.png" alt="Egypt Servers" style="height: 28px; max-height: 28px; width: auto; max-width: 150px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 2: Jad Technology -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/jad-technology.png" alt="Jad Technology" style="height: 34px; max-height: 34px; width: auto; max-width: 140px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 3: AMC Academy -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/amcacademy.png" alt="AMC Academy" style="height: 42px; max-height: 42px; width: auto; max-width: 100px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 4: Mini Fatora -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/mini-fatora.png" alt="Mini Fatora" style="height: 38px; max-height: 38px; width: auto; max-width: 130px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 5: Topline -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/topline.png" alt="Topline" style="height: 30px; max-height: 30px; width: auto; max-width: 120px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>

                    <!-- Row 2: 5 Real Clients -->
                    <!-- Logo 6: My Line -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/my-line.png" alt="My Line Call Centre" style="height: 34px; max-height: 34px; width: auto; max-width: 130px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 7: Aswan -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/aswan.png" alt="Aswan" style="height: 34px; max-height: 34px; width: auto; max-width: 130px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 8: MIT -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/mit.png" alt="MIT" style="height: 36px; max-height: 36px; width: auto; max-width: 90px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 9: Technosoft -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/technosoft.png" alt="Technosoft" style="height: 36px; max-height: 36px; width: auto; max-width: 90px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                    <!-- Logo 10: OBD Ultra -->
                    <div class="flex items-center justify-center h-12 w-full">
                        <img src="/images/clients/obdultra.png" alt="OBD Ultra" style="height: 26px; max-height: 26px; width: auto; max-width: 110px; object-fit: contain;" class="opacity-80 hover:opacity-100 transition duration-200">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 4: WHY CUSTOMERS LOVE WORKING WITH US (Testimonials)  -->
    <!-- Quote with Big Quotation Marks + 5 Avatars on Curved Arc      -->
    <!-- ============================================================ -->
    <section class="py-16 md:py-24 px-6 sm:px-10 bg-[#f5f5f7] relative">
        <div class="max-w-[1100px] mx-auto text-center">
            
            <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mx-auto mb-4"></div>
            <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight mb-12">
                @if($isAr)
                    لماذا يفضل العملاء <br>
                    <span style="color: #0071e3;">العمل معنا؟</span>
                @else
                    Why customers love <br>
                    <span style="color: #0071e3;">working with us</span>
                @endif
            </h2>

            <!-- Testimonial Quote with Large Quotation Marks and Nav Arrows (Matching image.png) -->
            <div class="relative max-w-[840px] mx-auto mb-14 flex items-center justify-between gap-6 px-4">
                <!-- Left Nav Arrow Button -->
                <button type="button" id="testimonial-prev" style="border: 1.5px solid #0071e3; color: #0071e3;" class="w-11 h-11 rounded-full flex items-center justify-center hover:bg-[#0071e3] hover:text-white transition shrink-0 shadow-sm cursor-pointer" aria-label="Previous testimonial">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <!-- Center Testimonial Text Box with Hanging Quotes -->
                <div class="relative max-w-[660px] px-6 py-2 min-h-[90px] flex items-center justify-center">
                    <span style="color: #0071e3;" class="text-3xl sm:text-4xl font-serif font-black absolute -top-3 left-0 select-none opacity-80">“</span>
                    
                    <p id="testimonial-quote-text" class="text-[14px] sm:text-[16px] text-[#515154] leading-[1.85] font-normal text-center transition-opacity duration-300">
                        {{ $isAr 
                            ? 'شراكتنا مع Musoftware امتدت لسنوات في بناء وتشغيل أضخم منصاتنا التسويقية والذكاء الاصطناعي مثل AMCTasks و AMC Social و AMC Turbo. كفاءة هندسية نادرة، استقرار برمجي غير مسبوق، ودعم فني مخلص جعلهم الشريك التكنولوجي الأول لمجموعتنا.' 
                            : 'Our strategic partnership with Musoftware has powered our most ambitious AI and marketing automation platforms, including AMCTasks, AMC Social, and AMC Turbo. Their exceptional engineering, unbreakable uptime, and absolute technical ownership make them our #1 core technology partner.' }}
                    </p>

                    <span style="color: #0071e3;" class="text-3xl sm:text-4xl font-serif font-black absolute -bottom-3 right-0 select-none opacity-80">”</span>
                </div>

                <!-- Right Nav Arrow Button -->
                <button type="button" id="testimonial-next" style="border: 1.5px solid #0071e3; color: #0071e3;" class="w-11 h-11 rounded-full flex items-center justify-center hover:bg-[#0071e3] hover:text-white transition shrink-0 shadow-sm cursor-pointer" aria-label="Next testimonial">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- Client Avatars in a Row with 5 Stars (Active Dynamic Switcher) -->
            <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 max-w-[900px] mx-auto pt-6">
                
                <!-- Avatar 0 -->
                <div id="testimonial-avatar-0" class="testimonial-avatar-item flex flex-col items-center opacity-70 hover:opacity-100 transition duration-300 cursor-pointer" onclick="switchTestimonial(0)">
                    <div class="relative mb-2">
                        <img src="/images/avatars/client_black_sweater.jpg" alt="Osama Ayman" class="avatar-img w-14 h-14 rounded-full object-cover border border-slate-200 transition duration-300">
                        <span class="avatar-badge hidden absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white bg-[#0071e3] flex items-center justify-center text-[9px] text-white font-bold">✓</span>
                    </div>
                    <div class="text-amber-400 text-xs tracking-wider mb-1">★★★★★</div>
                    <span class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'أ. أسامة أيمن' : 'Osama Ayman' }}</span>
                    <span class="avatar-role text-[11px] text-slate-400 transition-colors">{{ $isAr ? 'Jad Logistics' : 'Jad Logistics' }}</span>
                </div>

                <!-- Avatar 1 -->
                <div id="testimonial-avatar-1" class="testimonial-avatar-item flex flex-col items-center opacity-70 hover:opacity-100 transition duration-300 cursor-pointer" onclick="switchTestimonial(1)">
                    <div class="relative mb-2">
                        <img src="/images/avatars/client_black_jacket.jpg" alt="Mostafa Mano" class="avatar-img w-14 h-14 rounded-full object-cover border border-slate-200 transition duration-300">
                        <span class="avatar-badge hidden absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white bg-[#0071e3] flex items-center justify-center text-[9px] text-white font-bold">✓</span>
                    </div>
                    <div class="text-amber-400 text-xs tracking-wider mb-1">★★★★★</div>
                    <span class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'أ. مصطفى مانو' : 'Mostafa Mano' }}</span>
                    <span class="avatar-role text-[11px] text-slate-400 transition-colors">{{ $isAr ? 'ClickOrder Retail' : 'ClickOrder Retail' }}</span>
                </div>

                <!-- Avatar 2 (Featured / Active Default: Ahmed Maher) -->
                <div id="testimonial-avatar-2" class="testimonial-avatar-item flex flex-col items-center scale-110 z-10 cursor-pointer transition duration-300" onclick="switchTestimonial(2)">
                    <div class="relative mb-2">
                        <img src="/images/avatars/ahmed_maher.jpg" alt="Ahmed Maher" style="border: 2px solid #0071e3;" class="avatar-img w-16 h-16 rounded-full object-cover shadow-lg transition duration-300">
                        <span class="avatar-badge absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white bg-[#0071e3] flex items-center justify-center text-[9px] text-white font-bold">✓</span>
                    </div>
                    <div class="text-amber-400 text-sm tracking-wider mb-1">★★★★★</div>
                    <span class="font-bold text-[14px] text-[#1d1d1f]">{{ $isAr ? 'أ. أحمد ماهر' : 'Ahmed Maher' }}</span>
                    <span style="color: #0071e3;" class="avatar-role text-[11px] font-bold transition-colors">{{ $isAr ? 'مؤسس ورئيس AMC Academy' : 'Founder & CEO, AMC Academy' }}</span>
                </div>

                <!-- Avatar 3 -->
                <div id="testimonial-avatar-3" class="testimonial-avatar-item flex flex-col items-center opacity-70 hover:opacity-100 transition duration-300 cursor-pointer" onclick="switchTestimonial(3)">
                    <div class="relative mb-2">
                        <img src="/images/avatars/client_white_tshirt.jpg" alt="Abdelrahman" class="avatar-img w-14 h-14 rounded-full object-cover border border-slate-200 transition duration-300">
                        <span class="avatar-badge hidden absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white bg-[#0071e3] flex items-center justify-center text-[9px] text-white font-bold">✓</span>
                    </div>
                    <div class="text-amber-400 text-xs tracking-wider mb-1">★★★★★</div>
                    <span class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'أ. عبد الرحمن' : 'Abdelrahman' }}</span>
                    <span class="avatar-role text-[11px] text-slate-400 transition-colors">{{ $isAr ? 'TechMate Solutions' : 'TechMate Solutions' }}</span>
                </div>

                <!-- Avatar 4 -->
                <div id="testimonial-avatar-4" class="testimonial-avatar-item flex flex-col items-center opacity-70 hover:opacity-100 transition duration-300 cursor-pointer" onclick="switchTestimonial(4)">
                    <div class="relative mb-2">
                        <img src="/images/avatars/client_blue_shirt.jpg" alt="Mohmmed Hamed" class="avatar-img w-14 h-14 rounded-full object-cover border border-slate-200 transition duration-300">
                        <span class="avatar-badge hidden absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white bg-[#0071e3] flex items-center justify-center text-[9px] text-white font-bold">✓</span>
                    </div>
                    <div class="text-amber-400 text-xs tracking-wider mb-1">★★★★★</div>
                    <span class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'أ. محمد حامد' : 'Mohmmed Hamed' }}</span>
                    <span class="avatar-role text-[11px] text-slate-400 transition-colors">{{ $isAr ? 'Sampath Group' : 'Sampath Group' }}</span>
                </div>

            </div>

            <!-- Authentic Decorative Curved Dashed Lines -->
            <!-- Left curve: starting with dot below avatars, curving up-left with arrowhead -->
            <div class="absolute -left-2 sm:left-4 md:left-8 lg:left-14 bottom-6 hidden md:block pointer-events-none select-none opacity-60">
                <svg class="w-36 h-36 lg:w-44 lg:h-44 text-[#0071e3]" viewBox="0 0 160 160" fill="none">
                    <circle cx="120" cy="140" r="4.5" fill="#0071e3" />
                    <path d="M120,140 C50,135 25,90 20,25" stroke="#0071e3" stroke-width="2" stroke-dasharray="4 4" />
                    <polyline points="30,22 18,22 20,34" stroke="#0071e3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            <!-- Right curve: starting top-right, curving down-left with arrowhead pointing at Case Studies -->
            <div class="absolute -right-2 sm:right-4 md:right-8 lg:right-14 -bottom-10 hidden md:block pointer-events-none select-none z-10 opacity-60">
                <svg class="w-36 h-36 lg:w-44 lg:h-44 text-[#0071e3]" viewBox="0 0 160 160" fill="none">
                    <path d="M140,20 C145,80 110,130 35,145" stroke="#0071e3" stroke-width="2" stroke-dasharray="4 4" />
                    <polyline points="48,136 33,145 42,157" stroke="#0071e3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 5: OUR RECENT CASE STUDIES (Matching image.png)       -->
    <!-- 3 TWO-TONE SPLIT CARDS (Single square balanced image on left, -->
    <!-- Soft pastel pod on right with authentic project details)     -->
    <!-- ============================================================ -->
    <section id="case-studies" class="py-16 md:py-24 px-6 sm:px-10 bg-white relative">
        <div class="max-w-[1240px] mx-auto">
            
            <!-- Section Header -->
            <div class="text-center max-w-[600px] mx-auto mb-14 relative">
                <!-- Small Center Dot -->
                <div class="w-3 h-3 rounded-full bg-[#0071e3] mx-auto mb-4 opacity-90"></div>
                <span class="text-[14px] text-slate-500 font-medium block">{{ $isAr ? 'أعمالنا الأخيرة' : 'Our recent' }}</span>
                <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight">
                    {{ $isAr ? 'دراسات الحالة والمشاريع' : 'Case studies' }}
                </h2>
            </div>

            <!-- CARD 1: Two-Tone Split (Left pod with single square balanced image, Right pod #f1f4ff) -->
            <div class="mb-10 rounded-3xl overflow-hidden border border-slate-100 shadow-sm flex flex-col lg:flex-row items-stretch">
                <!-- Left Pod: 45% width with single square balanced image -->
                <div style="background-color: #dbe7f6;" class="lg:w-[45%] flex items-center justify-center p-0 overflow-hidden">
                    <img src="/images/portfolio/case_study_amc_square.png" alt="AMC Academy & AMCTasks Platform" class="w-full h-full aspect-square object-cover block">
                </div>

                <!-- Right Pod: 55% width, Background #f1f4ff -->
                <div style="background-color: #f1f4ff;" class="lg:w-[55%] p-8 sm:p-12 flex flex-col justify-between text-start">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span style="background-color: rgba(0, 113, 227, 0.1); color: #0071e3;" class="px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider">
                                AMC ACADEMY
                            </span>
                            <span class="text-[12px] text-slate-500 font-medium">
                                {{ $isAr ? 'أتمتة تسويقية وروبوتات ذكاء اصطناعي' : 'AI Marketing & Chatbot Cloud' }}
                            </span>
                        </div>

                        <h3 class="text-[24px] sm:text-[30px] font-bold text-[#1d1d1f] tracking-tight">
                            {{ $isAr ? 'منظومة AMCTasks.com & AMC Academy للتسويق الرقمي والأتمتة' : 'AMCTasks.com & AMC Academy AI Marketing Cloud' }}
                        </h3>

                        <p class="text-[14px] sm:text-[15px] text-[#515154] leading-[1.75]">
                            {{ $isAr 
                                ? 'شركة مصرية رائدة في التسويق الرقمي وتطوير الأعمال والاستشارات أسسها خبير التسويق الرقمي ورائد الأعمال "أحمد ماهر". قمنا في Musoftware بهندسة وتطوير بنيتها البرمجية ومنصتها الرائدة AMCTasks.com لإدارة حسابات السوشيال ميديا (فيسبوك، تويتر، إنستجرام، تليجرام، فايبر، VK) من لوحة تحكم واحدة، وبرمجة بوتات الرد الذكي الآلي (24/7 Chatbots) على الرسائل والتعليقات، مما قلص تكاليف التشغيل ووفّر فرق خدمة عملاء ضخمة.' 
                                : 'An enterprise marketing and automation cloud engineered for AMC Academy, founded by entrepreneur and marketing expert Ahmed Maher. Musoftware architected and delivered the flagship AMCTasks.com platform—orchestrating multi-channel social management (Facebook, Instagram, Telegram, Twitter, Viber, VK) from a unified dashboard, paired with 24/7 intelligent chatbots that drastically reduced operating costs.' }}
                        </p>

                        <!-- Key Pillars -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2 border-t border-slate-200/50">
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>
                                <span>{{ $isAr ? 'AMCTasks: إدارة السوشيال ميديا بـ Dashboard موحدة' : 'AMCTasks: Unified Social Dashboard' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>
                                <span>{{ $isAr ? 'شات بوت ذكي 24/7 للرد الفوري على العملاء' : '24/7 Automated Comment & Inbox Bots' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>
                                <span>{{ $isAr ? 'AMC Turbo & AMC Social: أتمتة حسابات وبوتات' : 'AMC Turbo & AMC Social Automation' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0071e3] shrink-0"></span>
                                <span>{{ $isAr ? 'أدوات SEO: صناعة المقالات والـ Backlinks' : 'SEO Suite: Article Maker & Backlinks' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex items-center justify-between">
                        <div class="text-[12px] text-slate-500 font-medium">
                            <span class="text-slate-400">{{ $isAr ? 'المؤسس:' : 'Founder:' }}</span>
                            <span class="font-bold text-[#1d1d1f]">{{ $isAr ? 'أحمد ماهر' : 'Ahmed Maher' }}</span>
                        </div>
                        <a href="/portfolio/amc-academy" style="color: #0071e3;" class="font-semibold hover:opacity-80 inline-flex items-center gap-1.5 text-[14px] transition">
                            <span>{{ $isAr ? 'قراءة تقرير دراسة الحالة' : 'Read full case study' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Two-Tone Split (Left pod with single square balanced image, Right pod #f0fbf7) -->
            <div class="mb-10 rounded-3xl overflow-hidden border border-slate-100 shadow-sm flex flex-col lg:flex-row items-stretch">
                <!-- Left Pod: 45% width with single square balanced image -->
                <div style="background-color: #e1f5ee;" class="lg:w-[45%] flex items-center justify-center p-0 overflow-hidden">
                    <img src="/images/portfolio/case_study_bulk_data_square.png" alt="Distributed Bulk Data Automation System" class="w-full h-full aspect-square object-cover block">
                </div>

                <!-- Right Pod: 55% width, Background #f0fbf7 -->
                <div style="background-color: #f0fbf7;" class="lg:w-[55%] p-8 sm:p-12 flex flex-col justify-between text-start">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span style="background-color: rgba(16, 185, 129, 0.12); color: #059669;" class="px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider">
                                DISTRIBUTED AUTOMATION
                            </span>
                            <span class="text-[12px] text-slate-500 font-medium">
                                {{ $isAr ? 'أنظمة موزعة وبيانات ضخمة' : 'Big Data & RPA Engine' }}
                            </span>
                        </div>

                        <h3 class="text-[24px] sm:text-[30px] font-bold text-[#1d1d1f] tracking-tight">
                            {{ $isAr ? 'منظومة أتمتة ومعالجة البيانات المجمعة الذكية' : 'Distributed Bulk Data & Multi-Worker Automation Engine' }}
                        </h3>

                        <p class="text-[14px] sm:text-[15px] text-[#515154] leading-[1.75]">
                            {{ $isAr 
                                ? 'بنية تحتية موزعة (Master-Worker Architecture) مبتكرة لأتمتة فحص ومزامنة ملايين السجلات عبر بوابات إلكترونية متعددة في دقائق معدودة. يتكون النظام من عقل مدبر مركزي مدعوم بمحرك Elasticsearch لمعالجة وتصفية ملفات البيانات الضخمة في أجزاء من الثانية، متصل بشبكة روبوتات سطح مكتب ذكية (C# / .NET) تحاكي السلوك البشري ومزودة بتبديل ذكي للبروكسي (Proxy Rotation) لمنع الحظر مع نظام قفل متقدم للتزامن دون أي تكرار.' 
                                : 'A distributed Master-Worker infrastructure engineered to query, validate, and synchronize millions of records across multi-portal environments in minutes. Features a central orchestration dashboard powered by Elasticsearch for sub-second record querying, coordinating concurrent C# .NET desktop workers equipped with intelligent proxy rotation, anti-rate-limiting failovers, and transactional task-locking.' }}
                        </p>

                        <!-- Key Pillars -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2 border-t border-slate-200/50">
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #059669;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'بنية موزعة Master-Worker لمعالجة الملايين' : 'Distributed Master-Worker Cluster' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #059669;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'تقنية Smart Proxy لمنع وتجاوز الحظر' : 'Smart Proxy Rotation & Rate Bypass' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #059669;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'محرك Elasticsearch للبحث في أجزاء الثانية' : 'Elasticsearch: Sub-second Search' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #059669;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'قفل التزامن وتنبيهات فورية عبر Telegram' : 'Concurrency Lock & Telegram Alerts' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex items-center justify-between">
                        <div class="text-[12px] text-slate-500 font-medium">
                            <span class="text-slate-400">{{ $isAr ? 'البنية الهندسية:' : 'Architecture:' }}</span>
                            <span class="font-bold text-[#1d1d1f]">{{ $isAr ? 'نظام موزع (Distributed System)' : 'Distributed Cluster' }}</span>
                        </div>
                        <a href="/portfolio/bulk-data-automation" style="color: #059669;" class="font-semibold hover:opacity-80 inline-flex items-center gap-1.5 text-[14px] transition">
                            <span>{{ $isAr ? 'قراءة تقرير دراسة الحالة' : 'Read full case study' }}</span>
                            <span class="rtl:rotate-180">➔</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD 3: WhatsApp Oman Alerts & Campaign System -->
            <div class="rounded-3xl overflow-hidden border border-slate-100 shadow-sm flex flex-col lg:flex-row items-stretch">
                <!-- Left Pod: 45% width with square image -->
                <div style="background-color: #0a1628;" class="lg:w-[45%] flex items-center justify-center p-0 overflow-hidden">
                    <img src="/images/portfolio/case_study_whatsapp_oman_square.jpg" alt="Intelligent WhatsApp Alert and Campaign System" class="w-full h-full aspect-square object-cover block">
                </div>

                <!-- Right Pod: 55% width -->
                <div style="background-color: #f0fdf4;" class="lg:w-[55%] p-8 sm:p-12 flex flex-col justify-between text-start">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span style="background-color: rgba(37,211,102,0.12); color: #16a34a;" class="px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-wider">
                                WHATSAPP BUSINESS API
                            </span>
                            <span class="text-[12px] text-slate-500 font-medium">
                                {{ $isAr ? 'أنظمة التواصل والتسويق الآلي' : 'Communication & Marketing Automation' }}
                            </span>
                        </div>

                        <h3 class="text-[24px] sm:text-[30px] font-bold text-[#1d1d1f] tracking-tight">
                            {{ $isAr ? 'نظام التنبيهات الذكي والحملات الإعلانية الرسمية عبر واتساب' : 'Intelligent WhatsApp Alert & Official Campaign System' }}
                        </h3>

                        <p class="text-[14px] sm:text-[15px] text-[#515154] leading-[1.75]">
                            {{ $isAr
                                ? 'منظومتان برمجيتان متكاملتان لشركة ناشئة في عُمان: محرك تنبيهات ذكي يطلق رسائل واتساب آنية في أقل من ثانية مرتبطاً بأحداث الباك-إند، ومنصة حملات إعلانية رسمية عبر WhatsApp Business API تضمن صفر حظر وأعلى معدلات تسليم.'
                                : 'Two integrated systems for an Oman-based company: an event-driven alert engine firing real-time branded WhatsApp notifications in under one second, and an official WhatsApp Business API campaign platform ensuring zero number bans and maximum delivery rates.' }}
                        </p>

                        <!-- Key Pillars -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2 border-t border-slate-200/50">
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #25D366;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'محرك تنبيهات فوري مرتبط بأحداث الباك-إند' : 'Event-Driven Real-Time Alert Engine' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #25D366;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'تكامل رسمي مع WhatsApp Business API' : 'Official WhatsApp Business API Integration' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #25D366;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'حملات إعلانية رسمية بلا خطر حظر الأرقام' : 'Approved Campaigns with Zero Ban Risk' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] sm:text-[13px] text-[#1d1d1f] font-medium">
                                <span style="background-color: #25D366;" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                <span>{{ $isAr ? 'معدل قراءة 98% مقارنة بـ SMS والإيميل' : '98% Read Rate vs. SMS & Email' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex items-center justify-between">
                        <div class="text-[12px] text-slate-500 font-medium">
                            <span class="text-slate-400">{{ $isAr ? 'النظام:' : 'System:' }}</span>
                            <span class="font-bold text-[#1d1d1f]">{{ $isAr ? 'منظومتان متكاملتان' : 'Two Integrated Platforms' }}</span>
                        </div>
                        <a href="/portfolio/whatsapp-oman-alerts" style="color: #16a34a;" class="font-semibold hover:opacity-80 inline-flex items-center gap-1.5 text-[14px] transition">
                            <span>{{ $isAr ? 'قراءة تقرير دراسة الحالة' : 'Read full case study' }}</span>
                            <span class="rtl:rotate-180">&#10148;</span>
                        </a>
                    </div>
                </div>
            </div>


            <!-- Bottom Link: Read more case studies (Matching image.png) -->
            <div class="mt-8 text-end">
                <a href="/portfolio" style="color: #0071e3;" class="font-semibold hover:opacity-80 inline-flex items-center gap-1.5 text-[15px] transition">
                    <span>{{ $isAr ? 'استعراض كل دراسات الحالة' : 'Read more case studies' }}</span>
                    <span class="rtl:rotate-180">➔</span>
                </a>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 6: WAY OF BUILDING GREAT SOFTWARE (3 Alternating Rows)-->
    <!-- With Decorative Corner Circles Behind Photos                  -->
    <!-- ============================================================ -->
    <section class="py-16 md:py-24 px-6 sm:px-10 bg-[#f5f5f7]">
        <div class="max-w-[1240px] mx-auto">
            
            <!-- Section Header -->
            <div class="text-center max-w-[700px] mx-auto mb-16">
                <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mx-auto mb-3"></div>
                <span class="text-[14px] text-slate-500 font-medium block">{{ $isAr ? 'منهجية البناء والتحول الرقمي' : 'Way of building' }}</span>
                <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight mb-3">
                    {{ $isAr ? 'برمجيات عظيمة لكيانات كبرى' : 'Great Software' }}
                </h2>
                <p class="text-[15px] text-[#515154] leading-relaxed">
                    {{ $isAr 
                        ? 'قصة شراكتنا الهندسية الكاملة مع كيان AMC Academy ورائد الأعمال أحمد ماهر — من هندسة منصات الأتمتة السحابية وإدارة الشات بوت إلى أدوات السيو والمنصات التابعة.' 
                        : 'Our end-to-end engineering partnership with AMC Academy & marketing visionary Ahmed Maher — from multi-channel cloud automation to AI SEO engines.' }}
                </p>
            </div>

            <!-- ROW 1: AMCTasks & AMC Social (Text Left, Photo Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center mb-24">
                <div class="lg:col-span-6 space-y-4 text-start">
                    <span style="background-color: #e8f2fe; color: #0071e3;" class="text-[12px] font-bold px-3 py-1 rounded-full inline-block">
                        {{ $isAr ? 'منصة AMCTasks & AMC Social' : 'AMCTasks.com & AMC Social Platform' }}
                    </span>
                    <h3 class="text-[24px] sm:text-[28px] font-bold text-[#1d1d1f] tracking-tight leading-snug">
                        {{ $isAr ? 'أتمتة السوشيال ميديا وبناء الشات بوت الذكي 24/7' : 'Social Media Automation & Intelligent 24/7 Chatbots' }}
                    </h3>
                    <p class="text-[15px] text-[#515154] leading-[1.7]">
                        {{ $isAr 
                            ? 'قمنا بتصميم وهندسة وتطوير منصة AMCTasks.com و AMC Social كمنظومة سحابية متكاملة تتيح للمسوقين والشركات إدارة كافة قنوات التواصل (فيسبوك، تويتر، إنستجرام، تليجرام، فايبر، وشبكة VK) من لوحة تحكم مركزية واحدة موحدة.' 
                            : 'We engineered AMCTasks.com and AMC Social as a unified multi-channel marketing system, empowering businesses to manage Facebook, Instagram, Twitter, Telegram, Viber, and VK from one centralized dashboard.' }}
                    </p>
                    <p class="text-[15px] text-[#515154] leading-[1.7]">
                        {{ $isAr 
                            ? 'ابتكرنا محرك شات بوت ذكي يقوم بالرد اللحظي والآلي على رسائل وكومنتات العملاء على مدار 24 ساعة، مما خفض تكاليف التشغيل بنسبة قياسية وقام بدور فريق خدمة عملاء متكامل يغلق المبيعات تلقائياً، مع جدولة المنشورات ومتابعة تفاعل الجماهير بأعلى موثوقية وأمان (Legit & Verified).' 
                            : 'We built a high-performance visual Chatbot builder delivering instantaneous automated replies to DMs and comments 24/7. This reduced operating costs dramatically and replaced large support teams while automating sales conversion.' }}
                    </p>
                    <!-- Clean White Blockquote with Apple Blue border -->
                    <blockquote style="background-color: #ffffff; border-left: 3px solid #0071e3;" class="p-4 rounded-xl rtl:border-l-0 rtl:border-r-3 text-[14px] text-[#1d1d1f] italic shadow-2xs">
                        {{ $isAr 
                            ? '"منصة AMCTasks أحدثت طفرة لآلاف المسوقين في الوطن العربي — إدارة مركزية للشات بوت والردود الآلية جعلت خدمة العملاء فورية ومبيعاتنا لا تتوقف طوال 24 ساعة."' 
                            : '"AMCTasks became an indispensable marketing engine — automated chatbots and unified multichannel control saved massive operating costs and unlocked non-stop sales."' }}
                    </blockquote>
                    <!-- Author info -->
                    <div class="flex items-center gap-3 pt-1">
                        <img src="/images/clients/amcacademy.png" alt="AMC Academy" class="w-10 h-10 rounded-full object-contain p-1 border border-slate-200 bg-white">
                        <div>
                            <div class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'أ. أحمد ماهر' : 'Ahmed Maher' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $isAr ? 'مؤسس AMC Academy وخبير التسويق الرقمي' : 'Founder, AMC Academy & Marketing Expert' }}</div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 flex justify-center">
                    <div class="relative max-w-[480px]">
                        <!-- Subtle Decorative Corner Circles -->
                        <div style="background-color: #0071e3; top: -20px; left: -20px;" class="absolute w-24 h-24 rounded-full z-0 opacity-15"></div>
                        <div style="background-color: #0071e3; bottom: -12px; left: 50%; transform: translateX(-50%);" class="absolute w-8 h-8 rounded-full z-0 opacity-25"></div>
                        
                        <div class="relative z-10 rounded-3xl overflow-hidden shadow-xl border-4 border-white">
                            <img src="/images/building/team_scale_clean.jpg" alt="{{ $isAr ? 'منصة AMCTasks السحابية' : 'AMCTasks Cloud Platform' }}" class="w-full h-auto object-cover block">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: AMC Turbo & SEO Suite (Photo Left, Text Right - REVERSED) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center mb-24">
                <div class="lg:col-span-6 flex justify-center">
                    <div class="relative max-w-[480px]">
                        <!-- Subtle Decorative Circles -->
                        <div style="background-color: #0071e3; bottom: -20px; left: -20px;" class="absolute w-24 h-24 rounded-full z-0 opacity-15"></div>
                        <div style="background-color: #0071e3; top: -14px; right: -14px;" class="absolute w-10 h-10 rounded-full z-0 opacity-25"></div>
                        
                        <div class="relative z-10 rounded-3xl overflow-hidden shadow-xl border-4 border-white">
                            <img src="/images/building/code_engineers_clean.jpg" alt="{{ $isAr ? 'تطوير أدوات AMC Turbo والـ SEO' : 'AMC Turbo & SEO Development' }}" class="w-full h-auto object-cover block">
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 space-y-4 text-start">
                    <span style="background-color: #e8f2fe; color: #0071e3;" class="text-[12px] font-bold px-3 py-1 rounded-full inline-block">
                        {{ $isAr ? 'محرك AMC Turbo وأدوات السيو والـ AI' : 'AMC Turbo & AI SEO Automation Suite' }}
                    </span>
                    <h3 class="text-[24px] sm:text-[28px] font-bold text-[#1d1d1f] tracking-tight leading-snug">
                        {{ $isAr ? 'محرك AMC Turbo وصناعة المقالات والباك لينك والتحليل التنافسي' : 'AMC Turbo, Article Maker & Algorithmic Backlinks' }}
                    </h3>
                    <p class="text-[15px] text-[#515154] leading-[1.7]">
                        {{ $isAr 
                            ? 'طورنا نظام AMC Turbo العملاق لإدارة وتفعيل حسابات واتساب وتليجرام وفيسبوك بقدرات استثنائية لإطلاق البوتات الذكية والتفاعل اللحظي مع الجماهير المستهدفة.' 
                            : 'We developed AMC Turbo, a high-throughput engine for managing accounts across WhatsApp, Telegram, and Facebook with sub-second automated chat flows.' }}
                    </p>
                    <p class="text-[15px] text-[#515154] leading-[1.7]">
                        {{ $isAr 
                            ? 'بجانب حزمة برمجيات الـ SEO الرائدة: برنامج AMC Article Maker لإنشاء وتوليد المقالات الاحترافية، وبرنامج AMC Backlinks لبناء الروابط الخلفية واعتلاء نتائج البحث الأولى في جوجل، وأداة دراسة محتوى فيسبوك التي يعتمد عليها عشرات المسوقين وصناع المحتوى على اليوتيوب لاستخراج أفكار المنافسين وتحقيق أقصى وصول إعلاني.' 
                            : 'Engineered an end-to-end SEO automation toolkit: AMC Article Maker for algorithmic content drafting, AMC Backlinks for automated link distribution to dominate Google rankings, and intelligent Facebook content analyzers featured across YouTube tutorials.' }}
                    </p>
                    <!-- Clean White Blockquote with Apple Blue border -->
                    <blockquote style="background-color: #ffffff; border-left: 3px solid #0071e3;" class="p-4 rounded-xl rtl:border-l-0 rtl:border-r-3 text-[14px] text-[#1d1d1f] italic shadow-2xs">
                        {{ $isAr 
                            ? '"برامج AMC أصبحت المرجع الأول للمسوقين وصناع المحتوى على اليوتيوب والمجتمعات التقنية للتصدر في جوجل وإدارة حملات الواتساب والتليجرام باحترافية."' 
                            : '"The AMC software suite became the gold standard across YouTube tutorials and SEO communities for dominating Google rankings and automating massive outreach."' }}
                    </blockquote>
                    <!-- Author info -->
                    <div class="flex items-center gap-3 pt-1">
                        <img src="/images/clients/amcacademy.png" alt="AMC Academy" class="w-10 h-10 rounded-full object-contain p-1 border border-slate-200 bg-white">
                        <div>
                            <div class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'أكاديمية AMC — قطاع الحلول البرمجية' : 'AMC Academy — Software Solutions' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $isAr ? 'Marketing Automation & SEO Tools' : 'Marketing Automation & SEO Tools' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 3: Ecosystem Platforms (Text Left, Photo Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center">
                <div class="lg:col-span-6 space-y-4 text-start">
                    <span style="background-color: #e8f2fe; color: #0071e3;" class="text-[12px] font-bold px-3 py-1 rounded-full inline-block">
                        {{ $isAr ? 'منظومة المنصات المتخصصة' : 'Ecosystem SaaS Platforms' }}
                    </span>
                    <h3 class="text-[24px] sm:text-[28px] font-bold text-[#1d1d1f] tracking-tight leading-snug">
                        {{ $isAr ? 'بناء وإطلاق منصات Whatscontact و Salesup و Kbdny' : 'Deploying Whatscontact.com, Salesup.me & Kbdny.com' }}
                    </h3>
                    <p class="text-[15px] text-[#515154] leading-[1.7]">
                        {{ $isAr 
                            ? 'امتدت شراكتنا الهندسية الشاملة مع AMC Academy لتشمل تصميم وتطوير وتشغيل عدة منصات رقمية متخصصة ومستقلة: منصة Whatscontact.com للربط السحابي المباشر مع واجهات واتساب، ومنصة Salesup.me لتسريع المبيعات وإدارة قنوات التحويل (Funnels)، ومنصة Kbdny.com لحلول التجارة الرقمية ودعم رواد الأعمال.' 
                            : 'Our extensive engineering engagement with AMC Academy delivered dedicated platforms: Whatscontact.com for enterprise WhatsApp Cloud API integration, Salesup.me for sales acceleration and funnels, and Kbdny.com for digital commerce.' }}
                    </p>
                    <p class="text-[15px] text-[#515154] leading-[1.7]">
                        {{ $isAr 
                            ? 'جميع هذه المواقع والبرامج تم بناؤها وتسليمها بسورس كود كامل ومملوك 100% للعميل، على بنية تحتية سحابية مستقلة صُممت لتتحمل مئات الآلاف من طلبات الـ Webhooks والرسائل اللحظية دون أي رسوم احتكارية أو توقف، مما جعل كيان AMC منافساً قوياً للأدوات العالمية في أتمتة التسويق.' 
                            : 'Each platform was engineered with 100% source code ownership, decoupled architecture, and dedicated high-concurrency infrastructure handling millions of webhooks without third-party lock-in — positioning AMC as a market leader.' }}
                    </p>
                    <!-- Clean White Blockquote with Apple Blue border -->
                    <blockquote style="background-color: #ffffff; border-left: 3px solid #0071e3;" class="p-4 rounded-xl rtl:border-l-0 rtl:border-r-3 text-[14px] text-[#1d1d1f] italic shadow-2xs">
                        {{ $isAr 
                            ? '"فريق Musoftware تولى بناء كافة مواقع وبرامج الكيان من الألف إلى الياء. دقة هندسية لا تضاهى، والتزام استثنائي بتسليم الكود الكامل وتوفير بنية سحابية تنافس المنصات العالمية."' 
                            : '"Musoftware engineered AMC\'s entire software ecosystem from end to end. Flawless execution, total code ownership, and world-class multi-tenant infrastructure."' }}
                    </blockquote>
                    <!-- Author info -->
                    <div class="flex items-center gap-3 pt-1">
                        <img src="/images/clients/amcacademy.png" alt="AMC Academy" class="w-10 h-10 rounded-full object-contain p-1 border border-slate-200 bg-white">
                        <div>
                            <div class="font-bold text-[13px] text-[#1d1d1f]">{{ $isAr ? 'منظومة برمجيات AMC Academy' : 'AMC Academy Technology Ecosystem' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $isAr ? 'شراكة هندسية مستمرة وتطوير متواصل' : 'Strategic Engineering Partnership' }}</div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 flex justify-center">
                    <div class="relative max-w-[480px]">
                        <!-- Subtle Decorative Corner Circle -->
                        <div style="background-color: #0071e3; top: -20px; right: -20px;" class="absolute w-24 h-24 rounded-full z-0 opacity-15"></div>
                        
                        <div class="relative z-10 rounded-3xl overflow-hidden shadow-xl border-4 border-white">
                            <img src="/images/building/cloud_scale_clean.jpg" alt="{{ $isAr ? 'بنية سحابية قابلة للتوسع' : 'Cloud Architecture & Scaling' }}" class="w-full h-auto object-cover block">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 7: OUR DESIGN AND DEVELOPMENT APPROACH (6 Cards Grid)-->
    <!-- Matching image.png layout, authentic icons & exact highlights -->
    <!-- ============================================================ -->
    <section class="py-16 md:py-24 px-6 sm:px-10 bg-white border-y border-slate-100">
        <div class="max-w-[1200px] mx-auto">
            
            <!-- Section Header -->
            <div class="text-center max-w-[600px] mx-auto mb-14">
                <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mx-auto mb-3"></div>
                <span class="text-[15px] text-slate-500 font-normal block mb-1">
                    {{ $isAr ? 'منهجيتنا في' : 'Our design and' }}
                </span>
                <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight">
                    {{ $isAr ? 'التصميم والتطوير الهندسي' : 'development approach' }}
                </h2>
            </div>

            <!-- 6 Feature Cards (Matching image.png) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Card 1: UX Driven Engineering (Rocket icon) -->
                <div class="p-7 sm:p-8 rounded-2xl bg-[#fbfbfd] border border-slate-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-200 flex items-start gap-5">
                    <img src="/images/approach/approach_rocket.png" alt="UX Driven Engineering" class="w-[54px] h-[54px] rounded-[14px] shrink-0 object-contain shadow-sm" />
                    <div>
                        <h4 class="text-[16px] sm:text-[17px] font-bold text-[#1d1d1f] mb-2">
                            {{ $isAr ? 'هندسة مدفوعة بتجربة المستخدم (UX)' : 'UX Driven Engineering' }}
                        </h4>
                        <p class="text-[13px] text-slate-500 leading-relaxed">
                            @if($isAr)
                                على عكس الشركات التقليدية، نحن <span class="font-semibold text-[#0071e3]">شركة تركز أولاً على تجربة المستخدم (UX)</span>. يقود مصممو واجهات الاستخدام العمل لضمان تحويل التجارب المرئية بدقة إلى كود فائق الأداء.
                            @else
                                Unlike other companies, we are a <span class="font-semibold text-[#0071e3]">UX first</span> development company. Projects are driven by designers and they make sure design and experiences translate to code.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Card 2: Developing Shared Understanding (Code icon) -->
                <div class="p-7 sm:p-8 rounded-2xl bg-[#fbfbfd] border border-slate-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-200 flex items-start gap-5">
                    <img src="/images/approach/approach_code.png" alt="Developing Shared Understanding" class="w-[54px] h-[54px] rounded-[14px] shrink-0 object-contain shadow-sm" />
                    <div>
                        <h4 class="text-[16px] sm:text-[17px] font-bold text-[#1d1d1f] mb-2">
                            {{ $isAr ? 'بناء رؤية وفهم تقني مشترك' : 'Developing Shared Understanding' }}
                        </h4>
                        <p class="text-[13px] text-slate-500 leading-relaxed">
                            @if($isAr)
                                على عكس الشركات التقليدية، نحن <span class="font-semibold text-[#0071e3]">شركة تركز على الفهم المشترك</span>. يقود المصممون والمهندسون التخطيط لضمان توافق كل سطر برمجي مع أهدافك التشغيلية.
                            @else
                                Unlike other companies, we are a <span class="font-semibold text-[#0071e3]">UX first</span> development company. Projects are driven by designers and they make sure design and experiences translate to code.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Card 3: Proven Experience and Expertise (Pulse icon) -->
                <div class="p-7 sm:p-8 rounded-2xl bg-[#fbfbfd] border border-slate-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-200 flex items-start gap-5">
                    <img src="/images/approach/approach_pulse.png" alt="Proven Experience and Expertise" class="w-[54px] h-[54px] rounded-[14px] shrink-0 object-contain shadow-sm" />
                    <div>
                        <h4 class="text-[16px] sm:text-[17px] font-bold text-[#1d1d1f] mb-2">
                            {{ $isAr ? 'خبرة هندسية مثبتة بالنتائج' : 'Proven Experience and Expertise' }}
                        </h4>
                        <p class="text-[13px] text-slate-500 leading-relaxed">
                            @if($isAr)
                                على عكس الشركات التقليدية، نحن <span class="font-semibold text-[#0071e3]">نمتلك خبرة عملية مثبتة</span>. عشرات الأنظمة السحابية والحلول المؤسسية التي تخدم آلاف المستخدمين يومياً بأعلى معدلات الاستقرار.
                            @else
                                Unlike other companies, we are a <span class="font-semibold text-[#0071e3]">UX first</span> development company. Projects are driven by designers and they make sure design and experiences translate to code.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Card 4: Security & Intellectual Property (Shield icon) -->
                <div class="p-7 sm:p-8 rounded-2xl bg-[#fbfbfd] border border-slate-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-200 flex items-start gap-5">
                    <img src="/images/approach/approach_shield.png" alt="Security & Intellectual Property" class="w-[54px] h-[54px] rounded-[14px] shrink-0 object-contain shadow-sm" />
                    <div>
                        <h4 class="text-[16px] sm:text-[17px] font-bold text-[#1d1d1f] mb-2">
                            {{ $isAr ? 'الأمان وحماية الملكية الفكرية (IP)' : 'Security & Intellectual Property (IP)' }}
                        </h4>
                        <p class="text-[13px] text-slate-500 leading-relaxed">
                            @if($isAr)
                                على عكس الشركات التقليدية، نضمن لك <span class="font-semibold text-[#0071e3]">حماية تامة للملكية الفكرية والبيانات</span>. ملكية حصرية 100% للسورس كود وقواعد البيانات دون أي تبعية.
                            @else
                                Unlike other companies, we are a <span class="font-semibold text-[#0071e3]">UX first</span> development company. Projects are driven by designers and they make sure design and experiences translate to code.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Card 5: Code Reviews (Checkmark icon) -->
                <div class="p-7 sm:p-8 rounded-2xl bg-[#fbfbfd] border border-slate-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-200 flex items-start gap-5">
                    <img src="/images/approach/approach_check.png" alt="Code Reviews" class="w-[54px] h-[54px] rounded-[14px] shrink-0 object-contain shadow-sm" />
                    <div>
                        <h4 class="text-[16px] sm:text-[17px] font-bold text-[#1d1d1f] mb-2">
                            {{ $isAr ? 'مراجعة الكود الصارمة (Code Reviews)' : 'Code Reviews' }}
                        </h4>
                        <p class="text-[13px] text-slate-500 leading-relaxed">
                            @if($isAr)
                                على عكس الشركات التقليدية، نطبق <span class="font-semibold text-[#0071e3]">مراجعة كود صارمة متعددة المراحل</span>. فحص دقيق لمنع أي ثغرات أو تسريبات للذاكرة قبل اعتماد النسخ.
                            @else
                                Unlike other companies, we are a <span class="font-semibold text-[#0071e3]">UX first</span> development company. Projects are driven by designers and they make sure design and experiences translate to code.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Card 6: Quality Assurance & Testing (Lock icon) -->
                <div class="p-7 sm:p-8 rounded-2xl bg-[#fbfbfd] border border-slate-200/70 shadow-[0_2px_12px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.04)] transition-all duration-200 flex items-start gap-5">
                    <img src="/images/approach/approach_lock.png" alt="Quality Assurance & Testing" class="w-[54px] h-[54px] rounded-[14px] shrink-0 object-contain shadow-sm" />
                    <div>
                        <h4 class="text-[16px] sm:text-[17px] font-bold text-[#1d1d1f] mb-2">
                            {{ $isAr ? 'ضمان الجودة واختبارات الأداء' : 'Quality Assurance & Testing' }}
                        </h4>
                        <p class="text-[13px] text-slate-500 leading-relaxed">
                            @if($isAr)
                                على عكس الشركات التقليدية، نخضع الأنظمة <span class="font-semibold text-[#0071e3]">لاختبارات جودة وضغط واقعية</span>. محاكاة كاملة لأحمال الذروة لضمان أداء فائق وتجربة مستخدم مستقرة.
                            @else
                                Unlike other companies, we are a <span class="font-semibold text-[#0071e3]">UX first</span> development company. Projects are driven by designers and they make sure design and experiences translate to code.
                            @endif
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECTION 8: OUR TECH STACK (Interactive Category Tabs + Logos) -->
    <!-- Matching image.png exactly with real official logos           -->
    <!-- ============================================================ -->
    <section style="background-color: #f5f5f7;" class="py-16 md:py-24 px-6 sm:px-10 border-y border-slate-200/60" id="tech-stack-section">
        <div class="max-w-[1100px] mx-auto text-center">
            
            <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mx-auto mb-3"></div>
            <span class="text-[14px] text-slate-500 font-medium block">{{ $isAr ? 'حزمتنا البرمجية' : 'Our' }}</span>
            <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight mb-8">
                {{ $isAr ? 'التقنيات التي نبني بها' : 'Tech Stack' }}
            </h2>

            <!-- Category Tabs (Interactive switching matching image.png) -->
            <div class="flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-[15px] font-medium border-b border-slate-200 pb-4 mb-14">
                <button type="button" onclick="switchTechTab('backend')" id="tab-btn-backend" class="tech-tab-btn font-bold pb-4 -mb-4 transition-all duration-200 cursor-pointer" style="color: #0071e3; border-bottom: 2px solid #0071e3;">Backend</button>
                <button type="button" onclick="switchTechTab('frontend')" id="tab-btn-frontend" class="tech-tab-btn font-medium pb-4 -mb-4 text-slate-500 hover:text-slate-900 transition-all duration-200 cursor-pointer" style="border-bottom: 2px solid transparent;">Frontend</button>
                <button type="button" onclick="switchTechTab('databases')" id="tab-btn-databases" class="tech-tab-btn font-medium pb-4 -mb-4 text-slate-500 hover:text-slate-900 transition-all duration-200 cursor-pointer" style="border-bottom: 2px solid transparent;">Databases</button>
                <button type="button" onclick="switchTechTab('cms')" id="tab-btn-cms" class="tech-tab-btn font-medium pb-4 -mb-4 text-slate-500 hover:text-slate-900 transition-all duration-200 cursor-pointer" style="border-bottom: 2px solid transparent;">CMS</button>
                <button type="button" onclick="switchTechTab('cloudtesting')" id="tab-btn-cloudtesting" class="tech-tab-btn font-medium pb-4 -mb-4 text-slate-500 hover:text-slate-900 transition-all duration-200 cursor-pointer" style="border-bottom: 2px solid transparent;">CloudTesting</button>
                <button type="button" onclick="switchTechTab('devops')" id="tab-btn-devops" class="tech-tab-btn font-medium pb-4 -mb-4 text-slate-500 hover:text-slate-900 transition-all duration-200 cursor-pointer" style="border-bottom: 2px solid transparent;">DevOps</button>
            </div>

            <!-- Tab Panels (Real Official Logos from image.png) -->
            <div class="min-h-[170px] flex items-center justify-center">
                
                <!-- Panel 1: Backend (Default Active - Exactly matching image.png) -->
                <div id="tech-panel-backend" class="tech-panel space-y-10 w-full max-w-[950px] mx-auto transition-all duration-300">
                    <!-- Row 1: Node, PHP, MySQL, Java, .NET -->
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/backend/nodejs.png" alt="Node.js" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/php.png" alt="PHP" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/mysql.png" alt="MySQL" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/java.png" alt="Java" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/dotnet.png" alt=".NET Core" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                    <!-- Row 2: Python, Rails, Go, MongoDB -->
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/backend/python.png" alt="Python" class="h-9 sm:h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/rails.png" alt="Ruby on Rails" class="h-9 sm:h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/go.png" alt="Go Lang" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/mongodb.png" alt="MongoDB" class="h-9 sm:h-11 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                </div>

                <!-- Panel 2: Frontend -->
                <div id="tech-panel-frontend" class="tech-panel space-y-10 w-full max-w-[950px] mx-auto hidden transition-all duration-300">
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/frontend/react.svg" alt="React" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/frontend/nextjs.svg" alt="Next.js" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/frontend/vue.svg" alt="Vue.js" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/frontend/angular.svg" alt="Angular" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/frontend/typescript.svg" alt="TypeScript" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/frontend/flutter.svg" alt="Flutter" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/frontend/tailwind.svg" alt="Tailwind CSS" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/frontend/vite.svg" alt="Vite" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                </div>

                <!-- Panel 3: Databases -->
                <div id="tech-panel-databases" class="tech-panel space-y-10 w-full max-w-[950px] mx-auto hidden transition-all duration-300">
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/databases/postgresql.svg" alt="PostgreSQL" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/backend/mysql.png" alt="MySQL" class="h-10 sm:h-12 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/databases/redis.svg" alt="Redis" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/backend/mongodb.png" alt="MongoDB" class="h-9 sm:h-11 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/databases/sqlite.svg" alt="SQLite" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/databases/supabase.svg" alt="Supabase" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                </div>

                <!-- Panel 4: CMS -->
                <div id="tech-panel-cms" class="tech-panel space-y-10 w-full max-w-[950px] mx-auto hidden transition-all duration-300">
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/cms/wordpress.svg" alt="WordPress" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/cms/filament.svg" alt="Filament" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/cms/strapi.svg" alt="Strapi" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/cms/shopify.svg" alt="Shopify" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/cms/sanity.svg" alt="Sanity" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                </div>

                <!-- Panel 5: CloudTesting -->
                <div id="tech-panel-cloudtesting" class="tech-panel space-y-10 w-full max-w-[950px] mx-auto hidden transition-all duration-300">
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/cloudtesting/aws.svg" alt="AWS" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/cloudtesting/docker.svg" alt="Docker" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/cloudtesting/playwright.svg" alt="Playwright" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/cloudtesting/pest.svg" alt="Pest PHP" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/cloudtesting/postman.svg" alt="Postman" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                </div>

                <!-- Panel 6: DevOps -->
                <div id="tech-panel-devops" class="tech-panel space-y-10 w-full max-w-[950px] mx-auto hidden transition-all duration-300">
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/devops/kubernetes.svg" alt="Kubernetes" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/devops/terraform.svg" alt="Terraform" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/devops/cloudflare.svg" alt="Cloudflare" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14">
                        <img src="/images/tech/cloudtesting/docker.svg" alt="Docker" class="h-10 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/devops/nginx.svg" alt="NGINX" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                        <img src="/images/tech/devops/linux.svg" alt="Linux" class="h-9 w-auto object-contain hover:scale-110 transition duration-200">
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Interactive Tab Switcher Script -->
    <script>
        function switchTechTab(category) {
            const tabs = ['backend', 'frontend', 'databases', 'cms', 'cloudtesting', 'devops'];
            tabs.forEach(tab => {
                const btn = document.getElementById('tab-btn-' + tab);
                const panel = document.getElementById('tech-panel-' + tab);
                if (tab === category) {
                    btn.style.color = '#0071e3';
                    btn.style.borderBottom = '2px solid #0071e3';
                    btn.classList.add('font-bold');
                    btn.classList.remove('font-medium', 'text-slate-500');
                    if (panel) {
                        panel.classList.remove('hidden');
                    }
                } else {
                    btn.style.color = '';
                    btn.style.borderBottom = '2px solid transparent';
                    btn.classList.remove('font-bold');
                    btn.classList.add('font-medium', 'text-slate-500');
                    if (panel) {
                        panel.classList.add('hidden');
                    }
                }
            });
        }
    </script>

    <!-- ============================================================ -->
    <!-- SECTION 9: HOW DEVELOPMENT WORKS (Timeline Pipeline)         -->
    <!-- Horizontal pink line with Trophy + alternating staggered cards -->
    <!-- Matching image.png                                            -->
    <!-- ============================================================ -->
    <section id="how-it-works" class="py-16 md:py-24 px-6 sm:px-10 bg-white relative overflow-hidden">
        <div class="max-w-[1240px] mx-auto text-center">
            
            <!-- Section Header -->
            <div class="max-w-[600px] mx-auto mb-16">
                <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mx-auto mb-3"></div>
                <span class="text-[15px] text-slate-500 font-normal block">{{ $isAr ? 'كيف يتم التطوير' : 'How development' }}</span>
                <h2 class="text-[32px] sm:text-[36px] font-bold text-[#1d1d1f] tracking-tight">
                    {{ $isAr ? 'من خلال منظومة Musoftware' : 'through Musoftware works' }}
                </h2>
            </div>

            <!-- DESKTOP TIMELINE (Staggered Layout: 1, 3, 5 Top | 2, 4, 6 Bottom | Center Line with Ticks & Trophy) -->
            <div class="hidden lg:block w-full select-none" dir="ltr" style="max-width: 1140px; margin: 0 auto; position: relative;">
                
                <!-- TOP ROW (Steps 1, 3, 5) -->
                <div style="position: relative; width: 100%; height: 145px;">
                    <!-- Step 1 -->
                    <div style="position: absolute; left: 0%; width: 26%; height: 100%; background: #ffffff; border-radius: 12px; border: 1px solid #eef0f4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px 22px; text-align: left; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                            <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#1</span>
                            <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'اختيار وتشكيل الفريق المناسب' : 'Assemble the right team' }}</span>
                        </div>
                        <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                            {{ $isAr ? 'نختار ونفرز المهندسين المتخصصين بحسب طبيعة مشروعك لضمان وجود الكفاءة المناسبة.' : 'We handle all aspects of vetting and choosing the right team that you don\'t have the time, expertise, or desire to do.' }}
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div style="position: absolute; left: 28%; width: 26%; height: 100%; background: #ffffff; border-radius: 12px; border: 1px solid #eef0f4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px 22px; text-align: left; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                            <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#3</span>
                            <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'المعمارية التقنية وهندسة النظم' : 'Tech architecture' }}</span>
                        </div>
                        <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                            {{ $isAr ? 'تخطيط المعمارية وتفكيك النظام إلى خدمات ومخططات بيانات واضحة لضمان السرعة والتحمل.' : 'We break monolithic apps into microservices. Decoupling the code allows teams to move faster and more independently.' }}
                        </p>
                    </div>

                    <!-- Step 5 -->
                    <div style="position: absolute; left: 56%; width: 26%; height: 100%; background: #ffffff; border-radius: 12px; border: 1px solid #eef0f4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px 22px; text-align: left; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                            <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#5</span>
                            <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'مراجعة الكود واختبارات الجودة' : 'Code reviews' }}</span>
                        </div>
                        <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                            {{ $isAr ? 'مراجعة صارمة لكل سطر كود قبل رفعه للسيرفر لمنع أي ثغرات أو تسريبات للذاكرة.' : 'Code reviews before release help detect issues like memory leaks, file leaks, performance signs, and general bad smells.' }}
                        </p>
                    </div>
                </div>

                <!-- MIDDLE TIMELINE BAR WITH TICKS AND TROPHY -->
                <div style="position: relative; width: 100%; height: 60px;">
                    <!-- The Horizontal Connecting Line -->
                    <div style="position: absolute; top: 29px; left: 0; right: 32px; height: 2px; background-color: #0071e3;"></div>

                    <!-- Trophy at the right tip of the line -->
                    <div style="position: absolute; right: 0; top: 30px; transform: translateY(-50%); display: flex; align-items: center; justify-content: center;">
                        <img src="/images/tech/trophy_clean.png" alt="Finished" style="width: 33px; height: 41px; object-fit: contain;" />
                    </div>

                    <!-- Vertical Connector Ticks (Upward to Top Row Cards) -->
                    <!-- Tick 1: Center of Card 1 (13%) -->
                    <div style="position: absolute; left: 13%; top: 0; width: 2px; height: 30px; transform: translateX(-50%); background-color: #0071e3;"></div>
                    <!-- Tick 3: Center of Card 3 (41%) -->
                    <div style="position: absolute; left: 41%; top: 0; width: 2px; height: 30px; transform: translateX(-50%); background-color: #0071e3;"></div>
                    <!-- Tick 5: Center of Card 5 (69%) -->
                    <div style="position: absolute; left: 69%; top: 0; width: 2px; height: 30px; transform: translateX(-50%); background-color: #0071e3;"></div>

                    <!-- Vertical Connector Ticks (Downward to Bottom Row Cards) -->
                    <!-- Tick 2: Center of Card 2 (27%) -->
                    <div style="position: absolute; left: 27%; top: 30px; width: 2px; height: 30px; transform: translateX(-50%); background-color: #0071e3;"></div>
                    <!-- Tick 4: Center of Card 4 (55%) -->
                    <div style="position: absolute; left: 55%; top: 30px; width: 2px; height: 30px; transform: translateX(-50%); background-color: #0071e3;"></div>
                    <!-- Tick 6: Center of Card 6 (83%) -->
                    <div style="position: absolute; left: 83%; top: 30px; width: 2px; height: 30px; transform: translateX(-50%); background-color: #0071e3;"></div>
                </div>

                <!-- BOTTOM ROW (Steps 2, 4, 6 - Staggered to the right) -->
                <div style="position: relative; width: 100%; height: 145px;">
                    <!-- Step 2 -->
                    <div style="position: absolute; left: 14%; width: 26%; height: 100%; background: #ffffff; border-radius: 12px; border: 1px solid #eef0f4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px 22px; text-align: left; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                            <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#2</span>
                            <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'تخطيط السبرنت ومراحل العمل' : 'Sprint planning' }}</span>
                        </div>
                        <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                            {{ $isAr ? 'خارطة طريق واضحة ومقسمة إلى مراحل زمنية محددة مع مهام واضحة لكل أسبوع.' : 'Sprint roadmap is a collective planning effort. Team members collaborate to clarify items and ensure shared understanding.' }}
                        </p>
                    </div>

                    <!-- Step 4 -->
                    <div style="position: absolute; left: 42%; width: 26%; height: 100%; background: #ffffff; border-radius: 12px; border: 1px solid #eef0f4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px 22px; text-align: left; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                            <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#4</span>
                            <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'الاجتماعات اليومية والعروض الأسبوعية' : 'Standups & weekly demos' }}</span>
                        </div>
                        <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                            {{ $isAr ? 'متابعة دورية وعروض حية أسبوعية للنسخة التجريبية للتأكد من سير المشروع في المسار الصحيح.' : 'Standups, weekly demos, and weekly reviews make sure everyone is on the same page and can raise their concerns.' }}
                        </p>
                    </div>

                    <!-- Step 6 -->
                    <div style="position: absolute; left: 70%; width: 26%; height: 100%; background: #ffffff; border-radius: 12px; border: 1px solid #eef0f4; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 20px 22px; text-align: left; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px;">
                            <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#6</span>
                            <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'التسليم التكراري ونشر النسخ' : 'Iterative delivery' }}</span>
                        </div>
                        <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                            {{ $isAr ? 'تسليم مرحلي مستمر ونشر تدريجي حتى الوصول للإطلاق الكامل وتسليم السورس كود.' : 'We divide the implementation process into several checkpoints rather than a single deadline.' }}
                        </p>
                    </div>
                </div>

            </div>

            <!-- MOBILE / TABLET TIMELINE (< lg: Clean Vertical Flow) -->
            <div class="lg:hidden relative space-y-6 text-start pl-6 sm:pl-8 border-l-2 border-[#0071e3] ml-4 sm:ml-6 py-4">
                <!-- Step 1 -->
                <div class="relative bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                    <div style="background-color: #0071e3;" class="absolute -left-[31px] sm:-left-[39px] top-6 w-3 h-3 rounded-full border-2 border-white ring-2 ring-[#0071e3]"></div>
                    <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                        <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#1</span>
                        <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'اختيار وتشكيل الفريق المناسب' : 'Assemble the right team' }}</span>
                    </div>
                    <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                        {{ $isAr ? 'نختار ونفرز المهندسين المتخصصين بحسب طبيعة مشروعك لضمان وجود الكفاءة المناسبة.' : 'We handle all aspects of vetting and choosing the right team that you don\'t have the time, expertise, or desire to do.' }}
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="relative bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                    <div style="background-color: #0071e3;" class="absolute -left-[31px] sm:-left-[39px] top-6 w-3 h-3 rounded-full border-2 border-white ring-2 ring-[#0071e3]"></div>
                    <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                        <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#2</span>
                        <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'تخطيط السبرنت ومراحل العمل' : 'Sprint planning' }}</span>
                    </div>
                    <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                        {{ $isAr ? 'خارطة طريق واضحة ومقسمة إلى مراحل زمنية محددة مع مهام واضحة لكل أسبوع.' : 'Sprint roadmap is a collective planning effort. Team members collaborate to clarify items and ensure shared understanding.' }}
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="relative bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                    <div style="background-color: #0071e3;" class="absolute -left-[31px] sm:-left-[39px] top-6 w-3 h-3 rounded-full border-2 border-white ring-2 ring-[#0071e3]"></div>
                    <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                        <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#3</span>
                        <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'المعمارية التقنية وهندسة النظم' : 'Tech architecture' }}</span>
                    </div>
                    <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                        {{ $isAr ? 'تخطيط المعمارية وتفكيك النظام إلى خدمات ومخططات بيانات واضحة لضمان السرعة والتحمل.' : 'We break monolithic apps into microservices. Decoupling the code allows teams to move faster and more independently.' }}
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="relative bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                    <div style="background-color: #0071e3;" class="absolute -left-[31px] sm:-left-[39px] top-6 w-3 h-3 rounded-full border-2 border-white ring-2 ring-[#0071e3]"></div>
                    <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                        <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#4</span>
                        <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'الاجتماعات اليومية والعروض الأسبوعية' : 'Standups & weekly demos' }}</span>
                    </div>
                    <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                        {{ $isAr ? 'متابعة دورية وعروض حية أسبوعية للنسخة التجريبية للتأكد من سير المشروع في المسار الصحيح.' : 'Standups, weekly demos, and weekly reviews make sure everyone is on the same page and can raise their concerns.' }}
                    </p>
                </div>

                <!-- Step 5 -->
                <div class="relative bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                    <div style="background-color: #0071e3;" class="absolute -left-[31px] sm:-left-[39px] top-6 w-3 h-3 rounded-full border-2 border-white ring-2 ring-[#0071e3]"></div>
                    <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                        <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#5</span>
                        <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'مراجعة الكود واختبارات الجودة' : 'Code reviews' }}</span>
                    </div>
                    <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                        {{ $isAr ? 'مراجعة صارمة لكل سطر كود قبل رفعه للسيرفر لمنع أي ثغرات أو تسريبات للذاكرة.' : 'Code reviews before release help detect issues like memory leaks, file leaks, performance signs, and general bad smells.' }}
                    </p>
                </div>

                <!-- Step 6 -->
                <div class="relative bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                    <div style="background-color: #0071e3;" class="absolute -left-[31px] sm:-left-[39px] top-6 w-3 h-3 rounded-full border-2 border-white ring-2 ring-[#0071e3]"></div>
                    <div style="display: flex; align-items: baseline; margin-bottom: 6px;">
                        <span style="color: #0071e3; font-weight: 700; font-size: 15px; margin-right: 6px;">#6</span>
                        <span style="color: #1a1a2e; font-weight: 700; font-size: 15px;">{{ $isAr ? 'التسليم التكراري ونشر النسخ' : 'Iterative delivery' }}</span>
                    </div>
                    <p style="color: #64748b; font-size: 12.5px; line-height: 1.55; margin: 0;">
                        {{ $isAr ? 'تسليم مرحلي مستمر ونشر تدريجي حتى الوصول للإطلاق الكامل وتسليم السورس كود.' : 'We divide the implementation process into several checkpoints rather than a single deadline.' }}
                    </p>
                </div>

                <!-- Mobile Trophy End -->
                <div class="flex items-center gap-3 pt-2">
                    <div class="relative -left-[37px] sm:-left-[45px] bg-white p-1 rounded-full shadow-sm">
                        <img src="/images/tech/trophy_clean.png" alt="Finished" class="w-6 h-auto object-contain" />
                    </div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $isAr ? 'اكتمال المشروع بنجاح' : 'Successful Delivery' }}</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ============================================================ -->
    <!-- ============================================================ -->
    <!-- SECTION 10: FEATURED MARKETPLACE SERVICES (Animated Carousel) -->
    <!-- Smooth moving slider featuring real Musoftware services       -->
    <!-- Matching image.png layout with auto-scroll & interactive nav  -->
    <!-- ============================================================ -->
    <section id="featured-services-carousel" style="background-color: #f5f5f7;" class="py-16 md:py-24 px-6 sm:px-10 border-y border-slate-200/60 relative overflow-hidden">
        <div class="max-w-[1280px] mx-auto">
            
            <!-- Section Header with Controls -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div class="text-center md:text-start max-w-[650px]">
                    <div style="background-color: #0071e3;" class="w-10 h-1 rounded-full mb-3 mx-auto md:mx-0"></div>
                    <span class="text-[15px] text-slate-500 font-normal block mb-1">
                        {{ $isAr ? 'خدمات ومنتجات المتجر' : 'Featured' }}
                    </span>
                    <h2 class="text-[32px] sm:text-[38px] font-bold text-[#1d1d1f] tracking-tight">
                        {{ $isAr ? 'أنظمة وحلول برمجية جاهزة للإطلاق' : 'Resources & Services' }}
                    </h2>
                </div>

                <!-- Carousel Navigation Controls -->
                <div class="flex items-center justify-center md:justify-end gap-3">
                    <button id="carousel-prev-btn" 
                            type="button" 
                            aria-label="Previous service"
                            class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 flex items-center justify-center shadow-sm transition active:scale-95 focus:outline-none">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <button id="carousel-next-btn" 
                            type="button" 
                            aria-label="Next service"
                            class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 flex items-center justify-center shadow-sm transition active:scale-95 focus:outline-none">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                    <a href="/marketplace" 
                       style="color: #0071e3; background-color: #eff6ff;" 
                       class="px-4 py-2 rounded-full text-[13px] font-semibold hover:bg-blue-100/70 transition flex items-center gap-1.5 ml-2">
                        <span>{{ $isAr ? 'كل خدمات المتجر' : 'View Store' }}</span>
                        <span class="rtl:rotate-180">➔</span>
                    </a>
                </div>
            </div>

            <!-- Moving Carousel Track Container -->
            <div class="relative group/carousel">
                
                <div id="services-carousel-track" 
                     class="flex gap-6 overflow-x-auto scroll-smooth pb-4 pt-1 select-none"
                     style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;">
                    
                    <!-- Service Card 1: Enterprise ERP & Accounting -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/stockmanager.png" alt="Enterprise Cloud ERP" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    ERP
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'إدارة الموارد والمالية' : 'ERP & Accounting' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'منظومة إدارة موارد الشركات والمخازن السحابية' : 'Enterprise Cloud ERP & Multi-Branch Accounting' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'إدارة متكاملة للمخزون، الفواتير الإلكترونية المعتمدة، الحسابات العامة وشجرة الحسابات.' : 'Comprehensive inventory, multi-currency ledger, compliant e-invoicing & real-time branch audits.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=business" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'جاهز للتثبيت' : 'Ready' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 2: AI WhatsApp CRM & Bots -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/trenz-whatscrm.png" alt="WhatsApp CRM" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    AI CRM
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'الأتمتة والذكاء الاصطناعي' : 'Automation & AI' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'منظومة أتمتة الواتساب وخدمة العملاء بالذكاء الاصطناعي' : 'AI WhatsApp & Omnichannel Customer CRM' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'صندوق وارد موحد لمحادثات واتساب، ردود ذكية مؤتمتة، وحملات رسائل مستهدفة.' : 'Unified multi-agent WhatsApp inbox, automated GPT-powered replies & campaign dispatch.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=programming-tech" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'الأكثر طلباً' : 'Popular' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 3: Multi-Vendor Marketplace -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/acelbay.png" alt="Multi-Vendor E-Commerce" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    Store
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'المتاجر الإلكترونية' : 'E-Commerce' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'منصة المتاجر الإلكترونية المتعددة ونظام العمولات' : 'Multi-Vendor E-Commerce Marketplace' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'متجر إلكتروني متكامل، لوحات تحكم للبائعين، حساب عمولات فوري، وبوابات دفع عالمية.' : 'Complete marketplace engine with vendor portals, automatic payouts & multi-gateway checkout.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=web-development" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'متكامل' : 'Turnkey' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 4: FinTech Real-Time Analytics -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/chartcash.png" alt="FinTech Visualizer" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    FinTech
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'التقنية المالية والتحليلات' : 'FinTech & Trading' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'شاشات تحليل وتداول الأسواق المالية اللحظية' : 'Real-Time Financial Charts & Market Visualizer' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'رسم بياني متقدم، ربط مع محركات WebSocket، وخوارزميات إشارات تداول لحظية.' : 'High-speed WebSocket trading charts, volume analytics & algorithmic execution telemetry.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=programming-tech" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'فائق السرعة' : 'Fast' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 5: Cloud POS & Cashier System -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/apple-light/fintech-pos.jpg" alt="Cloud POS System" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    POS
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'نقاط البيع السحابية' : 'Point of Sale' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'كاشير نقاط البيع السحابي للفروع والمتاجر' : 'Cloud POS & Multi-Branch Cashier Platform' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'واجهة بيع باللمس، دعم الطابعات الحرارية والباركود، والعمل أوفلاين دون اتصال.' : 'Touch checkout, thermal receipt printing, barcode scanner integration & offline resiliency.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=business" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'جاهز' : 'Ready' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 6: Electronic Invoicing Engine -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/minifatora.png" alt="Electronic Invoicing" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    Billing
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'الفوترة الإلكترونية والامتثال' : 'Fiscal Compliance' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'منظومة الفوترة الإلكترونية والربط الضريبي المعتمد' : 'E-Invoicing & Fiscal Compliance Gateway' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'توليد فواتير مشفرة، QR كود ديناميكي، وربط مباشر مع هيئات الضرائب والجمارك.' : 'Cryptographic invoice signing, dynamic ZATCA QR codes & seamless tax authority integrations.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=business" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'معتمد' : 'Compliant' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 7: Telecom & SMS Gateway Engine -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/telecom-system.png" alt="Telecom & SMS Gateway" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    Gateway
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'بوابات الرسائل والاتصالات' : 'Telecom & SMS' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'منظومة إرسال رسائل OTP والربط مع شبكات الاتصالات' : 'High-Volume SMS & OTP Gateway Routing Engine' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'إرسال آمن لرسائل التحقق، تأكيد العمليات البنكية ومطابقة تحويلات فودافون كاش وإنستاباي.' : 'High-throughput OTP dispatch, instant payment matching & mobile wallet gateway routing.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=programming-tech" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'بنية سحابية' : 'Infra' }}
                            </span>
                        </div>
                    </div>

                    <!-- Service Card 8: Cross-Platform Mobile Apps -->
                    <div class="w-[280px] sm:w-[310px] flex-shrink-0 rounded-2xl bg-white border border-slate-100 p-4 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_28px_rgba(0,0,0,0.07)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group"
                         style="scroll-snap-align: start;">
                        <div>
                            <div class="aspect-[16/10] bg-slate-100 rounded-xl overflow-hidden mb-3.5 relative">
                                <img src="/images/portfolio/qcoin-app.png" alt="Mobile App Development" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-md text-white text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                    Mobile
                                </div>
                            </div>
                            <span style="color: #0071e3;" class="text-[11px] font-bold uppercase tracking-wider block mb-1">
                                {{ $isAr ? 'تطبيقات الهواتف الذكية' : 'Mobile Apps' }}
                            </span>
                            <h4 class="text-[14px] font-bold text-[#1d1d1f] leading-snug line-clamp-2 mb-2 group-hover:text-[#0071e3] transition">
                                {{ $isAr ? 'تطوير تطبيقات الجوال الاحترافية (iOS & Android)' : 'Cross-Platform Mobile App Engineering' }}
                            </h4>
                            <p class="text-[12px] text-slate-500 line-clamp-2 leading-relaxed">
                                {{ $isAr ? 'بناء تطبيقات متكاملة بهوية بصرية مميزة وسرعة 60 إطار في الثانية باستخدام Flutter وReact Native.' : 'High-performance Flutter & React Native apps with fluid 60fps animations and offline caching.' }}
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                            <a href="/marketplace?category=web-development" style="color: #0071e3;" class="text-[13px] font-semibold hover:opacity-80 inline-flex items-center gap-1">
                                <span>{{ $isAr ? 'استكشف في المتجر' : 'Explore in Store' }}</span>
                                <span class="rtl:rotate-180">➔</span>
                            </a>
                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $isAr ? 'Native' : 'Native' }}
                            </span>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </section>

    <!-- Carousel Motion & Interaction Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const track = document.getElementById('services-carousel-track');
            const prevBtn = document.getElementById('carousel-prev-btn');
            const nextBtn = document.getElementById('carousel-next-btn');

            if (!track) return;

            const isRTL = document.documentElement.dir === 'rtl';
            const cardWidth = 334; // card width (310px) + gap (24px)
            let autoScrollInterval = null;
            let isUserInteracting = false;

            function scrollNext() {
                const maxScrollLeft = track.scrollWidth - track.clientWidth;
                const currentScroll = Math.abs(track.scrollLeft);

                if (currentScroll >= maxScrollLeft - 10) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: isRTL ? -cardWidth : cardWidth, behavior: 'smooth' });
                }
            }

            function scrollPrev() {
                const currentScroll = Math.abs(track.scrollLeft);
                if (currentScroll <= 10) {
                    const maxScrollLeft = track.scrollWidth - track.clientWidth;
                    track.scrollTo({ left: isRTL ? -maxScrollLeft : maxScrollLeft, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: isRTL ? cardWidth : -cardWidth, behavior: 'smooth' });
                }
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    scrollNext();
                    pauseAutoScroll();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    scrollPrev();
                    pauseAutoScroll();
                });
            }

            function startAutoScroll() {
                if (autoScrollInterval) clearInterval(autoScrollInterval);
                autoScrollInterval = setInterval(function () {
                    if (!isUserInteracting) {
                        scrollNext();
                    }
                }, 3200);
            }

            function pauseAutoScroll() {
                isUserInteracting = true;
                if (autoScrollInterval) clearInterval(autoScrollInterval);
                setTimeout(function () {
                    isUserInteracting = false;
                    startAutoScroll();
                }, 5000);
            }

            track.addEventListener('mouseenter', function () {
                isUserInteracting = true;
            });

            track.addEventListener('mouseleave', function () {
                isUserInteracting = false;
            });

            track.addEventListener('touchstart', function () {
                isUserInteracting = true;
            }, { passive: true });

            track.addEventListener('touchend', function () {
                setTimeout(function () {
                    isUserInteracting = false;
                }, 3000);
            }, { passive: true });

            startAutoScroll();
        });
    </script>

    <!-- ============================================================ -->
    <!-- SECTION 11: CALLOUT BANNER (Hire the best developers...)     -->
    <!-- Soft Container + Orange Button with Sunburst Rays             -->
    <!-- Matching image.png                                            -->
    <!-- ============================================================ -->
    <section id="cta-hire" class="py-16 md:py-24 px-6 sm:px-10 bg-white">
        <div style="background-color: #f5f5f7; border: 1px solid #e5e5ea;" class="max-w-[1100px] mx-auto rounded-3xl p-8 sm:p-14 relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 relative z-10">
                
                <!-- Left: Headline -->
                <div class="max-w-[550px]">
                    <h2 class="text-[28px] sm:text-[36px] font-bold text-[#1d1d1f] tracking-tight leading-tight">
                        {{ $isAr ? 'وظف أفضل المطورين والمهندسين لمشروعك!' : 'Hire the best developers and designers around!' }}
                    </h2>
                </div>

                <!-- Right: Apple Royal Blue Button -->
                <div class="relative flex items-center justify-center shrink-0">
                    <a href="/start-project" style="background-color: #0071e3; color: #ffffff;" class="relative z-10 inline-flex items-center justify-center px-8 py-4 rounded-xl font-bold text-[15px] shadow-sm hover:bg-[#0077ed] transition duration-200">
                        <span>{{ $isAr ? 'وظف كبار المهندسين الآن' : 'Hire Top Developers' }}</span>
                    </a>
                </div>

            </div>
        </div>
    </section>

</div>
@endsection

{{-- ============================================================ --}}
{{-- CUSTOM 3-COLUMN FOOTER (Matching image.png)                  --}}
{{-- Column 1: Logo + Summary + Google PageSpeed 100 Badge         --}}
{{-- Column 2: Links (About us, Services, Case Studies, How it works, Blog, Careers, Areas We Serve) --}}
{{-- Column 3: Contact us + Phone                                  --}}
{{-- Bottom Bar: Copyright + Social Outline Circles               --}}
{{-- ============================================================ --}}
@section('custom_footer')
<footer class="w-full bg-white border-t border-gray-100 pt-16 pb-12 px-6 sm:px-12 text-[#515154]">
    <div class="max-w-[1240px] mx-auto space-y-12">
        
        <!-- 3-Column Grid Matching image.png -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-16">
            
            <!-- Column 1: Logo + Intro + Google Badge (5 cols) -->
            <div class="md:col-span-5 space-y-5">
                <a href="/" class="flex items-center gap-2.5 group focus:outline-none" title="Musoftwares">
                    <svg class="w-6 h-6 fill-[#0071e3]" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg">
                        <path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/>
                    </svg>
                    <span class="text-[20px] font-bold tracking-tight text-[#1d1d1f]">
                        Musoftware
                    </span>
                </a>

                <p class="text-[13px] text-[#6e6e73] leading-relaxed max-w-[340px]">
                    {{ $isAr 
                        ? 'موسوفت ويرز هو استوديو هندسة وتطوير برمجيات مستقل يديره المهندس محمود أمين، يقدم أنظمة سحابية وتطبيقات مخصصة مع تسليم كامل للسورس كود.' 
                        : 'Musoftwares is an independent software engineering practice founded and operated by Mahmoud Amin, delivering custom cloud platforms with 100% source code ownership.' }}
                </p>
            </div>

            <!-- Column 2: Links (3 cols) -->
            <div class="md:col-span-3 space-y-4">
                <h4 class="text-[15px] font-bold text-[#1d1d1f]">
                    {{ $isAr ? 'الروابط' : 'Links' }}
                </h4>
                <ul class="space-y-2.5 text-[13px] text-[#6e6e73]">
                    <li><a href="/about/mahmoud-amin" class="hover:text-[#0071e3] transition">{{ $isAr ? 'من نحن' : 'About Us' }}</a></li>
                    <li><a href="#services-overview" class="hover:text-[#0071e3] transition">{{ $isAr ? 'الخدمات' : 'Services' }}</a></li>
                    <li><a href="#case-studies" class="hover:text-[#0071e3] transition">{{ $isAr ? 'أعمالنا' : 'Case Studies' }}</a></li>
                    <li><a href="#how-it-works" class="hover:text-[#0071e3] transition">{{ $isAr ? 'كيف نعمل' : 'How it works' }}</a></li>
                    <li><a href="/compare/laravel-vs-nodejs" class="hover:text-[#0071e3] transition">{{ $isAr ? 'المدونة والتوثيق' : 'Blog' }}</a></li>
                    <li><a href="/company/contact" class="hover:text-[#0071e3] transition">{{ $isAr ? 'الوظائف' : 'Careers' }}</a></li>
                    <li><a href="/portfolio" class="hover:text-[#0071e3] transition">{{ $isAr ? 'المناطق والقطاعات' : 'Areas We Serve' }}</a></li>
                </ul>
            </div>

            <!-- Column 3: Contact Us (4 cols) -->
            <div class="md:col-span-4 space-y-4">
                <h4 class="text-[15px] font-bold text-[#1d1d1f]">
                    {{ $isAr ? 'تواصل معنا' : 'Contact us' }}
                </h4>
                <p class="text-[13px] text-[#6e6e73] leading-relaxed">
                    {{ $isAr 
                        ? 'تواصل معنا مباشرة لمناقشة متطلبات مشروعك البرمجي، أو لحجز استشارة هندسية مع كبير المعماريين.' 
                        : 'Connect directly with our engineering team to discuss your architectural requirements and receive a build proposal.' }}
                </p>
                <div class="pt-2">
                    <a href="tel:+201015218548" class="text-[15px] font-bold text-[#1d1d1f] hover:text-[#0071e3] transition block dir-ltr">
                        +20 101 521 8548
                    </a>
                    <a href="mailto:admin@musoftwares.com" class="text-[13px] text-[#6e6e73] hover:text-[#0071e3] transition block mt-1">
                        admin@musoftwares.com
                    </a>
                </div>
            </div>

        </div>

        <!-- Bottom Bar: Copyright Left + Social Outlines Right (Matching image.png) -->
        <div class="border-t border-gray-100 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[12px] text-slate-400">
            <div>
                &copy; {{ date('Y') }} Copyright by Musoftwares. All rights reserved.
            </div>
            <div class="flex items-center gap-3">
                <!-- Facebook -->
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#0071e3] hover:border-[#0071e3] transition">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M9 8H6v4h3v12h5V12h3.642L18 8h-4V6.333C14 5.374 14.5 5 15.6 5H18V0h-3.808C10.596 0 9 1.583 9 4.615V8z"/></svg>
                </a>
                <!-- Instagram -->
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#0071e3] hover:border-[#0071e3] transition">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
                <!-- Twitter / X -->
                <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#0071e3] hover:border-[#0071e3] transition">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <!-- LinkedIn -->
                <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#0071e3] hover:border-[#0071e3] transition">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                </a>
            </div>
        </div>

    </div>
</footer>
@endsection

@push('scripts')
<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
<script>
    // Services Carousel Controller (01 - 05 pagination backed by Estimator)
    let currentServiceIndex = 0;
    const totalServicesCount = 5;

    function updateServiceCarouselUI() {
        const pageEl = document.getElementById('service-current-page');
        if (pageEl) {
            pageEl.textContent = String(currentServiceIndex + 1).padStart(2, '0');
        }

        const dots = document.querySelectorAll('.service-carousel-dot');
        dots.forEach((dot, idx) => {
            if (idx === currentServiceIndex) {
                dot.style.backgroundColor = '#0071e3';
                dot.classList.add('w-4');
                dot.classList.remove('w-2.5', 'bg-slate-300');
            } else {
                dot.style.backgroundColor = '#cbd5e1';
                dot.classList.remove('w-4');
                dot.classList.add('w-2.5');
            }
        });

        const cards = document.querySelectorAll('.service-slide-card');
        cards.forEach((card, idx) => {
            if (idx === currentServiceIndex) {
                card.style.borderColor = '#0071e3';
                card.style.transform = 'translateY(-4px)';
                card.classList.add('shadow-lg');
                card.classList.remove('shadow-sm');
            } else {
                card.style.borderColor = '#f1f5f9';
                card.style.transform = 'translateY(0)';
                card.classList.remove('shadow-lg');
                card.classList.add('shadow-sm');
            }
        });
    }

    function slideServices(delta) {
        currentServiceIndex = (currentServiceIndex + delta + totalServicesCount) % totalServicesCount;
        const targetCard = document.getElementById('service-card-' + currentServiceIndex);
        if (targetCard) {
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        updateServiceCarouselUI();
    }

    function goToService(index) {
        currentServiceIndex = Math.max(0, Math.min(totalServicesCount - 1, index));
        const targetCard = document.getElementById('service-card-' + currentServiceIndex);
        if (targetCard) {
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        updateServiceCarouselUI();
    }

    // Testimonials Switcher with Real Client Photos & Dynamic Active Highlighting
    const testimonialsData = [
        {
            quoteEn: "A quantum leap for our logistics operations. Musoftware's engineering rigor in building our real-time tracking ERP and WhatsApp dispatch saved us hundreds of manual hours every week.",
            quoteAr: "نقلة نوعية في إدارة أسطول الشحن وتتبع الشحنات اللحظي. احترافية فريق Musoftware في هندسة الـ ERP والربط مع الواتساب والمدفوعات وفرت علينا مئات ساعات العمل أسبوعياً.",
            nameEn: "Osama Ayman",
            nameAr: "أ. أسامة أيمن",
            roleEn: "Jad Logistics",
            roleAr: "Jad Logistics"
        },
        {
            quoteEn: "Rock-solid cloud & offline POS architecture. Zero lag, flawless multi-branch sync, and seamless payment gateway integration made scaling our retail operations effortless.",
            quoteAr: "نظام الـ POS المكتبي والسحابي صلب للغاية وسريع حتى مع انقطاع الإنترنت. الربط التلقائي لقنوات الدفع وتحديث الفروع لحظياً جعل التوسع سلساً دون أي مشاكل تقنية.",
            nameEn: "Mostafa Mano",
            nameAr: "أ. مصطفى مانو",
            roleEn: "ClickOrder Retail",
            roleAr: "ClickOrder Retail"
        },
        {
            quoteEn: "Our strategic partnership with Musoftware has powered our most ambitious AI and marketing automation platforms, including AMCTasks, AMC Social, and AMC Turbo. Their exceptional engineering, unbreakable uptime, and absolute technical ownership make them our #1 core technology partner.",
            quoteAr: "شراكتنا مع Musoftware امتدت لسنوات في بناء وتشغيل أضخم منصاتنا التسويقية والذكاء الاصطناعي مثل AMCTasks و AMC Social و AMC Turbo. كفاءة هندسية نادرة، استقرار برمجي غير مسبوق، ودعم فني مخلص جعلهم الشريك التكنولوجي الأول لمجموعتنا.",
            nameEn: "Ahmed Maher",
            nameAr: "أ. أحمد ماهر",
            roleEn: "Founder & CEO, AMC Academy",
            roleAr: "مؤسس ورئيس AMC Academy"
        },
        {
            quoteEn: "Outstanding architectural clarity and 100% source code ownership. Their speed, engineering discipline, and attention to detail when building enterprise systems are unmatched.",
            quoteAr: "التزام استثنائي بمعايير البرمجة النظيفة وتسليم كامل للسورس كود والداتا. سرعة استجابة مذهلة ودقة متناهية في هندسة النظم المعقدة بدون أي تعقيد.",
            nameEn: "Abdelrahman",
            nameAr: "أ. عبد الرحمن",
            roleEn: "TechMate Solutions",
            roleAr: "TechMate Solutions"
        },
        {
            quoteEn: "Without hesitation, I recommend Musoftware for any enterprise looking for high-reliability software. Top-tier execution, on-time delivery, and unmatched technical depth.",
            quoteAr: "بدون أي تردد أنصح بالتعامل مع Musoftware لأي مؤسسة تبحث عن برمجيات صلبة تدوم طويلاً. احترافية عالية، تسليم في الموعد، وتجربة مستخدم بسيطة وقوية.",
            nameEn: "Mohmmed Hamed",
            nameAr: "أ. محمد حامد",
            roleEn: "Sampath Group",
            roleAr: "Sampath Group"
        }
    ];

    let currentTestimonialIndex = 2;

    function switchTestimonial(index) {
        currentTestimonialIndex = (index + testimonialsData.length) % testimonialsData.length;
        const isArabic = document.documentElement.lang === 'ar' || document.documentElement.dir === 'rtl';
        const item = testimonialsData[currentTestimonialIndex];
        const quoteEl = document.getElementById('testimonial-quote-text');
        if (quoteEl) {
            quoteEl.style.opacity = '0';
            setTimeout(() => {
                quoteEl.textContent = isArabic ? item.quoteAr : item.quoteEn;
                quoteEl.style.opacity = '1';
            }, 180);
        }

        // Dynamically update avatar active states
        for (let i = 0; i < testimonialsData.length; i++) {
            const avatarWrap = document.getElementById('testimonial-avatar-' + i);
            if (!avatarWrap) continue;
            const img = avatarWrap.querySelector('.avatar-img');
            const badge = avatarWrap.querySelector('.avatar-badge');
            const role = avatarWrap.querySelector('.avatar-role');
            const isActive = (i === currentTestimonialIndex);

            if (isActive) {
                avatarWrap.className = 'testimonial-avatar-item flex flex-col items-center scale-110 z-10 cursor-pointer transition duration-300';
                if (img) {
                    img.className = 'avatar-img w-16 h-16 rounded-full object-cover shadow-lg transition duration-300';
                    img.style.border = '2px solid #0071e3';
                }
                if (badge) badge.classList.remove('hidden');
                if (role) {
                    role.style.color = '#0071e3';
                    role.classList.add('font-bold');
                    role.classList.remove('text-slate-400');
                }
            } else {
                avatarWrap.className = 'testimonial-avatar-item flex flex-col items-center opacity-70 hover:opacity-100 transition duration-300 cursor-pointer';
                if (img) {
                    img.className = 'avatar-img w-14 h-14 rounded-full object-cover border border-slate-200 transition duration-300';
                    img.style.border = '1px solid #e2e8f0';
                }
                if (badge) badge.classList.add('hidden');
                if (role) {
                    role.style.color = '';
                    role.classList.remove('font-bold');
                    role.classList.add('text-slate-400');
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const prevBtn = document.getElementById('testimonial-prev');
        const nextBtn = document.getElementById('testimonial-next');
        if (prevBtn) prevBtn.addEventListener('click', () => switchTestimonial(currentTestimonialIndex - 1));
        if (nextBtn) nextBtn.addEventListener('click', () => switchTestimonial(currentTestimonialIndex + 1));
    });
</script>
@endpush
