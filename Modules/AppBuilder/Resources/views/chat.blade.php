<!DOCTYPE html>
@php($rtl = app()->getLocale() === 'ar')
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="{{ $state ? 'noindex,nofollow' : 'index,follow' }}">
    <title>{{ __('builder.page_title') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600&display=swap">
    <style>
        :root{--ink:#111;--muted:#6b6b6b;--line:#e6e6e6;--soft:#f4f4f4;--accent:#111;--danger:#b42318}
        *{box-sizing:border-box}
        html,body{margin:0;background:#fff;color:var(--ink);font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;font-size:16px;line-height:1.7;overflow-x:clip}
        .wrap{max-width:720px;margin:0 auto;padding:24px 16px 168px}
        .empty{min-height:58vh;display:flex;flex-direction:column;justify-content:center;text-align:center}
        .empty h1{margin:0 0 8px;font-size:28px;font-weight:600;line-height:1.3}
        .empty p{margin:0;color:var(--muted)}
        .msg{margin:0 0 24px;white-space:pre-wrap;overflow-wrap:anywhere}
        .msg.team{border-inline-start:3px solid var(--ink);padding-inline-start:12px}
        .msg .who{display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:2px}
        .msg.user{margin-inline-start:auto;max-width:85%;width:fit-content;background:var(--soft);padding:10px 16px;border-radius:18px}
        .actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:8px 20px;border:1px solid var(--accent);border-radius:12px;background:var(--accent);color:#fff;font:inherit;font-weight:600;text-decoration:none;cursor:pointer}
        .btn.ghost{background:#fff;color:var(--ink);border-color:var(--line)}
        .btn:disabled{opacity:.5;cursor:default}
        .card{border:1px solid var(--line);border-radius:16px;padding:16px;margin:0 0 24px}
        .card .title{font-weight:600}
        .card .price{font-size:24px;font-weight:600;margin:4px 0}
        .card .note{color:var(--muted);font-size:14px;margin:0 0 12px}
        .form{display:grid;gap:8px;margin-top:12px}
        .form input:not([type=radio]){width:100%;min-height:44px;padding:8px 12px;border:1px solid var(--line);border-radius:10px;font:inherit}
        .shares{border:0;margin:0;padding:0;display:grid;gap:4px}
        .shares legend{font-weight:600;margin-bottom:4px}
        .share{display:flex;align-items:center;gap:8px;min-height:44px;padding:0 12px;border:1px solid var(--line);border-radius:10px;cursor:pointer}
        .share input{width:18px;height:18px}
        .status{display:flex;align-items:center;gap:12px;color:var(--muted);margin:0 0 24px}
        .spin{width:16px;height:16px;border:2px solid var(--line);border-top-color:var(--ink);border-radius:50%;animation:spin 1s linear infinite;flex:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        .hint{color:var(--muted);font-size:14px}
        .error{color:var(--danger);font-size:14px;margin:0 0 8px}
        .composer{position:fixed;inset-inline:0;bottom:0;background:#fff;border-top:1px solid var(--line);padding:12px 16px calc(16px + env(safe-area-inset-bottom))}
        .composer .box{max-width:720px;margin:0 auto}
        .composer form{display:flex;align-items:flex-end;gap:8px;border:1px solid var(--line);border-radius:20px;padding:8px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
        .composer textarea{flex:1;min-width:0;border:0;outline:0;resize:none;font:inherit;padding:6px 8px;max-height:200px;background:transparent}
        .send{width:44px;height:44px;border:0;border-radius:14px;background:var(--accent);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex:none}
        .send:disabled{opacity:.35;cursor:default}
        .send svg{transform:scaleX({{ $rtl ? '-1' : '1' }})}
        .attach{width:44px;height:44px;border:0;border-radius:14px;background:transparent;color:var(--muted);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex:none}
        .attach:hover{background:var(--soft);color:var(--ink)}
        .chips{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 8px}
        .chip{display:inline-flex;align-items:center;gap:6px;max-width:100%;padding:4px 6px 4px 12px;border:1px solid var(--line);border-radius:10px;font-size:14px;background:#fff}
        .chip span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px}
        .chip button{width:32px;height:32px;border:0;background:transparent;border-radius:8px;cursor:pointer;color:var(--muted);font:inherit}
        .ideas{margin-top:12px;display:grid;gap:8px}
        .checks{margin-top:12px;max-width:560px}
        .checks table{width:100%;border-collapse:collapse;border:1px solid var(--line);font-size:14px}
        .checks th,.checks td{padding:8px 12px;text-align:start;vertical-align:top;border-bottom:1px solid var(--line);overflow-wrap:anywhere}
        .checks tr:last-child td{border-bottom:0}
        .checks th{background:var(--soft);font-weight:600;color:var(--muted);overflow-wrap:normal}
        .checks td.result{white-space:nowrap;font-weight:600;color:#067647}
        .checks td.result.waiting{color:#b54708}
        .checks .note{display:block;margin:2px 0 0;color:var(--muted)}
        .starter{margin-top:12px;border:1px solid var(--line);border-radius:12px;overflow:hidden;max-width:520px}
        .starter img{display:block;width:100%;height:auto;aspect-ratio:16/10;object-fit:cover;object-position:top;border-bottom:1px solid var(--line)}
        .starter div{padding:10px 12px}
        .starter ul{margin:6px 0 0;padding-inline-start:18px;color:var(--muted);font-size:14px}
        .designs{margin-top:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,220px),1fr));gap:12px}
        .design{border:1px solid var(--line);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}
        .design img{display:block;width:100%;height:auto;aspect-ratio:16/10;object-fit:cover;object-position:top;border-bottom:1px solid var(--line)}
        .design div{padding:10px 12px;display:flex;flex-direction:column;gap:8px;flex:1}
        .design .btn{margin-top:auto}
        .idea{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;border:1px solid var(--line);border-radius:12px;padding:10px 12px}
        .idea .note{margin:2px 0 0}
        .msg.progress{border-inline-start:3px solid var(--line);padding-inline-start:12px}
        .shots{display:grid;gap:8px;margin-top:8px}
        .shots img{display:block;width:100%;height:auto;border:1px solid var(--line);border-radius:12px;background:var(--soft)}
        /* the customer's chats: always beside the chat on a wide screen, a panel from the menu button on a phone or a tablet */
        .chats{position:fixed;top:0;bottom:0;inset-inline-start:0;width:min(300px,86vw);display:flex;flex-direction:column;gap:4px;padding:12px;background:var(--soft);border-inline-end:1px solid var(--line);overflow-y:auto;z-index:40;transform:translateX({{ $rtl ? '100%' : '-100%' }});transition:transform .2s ease;visibility:hidden}
        body.chats-shown .chats{transform:none;visibility:visible;box-shadow:0 0 24px rgba(0,0,0,.12)}
        .chats-backdrop{display:none}
        body.chats-shown .chats-backdrop{display:block;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:39}
        .chats-head{display:flex;align-items:center;gap:8px;margin-bottom:8px}
        .chats-head .new{flex:1;justify-content:flex-start;padding:8px 12px}
        .chats h2{margin:8px 12px 4px;font-size:13px;font-weight:600;color:var(--muted)}
        .chats a.chat{display:grid;gap:0;min-height:44px;padding:8px 12px;border-radius:10px;color:var(--ink);text-decoration:none}
        .chats a.chat:hover{background:#ebebeb}
        .chats a.chat[aria-current]{background:#fff;box-shadow:inset 0 0 0 1px var(--line)}
        .chats a.chat strong{font-weight:400;font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .chats a.chat span{font-size:12px;color:var(--muted)}
        .chats .hint{margin:4px 12px}
        .icon-btn{width:44px;height:44px;flex:none;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);cursor:pointer}
        .icon-btn:hover{border-color:var(--ink)}
        .viewer .menu{margin-inline-end:auto}
        @media (prefers-reduced-motion:reduce){.chats{transition:none}}
        .bar{margin:-12px 0 24px}
        .bar .track{height:8px;border-radius:999px;background:var(--soft);overflow:hidden}
        .bar .fill{height:100%;border-radius:999px;background:var(--ink);transition:width .6s ease}
        .bar .pct{display:block;margin-top:6px;font-size:14px;font-weight:600}
        @media (prefers-reduced-motion:reduce){.bar .fill{transition:none}}
        .phases{margin:-8px 0 24px}
        .phases ol{list-style:none;margin:8px 0 0;padding:0;display:grid;gap:2px}
        .phases li{display:flex;align-items:flex-start;gap:12px;padding:8px 0}
        .phases li+li{border-top:1px solid var(--line)}
        .phases .mark{flex:none;width:22px;height:22px;border-radius:50%;border:2px solid var(--line);display:inline-flex;align-items:center;justify-content:center;margin-top:1px}
        .phases .done .mark{background:var(--ink);border-color:var(--ink);color:#fff}
        .phases .now .mark{border-color:var(--ink)}
        .phases .now .mark::after{content:'';width:8px;height:8px;border-radius:50%;background:var(--ink)}
        .phases .text{flex:1;min-width:0;overflow-wrap:anywhere}
        .phases .state{display:block;font-size:13px;color:var(--muted)}
        .phases .now .state{color:var(--ink);font-weight:600}
        .phases .next .text{color:var(--muted)}
        .msg .files{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;font-size:14px;color:var(--muted)}
        .msg .thumb{padding:0;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:var(--soft);cursor:zoom-in;min-width:44px;min-height:44px}
        .msg .thumb img{display:block;width:120px;height:120px;object-fit:cover}
        dialog.preview{max-width:min(96vw,900px);max-height:94vh;padding:12px;border:0;border-radius:16px;background:#fff;display:flex;flex-direction:column;gap:10px;align-items:center}
        dialog.preview:not([open]){display:none}
        dialog.preview::backdrop{background:rgba(0,0,0,.7)}
        dialog.preview img{max-width:100%;max-height:80vh;object-fit:contain;border-radius:10px}
        :focus-visible{outline:2px solid #2563eb;outline-offset:2px}
        [hidden]{display:none!important}
        .leave{max-width:min(420px,calc(100vw - 32px));border:1px solid var(--line);border-radius:16px;padding:24px;color:var(--ink)}
        .leave::backdrop{background:rgba(0,0,0,.35)}
        .leave h2{margin:0 0 8px;font-size:20px}
        .leave p{margin:0 0 16px;color:var(--muted);line-height:1.8}
        .how{margin-top:24px;padding:16px;border:1px solid var(--line);border-radius:16px;text-align:start}
        .how h2{margin:0 0 8px;font-size:16px}
        .how ol{margin:0;padding-inline-start:20px;display:grid;gap:6px;color:var(--muted);font-size:15px;line-height:1.7}
        /* the top bar stays at the top while the chat scrolls, so the cart is always one tap away */
        .viewer{position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid var(--line);padding:8px max(16px,calc((100% - 720px) / 2));display:flex;justify-content:flex-end;align-items:center;gap:12px;font-size:14px;color:var(--muted)}
        .viewer a{color:var(--ink);font-weight:600;min-height:44px;display:inline-flex;align-items:center}
        .viewer{flex-wrap:wrap}
        .card .before{color:var(--muted);text-decoration:line-through;font-size:16px;font-weight:400;margin-inline-start:8px}
        /* the list of requests: a cart button in the top bar opens it as a panel from the left (the whole screen on a phone) */
        .requests,.requests-backdrop{display:none}
        body.has-requests.requests-shown .requests{display:flex;flex-direction:column;gap:8px;position:fixed;left:0;top:0;bottom:0;width:min(380px,100vw);padding:16px;background:#fff;border-right:1px solid var(--line);box-shadow:0 0 24px rgba(0,0,0,.12);overflow-y:auto;z-index:30}
        body.has-requests.requests-shown .requests-backdrop{display:block;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:29}
        .requests .head{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .requests h2{margin:0;font-size:18px}
        .show-requests{display:none}
        body.has-requests .show-requests{display:inline-flex}
        .requests .close{display:inline-flex;align-items:center}
        /* the requests in four cards that open and close: working now, waiting for payment, in line, done */
        .requests > *{flex:none}
        .req-group{border:1px solid var(--line);border-radius:14px;background:#fff}
        .req-group summary{display:flex;align-items:center;gap:10px;min-height:48px;padding:0 14px;font-weight:600;cursor:pointer;list-style:none}
        .req-group summary::-webkit-details-marker{display:none}
        .req-group summary::after{content:"";width:8px;height:8px;margin-inline-start:auto;border-right:2px solid var(--muted);border-bottom:2px solid var(--muted);transform:translateY(-2px) rotate(45deg);transition:transform .15s}
        .req-group[open] summary::after{transform:translateY(2px) rotate(-135deg)}
        .req-group summary:focus-visible{outline:2px solid var(--ink);outline-offset:2px;border-radius:14px}
        .req-group .count{min-width:24px;height:24px;padding:0 7px;border-radius:12px;background:var(--soft);color:var(--ink);font-size:13px;line-height:24px;text-align:center}
        .req-group.building .count,.req-group.unpaid .count{background:var(--ink);color:#fff}
        .req-group .items{padding:0 14px 6px}
        .req-group .items .req:first-child{border-top:0}
        .req-group .pay{margin:0 0 8px}
        .req{display:flex;align-items:flex-start;gap:10px;min-height:44px;padding:10px 0;border-top:1px solid var(--line)}
        label.req{cursor:pointer}
        .req input{width:20px;height:20px;margin:4px 0 0;flex:none}
        .req .body{flex:1;min-width:0}
        .req .t{font-weight:600;overflow-wrap:anywhere}
        .req .meta{display:flex;flex-wrap:wrap;gap:4px 12px;font-size:14px;color:var(--muted)}
        .req .meta strong{color:var(--ink)}
        .req.done .t{color:var(--muted);font-weight:400}
        .req .cancel{min-height:32px;padding:0 10px;margin-inline-start:auto;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--muted);font:inherit;font-size:13px;cursor:pointer}
        .req .cancel:hover{color:var(--danger);border-color:var(--danger)}
        /* the paperclip's menu: a file, or a project the customer already has */
        .attach-menu{position:absolute;bottom:calc(100% + 8px);inset-inline-start:0;display:grid;gap:4px;min-width:260px;padding:6px;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:15}
        .attach-menu button{min-height:44px;padding:0 12px;border:0;border-radius:10px;background:transparent;color:var(--ink);font:inherit;text-align:start;cursor:pointer}
        .attach-menu button:hover{background:var(--soft)}
        .composer .box{position:relative}
        .bring{max-width:min(480px,calc(100vw - 32px));border:1px solid var(--line);border-radius:16px;padding:24px;color:var(--ink)}
        .bring::backdrop{background:rgba(0,0,0,.35)}
        .bring h2{margin:0 0 8px;font-size:20px}
        .bring p{margin:0 0 12px;color:var(--muted);line-height:1.8}
        .bring textarea{width:100%;min-height:96px;padding:8px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;resize:vertical}
        .requests .pay{display:grid;gap:8px;padding-top:12px;border-top:1px solid var(--line)}
        .requests .total{margin:0;font-weight:600}
        .show-requests{align-items:center;gap:8px;min-height:44px;padding:0 12px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font:inherit;font-weight:600;cursor:pointer}
        .show-requests:hover{border-color:var(--ink)}
        .req .review{font-weight:600;text-decoration:underline;min-height:44px;display:inline-flex;align-items:center}
        .show-requests .due{min-width:22px;height:22px;padding:0 6px;border-radius:11px;background:var(--ink);color:#fff;font-size:13px;line-height:22px;text-align:center}
        @media (min-width:768px){
            .wrap{max-width:760px;padding-inline:24px}
            .composer .box{max-width:760px}
        }
        @media (min-width:1100px){
            .chats{transform:none;visibility:visible;width:280px;z-index:5}
            body.chats-shown .chats{box-shadow:none}
            body.chats-shown .chats-backdrop,.chats-close,.viewer .menu{display:none}
            body{padding-inline-start:280px}
            .composer{inset-inline-start:280px}
        }
    </style>
</head>
<body>
@php($currentChat = $state['uuid'] ?? null)
<nav class="chats" id="chats" aria-label="{{ __('builder.chats') }}">
    <div class="chats-head">
        <a class="btn ghost new" href="{{ route('builder.index') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            {{ __('builder.new_chat') }}
        </a>
        <button type="button" class="icon-btn chats-close" id="chats-close" aria-label="{{ __('builder.requests.close') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <h2>{{ __('builder.chats') }}</h2>
    @forelse ($projects ?? [] as $own)
        <a class="chat" href="{{ $own['url'] }}" @if ($own['uuid'] === $currentChat) aria-current="page" @endif>
            <strong dir="auto">{{ $own['title'] }}</strong>
            <span>{{ $own['status'] }}</span>
        </a>
    @empty
        <p class="hint">{{ __('builder.no_chats') }}</p>
    @endforelse
</nav>
<div class="chats-backdrop" id="chats-backdrop"></div>

<div class="viewer">
    <button type="button" class="icon-btn menu" id="chats-open" aria-controls="chats" aria-expanded="false" aria-label="{{ __('builder.chats') }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <button type="button" class="show-requests" id="requests-open" aria-controls="requests"></button>
    <span dir="auto">{{ auth()->user()->name }}</span>
</div>
<main class="wrap" id="thread" aria-live="polite">
    <section class="empty" id="empty">
        <h1>{{ __('builder.greeting') }}</h1>
        <p>{{ __('builder.greeting_hint') }}</p>
        @unless ($state)
            <section class="how" aria-labelledby="how-title">
                <h2 id="how-title">{{ __('builder.how_title') }}</h2>
                <ol>
                    @foreach (__('builder.how_steps') as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </section>
        @endunless
    </section>
</main>

<div class="requests-backdrop" id="requests-backdrop"></div>
<aside class="requests" id="requests" aria-label="{{ __('builder.requests.title') }}"></aside>

<div class="composer">
    <div class="box">
        <p class="error" id="error" role="alert" hidden></p>
        <div class="attach-menu" id="attach-menu" role="menu" hidden>
            <button type="button" role="menuitem" id="attach-file">{{ __('builder.inspection.file') }}</button>
            <button type="button" role="menuitem" id="attach-project">{{ __('builder.inspection.bring') }}</button>
        </div>
        <div class="chips" id="chips" hidden></div>
        <form id="composer">
            <input type="file" id="files" accept="image/png,image/jpeg,image/webp,image/gif,application/pdf,.xlsx,.xls,.csv,.docx" multiple hidden>
            <button class="attach" type="button" id="attach" aria-label="{{ __('builder.attach') }}" title="{{ __('builder.attach') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <textarea id="text" dir="auto" rows="1" aria-label="{{ __('builder.placeholder') }}" maxlength="{{ config('builder.limits.message_chars') }}" placeholder="{{ __('builder.placeholder') }}" autofocus></textarea>
            <button class="send" type="submit" id="send" aria-label="{{ __('builder.send') }}" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </form>
    </div>
</div>

<script type="application/json" id="initial-state">@json($state)</script>
<script>
(() => {
    const T = @json(__('builder'));
    const PUSH = @json($push);
    const SIGNED_IN = @json(auth()->check());
    const BASE = @json(route('builder.index'));
    const CONTACT_URL = @json(route('company.contact'));
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const POLL_MS = 4000;
    const thread = document.getElementById('thread');
    const empty = document.getElementById('empty');
    const form = document.getElementById('composer');
    const input = document.getElementById('text');
    const sendBtn = document.getElementById('send');
    const errorBox = document.getElementById('error');
    const composer = document.querySelector('.composer');
    let state = JSON.parse(document.getElementById('initial-state').textContent);
    let waiting = false;
    let pollTimer = null;
    let payOpen = false;
    let picked = [];
    const MAX_FILES = @json((int) config('builder.limits.files_per_message'));
    // the server checks each kind's own limit; this only stops files that are too big for any kind
    const MAX_BYTES = @json(max((int) config('builder.limits.file_kb'), (int) config('builder.limits.office_file_kb')) * 1024);
    const PICTURE_BYTES = @json((int) config('builder.limits.file_kb') * 1024);
    // a big design picture is made smaller here for the chat; its original is asked for after payment.
    // The chat model takes pictures up to 8000 pixels a side.
    const PREVIEW_FROM_BYTES = 1536 * 1024;
    const PREVIEW_MAX_SIDE = 8000;
    const PREVIEW_WIDTH = 1600;
    const fileInput = document.getElementById('files');
    const chips = document.getElementById('chips');

    const el = (tag, attrs = {}, text = '') => {
        const node = document.createElement(tag);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        if (text) node.textContent = text;
        return node;
    };

    const link = (href, label, ghost = false) => el('a', {href, class: ghost ? 'btn ghost' : 'btn', target: '_blank', rel: 'noopener'}, label);

    // the input bar is fixed, so the thread keeps empty space as tall as the bar really is
    const keepRoomForComposer = () => { thread.style.paddingBottom = (composer.offsetHeight + 24) + 'px'; };

    const showError = (message) => {
        errorBox.textContent = message || '';
        errorBox.hidden = !message;
        keepRoomForComposer();
    };

    async function call(url, body) {
        const response = await fetch(url, {
            method: body ? 'POST' : 'GET',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF},
            body: body ? JSON.stringify(body) : undefined,
        });
        return answerOf(response);
    }

    // the visitor's country, looked up once from their own browser, so the chat answers in their dialect
    // (a Saudi customer hears Saudi Arabic); a slow or blocked lookup falls back to the time zone
    const TIMEZONE = (() => { try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (error) { return ''; } })();
    const COUNTRY = state ? Promise.resolve('') : (async () => {
        try {
            const response = await fetch('https://api.country.is/', {signal: AbortSignal.timeout(2000)});
            return response.ok ? String((await response.json()).country || '') : '';
        } catch (error) {
            return '';
        }
    })();

    async function upload(url, text, files) {
        const body = new FormData();
        if (text) body.append('text', text);
        body.append('timezone', TIMEZONE);
        body.append('country', await COUNTRY);
        files.forEach((file) => {
            body.append('files[]', file);
            if (file.isPreview) body.append('previews[]', file.name);
        });
        const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF}, body});
        return answerOf(response);
    }

    // the chat's own message when it sent one; the rate limiter's English "Too Many Attempts." (which the site's
    // error handler sends with status: error) is replaced by ours
    async function answerOf(response) {
        const data = await response.json().catch(() => ({}));
        if (response.status === 429 && data.status === 'error') throw new Error(T.errors.too_fast);
        // the sign-in ran out: Google, then back to this chat
        if (response.status === 401 && data.redirect) { location.href = data.redirect; throw new Error(data.message); }
        if (!response.ok) throw new Error(data.message || T.errors.unavailable);
        return data;
    }

    function renderChips() {
        chips.replaceChildren(...picked.map((file, index) => {
            const chip = el('span', {class: 'chip'});
            const remove = el('button', {type: 'button', 'aria-label': T.remove_file + ' ' + file.name}, '×');
            remove.addEventListener('click', () => { picked.splice(index, 1); renderChips(); });
            chip.append(el('span', {dir: 'auto'}, file.name), remove);
            return chip;
        }));
        if (picked.some((file) => file.isPreview)) chips.append(el('p', {class: 'hint'}, T.compressed));
        chips.hidden = picked.length === 0;
        sendBtn.disabled = waiting || (!input.value.trim() && picked.length === 0);
        keepRoomForComposer();
    }

    async function pickFiles(list) {
        showError('');
        for (const original of list) {
            if (picked.length >= MAX_FILES) { showError(T.too_many_files); break; }
            const file = await smallerIfBig(original);
            const limit = /^image\/|pdf$/.test(file.type) ? PICTURE_BYTES : MAX_BYTES;
            if (file.size > limit) { showError(file.type === 'application/pdf' ? T.pdf_too_big : T.file_too_big); continue; }
            picked.push(file);
        }
        renderChips();
    }

    /** A picture too big for the chat becomes a smaller JPEG, marked as a preview; other files stay as they are. */
    async function smallerIfBig(file) {
        if (!/^image\/(png|jpeg|webp)$/.test(file.type) || !window.createImageBitmap) return file;
        try {
            const bitmap = await createImageBitmap(file);
            const tooTall = Math.max(bitmap.width, bitmap.height) > PREVIEW_MAX_SIDE;
            if (file.size <= PREVIEW_FROM_BYTES && !tooTall) return file;
            return (await previewOf(bitmap, file.name)) || file;
        } catch (error) {
            return file;
        }
    }

    async function previewOf(bitmap, name) {
        const scale = Math.min(1, PREVIEW_WIDTH / bitmap.width, PREVIEW_MAX_SIDE / bitmap.height);
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        for (const quality of [0.85, 0.75, 0.65, 0.55]) {
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
            if (blob && blob.size <= PICTURE_BYTES * 0.9) {
                const preview = new File([blob], name.replace(/\.[^.]+$/, '') + '.jpg', {type: 'image/jpeg'});
                preview.isPreview = true;
                return preview;
            }
        }
        return null;
    }

    /** A picture the customer sent, small under the message; a tap opens it full size. */
    function thumbnail(file) {
        const button = el('button', {type: 'button', class: 'thumb', 'aria-label': T.preview_open + ': ' + file.name});
        button.append(el('img', {src: file.url, alt: '', loading: 'lazy'}));
        button.addEventListener('click', () => openPreview(file));
        return button;
    }

    function openPreview(file) {
        const dialog = el('dialog', {class: 'preview', 'aria-label': file.name});
        const close = el('button', {type: 'button', class: 'btn ghost'}, T.preview_close);
        close.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => dialog.remove());
        // a tap outside the picture closes it, like a phone's gallery
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
        dialog.append(el('img', {src: file.url, alt: file.name}), close);
        document.body.append(dialog);
        dialog.showModal();
        close.focus();
    }

    function messageNode(message) {
        const node = el('div', {class: 'msg ' + message.role, dir: 'auto'});
        if (message.role === 'team') node.append(el('span', {class: 'who'}, T.team_label));
        if (message.role === 'progress') node.append(el('span', {class: 'who'}, T.progress_label));
        node.append(document.createTextNode(message.text));
        if (message.images && message.images.length) {
            const shots = el('div', {class: 'shots'});
            message.images.forEach((src) => shots.append(el('img', {src, alt: message.text, loading: 'lazy'})));
            node.append(shots);
        }
        if (message.files && message.files.length) {
            const files = el('div', {class: 'files'});
            message.files.forEach((file) => files.append(file.url ? thumbnail(file) : el('span', {dir: 'auto'}, file.name)));
            node.append(files);
        }
        const actions = el('div', {class: 'actions'});
        if (state.delivery && message.delivered_version === state.delivery.version) {
            (state.delivery.actions || []).forEach((button) => actions.append(link(button.url, button.label, !button.main)));
            if (state.trial && state.trial.can_request) actions.append(trialButton(state.trial));
        }
        if (actions.children.length) node.append(actions);
        if (message.checks && message.checks.length) node.append(checksNode(message.checks));
        if (message.ideas && message.ideas.length && state.open_for_chat) node.append(ideasNode(message.ideas));
        if (message.starter) node.append(starterNode(message.starter));
        if (message.designs && message.designs.length && state.open_for_chat) node.append(designsNode(message.designs));
        return node;
    }

    /** A program we already have, offered as the starting point: its picture, what it does and its main parts. */
    function starterNode(starter) {
        const box = el('div', {class: 'starter'});
        if (starter.image) box.append(el('img', {src: starter.image, alt: starter.title, loading: 'lazy'}));
        const text = el('div', {dir: 'auto'});
        text.append(el('p', {class: 'who'}, T.starter_title), el('strong', {}, starter.title), el('p', {class: 'note'}, starter.summary));
        if (starter.features.length) {
            const list = el('ul');
            starter.features.forEach((feature) => list.append(el('li', {}, feature)));
            text.append(list);
        }
        box.append(text);
        return box;
    }

    /** Free ready designs to pick from: a picture, a name and a button that tells the chat which one. */
    function designsNode(designs) {
        const box = el('div', {class: 'designs'});
        designs.forEach((design) => {
            const card = el('div', {class: 'design'});
            if (design.image) card.append(el('img', {src: design.image, alt: design.title, loading: 'lazy'}));
            const text = el('div', {dir: 'auto'});
            text.append(el('strong', {}, design.title), el('p', {class: 'note'}, design.summary));
            const pick = el('button', {class: 'btn ghost', type: 'button'}, T.design_pick);
            pick.addEventListener('click', () => { if (!waiting) send(T.design_message.replace(':design', design.title).replace(':slug', design.slug)); });
            text.append(pick);
            card.append(text);
            box.append(card);
        });
        return box;
    }

    /** What was tried before the delivery: one plain line per thing, and whether it works. */
    function checksNode(checks) {
        const box = el('div', {class: 'checks'});
        const table = el('table');
        const head = el('tr');
        head.append(el('th', {scope: 'col'}, T.checks_what), el('th', {scope: 'col'}, T.checks_result));
        table.append(head);
        checks.forEach((check) => {
            const what = el('td', {dir: 'auto'}, check.what);
            if (check.note) what.append(el('span', {class: 'note'}, check.note));
            const row = el('tr');
            row.append(what, el('td', {class: check.passed ? 'result' : 'result waiting'}, check.passed ? T.checks_ok : T.checks_waiting));
            table.append(row);
        });
        box.append(el('p', {class: 'who'}, T.checks_title), table);
        return box;
    }

    /** The engineer's ideas for the next version; asking about one sends it to the chat, which prices it. */
    function ideasNode(ideas) {
        const box = el('div', {class: 'ideas'});
        box.append(el('p', {class: 'who'}, T.ideas_title));
        ideas.forEach((idea) => {
            const row = el('div', {class: 'idea'});
            const text = el('div', {dir: 'auto'});
            text.append(el('strong', {}, idea.title));
            if (idea.description) text.append(el('p', {class: 'note'}, idea.description));
            const ask = el('button', {class: 'btn ghost', type: 'button'}, T.idea_ask);
            ask.addEventListener('click', () => { if (!waiting) send(T.idea_message.replace(':idea', idea.title)); });
            row.append(text, ask);
            box.append(row);
        });
        return box;
    }

    function trialButton(trial) {
        const button = el('button', {class: 'btn ghost', type: 'button'}, T.trial_request);
        button.addEventListener('click', async () => {
            button.disabled = true;
            showError('');
            try { state = await call(trial.request_url, {}); render(); } catch (error) { showError(error.message); button.disabled = false; }
        });
        return button;
    }

    /** The live trial card: preparing, or the link with its password and the time it closes. */
    function trialNode(trial) {
        if (trial.waiting) return statusNode(T.trial_waiting);
        if (!trial.url) return trial.failed ? el('p', {class: 'hint'}, T.trial_failed) : null;
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title'}, T.trial_title));
        card.append(el('p', {class: 'note'}, T.trial_note.replace(':time', trial.until || '')));
        card.append(el('p', {class: 'note', dir: 'ltr'}, T.trial_password + ': ' + trial.password));
        const actions = el('div', {class: 'actions'});
        actions.append(link(trial.url, T.trial_open));
        card.append(actions);
        return card;
    }

    function freeFixNode(quote) {
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title', dir: 'auto'}, quote.title || ''));
        card.append(el('div', {class: 'price'}, T.fix_free));
        card.append(el('p', {class: 'note'}, T.fix_note));
        card.append(actionButton(T.fix_go, quote.fix_url));
        return card;
    }

    /** One button that sends one request and shows the new state: a free start, a wallet payment, an admin's mark. */
    function actionButton(label, url, {confirm = '', ghost = false} = {}) {
        const button = el('button', {class: ghost ? 'btn ghost' : 'btn', type: 'button'}, label);
        button.addEventListener('click', async () => {
            if (confirm && !window.confirm(confirm)) return;
            button.disabled = true;
            showError('');
            try {
                state = await call(url, {});
                render();
            } catch (error) {
                showError(error.message);
                button.disabled = false;
            }
        });
        return button;
    }

    /** A price in the customer's own money when the site has a rate for it; the gateway charges with the same rate. */
    function money(amount, currency) {
        const value = Number(String(amount).replaceAll(',', ''));
        const local = state && state.local_price;
        if (!local || currency !== local.from) return amount + ' ' + currency;
        return Math.round(value * local.rate).toLocaleString('en-US') + ' ' + local.name;
    }

    function quoteNode(quote) {
        if (quote.free) return freeFixNode(quote);
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title', dir: 'auto'}, quote.title || ''));
        const price = el('div', {class: 'price'}, money(quote.amount, quote.currency));
        if (quote.discount) price.append(el('span', {class: 'before'}, money(quote.discount.before, quote.currency)));
        card.append(price);
        if (quote.range) card.append(el('p', {class: 'note'}, T.range_note
            .replaceAll(':from', money(quote.range.from, quote.currency))
            .replaceAll(':to', money(quote.range.to, quote.currency))
            .replaceAll(':price', money(quote.amount, quote.currency))));
        if (quote.discount) card.append(el('p', {class: 'note'}, T.leave.applied.replace(':percent', quote.discount.percent)));
        if (quote.design_fee) card.append(el('p', {class: 'note'}, T.design_included.replace(':amount', money(quote.design_fee, quote.currency))));
        card.append(el('p', {class: 'note'}, T.pay_note + ' ' + T.close_page_note));
        appendPayForm(card, quote.pay_url, T.pay, quote.shares || []);
        return card;
    }

    /** The feasibility study: offered for its price, being written, failed, or the link to read and save it as a PDF. */
    function studyNode(study) {
        if (study.status === 'writing') return statusNode(T.study_writing);
        const box = el('section', {class: 'card'});
        if (study.status === 'offer') {
            box.append(el('div', {class: 'title'}, T.study_offer_title));
            box.append(el('p', {class: 'note'}, T.study_offer.replace(':price', money(study.price, study.currency))));
            box.append(...payFormNodes(study.pay_url, T.study_buy.replace(':price', money(study.price, study.currency)), [], false, () => {}, true));
            return box;
        }
        if (study.status === 'ready') box.append(link(study.url, T.study_open, true));
        if (study.status === 'failed') box.append(el('p', {class: 'note'}, T.study_failed));
        if (study.credit) box.append(el('p', {class: 'note'}, T.study_credit.replace(':amount', money(study.credit, study.currency))));
        return box;
    }

    /** The share the customer pays now: all of it, or a deposit with the rest before the files are handed over. */
    function sharePicker(shares) {
        const group = el('fieldset', {class: 'shares'});
        group.append(el('legend', {}, T.share_label));
        shares.forEach((share, index) => {
            const label = el('label', {class: 'share'});
            const input = el('input', {type: 'radio', name: 'share', value: String(share)});
            if (index === 0) input.checked = true;
            label.append(input, document.createTextNode(' ' + (share === 100 ? T.share_full : T.share_part.replace(':percent', share))));
            group.append(label);
        });
        return group;
    }

    /** The customer's details and the button that opens the payment page. */
    function appendPayForm(card, url, openLabel, shares) {
        card.append(...payFormNodes(url, openLabel, shares, payOpen, () => { payOpen = true; }));
        appendWalletPay(card);
        appendAdminPaid(card);
    }

    /** A button that opens the details form, and the form that sends the customer to the payment page. */
    function payFormNodes(url, openLabel, shares, isOpen, onOpen, ghost = false) {
        const open = el('button', {class: ghost ? 'btn ghost' : 'btn', type: 'button'}, openLabel);
        const payForm = el('form', {class: 'form'});
        payForm.hidden = !isOpen;
        open.hidden = isOpen;
        if (shares.length > 1) payForm.append(sharePicker(shares));
        [['client_name', T.name, 'text', 'name'], ['client_email', T.email, 'email', 'email'], ['client_whatsapp', T.whatsapp, 'tel', 'tel']]
            .forEach(([name, label, type, autocomplete]) => payForm.append(el('input', {name, type, autocomplete, required: '', placeholder: label, 'aria-label': label})));
        const go = el('button', {class: 'btn', type: 'submit'}, T.pay_go);
        payForm.append(go);
        open.addEventListener('click', () => { onOpen(); open.hidden = true; payForm.hidden = false; payForm.querySelector('input:not([type=radio])').focus(); });
        payForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            go.disabled = true;
            showError('');
            try {
                const data = await call(url, Object.fromEntries(new FormData(payForm)));
                window.location.href = data.url;
            } catch (error) {
                showError(error.message);
                go.disabled = false;
            }
        });
        return [open, payForm];
    }

    /** A signed-in customer can pay from the site wallet: a button when it covers the price, otherwise a top-up link. */
    function appendWalletPay(card) {
        const wallet = state.wallet;
        if (!wallet) return;
        if (!wallet.enough) {
            card.append(el('p', {class: 'note'}, T.wallet_short.replace(':balance', wallet.balance)), link(wallet.top_up_url, T.wallet_top_up, true));
            return;
        }
        card.append(actionButton(T.wallet_pay.replace(':balance', wallet.balance), wallet.url, {confirm: T.wallet_confirm, ghost: true}));
    }

    /** Admins only: mark the price as paid without the gateway, and the build starts as after a real payment. */
    function appendAdminPaid(card) {
        if (!state.admin_paid_url) return;
        card.append(actionButton(T.admin_mark_paid, state.admin_paid_url, {confirm: T.admin_mark_paid_confirm, ghost: true}));
    }

    /** After a deposit: the program is ready to see, and the files come with the rest of the payment. */
    function balanceNode(balance) {
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title'}, T.balance_title));
        card.append(el('div', {class: 'price'}, money(balance.due, balance.currency)));
        card.append(el('p', {class: 'note'}, T.balance_note));
        appendPayForm(card, balance.pay_url, T.balance_pay, []);
        return card;
    }

    /** Asks the browser for notifications, then gives the site this browser's Firebase token. */
    async function enablePush(url, button) {
        button.disabled = true;
        try {
            if (await Notification.requestPermission() !== 'granted') { button.disabled = false; return; }
            await loadScript('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js');
            await loadScript('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js');
            if (!firebase.apps.length) firebase.initializeApp(PUSH.config);
            const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            const token = await firebase.messaging().getToken({vapidKey: PUSH.vapidKey, serviceWorkerRegistration: registration});
            if (token) { await call(url, {token}); button.replaceWith(el('p', {class: 'hint'}, T.push_on)); }
        } catch (error) {
            showError(T.push_failed);
            button.disabled = false;
        }
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = el('script', {src});
            script.onload = resolve;
            script.onerror = reject;
            document.head.append(script);
        });
    }

    function pushNode(url) {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) return null;
        const box = el('div', {class: 'card'});
        const button = el('button', {class: 'btn ghost', type: 'button'}, T.push_ask);
        button.addEventListener('click', () => enablePush(url, button));
        box.append(el('p', {class: 'note'}, T.push_why), button);
        return box;
    }

    /** How much of the build is done; the line above it says what was finished last. */
    function barNode(percent) {
        const box = el('div', {class: 'bar'});
        const track = el('div', {class: 'track', role: 'progressbar', 'aria-valuemin': '0', 'aria-valuemax': '100', 'aria-valuenow': String(percent), 'aria-label': T.progress_bar});
        const fill = el('div', {class: 'fill'});
        fill.style.width = percent + '%';
        track.append(fill);
        box.append(track, el('span', {class: 'pct', dir: 'ltr'}, percent + '%'));
        return box;
    }

    /** The build's phases: finished, being built now, still to come. */
    function phasesNode(phases) {
        const done = phases.filter((phase) => phase.state === 'done').length;
        const box = el('section', {class: 'phases', 'aria-label': T.phases_title});
        const title = el('div', {class: 'title'}, T.phases_title + ' ');
        title.append(el('span', {class: 'hint', dir: 'ltr'}, done + ' / ' + phases.length));
        box.append(title);
        const list = el('ol');
        phases.forEach((phase) => {
            const mark = el('span', {class: 'mark', 'aria-hidden': 'true'});
            if (phase.state === 'done') mark.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>';
            const text = el('span', {class: 'text'}, phase.title);
            text.append(el('span', {class: 'state'}, T.phase_states[phase.state]));
            const item = el('li', {class: phase.state});
            item.append(mark, text);
            list.append(item);
        });
        box.append(list);
        return box;
    }

    /** After payment: the build waits a while for the full-quality design files the chat got smaller. */
    function originalsNode(originals) {
        const card = el('section', {class: 'card'});
        const body = T.originals.body.replace(':names', originals.names.join('، ')).replace(':mb', originals.max_mb);
        const picker = el('input', {type: 'file', multiple: 'multiple', accept: 'image/png,image/jpeg,image/webp,application/pdf', hidden: 'hidden'});
        const send = el('button', {class: 'btn', type: 'button'}, T.originals.upload);
        const skip = el('button', {class: 'btn ghost', type: 'button'}, T.originals.skip);
        const actions = el('div', {class: 'actions'});
        actions.append(send, skip);
        card.append(el('div', {class: 'title'}, T.originals.title), el('p', {class: 'note'}, body), picker, actions);
        send.addEventListener('click', () => picker.click());
        picker.addEventListener('change', () => sendOriginals(originals.url, [...picker.files], send));
        skip.addEventListener('click', () => sendOriginals(originals.skip_url, [], skip));
        return card;
    }

    async function sendOriginals(url, files, button) {
        button.disabled = true;
        button.textContent = files.length ? T.originals.sending : button.textContent;
        try {
            state = files.length ? await upload(url, '', files) : await call(url, {});
        } catch (error) {
            showError(error.message);
        }
        render();
    }

    function statusNode(text) {
        const node = el('div', {class: 'status'});
        node.append(el('span', {class: 'spin', 'aria-hidden': 'true'}), el('span', {}, text));
        return node;
    }

    // A customer who starts to leave (the mouse heads for the tabs, or they switch away and come back) is offered the
    // discount once when they saw a price, otherwise a way to talk to a programmer. Closing the tab cannot show our own
    // words; browsers do not allow it.
    function watchForLeaving() {
        let away = false;
        // a customer who closes the page on a price may get the discount as a notification later
        window.addEventListener('pagehide', () => {
            const quote = state && state.quote;
            if (!quote || !quote.leave_offer || !navigator.sendBeacon) return;
            const body = new FormData();
            body.append('_token', CSRF);
            navigator.sendBeacon(quote.left_url, body);
        });
        // they came back from that notification: the offer shows at once
        if (state && state.quote && state.quote.offer_now) setTimeout(offerLeaveDiscount, 600);
        document.documentElement.addEventListener('mouseleave', (event) => {
            if (event.clientY <= 0) onLeaving();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') { away = true; return; }
            if (away) onLeaving();
            away = false;
        });
    }

    function onLeaving() {
        if (!offerLeaveDiscount()) offerContact();
    }

    /** True when the discount was shown. */
    function offerLeaveDiscount() {
        const quote = state && state.quote;
        if (!quote || !quote.leave_offer || payOpen || waiting || leaveOffered()) return false;
        rememberLeaveOffer();
        const dialog = el('dialog', {class: 'leave', 'aria-labelledby': 'leave-title'});
        const take = el('button', {class: 'btn', type: 'button'}, T.leave.take);
        const no = el('button', {class: 'btn ghost', type: 'button'}, T.leave.no);
        const actions = el('div', {class: 'actions'});
        actions.append(take, no);
        dialog.append(el('h2', {id: 'leave-title'}, T.leave.title), el('p', {}, T.leave.body.replace(':percent', quote.leave_offer)), actions);
        no.addEventListener('click', () => dialog.close());
        take.addEventListener('click', () => takeLeaveDiscount(dialog, take, quote.discount_url));
        dialog.addEventListener('close', () => dialog.remove());
        document.body.append(dialog);
        dialog.showModal();
        return true;
    }

    /** Once per visit: a customer about to leave can reach a programmer instead. */
    function offerContact() {
        if (payOpen || waiting || seenOnce('contact-offer')) return;
        const dialog = el('dialog', {class: 'leave', 'aria-labelledby': 'contact-title'});
        const go = el('a', {class: 'btn', href: CONTACT_URL}, T.contact.go);
        const stay = el('button', {class: 'btn ghost', type: 'button'}, T.contact.stay);
        const actions = el('div', {class: 'actions'});
        actions.append(go, stay);
        dialog.append(el('h2', {id: 'contact-title'}, T.contact.title), el('p', {}, T.contact.body), actions);
        stay.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => dialog.remove());
        document.body.append(dialog);
        dialog.showModal();
    }

    // true the second time in this browser tab; private mode shows it each time, which is harmless
    function seenOnce(key) {
        try {
            if (sessionStorage.getItem(key) === '1') return true;
            sessionStorage.setItem(key, '1');
        } catch (error) { /* private mode */ }
        return false;
    }

    async function takeLeaveDiscount(dialog, button, url) {
        button.disabled = true;
        try {
            state = await call(url, {});
        } catch (error) {
            showError(error.message);
        }
        dialog.close();
        render();
    }

    // once per project in this browser; the server also gives it only once
    function leaveOffered() {
        try { return sessionStorage.getItem('leave-offer-' + state.uuid) === '1'; } catch (error) { return false; }
    }

    function rememberLeaveOffer() {
        try { sessionStorage.setItem('leave-offer-' + state.uuid, '1'); } catch (error) { /* private mode: the server still limits it */ }
    }

    // The list of requests beside the chat. It is drawn again only when it changes, so the polling during a build never
    // wipes the details the customer is typing into its payment form.
    const requestsBox = document.getElementById('requests');
    const requestsOpen = document.getElementById('requests-open');
    const unticked = new Set();
    let requestsKey = '';
    let requestsPayOpen = false;

    function renderRequests() {
        const list = state && state.requests;
        document.body.classList.toggle('has-requests', !!list);
        if (!list) { requestsBox.replaceChildren(); requestsKey = ''; return; }
        drawRequestsButton(list);
        const key = JSON.stringify(list);
        if (key === requestsKey) return;
        requestsKey = key;
        requestsBox.replaceChildren(...requestsNodes(list));
    }

    const CART_ICON = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';

    /** The cart button: its icon, its name, and how many requests wait for payment. */
    function drawRequestsButton(list) {
        const due = list.items.filter((item) => item.status === 'unpaid').length;
        requestsOpen.innerHTML = CART_ICON;
        requestsOpen.append(el('span', {}, T.requests.title));
        if (due) requestsOpen.append(el('span', {class: 'due', 'aria-label': T.requests.status.unpaid + ': ' + due}, String(due)));
    }

    function requestsNodes(list) {
        const head = el('div', {class: 'head'});
        const close = el('button', {class: 'btn ghost close', type: 'button'}, T.requests.close);
        close.addEventListener('click', hideRequests);
        head.append(el('h2', {}, T.requests.title), close);
        const nodes = [head, el('p', {class: 'hint'}, T.requests.how)];
        if (list.warranty) nodes.push(el('p', {class: 'hint'}, (list.warranty.active ? T.requests.warranty : T.requests.warranty_over).replace(':date', list.warranty.until)));
        const changes = list.warranty && list.warranty.changes;
        if (changes) nodes.push(el('p', {class: 'hint'}, (changes.left ? T.requests.changes_left : changes.pack ? T.requests.changes_pack : T.requests.changes_used)
            .replaceAll(':total', changes.total).replace(':left', changes.left).replace(':amount', money(changes.pack, list.currency))));
        if (!list.items.length) return [...nodes, el('p', {class: 'hint'}, T.requests.empty)];
        return [...nodes, ...REQUEST_GROUPS.map((group) => requestGroup(group, list)).filter(Boolean)];
    }

    /** Which card each request sits in, by its status; a card with no requests is not shown. */
    const REQUEST_GROUPS = [
        {key: 'building', statuses: ['building'], open: true},
        {key: 'unpaid', statuses: ['unpaid', 'locked'], open: true},
        {key: 'queue', statuses: ['reviewing', 'ready'], open: true},
        {key: 'done', statuses: ['done'], open: false},
    ];

    // a card the customer opened or closed stays that way when the list is drawn again
    const groupOpen = {};

    /** One card: its name and count, its requests, and for the unpaid card the payment. */
    function requestGroup(group, list) {
        const items = list.items.filter((item) => group.statuses.includes(item.status));
        if (!items.length) return null;
        const card = el('details', {class: 'req-group ' + group.key});
        card.open = groupOpen[group.key] ?? group.open;
        card.addEventListener('toggle', () => { groupOpen[group.key] = card.open; });
        const summary = el('summary', {});
        summary.append(el('span', {}, T.requests.groups[group.key]), el('span', {class: 'count'}, String(items.length)));
        const body = el('div', {class: 'items'});
        items.forEach((item) => body.append(requestRow(item, list.currency)));
        const unpaid = items.filter((item) => item.status === 'unpaid');
        if (unpaid.length) body.append(requestsPay(list, unpaid));
        card.append(summary, body);
        return card;
    }

    /** One request: fix or development, free or its price, and where it is now. An unpaid one can be ticked. */
    function requestRow(item, currency) {
        const unpaid = item.status === 'unpaid';
        const row = el(unpaid ? 'label' : 'div', {class: 'req' + (item.status === 'done' ? ' done' : '')});
        if (unpaid) {
            const box = el('input', {type: 'checkbox', 'aria-label': item.title});
            box.checked = !unticked.has(item.id);
            box.addEventListener('change', () => { box.checked ? unticked.delete(item.id) : unticked.add(item.id); updateRequestsTotal(); });
            row.append(box);
        }
        const body = el('div', {class: 'body'});
        const meta = el('div', {class: 'meta'});
        meta.append(
            el('span', {}, T.requests[item.kind] || ''),
            el('strong', {}, item.free ? T.requests.free : item.status === 'locked' ? '' : money(item.amount, currency)),
            el('span', {}, T.requests.status[item.status] || item.status),
        );
        // only the owner gets this link: the request waits for their approval on the admin page
        if (item.review_url) meta.append(el('a', {href: item.review_url, class: 'review'}, T.requests.review));
        if (item.cancel_url) meta.append(cancelButton(item));
        body.append(el('div', {class: 't', dir: 'auto'}, item.title), meta);
        row.append(body);
        return row;
    }

    /** Takes back a request the engineer has not started yet. */
    function cancelButton(item) {
        const button = el('button', {class: 'cancel', type: 'button', 'aria-label': T.requests.cancel + ': ' + item.title}, T.requests.cancel);
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            if (!window.confirm(T.requests.cancel_confirm)) return;
            button.disabled = true;
            showError('');
            try { state = await call(item.cancel_url, {}); unticked.delete(item.id); render(); } catch (error) { showError(error.message); button.disabled = false; }
        });
        return button;
    }

    let requestsPayParts = null;

    /** The total of the ticked requests and one payment for them; an admin can mark them paid instead. */
    function requestsPay(list, unpaid) {
        const box = el('div', {class: 'pay'});
        const total = el('p', {class: 'total'});
        const ids = el('input', {type: 'hidden', name: 'ids'});
        const [open, payForm] = payFormNodes(list.pay_url, T.requests.pay, [], requestsPayOpen, () => { requestsPayOpen = true; });
        payForm.append(ids);
        box.append(total, open, payForm);
        const admin = list.admin_paid_url ? el('button', {class: 'btn ghost', type: 'button'}, T.requests.admin_paid) : null;
        if (admin) {
            admin.addEventListener('click', () => markRequestsPaid(list.admin_paid_url, ids.value, admin));
            box.append(admin);
        }
        requestsPayParts = {unpaid, currency: list.currency, total, ids, buttons: [open, payForm.querySelector('[type=submit]'), admin].filter(Boolean)};
        updateRequestsTotal();
        return box;
    }

    function updateRequestsTotal() {
        if (!requestsPayParts) return;
        const chosen = requestsPayParts.unpaid.filter((item) => !unticked.has(item.id));
        const sum = chosen.reduce((all, item) => all + Number(item.amount), 0);
        requestsPayParts.total.textContent = T.requests.total.replace(':amount', money(sum, requestsPayParts.currency));
        requestsPayParts.ids.value = chosen.map((item) => item.id).join(',');
        requestsPayParts.buttons.forEach((button) => { button.disabled = chosen.length === 0; });
    }

    async function markRequestsPaid(url, ids, button) {
        if (!ids || !window.confirm(T.requests.admin_paid_confirm)) return;
        button.disabled = true;
        showError('');
        try { state = await call(url, {ids}); render(); } catch (error) { showError(error.message); button.disabled = false; }
    }

    // the chats panel on a phone or a tablet; on a wide screen it is always shown and these buttons are hidden
    const chatsOpen = document.getElementById('chats-open');
    function showChats() { document.body.classList.add('chats-shown'); chatsOpen.setAttribute('aria-expanded', 'true'); document.getElementById('chats-close').focus(); }
    function hideChats() { document.body.classList.remove('chats-shown'); chatsOpen.setAttribute('aria-expanded', 'false'); chatsOpen.focus(); }
    chatsOpen.addEventListener('click', showChats);
    document.getElementById('chats-close').addEventListener('click', hideChats);
    document.getElementById('chats-backdrop').addEventListener('click', hideChats);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && document.body.classList.contains('chats-shown')) hideChats(); });

    function showRequests() { document.body.classList.add('requests-shown'); requestsBox.querySelector('.close')?.focus(); }
    function hideRequests() { document.body.classList.remove('requests-shown'); requestsOpen.focus(); }
    requestsOpen.addEventListener('click', showRequests);
    document.getElementById('requests-backdrop').addEventListener('click', hideRequests);
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && document.body.classList.contains('requests-shown')) hideRequests(); });

    /** A project the customer already has: its Google Drive link and what to change; a paid inspection comes next. */
    function openBringForm() {
        const dialog = el('dialog', {class: 'bring', 'aria-labelledby': 'bring-title'});
        const form = el('form', {class: 'form'});
        const link = el('input', {name: 'drive_url', type: 'url', required: '', dir: 'ltr', placeholder: 'https://drive.google.com/...', 'aria-label': T.inspection.link});
        const need = el('textarea', {name: 'need', required: '', dir: 'auto', maxlength: '3000', placeholder: T.inspection.need, 'aria-label': T.inspection.need});
        const go = el('button', {class: 'btn', type: 'submit'}, T.inspection.send);
        const back = el('button', {class: 'btn ghost', type: 'button'}, T.inspection.cancel);
        const error = el('p', {class: 'error', role: 'alert', hidden: ''});
        const actions = el('div', {class: 'actions'});
        actions.append(go, back);
        form.append(link, need, error, actions);
        dialog.append(el('h2', {id: 'bring-title'}, T.inspection.title), el('p', {}, T.inspection.how), form);
        back.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => dialog.remove());
        form.addEventListener('submit', (event) => { event.preventDefault(); sendProject(dialog, go, error, link.value.trim(), need.value.trim()); });
        document.body.append(dialog);
        dialog.showModal();
        link.focus();
    }

    async function sendProject(dialog, button, error, driveUrl, need) {
        button.disabled = true;
        error.hidden = true;
        try {
            const isNew = !state || !state.uuid;
            state = await call(bringUrl(), {drive_url: driveUrl, need, timezone: TIMEZONE, country: await COUNTRY});
            if (isNew) history.replaceState(null, '', BASE + '/' + state.uuid);
            dialog.close();
            render();
        } catch (failure) {
            error.textContent = failure.message;
            error.hidden = false;
            button.disabled = false;
        }
    }

    /** The inspection fee: what it is, and paying it; an admin can mark it paid instead. */
    function inspectionNode(inspection) {
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title'}, T.inspection.card_title));
        card.append(el('div', {class: 'price'}, money(inspection.price, inspection.currency)));
        card.append(el('p', {class: 'note'}, T.inspection.card_note));
        card.append(...payFormNodes(inspection.pay_url, T.inspection.pay.replace(':price', money(inspection.price, inspection.currency)), [], payOpen, () => { payOpen = true; }));
        if (inspection.admin_paid_url) card.append(actionButton(T.inspection.admin_paid, inspection.admin_paid_url, {confirm: T.admin_mark_paid_confirm, ghost: true}));
        return card;
    }

    function render() {
        renderRequests();
        thread.querySelectorAll(':scope > :not(#empty)').forEach((node) => node.remove());
        const messages = state ? state.messages : [];
        empty.hidden = messages.length > 0 || waiting;
        messages.forEach((message) => thread.append(messageNode(message)));
        if (waiting || (state && state.answering)) thread.append(statusNode(T.thinking));
        if (state && state.looking) thread.append(statusNode(state.looking === 'file' ? T.reading_file : T.looking));
        if (state && state.quote) thread.append(quoteNode(state.quote));
        if (state && state.study) thread.append(studyNode(state.study));
        if (state && state.inspection) thread.append(inspectionNode(state.inspection));
        if (state && state.progress) thread.append(statusNode(state.progress));
        if (state && state.progress_percent !== null && state.progress_percent !== undefined) thread.append(barNode(state.progress_percent));
        if (state && state.phases && state.phases.length) thread.append(phasesNode(state.phases));
        if (state && state.waiting_for_answer) thread.append(el('p', {class: 'hint'}, T.waiting_answer));
        if (state && state.originals) thread.append(originalsNode(state.originals));
        if (state && state.trial) { const node = trialNode(state.trial); if (node) thread.append(node); }
        if (state && state.balance) thread.append(balanceNode(state.balance));
        if (state && state.push) { const node = pushNode(state.push); if (node) thread.append(node); }
        if (state && state.failed) {
            thread.append(el('div', {class: 'msg'}, T.failed));
        }
        if (state && messages.length === 1 && !waiting) thread.append(el('p', {class: 'hint'}, T.copy_link));
        input.disabled = waiting || (state && (!state.open_for_chat || state.looking || state.answering));
        sendBtn.disabled = input.disabled || (!input.value.trim() && picked.length === 0);
        document.getElementById('attach').disabled = input.disabled;
        keepRoomForComposer();
        window.scrollTo({top: document.body.scrollHeight});
        schedulePoll();
    }

    function schedulePoll() {
        clearTimeout(pollTimer);
        const studyWriting = state && state.study && state.study.status === 'writing';
        // a request in line or being built changes on its own; a paid one appears when the gateway reports it
        const requestsMoving = state && state.requests && state.requests.items.some((item) => ['reviewing', 'ready', 'building'].includes(item.status));
        if (!state || (!state.progress && !state.looking && !state.answering && !state.waiting_for_answer && !(state.trial && state.trial.waiting) && !studyWriting && !requestsMoving)) return;
        pollTimer = setTimeout(async () => {
            try { state = await call(BASE + '/' + state.uuid + '/state'); } catch (error) { /* try again on the next tick */ }
            render();
        }, POLL_MS);
    }

    async function send(text, files = []) {
        const url = state ? BASE + '/' + state.uuid + '/messages' : BASE + '/messages';
        const names = files.map((file) => file.name);
        waiting = true;
        showError('');
        if (state) state.messages.push({id: 0, role: 'user', text, files: names});
        else state = {messages: [{id: 0, role: 'user', text, files: names}], open_for_chat: true, uuid: null, quote: null, progress: null, delivery: null, failed: false};
        const optimistic = state;
        render();
        try {
            state = files.length ? await upload(url, text, files) : await call(url, {text});
            if (!optimistic.uuid) history.replaceState(null, '', BASE + '/' + state.uuid);
            input.value = '';
            picked = [];
            renderChips();
        } catch (error) {
            optimistic.messages.pop();
            state = optimistic.uuid ? optimistic : null;
            input.value = text;
            showError(error.message);
        }
        waiting = false;
        render();
        input.focus();
    }

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 200) + 'px';
        keepRoomForComposer();
        sendBtn.disabled = waiting || (!input.value.trim() && picked.length === 0);
    });
    // the paperclip: a file straight away, or a menu that also offers bringing a project of one's own
    const attachMenu = document.getElementById('attach-menu');
    const bringUrl = () => (state ? state.bring_url : BASE + '/inspect');
    document.getElementById('attach').addEventListener('click', () => {
        if (!bringUrl()) { fileInput.click(); return; }
        attachMenu.hidden = !attachMenu.hidden;
    });
    document.getElementById('attach-file').addEventListener('click', () => { attachMenu.hidden = true; fileInput.click(); });
    document.getElementById('attach-project').addEventListener('click', () => { attachMenu.hidden = true; openBringForm(); });
    document.addEventListener('click', (event) => { if (!event.target.closest('#attach-menu, #attach')) attachMenu.hidden = true; });
    // copied first: the list empties when the input is cleared, and the pictures are made smaller after that
    fileInput.addEventListener('change', () => { const chosen = [...fileInput.files]; fileInput.value = ''; pickFiles(chosen); });
    input.addEventListener('paste', (event) => {
        const pasted = [...(event.clipboardData?.files || [])];
        if (pasted.length) { event.preventDefault(); pickFiles(pasted); }
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); form.requestSubmit(); }
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const text = input.value.trim();
        if ((text || picked.length) && !waiting) send(text, [...picked]);
    });

    watchForLeaving();
    render();
})();
</script>
</body>
</html>
