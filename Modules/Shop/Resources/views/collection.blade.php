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
    @include('shop::partials.sorts')
    @include('shop::partials.grid', ['products' => $products])
    {{ $products->links('shop::partials.pagination') }}
@endsection
