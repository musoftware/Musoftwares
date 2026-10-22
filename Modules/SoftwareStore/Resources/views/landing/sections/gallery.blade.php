@if (count($gallery) > 1)
<section class="py-16 sm:py-20">
    <div class="max-w-[1100px] mx-auto px-4 sm:px-6">
        @include('softwarestore::landing.heading', ['kicker' => $copy['gallery']['kicker'] ?? null, 'title' => $copy['gallery']['title'] ?? __('software.gallery')])
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            @foreach ($gallery as $i => $url)
                <button type="button" data-lightbox="{{ $url }}" class="block text-start rounded-2xl overflow-hidden border border-black/10 bg-white">
                    <img src="{{ $url }}" alt="{{ $product->localized('name') }} {{ $i + 1 }}" loading="lazy" class="w-full h-auto">
                </button>
            @endforeach
        </div>
    </div>
</section>

<dialog id="sw-lightbox" class="max-w-[95vw] max-h-[95vh] p-0 bg-transparent backdrop:bg-black/80">
    <img id="sw-lightbox-img" alt="" class="max-w-[95vw] max-h-[90vh] rounded-xl">
    <form method="dialog" class="text-center mt-2">
        <button class="h-10 px-6 rounded-xl bg-white font-semibold">{{ __('software.close') }}</button>
    </form>
</dialog>
@push('scripts')
<script>
    document.querySelectorAll('[data-lightbox]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('sw-lightbox-img').src = btn.dataset.lightbox;
            document.getElementById('sw-lightbox').showModal();
        });
    });
</script>
@endpush
@endif
