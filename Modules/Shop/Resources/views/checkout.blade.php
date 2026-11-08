@extends('shop::layout')

@php use Modules\Shop\Support\Money; @endphp

@section('content')
    <h1 class="title">إتمام الطلب</h1>
    <p class="lead">الدفع عند الاستلام. مش محتاج تعمل حساب.</p>

    <div class="checkout">
        <form method="post" action="{{ route('shop.checkout.store') }}">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;width:1px;height:1px;opacity:0">
            @include('shop::partials.input', ['name' => 'customer_name', 'label' => 'الاسم بالكامل', 'autocomplete' => 'name'])
            @include('shop::partials.input', ['name' => 'phone', 'label' => 'رقم الموبايل', 'type' => 'tel', 'autocomplete' => 'tel', 'dir' => 'ltr'])

            <div class="field">
                <label for="governorate">المحافظة</label>
                <select id="governorate" name="governorate" autocomplete="address-level1" required>
                    <option value="">اختار المحافظة</option>
                    @foreach (config('shop.governorates') as $governorate)
                        <option value="{{ $governorate }}" @selected(old('governorate') === $governorate)>{{ $governorate }}</option>
                    @endforeach
                </select>
                @error('governorate') <p class="err">{{ $message }}</p> @enderror
            </div>

            @include('shop::partials.input', ['name' => 'address', 'label' => 'العنوان بالتفصيل', 'autocomplete' => 'street-address'])

            <div class="field">
                <label for="notes">ملاحظات (اختياري)</label>
                <textarea id="notes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea>
                @error('notes') <p class="err">{{ $message }}</p> @enderror
            </div>

            <div class="actions"><button class="btn" type="submit">تأكيد الطلب</button></div>
        </form>

        <aside aria-label="ملخص الطلب">
            <h2 class="section-title" style="margin-top:0">طلبك</h2>
            @foreach ($lines as $line)
                <div class="line">
                    <img src="{{ $line['product']->mainImageUrl() }}" alt="{{ $line['product']->name }}" width="72" height="72" loading="lazy">
                    <div>
                        <p class="line-name">{{ $line['product']->name }}</p>
                        @include('shop::partials.line-meta', ['line' => $line])
                        <p>{{ $line['quantity'] }} × {{ Money::format($line['unit_price']) }}</p>
                    </div>
                </div>
            @endforeach
            <div class="totals">
                <div><span>المنتجات</span><span>{{ Money::format($subtotal) }}</span></div>
                <div><span>الشحن</span><span>{{ $shipping > 0 ? Money::format($shipping) : 'مجانا' }}</span></div>
                <div class="grand"><span>الإجمالي</span><span>{{ Money::format($total) }}</span></div>
            </div>
        </aside>
    </div>
@endsection
