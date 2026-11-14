{{-- The store's collections as picture cards: on the home page and on the page of all collections. --}}
    <div class="cats">
        @foreach ($categories as $category)
            <a class="cat" href="{{ $category->url() }}">
                @if ($category->imageUrl())
                    <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" width="400" height="400" loading="lazy" decoding="async">
                    {{ $category->name }}
                @else
                    <span class="cat-text">{{ $category->name }}</span>
                @endif
            </a>
        @endforeach
    </div>
