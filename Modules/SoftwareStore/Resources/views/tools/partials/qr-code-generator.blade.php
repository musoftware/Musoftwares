@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <form id="qg-form" class="space-y-4" onsubmit="return false">
        <div>
            <span class="ft-label">{{ $f['type'] }}</span>
            <div class="flex flex-wrap gap-2" role="radiogroup">
                @foreach ($f['types'] as $key => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="qg-type" value="{{ $key }}" class="peer sr-only" @checked($loop->first)>
                        <span class="inline-block rounded-full border border-black/10 px-3.5 py-1.5 text-sm peer-checked:border-[#0071e3] peer-checked:bg-[#0071e3]/10 peer-checked:text-[#0071e3]">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div data-for="url"><label class="ft-label" for="qg-url">{{ $f['url'] }}</label><input id="qg-url" class="ft-input" type="url" dir="ltr" placeholder="https://example.com" value="https://"></div>
        <div data-for="text" class="hidden"><label class="ft-label" for="qg-text">{{ $f['text'] }}</label><textarea id="qg-text" class="ft-input" rows="3"></textarea></div>
        <div data-for="wifi" class="hidden space-y-3">
            <div><label class="ft-label" for="qg-ssid">{{ $f['ssid'] }}</label><input id="qg-ssid" class="ft-input" dir="auto"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ft-label" for="qg-pass">{{ $f['password'] }}</label><input id="qg-pass" class="ft-input" dir="ltr"></div>
                <div><label class="ft-label" for="qg-sec">{{ $f['security'] }}</label>
                    <select id="qg-sec" class="ft-input"><option value="WPA">WPA/WPA2</option><option value="WEP">WEP</option><option value="nopass">{{ $f['none'] }}</option></select></div>
            </div>
        </div>
        <div data-for="vcard" class="hidden space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ft-label" for="qg-fn">{{ $f['first_name'] }}</label><input id="qg-fn" class="ft-input" dir="auto"></div>
                <div><label class="ft-label" for="qg-ln">{{ $f['last_name'] }}</label><input id="qg-ln" class="ft-input" dir="auto"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ft-label" for="qg-ph">{{ $f['phone'] }}</label><input id="qg-ph" class="ft-input" type="tel" dir="ltr"></div>
                <div><label class="ft-label" for="qg-em">{{ $f['email'] }}</label><input id="qg-em" class="ft-input" type="email" dir="ltr"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ft-label" for="qg-co">{{ $f['company'] }}</label><input id="qg-co" class="ft-input" dir="auto"></div>
                <div><label class="ft-label" for="qg-web">{{ $f['website'] }}</label><input id="qg-web" class="ft-input" dir="ltr"></div>
            </div>
        </div>
        <div data-for="email" class="hidden space-y-3">
            <div><label class="ft-label" for="qg-to">{{ $f['email'] }}</label><input id="qg-to" class="ft-input" type="email" dir="ltr"></div>
            <div><label class="ft-label" for="qg-subj">{{ $f['subject'] }}</label><input id="qg-subj" class="ft-input" dir="auto"></div>
            <div><label class="ft-label" for="qg-body">{{ $f['message'] }}</label><textarea id="qg-body" class="ft-input" rows="2" dir="auto"></textarea></div>
        </div>
        <div data-for="sms" class="hidden space-y-3">
            <div><label class="ft-label" for="qg-smsto">{{ $f['phone'] }}</label><input id="qg-smsto" class="ft-input" type="tel" dir="ltr"></div>
            <div><label class="ft-label" for="qg-smsbody">{{ $f['message'] }}</label><textarea id="qg-smsbody" class="ft-input" rows="2" dir="auto"></textarea></div>
        </div>

        <div class="flex items-center gap-3">
            <label class="ft-label !mb-0" for="qg-color">{{ $f['color'] }}</label>
            <input id="qg-color" type="color" value="#000000" class="h-10 w-14 rounded-lg border border-black/10 p-1">
        </div>
    </form>

    <div class="flex flex-col items-center gap-4">
        <canvas id="qg-qr" class="w-64 h-64 rounded-xl border border-black/5" aria-label="QR"></canvas>
        <button type="button" id="qg-download" class="ft-btn"><i class="ri-download-2-line" aria-hidden="true"></i>{{ __('free_tools.ui.download_png') }}</button>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const type = () => document.querySelector('[name="qg-type"]:checked').value;
        // Wi-Fi and contact formats escape \ ; , : and " inside values
        const esc = v => String(v || '').replace(/([\\;,:"])/g, '\\$1');

        function payload() {
            switch (type()) {
                case 'url': return $('qg-url').value.trim();
                case 'text': return $('qg-text').value;
                case 'wifi': return `WIFI:T:${$('qg-sec').value};S:${esc($('qg-ssid').value)};P:${$('qg-sec').value === 'nopass' ? '' : esc($('qg-pass').value)};;`;
                case 'vcard': return ['BEGIN:VCARD', 'VERSION:3.0', `N:${esc($('qg-ln').value)};${esc($('qg-fn').value)};;;`, `FN:${esc(($('qg-fn').value + ' ' + $('qg-ln').value).trim())}`,
                    $('qg-co').value && `ORG:${esc($('qg-co').value)}`, $('qg-ph').value && `TEL:${$('qg-ph').value}`, $('qg-em').value && `EMAIL:${$('qg-em').value}`,
                    $('qg-web').value && `URL:${$('qg-web').value}`, 'END:VCARD'].filter(Boolean).join('\n');
                case 'email': return `mailto:${$('qg-to').value}?subject=${encodeURIComponent($('qg-subj').value)}&body=${encodeURIComponent($('qg-body').value)}`;
                case 'sms': return `SMSTO:${$('qg-smsto').value}:${$('qg-smsbody').value}`;
            }
        }

        function render() {
            document.querySelectorAll('#qg-form [data-for]').forEach(el => el.classList.toggle('hidden', el.dataset.for !== type()));
            FT.qr($('qg-qr'), payload() || ' ', 320, $('qg-color').value);
        }

        $('qg-form').addEventListener('input', render);
        $('qg-form').addEventListener('change', render);
        $('qg-download').addEventListener('click', () => $('qg-qr').toBlob(blob => FT.download('qr-code.png', blob)));
        render();
    });
</script>
