@php
    $paths = [
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-4.3-4.3',
        'bag' => 'M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2',
    ];
@endphp
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] }}"/></svg>
