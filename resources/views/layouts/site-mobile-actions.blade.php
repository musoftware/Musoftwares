{{-- Same floating WhatsApp button (desktop) and bottom action bar (phones) as the React public layout
     (FloatingWhatsAppButton + MobileBottomActionBar). Items come from SiteNav::footer()['mobile_bar']. --}}
@php($bar = \App\Support\SiteNav::footer()['mobile_bar'])

{{-- Keeps the last footer line visible above the phone bar. --}}
<div class="h-20 lg:hidden" aria-hidden="true"></div>

<a href="{{ $bar['whatsapp']['url'] }}" target="_blank" rel="noopener noreferrer"
   class="hidden lg:flex fixed bottom-6 end-6 z-50 items-center gap-3 bg-[#25D366] hover:bg-[#1DA851] text-white py-3 px-5 rounded-full shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-xl group border border-emerald-400/30"
   aria-label="{{ $bar['whatsapp']['label'] }}">
    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
    <span class="font-semibold text-sm whitespace-nowrap overflow-hidden max-w-0 group-hover:max-w-xs transition-all duration-500 ease-in-out">{{ $bar['whatsapp']['label'] }}</span>
</a>

<div id="site-mobile-bar" class="lg:hidden fixed bottom-0 inset-x-0 z-40 px-4 pb-4 pt-2 bg-white/90 backdrop-blur-xl border-t border-zinc-200/80 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] transition-transform duration-300">
    <div class="max-w-md mx-auto flex items-center justify-between gap-2">
        <a href="{{ $bar['pricing']['url'] }}" class="flex-1 flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-zinc-600 hover:text-zinc-900 active:bg-zinc-100 transition text-[11px] font-bold">
            <svg class="w-5 h-5 mb-0.5 text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            <span>{{ $bar['pricing']['label'] }}</span>
        </a>
        <a href="{{ $bar['whatsapp']['url'] }}" target="_blank" rel="noopener noreferrer" class="flex-[2] flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-zinc-950 text-white hover:bg-zinc-800 active:scale-95 transition shadow-sm text-xs font-bold uppercase tracking-wider">
            <svg class="w-4 h-4 text-emerald-400 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
            <span>{{ $bar['whatsapp']['label'] }}</span>
        </a>
        <a href="{{ $bar['account']['url'] }}" class="flex-1 flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-zinc-600 hover:text-zinc-900 active:bg-zinc-100 transition text-[11px] font-bold">
            <svg class="w-5 h-5 mb-0.5 text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
            <span>{{ $bar['account']['label'] }}</span>
        </a>
    </div>
</div>
<script>
    (function () {
        // Same behaviour as MobileBottomActionBar: hide while scrolling down, show when scrolling up.
        var bar = document.getElementById('site-mobile-bar');
        if (!bar) return;
        var last = window.scrollY;
        window.addEventListener('scroll', function () {
            var y = window.scrollY;
            if (y > 100 && y > last + 15) bar.style.transform = 'translateY(100%)';
            else if (y < last - 10) bar.style.transform = 'translateY(0)';
            last = y;
        }, { passive: true });
    })();
</script>
