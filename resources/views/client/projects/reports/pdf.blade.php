@php
    $isRtl = preg_match('/\p{Arabic}/u', ($report->title ?? '') . ' ' . ($report->body ?? '')) === 1;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title>{{ $report->title }} — {{ $project->project_name }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            line-height: 1.55;
            font-size: 12px;
            background: #ffffff;
        }

        .page-container {
            width: 100%;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .rtl {
            direction: rtl;
            text-align: right;
        }

        .header {
            border-bottom: 3px solid #059669;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }

        .eyebrow {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #059669;
            border: 1px solid #a7f3d0;
            background: #ecfdf5;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 10px;
            line-height: 1.25;
        }

        .meta {
            margin-top: 8px;
            font-size: 10px;
            color: #64748b;
        }

        .meta strong {
            color: #334155;
            font-weight: 600;
        }

        .ltr .meta span {
            margin-right: 14px;
        }

        .rtl .meta span {
            margin-left: 14px;
        }

        .summary {
            background: #f8fafc;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-style: italic;
            color: #475569;
            font-size: 11px;
        }

        .ltr .summary {
            border-left: 3px solid #cbd5e1;
        }

        .rtl .summary {
            border-right: 3px solid #cbd5e1;
        }

        .body {
            font-size: 12px;
            color: #1f2937;
        }

        .body h1,
        .body h2,
        .body h3 {
            color: #0f172a;
            margin: 18px 0 8px;
            font-weight: 700;
        }

        .body h1 { font-size: 18px; }
        .body h2 { font-size: 15px; }
        .body h3 { font-size: 13px; }

        .body p { margin: 0 0 10px; }

        .body ul,
        .body ol {
            margin: 0 0 10px 22px;
        }

        .body li { margin-bottom: 4px; }

        .body strong { font-weight: 700; color: #0f172a; }

        .body code {
            background: #f3f4f6;
            color: #1f2937;
            padding: 2px 5px;
            border-radius: 4px;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 10px;
        }

        .body pre {
            background: #f9fafb;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            border-radius: 6px;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9.5px;
            margin: 0 0 12px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .body pre code {
            background: transparent;
            padding: 0;
            border-radius: 0;
            border: none;
            color: inherit;
            font-size: inherit;
        }

        .body blockquote {
            padding: 0 12px;
            color: #475569;
            margin: 0 0 10px;
            font-style: italic;
        }

        .ltr blockquote {
            border-left: 3px solid #cbd5e1;
        }

        .rtl blockquote {
            border-right: 3px solid #cbd5e1;
        }

        .body hr {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
        }

        .body a {
            color: #059669;
            text-decoration: none;
        }

        .body table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 12px;
        }

        .body th,
        .body td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 11px;
        }

        .ltr th,
        .ltr td {
            text-align: left;
        }

        .rtl th,
        .rtl td {
            text-align: right;
        }

        .body th {
            background: #f1f5f9;
            font-weight: 600;
        }

        .footer {
            position: fixed;
            bottom: 24px;
            left: 50px;
            right: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }

        .empty {
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            padding: 30px 0;
        }
    </style>
</head>

<body>
    <div class="page-container {{ $isRtl ? 'rtl' : 'ltr' }}">
        <div class="header">
            <span class="eyebrow">{{ __('general.report') }}</span>
            <h1 class="title">{{ $report->title }}</h1>
            <div class="meta">
                <span><strong>{{ __('general.project') }}:</strong> {{ $project->project_name }}</span>
                @if ($report->published_at)
                    <span><strong>{{ __('general.publish_date') }}:</strong> {{ $report->published_at->format('Y-m-d H:i') }}</span>
                @endif
                @if ($report->author)
                    <span><strong>{{ __('general.report_author') }}:</strong> {{ $report->author->name }}</span>
                @endif
            </div>
        </div>

        @if (!empty($report->summary))
            <div class="summary">{{ $report->summary }}</div>
        @endif

        <div class="body">
            @if (!empty($report->body))
                {!! \Illuminate\Support\Str::markdown($report->body) !!}
            @else
                <p class="empty">{{ __('general.no_content') ?? 'No content available for this report.' }}</p>
            @endif
        </div>

        <div class="footer">
            {{ $project->project_name }} — {{ $report->title }} — {{ __('general.generated_on') }} {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>

</html>