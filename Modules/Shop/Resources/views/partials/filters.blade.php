@php
    $groups = ['size' => ['المقاس', $choices['sizes'], $filters->sizes], 'color' => ['اللون', $choices['colors'], $filters->colors]];
    $hasChoices = $choices['sizes'] !== [] || $choices['colors'] !== [] || $choices['prices'] !== [];
@endphp
@if ($hasChoices)
    <details class="filters" id="filters">
        <summary>@include('shop::partials.icon', ['name' => 'filter']) الفلاتر @if ($filters->count() > 0)<small>({{ $filters->count() }})</small>@endif</summary>
        <form method="get" action="{{ $category->url() }}" data-auto-submit="wide">
            @if ($sort !== 'featured') <input type="hidden" name="sort" value="{{ $sort }}"> @endif
            @foreach ($groups as $field => [$legend, $options, $chosen])
                @if ($options !== [])
                    <fieldset class="field">
                        <legend>{{ $legend }}</legend>
                        <div class="opts">
                            @foreach ($options as $option)
                                <label class="opt">
                                    <input type="checkbox" name="{{ $field }}[]" value="{{ $option }}" @checked(in_array($option, $chosen, true))>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            @endforeach
            @if ($choices['prices'] !== [])
                <fieldset class="field">
                    <legend>السعر</legend>
                    <label class="pick"><input type="radio" name="price" value="" @checked($filters->price === null)> كل الأسعار</label>
                    @foreach ($choices['prices'] as $range)
                        <label class="pick"><input type="radio" name="price" value="{{ $range['key'] }}" @checked($filters->price === $range['key'])> {{ $range['label'] }}</label>
                    @endforeach
                </fieldset>
            @endif
            <div class="filter-actions">
                <button class="small-btn" type="submit">اعرض النتائج</button>
                @if ($filters->count() > 0)
                    <a class="link-btn" rel="nofollow" href="{{ $sort === 'featured' ? $category->url() : $category->url().'?sort='.$sort }}">امسح الفلاتر</a>
                @endif
            </div>
        </form>
    </details>
    {{-- On a wide screen the filters stay open beside the products. On a phone they stay folded, so the products are not pushed down. --}}
    <script>if (window.matchMedia('(min-width:960px)').matches) document.getElementById('filters').open = true;</script>
@endif
