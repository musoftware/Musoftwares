<footer class="foot">
    <div class="wrap">
        <strong>{{ config('shop.name') }}</strong>
        <p>{{ config('shop.announcement') }}</p>
        @if ($navCategories->isNotEmpty())
            <nav class="foot-links" aria-label="الأقسام">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ $navCategory->url() }}">{{ $navCategory->name }}</a>
                @endforeach
            </nav>
        @endif
        <small>&copy; {{ now('Africa/Cairo')->year }} {{ config('shop.name') }}</small>
    </div>
</footer>
