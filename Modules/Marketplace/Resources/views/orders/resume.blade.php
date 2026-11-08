@extends('marketplace::layouts.marketplace-master')

@section('content')
@php $isAr = app()->getLocale() === 'ar'; @endphp
<div class="max-w-md mx-auto px-4 py-16 text-center">
    @if ($ready)
        <h1 class="text-2xl font-bold text-[#1d1d1f]">{{ $isAr ? 'الدفع تم' : 'Payment received' }}</h1>
        <p class="mt-3 text-[15px] text-[#515154]">{{ $isAr ? 'بنسجل طلبك دلوقتي.' : 'We are placing your order now.' }}</p>
        <form id="resume-order" action="{{ route('marketplace.orders.store') }}" method="POST" class="mt-6">
            @csrf
            <input type="hidden" name="package_id" value="{{ $package->id }}">
            <button type="submit" class="w-full min-h-11 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white font-semibold text-[15px]">{{ $isAr ? 'أكد الطلب' : 'Confirm the order' }}</button>
        </form>
        @unless ($errors->any())
            <script>document.getElementById('resume-order').submit();</script>
        @endunless
    @else
        <h1 class="text-2xl font-bold text-[#1d1d1f]">{{ $isAr ? 'بنأكد الدفع' : 'Confirming your payment' }}</h1>
        <p class="mt-3 text-[15px] text-[#515154]">{{ $isAr ? 'استنى ثواني، الصفحة هتكمل لوحدها.' : 'Please wait a few seconds. This page will continue by itself.' }}</p>
        <p class="mt-6 text-[14px] text-[#515154]">{{ $isAr ? 'لو اتأخر كتير، كلمنا على واتساب:' : 'If it takes too long, message us on WhatsApp:' }}
            <a href="{{ \App\Support\SiteNav::WHATSAPP_URL }}" target="_blank" rel="noopener noreferrer" class="text-[#0071e3] underline">{{ $isAr ? 'واتساب' : 'WhatsApp' }}</a>
        </p>
        <script>setTimeout(function () { location.reload(); }, 5000);</script>
    @endif
</div>
@endsection
