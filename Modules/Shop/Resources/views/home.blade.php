@extends('shop::layout')

@section('content')
    <section class="hero">
        <h1 class="title">{{ config('shop.name') }}</h1>
        <p class="lead">{{ config('shop.tagline') }}</p>
    </section>

    @if ($categories->isNotEmpty())
        <h2 class="section-title">الأقسام</h2>
        @include('shop::partials.categories', ['categories' => $categories])
    @endif

    <h2 class="section-title">وصل حديثا</h2>
    @include('shop::partials.grid', ['products' => $products, 'emptyText' => 'لسه مفيش منتجات. ارجع لنا قريب.'])
@endsection
