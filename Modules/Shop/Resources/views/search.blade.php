@extends('shop::layout')

@section('content')
    <h1 class="title">البحث</h1>
    <form class="search-form" role="search" action="{{ route('shop.search') }}">
        <input type="search" name="q" value="{{ $term }}" placeholder="اكتب اسم المنتج" aria-label="اسم المنتج" required>
        <button class="btn" type="submit">بحث</button>
    </form>

    @if ($products)
        @include('shop::partials.sorts')
        @include('shop::partials.grid', ['products' => $products, 'emptyText' => 'مفيش منتجات بالاسم ده. جرب كلمة تانية.'])
        {{ $products->links('shop::partials.pagination') }}
    @endif
@endsection
