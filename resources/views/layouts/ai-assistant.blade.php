{{-- The site assistant: one button and one chat window, shared by the Blade and React layouts. --}}
@if (config('services.anthropic.key') && ! request()->is('admin', 'admin/*', 'embed', 'embed/*'))
@php
    $aiIsAr = app()->getLocale() === 'ar';
    $aiSay = fn (string $ar, string $en) => $aiIsAr ? $ar : $en;
    $aiStarters = [
        $aiSay('أنا جديد، أبدأ منين؟', 'I am new, where do I start?'),
        $aiSay('مشروعي هيتكلف كام؟', 'What will my project cost?'),
        $aiSay('عايز فكرة مشروع', 'I want a project idea'),
        $aiSay('هل عليّا فواتير؟', 'Do I have any invoices to pay?'),
    ];
@endphp

<button type="button" data-open-assistant aria-haspopup="dialog"
        class="fixed bottom-24 left-4 lg:left-6 z-50 flex items-center gap-2 h-12 px-4 rounded-full bg-[#0071e3] hover:bg-[#0077ed] text-white text-sm font-semibold shadow-lg transition-colors cursor-pointer">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l.9-5.4A8 8 0 1 1 21 12z"/></svg>
    <span>{{ $aiSay('اسأل المساعد', 'Ask the assistant') }}</span>
</button>

<section id="ai-assistant" role="dialog" aria-label="{{ $aiSay('المساعد', 'Assistant') }}"
         data-send-error="{{ $aiSay('حصلت مشكلة. جرب تاني.', 'Something went wrong. Please try again.') }}"
         data-typing="{{ $aiSay('بيكتب...', 'Typing...') }}"
         class="hidden fixed z-[60] inset-x-0 bottom-0 h-[85dvh] sm:inset-x-auto sm:left-6 sm:bottom-6 sm:w-[400px] sm:h-[600px] sm:max-h-[85dvh] flex-col overflow-hidden rounded-t-2xl sm:rounded-2xl bg-white text-[#1d1d1f] shadow-2xl border border-black/10">
    <header class="flex items-center justify-between gap-2 px-4 py-3 border-b border-black/10">
        <div class="min-w-0">
            <p class="text-[16px] font-bold">{{ $aiSay('المساعد', 'Assistant') }}</p>
            <p class="text-[13px] text-[#515154]">{{ $aiSay('اسأل عن الأسعار أو فكرتك أو حسابك', 'Ask about prices, your idea or your account') }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <button type="button" data-assistant-reset class="min-h-11 px-3 rounded-lg text-[13px] font-semibold text-[#0071e3] hover:bg-[#f5f5f7] cursor-pointer">{{ $aiSay('محادثة جديدة', 'New chat') }}</button>
            <button type="button" data-assistant-close aria-label="{{ $aiSay('اقفل', 'Close') }}" class="w-11 h-11 flex items-center justify-center rounded-lg hover:bg-[#f5f5f7] cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </header>

    <div data-assistant-log aria-live="polite" class="flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-3">
        <p class="self-start max-w-[85%] rounded-2xl bg-[#f5f5f7] px-4 py-2.5 text-[15px] leading-[1.7]">{{ $aiSay('أهلًا بيك! قولي عايز إيه، أو اختار سؤال من دول.', 'Welcome! Tell me what you need, or pick a question.') }}</p>
    </div>

    <div data-assistant-starters class="px-4 pb-3 flex flex-col gap-2">
        @foreach ($aiStarters as $aiStarter)
            <button type="button" data-assistant-starter class="min-h-11 text-start rounded-xl border border-black/15 px-4 py-2 text-[15px] font-semibold hover:border-[#0071e3] hover:bg-[#eef5fe] transition-colors cursor-pointer">{{ $aiStarter }}</button>
        @endforeach
    </div>

    <form data-assistant-form class="flex items-end gap-2 px-3 py-3 border-t border-black/10">
        <label for="ai-assistant-input" class="sr-only">{{ $aiSay('اكتب سؤالك', 'Type your question') }}</label>
        <textarea id="ai-assistant-input" rows="1" maxlength="500" required placeholder="{{ $aiSay('اكتب سؤالك هنا...', 'Type your question here...') }}"
                  class="flex-1 min-w-0 resize-none rounded-xl border border-black/15 px-3 py-2.5 text-[16px] bg-white text-[#1d1d1f] focus:border-[#0071e3] focus:ring-0"></textarea>
        <button type="submit" class="h-11 shrink-0 px-5 rounded-xl bg-[#0071e3] hover:bg-[#0077ed] disabled:opacity-50 text-white text-[15px] font-semibold cursor-pointer">{{ $aiSay('ابعت', 'Send') }}</button>
    </form>
</section>

<script>
    (function () {
        var panel = document.getElementById('ai-assistant');
        if (!panel) return;
        var log = panel.querySelector('[data-assistant-log]');
        var starters = panel.querySelector('[data-assistant-starters]');
        var form = panel.querySelector('[data-assistant-form]');
        var input = panel.querySelector('textarea');
        var sendButton = form.querySelector('[type=submit]');
        var greeting = log.firstElementChild;
        var historyLoaded = false;
        var linkPattern = /(https?:\/\/[^\s]+)/g;

        function xsrfToken() {
            var match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        }

        function request(method, url, body) {
            return fetch(url, {
                method: method,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrfToken() },
                body: body ? JSON.stringify(body) : undefined,
            }).then(function (response) { return response.json(); });
        }

        function isTrustedLink(address) {
            try {
                var host = new URL(address).hostname;
                return host === location.hostname || host === 'wa.me';
            } catch (error) {
                return false;
            }
        }

        function linkNode(address) {
            var link = document.createElement('a');
            link.href = address;
            link.textContent = address;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.className = 'text-[#0071e3] underline break-all';
            return link;
        }

        function fillText(bubble, text) {
            text.split(linkPattern).forEach(function (part) {
                var address = part.replace(/[.,،)]+$/, '');
                bubble.appendChild(isTrustedLink(address) ? linkNode(address) : document.createTextNode(part));
            });
        }

        function addBubble(role, text) {
            var bubble = document.createElement('p');
            var side = role === 'user' ? 'self-end bg-[#0071e3] text-white' : 'self-start bg-[#f5f5f7]';
            bubble.className = side + ' max-w-[85%] rounded-2xl px-4 py-2.5 text-[15px] leading-[1.7] whitespace-pre-line break-words';
            fillText(bubble, text);
            log.appendChild(bubble);
            log.scrollTop = log.scrollHeight;
            return bubble;
        }

        function setBusy(busy) {
            input.disabled = busy;
            sendButton.disabled = busy;
        }

        function send(text) {
            starters.classList.add('hidden');
            addBubble('user', text);
            var typing = addBubble('assistant', panel.dataset.typing);
            setBusy(true);
            request('POST', '/assistant/message', { message: text })
                .then(function (data) { return data.reply || panel.dataset.sendError; })
                .catch(function () { return panel.dataset.sendError; })
                .then(function (reply) {
                    typing.remove();
                    addBubble('assistant', reply);
                    setBusy(false);
                    input.focus();
                });
        }

        function loadHistory() {
            historyLoaded = true;
            request('GET', '/assistant/history').then(function (data) {
                if (!data.messages || data.messages.length === 0) return;
                starters.classList.add('hidden');
                data.messages.forEach(function (message) { addBubble(message.role, message.content); });
            }).catch(function () {});
        }

        function open() {
            panel.classList.remove('hidden');
            panel.classList.add('flex');
            if (!historyLoaded) loadHistory();
            input.focus();
        }

        function close() {
            panel.classList.add('hidden');
            panel.classList.remove('flex');
        }

        function reset() {
            request('POST', '/assistant/reset', {}).catch(function () {});
            log.replaceChildren(greeting);
            starters.classList.remove('hidden');
        }

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-open-assistant]')) open();
            if (event.target.closest('[data-assistant-close]')) close();
            if (event.target.closest('[data-assistant-reset]')) reset();
            var starter = event.target.closest('[data-assistant-starter]');
            if (starter) send(starter.textContent.trim());
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });

        function submit() {
            var text = input.value.trim();
            if (!text || sendButton.disabled) return;
            input.value = '';
            send(text);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submit();
        });

        input.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.shiftKey) return;
            event.preventDefault();
            submit();
        });
    })();
</script>
@endif
