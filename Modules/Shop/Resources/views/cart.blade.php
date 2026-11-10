@extends('shop::layout')

@php use Modules\Shop\Support\Money; @endphp

@section('content')
    <h1 class="title">سلة المشتريات</h1>

    @if ($lines->isEmpty())
        <p class="empty">السلة فاضية.</p>
        <a class="btn alt" href="{{ route('shop.home') }}">ارجع للمتجر</a>
    @else
        @foreach ($lines as $line)
            <div class="line">
                <img src="{{ $line['product']->imageUrlFor($line['color']) }}" alt="{{ $line['product']->name }}" width="72" height="72" loading="lazy">
                <div>
                    <a class="line-name" href="{{ $line['product']->url() }}">{{ $line['product']->name }}</a>
                    @include('shop::partials.line-meta', ['line' => $line])
                    <p>{{ Money::format($line['line_total']) }}</p>
                    <div class="line-tools">
                        <form method="post" action="{{ route('shop.cart.update', $line['key']) }}">
                            @csrf @method('patch')
                            <input class="qty" type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ config('shop.max_quantity') }}" inputmode="numeric" aria-label="كمية {{ $line['product']->name }}" required>
                            <button class="small-btn" type="submit">تحديث</button>
                        </form>
                        <form method="post" action="{{ route('shop.cart.remove', $line['key']) }}">
                            @csrf @method('delete')
                            <button class="link-btn" type="submit">حذف</button>
                        </form>
                    </div>
                </div>
            </div>
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
