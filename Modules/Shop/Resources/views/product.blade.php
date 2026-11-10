@extends('shop::layout')

@php use Modules\Shop\Support\Money; @endphp

@section('content')
    <ol class="crumbs">
        <li><a href="{{ route('shop.home') }}">الرئيسية</a></li>
        @if ($product->category?->is_active)
            <li><a href="{{ $product->category->url() }}">{{ $product->category->name }}</a></li>
        @endif
        <li aria-current="page">{{ $product->name }}</li>
    </ol>

    <div class="pdp">
        <div>
            <img id="main-image" class="main-img" src="{{ $product->mainImageUrl() }}" alt="{{ $product->name }}" width="800" height="800" fetchpriority="high" data-color-images="{{ json_encode((object) $product->colorImageUrls(), JSON_UNESCAPED_UNICODE) }}">
            @if (count($product->imageUrls()) > 1)
                <div class="thumbs">
                    @foreach ($product->imageUrls() as $imageUrl)
                        <a href="{{ $imageUrl }}" data-thumb @if ($loop->first) aria-current="true" @endif aria-label="صورة {{ $loop->iteration }} من {{ $product->name }}">
                            <img src="{{ $imageUrl }}" alt="" width="64" height="64" loading="lazy" decoding="async">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="title">{{ $product->name }}</h1>
            <p class="price">
                <span>{{ Money::format($product->price) }}</span>
                @if ($product->hasDiscount())
                    <del>{{ Money::format($product->compare_at_price) }}</del>
                    <span class="save">خصم {{ $product->discountPercent() }}%</span>
                @endif
            </p>

            @if ($product->in_stock)
                @include('shop::partials.buy-form')
            @else
                <p class="err" role="status">نفذت الكمية من المنتج ده حاليا.</p>
            @endif

            @if ($product->description)
                <div class="desc">{!! nl2br(e($product->description)) !!}</div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <h2 class="section-title">منتجات مشابهة</h2>
        <div class="grid">
            @foreach ($related as $relatedProduct)
                @include('shop::partials.product-card', ['product' => $relatedProduct])
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var mainImage = document.getElementById('main-image');
            var thumbs = document.querySelectorAll('[data-thumb]');
            var colorImages = JSON.parse(mainImage.dataset.colorImages || '{}');

            function show(url) {
                mainImage.src = url;
                thumbs.forEach(function (thumb) {
                    if (thumb.href === url) {
                        thumb.setAttribute('aria-current', 'true');
                    } else {
                        thumb.removeAttribute('aria-current');
                    }
                });
            }

            function showColor(input) {
                if (input.checked && colorImages[input.value]) {
                    show(colorImages[input.value]);
                }
            }

            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function (event) {
                    event.preventDefault();
                    show(thumb.href);
                });
            });

            document.querySelectorAll('input[name="color"]').forEach(function (input) {
                input.addEventListener('change', function () { showColor(input); });
                showColor(input);
            });
        })();
    </script>
@endpush
