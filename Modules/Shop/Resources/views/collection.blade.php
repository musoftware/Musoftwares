@extends('shop::layout')

@section('content')
    <ol class="crumbs">
        <li><a href="{{ route('shop.home') }}">الرئيسية</a></li>
        <li aria-current="page">{{ $category->name }}</li>
    </ol>
    <h1 class="title">{{ $category->name }}</h1>
    @if ($category->description)
        <p class="lead">{{ $category->description }}</p>
    @endif
    <div class="listing">
        @include('shop::partials.filters')
        <div class="listing-main">
            @include('shop::partials.sorts')
            @include('shop::partials.grid', ['products' => $products, 'emptyText' => $filters->count() > 0 ? 'مفيش منتجات بالاختيارات دي. جرب تشيل فلتر.' : null])
            {{ $products->links('shop::partials.pagination') }}
        </div>
    </div>
@endsection
