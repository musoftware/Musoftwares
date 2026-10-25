<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
    @foreach ($urls as $url)
        <url>
            <loc>{{ $url['loc'] }}</loc>
            @foreach ($url['alternates'] ?? \App\Support\LocaleUrl::alternates($url['loc']) as $lang => $href)
                <xhtml:link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}"/>
            @endforeach
            <lastmod>{{ $url['lastmod'] }}</lastmod>
            <changefreq>{{ $url['changefreq'] }}</changefreq>
            <priority>{{ $url['priority'] }}</priority>
        </url>
    @endforeach
</urlset>
