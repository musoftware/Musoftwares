<p class="announce">{{ config('shop.announcement') }}</p>
<header class="head">
    <div class="wrap head-row">
        <details class="menu">
            <summary class="icon-link" aria-label="القائمة">@include('shop::partials.icon', ['name' => 'menu'])</summary>
            <nav class="menu-panel" aria-label="الأقسام">
                <a href="{{ route('shop.home') }}">الرئيسية</a>
                @foreach ($navCategories as $navCategory)
                    <a href="{{ $navCategory->url() }}">{{ $navCategory->name }}</a>
                @endforeach
            </nav>
        </details>
        <a class="logo" href="{{ route('shop.home') }}">{{ config('shop.name') }}</a>
        <nav class="nav" aria-label="الأقسام">
            @foreach ($navCategories as $navCategory)
                <a href="{{ $navCategory->url() }}">{{ $navCategory->name }}</a>
            @endforeach
        </nav>
        <div class="head-icons">
            <a class="icon-link" href="{{ route('shop.search') }}" aria-label="بحث">@include('shop::partials.icon', ['name' => 'search'])</a>
            <a class="icon-link" href="{{ route('shop.cart') }}" aria-label="السلة، فيها {{ $cartCount }} قطعة">
                @include('shop::partials.icon', ['name' => 'bag'])
                @if ($cartCount > 0)
                    <span class="count" aria-hidden="true">{{ $cartCount }}</span>
                @endif
            </a>
        </div>
    </div>
</header>
