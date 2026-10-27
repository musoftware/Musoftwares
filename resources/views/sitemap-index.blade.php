{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($parts as $part)
    <sitemap>
        <loc>{{ route('sitemap.part', $part['name']) }}</loc>
        <lastmod>{{ $part['lastmod']->toAtomString() }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
