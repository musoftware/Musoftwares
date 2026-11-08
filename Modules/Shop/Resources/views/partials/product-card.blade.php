@php $firstRow = $firstRow ?? false; @endphp
<article>
    <a class="card" href="{{ $product->url() }}">
        <div class="card-img">
            @if ($product->mainImageUrl())
                <img src="{{ $product->mainImageUrl() }}" alt="{{ $product->name }}" width="500" height="500"
                     @if ($firstRow) fetchpriority="high" @else loading="lazy" @endif decoding="async">
            @endif
            @if (! $product->in_stock)
                <span class="flag out">نفذت الكمية</span>
            @elseif ($product->hasDiscount())
                <span class="flag">خصم {{ $product->discountPercent() }}%</span>
            @endif
        </div>
        <h3>{{ $product->name }}</h3>
        <p class="price">
            <span>{{ \Modules\Shop\Support\Money::format($product->price) }}</span>
            @if ($product->hasDiscount())
                <del>{{ \Modules\Shop\Support\Money::format($product->compare_at_price) }}</del>
            @endif
        </p>
    </a>
</article>
