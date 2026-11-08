@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="space-y-4">
        <div><label class="ft-label" for="ic-file">{{ $f['file'] }}</label><input id="ic-file" type="file" accept="image/*" class="ft-input"></div>
        <div><label class="ft-label" for="ic-quality">{{ $f['quality'] }}: <span id="ic-quality-value">75</span>%</label><input id="ic-quality" type="range" min="10" max="100" value="75" class="w-full"></div>
        <div><label class="ft-label" for="ic-width">{{ $f['max_width'] }}</label><input id="ic-width" class="ft-input" inputmode="numeric" dir="ltr" value="1600"></div>
        <div><label class="ft-label" for="ic-format">{{ $f['format'] }}</label>
            <select id="ic-format" class="ft-input" dir="ltr">
                <option value="image/jpeg">JPG</option>
                <option value="image/webp">WebP</option>
            </select>
        </div>
    </div>
    <div>
        <dl class="grid grid-cols-3 gap-3">
            <div class="ft-stat"><dt>{{ $f['before'] }}</dt><dd id="ic-before">—</dd></div>
            <div class="ft-stat"><dt>{{ $f['after'] }}</dt><dd id="ic-after">—</dd></div>
            <div class="ft-stat !bg-[#0071e3]/10"><dt>{{ $f['saved'] }}</dt><dd id="ic-saved" class="!text-[#0071e3]">—</dd></div>
        </dl>
        <img id="ic-preview" alt="" class="mt-4 max-h-64 mx-auto rounded-xl hidden">
        <p id="ic-error" class="mt-3 text-sm font-semibold text-red-600 hidden">{{ $f['error'] }}</p>
        <button type="button" id="ic-download" class="ft-btn mt-4 hidden"><i class="ri-download-line" aria-hidden="true"></i>{{ $f['download'] }}</button>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const size = bytes => bytes >= 1048576 ? FT.number(bytes / 1048576) + ' MB' : FT.number(bytes / 1024, 0) + ' KB';
        let image = null, original = null, result = null;

        function fail() { $('ic-error').classList.remove('hidden'); ['ic-preview', 'ic-download'].forEach(id => $(id).classList.add('hidden')); }

        function show(blob) {
            if (!blob) return fail();
            result = blob;
            $('ic-after').textContent = size(blob.size);
            $('ic-saved').textContent = FT.number(Math.max((1 - blob.size / original.size) * 100, 0), 0) + '%';
            if ($('ic-preview').src) URL.revokeObjectURL($('ic-preview').src);
            $('ic-preview').src = URL.createObjectURL(blob);
            ['ic-preview', 'ic-download'].forEach(id => $(id).classList.remove('hidden'));
            $('ic-error').classList.add('hidden');
        }

        function compress() {
            if (!image) return;
            const scale = Math.min(FT.parse($('ic-width').value) / image.naturalWidth || 1, 1), canvas = document.createElement('canvas');
            canvas.width = Math.round(image.naturalWidth * scale); canvas.height = Math.round(image.naturalHeight * scale);
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(show, $('ic-format').value, $('ic-quality').value / 100);
        }

        $('ic-file').addEventListener('change', e => {
            original = e.target.files[0];
            if (!original) return;
            $('ic-before').textContent = size(original.size);
            const next = new Image();
            next.onload = () => { image = next; compress(); };
            next.onerror = fail;
            next.src = URL.createObjectURL(original);
        });
        ['ic-quality', 'ic-width', 'ic-format'].forEach(id => $(id).addEventListener('input', () => { $('ic-quality-value').textContent = $('ic-quality').value; compress(); }));
        $('ic-download').addEventListener('click', () => {
            const extension = result.type === 'image/webp' ? 'webp' : 'jpg';
            FT.download(original.name.replace(/\.[^.]+$/, '') + '-compressed.' + extension, result);
        });
    });
</script>
