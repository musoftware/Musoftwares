@extends('shop::layout')

@section('content')
    <section class="hero">
        <h1 class="title">{{ config('shop.name') }}</h1>
        <p class="lead">{{ config('shop.tagline') }}</p>
    </section>

    @if ($categories->isNotEmpty())
        <h2 class="section-title">الأقسام</h2>
        <div class="cats">
            @foreach ($categories as $category)
                <a class="cat" href="{{ $category->url() }}">
                    @if ($category->imageUrl())
                        <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" width="400" height="400" loading="lazy" decoding="async">
                        {{ $category->name }}
                    @else
                        <span class="cat-text">{{ $category->name }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="section-title">وصل حديثا</h2>
    @include('shop::partials.grid', ['products' => $products, 'emptyText' => 'لسه مفيش منتجات. ارجع لنا قريب.'])
@endsection
