@php($lang = $project->customerLanguage())
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('builder.study_title', [], $lang) }} - {{ $project->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap">
    <style>
        :root{--ink:#111;--muted:#5f6368;--line:#e3e3e3;--soft:#f6f6f6}
        *{box-sizing:border-box}
        body{margin:0;background:#fff;color:var(--ink);font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;font-size:16px;line-height:1.75;overflow-x:clip}
        main{max-width:820px;margin:0 auto;padding:32px 16px 64px}
        header{border-bottom:2px solid var(--ink);padding-bottom:16px;margin-bottom:24px}
        header p{margin:0;color:var(--muted);font-size:14px}
        h1{margin:4px 0 0;font-size:28px;line-height:1.3}
        h2{font-size:20px;margin:32px 0 8px;padding-top:8px;border-top:1px solid var(--line);break-after:avoid}
        h3{font-size:17px;margin:16px 0 4px}
        p,li{overflow-wrap:anywhere}
        ul,ol{padding-inline-start:24px}
        table{width:100%;border-collapse:collapse;margin:8px 0;font-size:15px;break-inside:avoid}
        th,td{border:1px solid var(--line);padding:8px 10px;text-align:start;vertical-align:top}
        th{background:var(--soft)}
        .table-wrap{overflow-x:auto}
        .save{position:sticky;bottom:16px;display:flex;justify-content:center;margin-top:32px}
        .save button{min-height:48px;padding:0 28px;border:0;border-radius:12px;background:var(--ink);color:#fff;font:inherit;font-weight:600;cursor:pointer}
        .save button:focus-visible{outline:3px solid #2563eb;outline-offset:2px}
        footer{margin-top:40px;color:var(--muted);font-size:13px}
        @page{size:A4;margin:18mm 16mm}
        @media print{.save{display:none}main{padding:0;max-width:none}body{font-size:12pt}h2{break-after:avoid}}
    </style>
</head>
<body>
<main>
    <header>
        <p>{{ __('builder.study_title', [], $lang) }}</p>
        <h1 dir="auto">{{ $project->title }}</h1>
        <p>{{ __('builder.study_for', [], $lang) }} · <span dir="ltr">{{ $project->updated_at?->format('Y-m-d') }}</span></p>
    </header>

    {{-- written by the model, then reduced to safe structural tags with no attributes (FeasibilityStudy::clean) --}}
    <article id="study">{!! $project->study_html !!}</article>

    <footer>Musoftware · musoftwares.com</footer>

    <div class="save"><button type="button" onclick="window.print()">{{ __('builder.study_save', [], $lang) }}</button></div>
</main>
<script>
    // wide tables scroll inside their own box on phones instead of widening the page
    document.querySelectorAll('#study table').forEach((table) => {
        const wrap = document.createElement('div');
        wrap.className = 'table-wrap';
        table.replaceWith(wrap);
        wrap.append(table);
    });
</script>
</body>
</html>
