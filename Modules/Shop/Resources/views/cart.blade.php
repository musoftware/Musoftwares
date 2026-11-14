@extends('shop::layout')

@php use Modules\Shop\Support\Money; @endphp

@section('content')
    <h1 class="title">سلة المشتريات</h1>

    @if ($lines->isEmpty())
        <p class="empty">السلة فاضية.</p>
        <a class="btn alt" href="{{ route('shop.home') }}">ارجع للمتجر</a>
    @else
        @foreach ($lines as $line)
            @include('shop::partials.cart-line', ['line' => $line])
        @endforeach

        <div class="totals">
            <div class="grand"><span>المجموع</span><span>{{ Money::format($subtotal) }}</span></div>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('shop.checkout') }}">إتمام الطلب</a>
            <a class="btn alt" href="{{ route('shop.home') }}">كمل تسوق</a>
        </div>
    @endif
@endsection
