{{-- One header for every public page. Main links first, less important links inside "More". --}}
@php
    $isAr = app()->getLocale() === 'ar';
    $nav = \App\Support\SiteNav::links();
    $linkClass = fn (array $link) => $link['active'] ? 'text-[#0071e3] font-semibold' : 'text-[#1d1d1f]/75 hover:text-[#0071e3]';
    $moreActive = collect($nav['more'])->contains('active', true);
@endphp
<header class="sticky top-0 z-50 w-full bg-white/90 backdrop-blur-md border-b border-black/5">
    <div class="max-w-[1240px] mx-auto flex items-center justify-between gap-4 px-4 sm:px-8 h-16">

        <a href="/" class="flex items-center gap-2.5 shrink-0 focus:outline-none" title="Musoftware">
            <svg class="w-6 h-6 fill-[#0071e3]" viewBox="0 0 307 307" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M 48 54 L 48 223 L 51 226 L 52 226 L 54 228 L 55 228 L 57 230 L 58 230 L 60 232 L 61 232 L 63 234 L 64 234 L 66 236 L 67 236 L 69 238 L 70 238 L 72 240 L 73 240 L 75 242 L 76 242 L 78 244 L 79 244 L 81 246 L 82 246 L 84 248 L 91 252 L 94 255 L 97 256 L 99 258 L 100 258 L 102 260 L 103 260 L 105 262 L 106 262 L 108 264 L 109 264 L 132 280 L 135 281 L 141 286 L 144 287 L 146 289 L 153 293 L 155 291 L 158 290 L 161 287 L 162 287 L 164 285 L 165 285 L 167 283 L 168 283 L 170 281 L 171 281 L 173 279 L 174 279 L 176 277 L 177 277 L 179 275 L 180 275 L 182 273 L 183 273 L 185 271 L 186 271 L 188 269 L 189 269 L 191 267 L 192 267 L 194 265 L 195 265 L 197 263 L 198 263 L 200 261 L 201 261 L 203 259 L 204 259 L 206 257 L 207 257 L 209 255 L 210 255 L 212 253 L 213 253 L 215 251 L 216 251 L 218 249 L 219 249 L 221 247 L 222 247 L 224 245 L 225 245 L 227 243 L 228 243 L 230 241 L 231 241 L 233 239 L 234 239 L 236 237 L 237 237 L 239 235 L 240 235 L 242 233 L 243 233 L 245 231 L 246 231 L 256 224 L 256 220 L 257 219 L 257 216 L 256 215 L 256 54 L 254 56 L 250 58 L 247 61 L 246 61 L 243 64 L 236 68 L 226 76 L 225 76 L 223 78 L 219 80 L 216 83 L 215 83 L 213 85 L 206 89 L 203 92 L 196 96 L 193 99 L 186 103 L 183 106 L 182 106 L 180 108 L 173 112 L 170 115 L 169 115 L 164 119 L 164 120 L 166 122 L 167 122 L 174 128 L 176 128 L 180 125 L 181 125 L 184 122 L 188 120 L 191 117 L 198 113 L 201 110 L 202 110 L 204 108 L 211 104 L 214 101 L 215 101 L 217 99 L 224 95 L 227 92 L 231 90 L 237 85 L 239 84 L 241 85 L 241 216 L 238 219 L 237 219 L 232 223 L 229 224 L 227 226 L 223 228 L 220 231 L 217 232 L 215 234 L 211 236 L 208 239 L 202 242 L 200 244 L 199 244 L 197 246 L 196 246 L 194 248 L 193 248 L 191 250 L 190 250 L 188 252 L 187 252 L 185 254 L 184 254 L 182 256 L 181 256 L 179 258 L 178 258 L 176 260 L 175 260 L 173 262 L 172 262 L 170 264 L 163 268 L 160 271 L 159 271 L 154 275 L 151 275 L 149 273 L 148 273 L 146 271 L 145 271 L 143 269 L 142 269 L 140 267 L 139 267 L 137 265 L 136 265 L 134 263 L 133 263 L 131 261 L 130 261 L 128 259 L 127 259 L 125 257 L 124 257 L 122 255 L 121 255 L 119 253 L 118 253 L 116 251 L 115 251 L 92 235 L 86 232 L 80 227 L 77 226 L 75 224 L 68 220 L 64 216 L 64 85 L 66 84 L 68 86 L 69 86 L 72 89 L 73 89 L 75 91 L 82 95 L 85 98 L 86 98 L 92 103 L 93 103 L 95 105 L 102 109 L 105 112 L 106 112 L 112 117 L 113 117 L 115 119 L 122 123 L 125 126 L 126 126 L 128 128 L 129 128 L 131 130 L 138 134 L 145 140 L 152 144 L 159 150 L 163 152 L 166 155 L 167 155 L 175 161 L 177 161 L 180 158 L 181 158 L 183 156 L 184 156 L 186 154 L 187 154 L 189 152 L 190 152 L 192 150 L 199 146 L 202 143 L 203 143 L 210 138 L 211 139 L 211 204 L 201 211 L 200 211 L 198 213 L 197 213 L 195 215 L 194 215 L 192 217 L 191 217 L 189 219 L 188 219 L 186 221 L 185 221 L 183 223 L 182 223 L 180 225 L 179 225 L 177 227 L 176 227 L 174 229 L 173 229 L 171 231 L 170 231 L 168 233 L 167 233 L 165 235 L 164 235 L 162 237 L 161 237 L 159 239 L 158 239 L 156 241 L 155 241 L 153 243 L 152 243 L 150 241 L 149 241 L 147 239 L 146 239 L 144 237 L 143 237 L 141 235 L 140 235 L 138 233 L 137 233 L 135 231 L 134 231 L 132 229 L 131 229 L 108 213 L 105 212 L 102 209 L 99 208 L 94 204 L 94 141 L 93 140 L 94 139 L 98 140 L 104 145 L 105 145 L 128 161 L 130 161 L 140 153 L 141 153 L 137 149 L 134 148 L 131 145 L 130 145 L 127 142 L 120 138 L 117 135 L 116 135 L 114 133 L 113 133 L 111 131 L 110 131 L 108 129 L 101 125 L 98 122 L 97 122 L 95 120 L 88 116 L 85 113 L 78 109 L 78 211 L 88 218 L 89 218 L 91 220 L 92 220 L 94 222 L 95 222 L 97 224 L 98 224 L 100 226 L 101 226 L 103 228 L 104 228 L 106 230 L 107 230 L 109 232 L 110 232 L 112 234 L 113 234 L 115 236 L 116 236 L 118 238 L 119 238 L 121 240 L 122 240 L 124 242 L 125 242 L 127 244 L 128 244 L 130 246 L 131 246 L 133 248 L 134 248 L 136 250 L 137 250 L 139 252 L 140 252 L 142 254 L 143 254 L 145 256 L 146 256 L 148 258 L 149 258 L 151 260 L 155 259 L 157 257 L 158 257 L 160 255 L 161 255 L 163 253 L 164 253 L 166 251 L 167 251 L 169 249 L 170 249 L 172 247 L 173 247 L 175 245 L 176 245 L 178 243 L 179 243 L 181 241 L 182 241 L 184 239 L 185 239 L 187 237 L 188 237 L 211 221 L 214 220 L 217 217 L 223 214 L 227 210 L 227 109 L 224 110 L 222 112 L 218 114 L 215 117 L 214 117 L 212 119 L 208 121 L 205 124 L 204 124 L 202 126 L 201 126 L 199 128 L 192 132 L 189 135 L 186 136 L 183 139 L 182 139 L 177 143 L 174 143 L 168 138 L 167 138 L 161 133 L 160 133 L 157 130 L 153 128 L 150 125 L 143 121 L 133 113 L 132 113 L 130 111 L 126 109 L 123 106 L 122 106 L 120 104 L 113 100 L 110 97 L 109 97 L 107 95 L 100 91 L 97 88 L 90 84 L 87 81 L 83 79 L 80 76 L 73 72 L 70 69 L 63 65 L 56 59 L 55 59 L 49 54 Z"/></svg>
            <span class="text-[20px] font-bold tracking-tight text-[#1d1d1f]">Musoftware</span>
        </a>

        <nav class="hidden lg:flex items-center gap-7 text-[14px] font-medium" aria-label="{{ $isAr ? 'القائمة الرئيسية' : 'Main menu' }}">
            @foreach ($nav['main'] as $link)
                <a href="{{ $link['url'] }}" class="transition-colors {{ $linkClass($link) }}">{{ $link['label'] }}</a>
            @endforeach
            <details id="more-menu" class="relative group">
                <summary class="list-none cursor-pointer flex items-center gap-1 transition-colors {{ $moreActive ? 'text-[#0071e3] font-semibold' : 'text-[#1d1d1f]/75 hover:text-[#0071e3]' }} [&::-webkit-details-marker]:hidden">
                    {{ $isAr ? 'المزيد' : 'More' }}
                    <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 7.5l5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </summary>
                <div class="absolute top-full mt-3 start-0 min-w-[220px] bg-white border border-black/10 rounded-xl shadow-lg py-2">
                    @foreach ($nav['more'] as $link)
                        <a href="{{ $link['url'] }}" class="block px-4 py-2.5 text-[14px] transition-colors hover:bg-[#f5f5f7] {{ $linkClass($link) }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </details>
        </nav>

        <div class="flex items-center gap-2 sm:gap-4 shrink-0">
            <a href="https://wa.me/201015218548" target="_blank" rel="noopener noreferrer" class="hidden lg:inline-flex p-2 text-[#1d1d1f]/70 hover:text-[#25D366] transition-colors" title="{{ $isAr ? 'تواصل معنا على واتساب' : 'Chat with us on WhatsApp' }}" aria-label="WhatsApp">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
            </a>

            @auth
                @php
                    $userBal = (float) auth()->user()->available_balance();
                    $formattedBal = \App\Helpers\FinanceHelper::instance()->format_money($userBal, auth()->user()->currency_id ?: 1);
                @endphp
                <a href="{{ Route::has('billing.invoices.index') ? route('billing.invoices.index') : url('/dashboard') }}" class="hidden xl:inline-flex text-[13px] font-medium text-[#1d1d1f]/70 hover:text-[#0071e3] transition-colors" title="{{ $isAr ? 'رصيد المحفظة' : 'Wallet balance' }}">
                    <span dir="ltr" class="font-mono">{{ $formattedBal }}</span>
                </a>
                <a href="/dashboard" class="hidden lg:inline-flex items-center gap-1.5 text-[14px] font-medium text-[#1d1d1f] hover:text-[#0071e3] transition-colors">
                    {{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}
                    <svg class="w-4 h-4 rtl:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10h12m-5-5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <a href="/login" class="hidden lg:inline-flex text-[14px] font-medium text-[#1d1d1f]/75 hover:text-[#0071e3] transition-colors">{{ $isAr ? 'تسجيل الدخول' : 'Sign in' }}</a>
            @endauth

            <a href="/company/contact" style="background-color: #0071e3; color: #ffffff;" class="hidden sm:inline-flex items-center justify-center px-5 py-2 rounded-lg text-[14px] font-medium shadow-sm hover:bg-[#0077ed] transition-colors">{{ $isAr ? 'تواصل معنا' : 'Contact us' }}</a>

            <button type="button" onclick="document.getElementById('site-mobile-menu').classList.toggle('hidden')" class="lg:hidden p-2 text-[#1d1d1f] hover:text-[#0071e3] transition-colors" aria-label="{{ $isAr ? 'فتح القائمة' : 'Open menu' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    <div id="site-mobile-menu" class="hidden lg:hidden border-t border-black/5 bg-white px-5 py-4 text-[15px] font-medium text-[#1d1d1f] max-h-[calc(100vh-4rem)] overflow-y-auto">
        @foreach ($nav['main'] as $link)
            <a href="{{ $link['url'] }}" class="block py-3 border-b border-black/5 {{ $linkClass($link) }}">{{ $link['label'] }}</a>
        @endforeach
        <details class="border-b border-black/5" @if ($moreActive) open @endif>
            <summary class="py-3 cursor-pointer list-none flex items-center justify-between [&::-webkit-details-marker]:hidden">
                {{ $isAr ? 'المزيد' : 'More' }}
                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 7.5l5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </summary>
            <div class="pb-2 ps-3">
                @foreach ($nav['more'] as $link)
                    <a href="{{ $link['url'] }}" class="block py-2.5 text-[14px] {{ $linkClass($link) }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </details>
        <div class="pt-4 flex items-center gap-3">
            @auth
                <a href="/dashboard" class="flex-1 text-center py-2.5 rounded-lg border border-black/10 text-sm font-semibold hover:bg-[#f5f5f7] transition-colors">{{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}</a>
            @else
                <a href="/login" class="flex-1 text-center py-2.5 rounded-lg border border-black/10 text-sm font-semibold hover:bg-[#f5f5f7] transition-colors">{{ $isAr ? 'تسجيل الدخول' : 'Sign in' }}</a>
            @endauth
            <a href="/company/contact" style="background-color: #0071e3; color: #ffffff;" class="flex-1 text-center py-2.5 rounded-lg text-sm font-semibold hover:bg-[#0077ed] transition-colors">{{ $isAr ? 'تواصل معنا' : 'Contact us' }}</a>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            var menu = document.getElementById('more-menu');
            if (menu && menu.open && !menu.contains(event.target)) menu.open = false;
        });
    </script>
</header>
