@if ($products->isEmpty())
    <p class="empty">{{ $emptyText ?? 'لسه مفيش منتجات هنا.' }}</p>
@else
    <div class="grid">
        @foreach ($products as $product)
            @include('shop::partials.product-card', ['product' => $product, 'firstRow' => $loop->index < 2])
        @endforeach
    </div>
@endif
