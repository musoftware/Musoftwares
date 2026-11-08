@php
    $sortLabels = ['featured' => 'المميز', 'newest' => 'الأحدث', 'price_low' => 'السعر: الأقل أولا', 'price_high' => 'السعر: الأعلى أولا'];
@endphp
<nav class="sorts" aria-label="ترتيب المنتجات">
    @foreach ($sortLabels as $key => $label)
        <a rel="nofollow" href="{{ request()->fullUrlWithQuery(['sort' => $key === 'featured' ? null : $key, 'page' => null]) }}"
           @if ($sort === $key) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</nav>
