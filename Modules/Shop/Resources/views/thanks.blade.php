@extends('shop::layout')

@section('content')
    <div class="thanks">
        <h1 class="title">تم استلام طلبك</h1>
        <p class="lead">رقم الطلب: {{ $order->number() }}</p>
        <p class="lead">الإجمالي عند الاستلام: {{ \Modules\Shop\Support\Money::format($order->total) }}</p>
        <p class="lead">هنتصل بيك على الموبايل لتأكيد الطلب.</p>
        <div class="actions"><a class="btn alt" href="{{ route('shop.home') }}">ارجع للمتجر</a></div>
    </div>
@endsection
