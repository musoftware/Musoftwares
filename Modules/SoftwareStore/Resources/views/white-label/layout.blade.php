{{-- A partner's own store: only their name, logo, color and contact. Nothing here names Musoftware. --}}
@php
    $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $embed = $embed ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ $pageTitle ?? $partner->brand_name }}</title>
    @if ($partner->logoUrl())
        <link rel="icon" href="{{ $partner->logoUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    @vite(['resources/js/app.tsx'])
    <style>:root { --wl-accent: {{ $partner->accent_color }}; } body { font-family: {{ $locale === 'ar' ? "'Cairo'" : "'Inter'" }}, system-ui, sans-serif; }</style>
    @stack('head')
</head>
<body class="min-h-screen antialiased bg-[#fbfbfd] text-[#1d1d1f]">
    @unless ($embed)
        <header class="bg-white border-b border-black/5">
            <div class="max-w-[1100px] mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
                <a href="{{ route('white-label.store', $partner) }}" class="flex items-center gap-3 min-w-0">
                    @if ($partner->logoUrl())
                        <img src="{{ $partner->logoUrl() }}" alt="" class="h-9 w-9 rounded-lg object-contain">
                    @endif
                    <span class="font-bold truncate">{{ $partner->brand_name }}</span>
                </a>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => $locale === 'ar' ? 'en' : 'ar']) }}" class="text-black/60 hover:text-black">{{ $locale === 'ar' ? 'English' : 'العربية' }}</a>
                    @if ($partner->whatsappNumber())
                        <a href="https://wa.me/{{ $partner->whatsappNumber() }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-9 items-center gap-1.5 rounded-full bg-[#25d366] px-4 font-semibold text-white">
                            <i class="ri-whatsapp-line" aria-hidden="true"></i>{{ __('white_label.contact') }}
                        </a>
                    @endif
                </div>
            </div>
        </header>
    @endunless

    <main>@yield('content')</main>

    @unless ($embed)
        <footer class="py-8 text-center text-sm text-black/50">
            &copy; {{ date('Y') }} {{ $partner->brand_name }}
            @if ($partner->support_email)
                &middot; <a href="mailto:{{ $partner->support_email }}" class="hover:underline">{{ $partner->support_email }}</a>
            @endif
        </footer>
    @endunless
</body>
</html>
