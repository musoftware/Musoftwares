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
        .idea{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;border:1px solid var(--line);border-radius:12px;padding:10px 12px}
        .idea .note{margin:2px 0 0}
        .msg.progress{border-inline-start:3px solid var(--line);padding-inline-start:12px}
        .shots{display:grid;gap:8px;margin-top:8px}
        .shots img{display:block;width:100%;height:auto;border:1px solid var(--line);border-radius:12px;background:var(--soft)}
        .choose{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-top:24px;text-align:start}
        .choice{display:grid;gap:4px;padding:16px;border:1px solid var(--line);border-radius:16px;background:#fff;color:var(--ink);font:inherit;text-align:start;cursor:pointer;min-height:44px}
        .choice:hover{border-color:var(--ink)}
        .choice strong{font-size:18px}
        .choice span{color:var(--muted);font-size:14px}
        .login-ask{margin-top:16px;padding:16px;border:1px solid var(--line);border-radius:16px;text-align:start}
        .login-ask p{margin:0 0 12px;color:var(--ink)}
        .projects{margin-top:32px;text-align:start}
        .projects h2{font-size:16px;margin:0 0 8px}
        .projects a{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;padding:12px 0;border-top:1px solid var(--line);color:var(--ink);text-decoration:none;min-height:44px}
        .projects a span{color:var(--muted);font-size:14px}
        body.choosing .composer{display:none}
        .bar{margin:-12px 0 24px}
        .bar .track{height:8px;border-radius:999px;background:var(--soft);overflow:hidden}
        .bar .fill{height:100%;border-radius:999px;background:var(--ink);transition:width .6s ease}
        .bar .pct{display:block;margin-top:6px;font-size:14px;font-weight:600}
        @media (prefers-reduced-motion:reduce){.bar .fill{transition:none}}
        .msg .files{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;font-size:14px;color:var(--muted)}
        :focus-visible{outline:2px solid #2563eb;outline-offset:2px}
        [hidden]{display:none!important}
    </style>
</head>
<body>
<main class="wrap" id="thread" aria-live="polite">
    <section class="empty" id="empty">
        <h1>{{ __('builder.greeting') }}</h1>
        <p>{{ __('builder.greeting_hint') }}</p>
        @unless ($state)
            <div class="choose" id="choose">
                <button type="button" class="choice" data-size="big"><strong>{{ __('builder.start_big') }}</strong><span>{{ __('builder.start_big_note') }}</span></button>
                <button type="button" class="choice" data-size="small"><strong>{{ __('builder.start_small') }}</strong><span>{{ __('builder.start_small_note') }}</span></button>
            </div>
            @if ($loginUrl ?? null)
                <div class="login-ask" id="login-ask" hidden>
                    <p>{{ __('builder.start_big_note') }}</p>
                    <div class="actions">
                        <a class="btn" href="{{ $loginUrl }}">{{ __('builder.start_login') }}</a>
                        <button type="button" class="btn ghost" id="guest">{{ __('builder.start_guest') }}</button>
                    </div>
                </div>
            @endif
            @if (! empty($projects))
                <nav class="projects" aria-label="{{ __('builder.my_projects') }}">
                    <h2>{{ __('builder.my_projects') }}</h2>
                    @foreach ($projects as $own)
                        <a href="{{ $own['url'] }}"><strong dir="auto">{{ $own['title'] }}</strong><span>{{ $own['status'] }}</span></a>
                    @endforeach
                </nav>
            @endif
        @endunless
    </section>
</main>

<div class="composer">
    <div class="box">
        <p class="error" id="error" role="alert" hidden></p>
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
    const BASE = @json(route('builder.index'));
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

    async function upload(url, text, files) {
        const body = new FormData();
        if (text) body.append('text', text);
        files.forEach((file) => body.append('files[]', file));
        const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF}, body});
        return answerOf(response);
    }

    // the chat's own message when it sent one; the rate limiter's English "Too Many Attempts." (which the site's
    // error handler sends with status: error) is replaced by ours
    async function answerOf(response) {
        const data = await response.json().catch(() => ({}));
        if (response.status === 429 && data.status === 'error') throw new Error(T.errors.too_fast);
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
        chips.hidden = picked.length === 0;
        sendBtn.disabled = waiting || (!input.value.trim() && picked.length === 0);
        keepRoomForComposer();
    }

    function pickFiles(list) {
        showError('');
        for (const file of list) {
            if (picked.length >= MAX_FILES) { showError(T.too_many_files); break; }
            if (file.size > MAX_BYTES) { showError(T.file_too_big); continue; }
            picked.push(file);
        }
        renderChips();
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
            message.files.forEach((name) => files.append(el('span', {dir: 'auto'}, name)));
            node.append(files);
        }
        const actions = el('div', {class: 'actions'});
        if (state.delivery && message.delivered_version === state.delivery.version) {
            if (state.delivery.apk_url) actions.append(link(state.delivery.apk_url, T.download_apk));
            if (state.delivery.preview_url) actions.append(link(state.delivery.preview_url, T.open_program, Boolean(state.delivery.apk_url)));
            if (state.delivery.download_url) actions.append(link(state.delivery.download_url, T.download, Boolean(state.delivery.preview_url)));
            if (state.trial && state.trial.can_request) actions.append(trialButton(state.trial));
        }
        if (actions.children.length) node.append(actions);
        if (message.ideas && message.ideas.length && state.open_for_chat) node.append(ideasNode(message.ideas));
        return node;
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
        const go = el('button', {class: 'btn', type: 'button'}, T.fix_go);
        go.addEventListener('click', async () => {
            go.disabled = true;
            showError('');
            try {
                state = await call(quote.fix_url, {});
                render();
            } catch (error) {
                showError(error.message);
                go.disabled = false;
            }
        });
        card.append(go);
        return card;
    }

    function quoteNode(quote) {
        if (quote.free) return freeFixNode(quote);
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title', dir: 'auto'}, quote.title || ''));
        card.append(el('div', {class: 'price'}, quote.amount + ' ' + quote.currency));
        if (quote.design_fee) card.append(el('p', {class: 'note'}, T.design_included.replace(':amount', quote.design_fee + ' ' + quote.currency)));
        if (quote.study) card.append(studyNode(quote.study, quote.currency));
        card.append(el('p', {class: 'note'}, T.pay_note));
        appendPayForm(card, quote.pay_url, T.pay, quote.shares || []);
        return card;
    }

    /** The feasibility study: being written, or the link to read and save it as a PDF. */
    function studyNode(study, currency) {
        if (study.status === 'writing') return statusNode(T.study_writing);
        if (study.status !== 'ready') return el('span');
        const box = el('div', {class: 'actions'});
        box.append(link(study.url, T.study_open, true));
        if (study.fee) box.append(el('span', {class: 'note'}, T.study_included.replace(':amount', study.fee + ' ' + currency)));
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
        const open = el('button', {class: 'btn', type: 'button'}, openLabel);
        const payForm = el('form', {class: 'form'});
        payForm.hidden = !payOpen;
        open.hidden = payOpen;
        if (shares.length > 1) payForm.append(sharePicker(shares));
        [['client_name', T.name, 'text', 'name'], ['client_email', T.email, 'email', 'email'], ['client_whatsapp', T.whatsapp, 'tel', 'tel']]
            .forEach(([name, label, type, autocomplete]) => payForm.append(el('input', {name, type, autocomplete, required: '', placeholder: label, 'aria-label': label})));
        const go = el('button', {class: 'btn', type: 'submit'}, T.pay_go);
        payForm.append(go);
        open.addEventListener('click', () => { payOpen = true; open.hidden = true; payForm.hidden = false; payForm.querySelector('input:not([type=radio])').focus(); });
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
        card.append(open, payForm);
    }

    /** After a deposit: the program is ready to see, and the files come with the rest of the payment. */
    function balanceNode(balance) {
        const card = el('section', {class: 'card'});
        card.append(el('div', {class: 'title'}, T.balance_title));
        card.append(el('div', {class: 'price'}, balance.due + ' ' + balance.currency));
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
        const button = el('button', {class: 'btn ghost', type: 'button'}, T.push_ask);
        button.addEventListener('click', () => enablePush(url, button));
        return button;
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

    function statusNode(text) {
        const node = el('div', {class: 'status'});
        node.append(el('span', {class: 'spin', 'aria-hidden': 'true'}), el('span', {}, text));
        return node;
    }

    function render() {
        thread.querySelectorAll(':scope > :not(#empty)').forEach((node) => node.remove());
        const messages = state ? state.messages : [];
        empty.hidden = messages.length > 0 || waiting;
        messages.forEach((message) => thread.append(messageNode(message)));
        if (waiting || (state && state.answering)) thread.append(statusNode(T.thinking));
        if (state && state.looking) thread.append(statusNode(state.looking === 'file' ? T.reading_file : T.looking));
        if (state && state.quote) thread.append(quoteNode(state.quote));
        if (state && state.progress) thread.append(statusNode(state.progress));
        if (state && state.progress_percent !== null && state.progress_percent !== undefined) thread.append(barNode(state.progress_percent));
        if (state && state.waiting_for_answer) thread.append(el('p', {class: 'hint'}, T.waiting_answer));
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
        const studyWriting = state && state.quote && state.quote.study && state.quote.study.status === 'writing';
        if (!state || (!state.progress && !state.looking && !state.answering && !state.waiting_for_answer && !(state.trial && state.trial.waiting) && !studyWriting)) return;
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
    document.getElementById('attach').addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => { pickFiles(fileInput.files); fileInput.value = ''; });
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

    /** Big or simple: a guest with a big project is asked to sign in first, so the link is never lost. */
    function startChoice() {
        const choose = document.getElementById('choose');
        if (!choose) return;
        const loginAsk = document.getElementById('login-ask');
        const openChat = () => { document.body.classList.remove('choosing'); choose.hidden = true; if (loginAsk) loginAsk.hidden = true; keepRoomForComposer(); input.focus(); };
        if (@json((bool) ($chosen ?? false))) { openChat(); return; }
        document.body.classList.add('choosing');
        choose.querySelectorAll('.choice').forEach((button) => button.addEventListener('click', () => {
            if (button.dataset.size === 'big' && loginAsk) { choose.hidden = true; loginAsk.hidden = false; loginAsk.querySelector('a').focus(); return; }
            openChat();
        }));
        document.getElementById('guest')?.addEventListener('click', openChat);
    }

    startChoice();
    render();
})();
</script>
</body>
</html>
