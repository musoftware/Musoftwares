@php use Modules\Shop\Support\Money; @endphp
{{-- The cart, opened from the header on any page. Without JavaScript the header link goes to the cart page instead. --}}
<dialog class="panel" id="cart-panel" aria-labelledby="cart-panel-title" @if (session('cart_open')) data-open @endif>
    <div class="panel-head">
        <h2 id="cart-panel-title">سلة المشتريات @if ($cartCount > 0)<small>({{ $cartCount }})</small>@endif</h2>
        <button class="icon-link" type="button" data-cart-close aria-label="اقفل السلة">@include('shop::partials.icon', ['name' => 'close'])</button>
    </div>
    <div class="panel-body">
        @if (session('cart_open') && session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @forelse ($cartLines as $line)
            @include('shop::partials.cart-line', ['line' => $line, 'panel' => true])
        @empty
            <p class="empty">السلة فاضية.</p>
        @endforelse
    </div>
    @if ($cartLines->isNotEmpty())
        <div class="panel-foot">
            <div class="grand"><span>المجموع</span><span>{{ Money::format($cartSubtotal) }}</span></div>
            <a class="btn" href="{{ route('shop.checkout') }}">إتمام الطلب</a>
            <a class="btn alt" href="{{ route('shop.cart') }}">عرض السلة</a>
        </div>
    @endif
</dialog>
<script>
    (function () {
        var panel = document.getElementById('cart-panel');
        if (!panel || !panel.showModal) return;
        document.querySelectorAll('[data-cart-open]').forEach(function (link) {
            link.addEventListener('click', function (event) { event.preventDefault(); panel.showModal(); });
        });
        panel.addEventListener('click', function (event) {
            if (event.target === panel || event.target.closest('[data-cart-close]')) panel.close();
        });
        if (panel.hasAttribute('data-open')) panel.showModal();
    })();
</script>
