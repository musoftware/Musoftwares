<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('services.daily_digest_subject') }}</title>
    <style>
        body {
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f7;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .header {
            text-align: center;
            padding-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1d1d1f;
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 14px;
            color: #86868b;
            margin-top: 5px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1d1d1f;
            margin: 35px 0 15px 0;
            border-bottom: 1px solid #e8e8ed;
            padding-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            border: 1px solid #e8e8ed;
            transition: transform 0.2s ease;
        }
        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .title-group {
            margin-bottom: 12px;
        }
        .title-en {
            font-size: 16px;
            font-weight: 600;
            color: #1d1d1f;
            margin: 0;
        }
        .title-ar {
            font-size: 16px;
            font-weight: 600;
            color: #1d1d1f;
            margin: 4px 0 0 0;
            text-align: right;
            direction: rtl;
        }
        .desc-en {
            font-size: 14px;
            color: #515154;
            line-height: 1.5;
            margin: 10px 0;
        }
        .desc-ar {
            font-size: 14px;
            color: #515154;
            line-height: 1.5;
            margin: 10px 0;
            text-align: right;
            direction: rtl;
        }
        .btn-group {
            margin-top: 18px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            background-color: #0071e3;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 980px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .btn:hover {
            background-color: #0077ed;
        }
        .footer {
            text-align: center;
            padding-top: 40px;
            font-size: 12px;
            color: #86868b;
            line-height: 1.5;
        }
        .footer a {
            color: #0071e3;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="https://www.musoftwares.com" class="logo">Musoftware</a>
            <div class="subtitle">{{ __('services.daily_digest_fcm_body') }}</div>
        </div>

        <!-- Website Services Section -->
        @if($websiteServices->isNotEmpty())
            <div class="section-title">
                {{ __('services.explore_website_services') }} / {{ __('services.explore_website_services', [], 'ar') }}
            </div>
            @foreach($websiteServices as $service)
                <div class="card">
                    @if($service->primary_image_en)
                        <img src="{{ asset($service->primary_image_en) }}" alt="Service Image" class="card-image">
                    @endif
                    <div class="title-group">
                        <h3 class="title-en">{{ $service->title_en }}</h3>
                        <h3 class="title-ar">{{ $service->title_ar }}</h3>
                    </div>
                    <p class="desc-en">{{ Str::limit(strip_tags($service->description_en), 180) }}</p>
                    <p class="desc-ar">{{ Str::limit(strip_tags($service->description_ar), 180) }}</p>
                    
                    <div class="btn-group">
                        <a href="{{ route('website-services.show', ['slug' => $service->slug]) }}" class="btn">
                            {{ __('services.explore_button') }} / {{ __('services.explore_button', [], 'ar') }}
                        </a>
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Marketplace Services Section -->
        @if($marketplaceServices->isNotEmpty())
            <div class="section-title">
                {{ __('services.explore_marketplace_services') }} / {{ __('services.explore_marketplace_services', [], 'ar') }}
            </div>
            @foreach($marketplaceServices as $service)
                @php
                    $titleEn = ($service->title_translations['en'] ?? '') ?: $service->title;
                    $titleAr = ($service->title_translations['ar'] ?? '') ?: $service->title;
                    $descEn = ($service->description_translations['en'] ?? '') ?: $service->description;
                    $descAr = ($service->description_translations['ar'] ?? '') ?: $service->description;
                @endphp
                <div class="card">
                    @if($service->cover_image)
                        <img src="{{ $service->cover_image }}" alt="Service Cover" class="card-image">
                    @endif
                    <div class="title-group">
                        <h3 class="title-en">{{ $titleEn }}</h3>
                        <h3 class="title-ar">{{ $titleAr }}</h3>
                    </div>
                    <p class="desc-en">{{ Str::limit(strip_tags($descEn), 180) }}</p>
                    <p class="desc-ar">{{ Str::limit(strip_tags($descAr), 180) }}</p>
                    
                    <div class="btn-group">
                        <a href="{{ $service->url }}" class="btn">
                            {{ __('services.explore_button') }} / {{ __('services.explore_button', [], 'ar') }}
                        </a>
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} Musoftware. All rights reserved.</p>
            <p>
                You are receiving this because you registered on our platform. 
                If you wish to change your notification preferences, please visit your <a href="https://www.musoftwares.com/app/profile">Profile Settings</a>.
            </p>
        </div>
    </div>
</body>
</html>
