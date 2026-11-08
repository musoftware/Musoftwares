@php $f = $copy['form']; @endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div><label class="ft-label" for="ip-files">{{ $f['files'] }}</label><input id="ip-files" type="file" accept="image/*" multiple class="ft-input"></div>
    <div><label class="ft-label" for="ip-page">{{ $f['page'] }}</label>
        <select id="ip-page" class="ft-input">
            <option value="a4">{{ $f['a4'] }}</option>
            <option value="fit">{{ $f['fit'] }}</option>
        </select>
    </div>
</div>
<ol id="ip-list" class="mt-5 space-y-2"></ol>
<p id="ip-error" class="mt-3 text-sm font-semibold text-red-600 hidden">{{ $f['error'] }}</p>
<button type="button" id="ip-make" class="ft-btn mt-4" disabled><i class="ri-file-pdf-line" aria-hidden="true"></i><span id="ip-label">{{ $f['make'] }}</span></button>
<script>
    window.addEventListener('load', () => {
        const $ = id => document.getElementById(id);
        const TEXT = @json($f);
        const MAX_SIDE = 2000, QUALITY = 0.82, A4 = [595.28, 841.89], MARGIN = 24, POINTS_PER_PIXEL = 0.75;
        let files = [];

        function button(label, action) {
            const element = document.createElement('button');
            element.type = 'button';
            element.className = 'ft-btn !bg-white !text-[#1d1d1f] !px-3 shrink-0';
            element.textContent = label;
            element.addEventListener('click', action);
            return element;
        }

        const moveUp = index => { [files[index - 1], files[index]] = [files[index], files[index - 1]]; render(); };
        const remove = index => { files = files.filter((_, position) => position !== index); render(); };

        function row(file, index) {
            const item = document.createElement('li'), name = document.createElement('span');
            item.className = 'flex items-center gap-2 bg-[#f5f5f7] rounded-xl p-2';
            name.className = 'min-w-0 flex-1 truncate text-sm';
            name.dir = 'auto';
            name.textContent = (index + 1) + '. ' + file.name;
            item.append(name);
            if (index > 0) item.append(button(TEXT.up, () => moveUp(index)));
            item.append(button(TEXT.remove, () => remove(index)));
            return item;
        }

        function render() {
            $('ip-list').replaceChildren(...files.map(row));
            $('ip-make').disabled = !files.length;
            $('ip-make').classList.toggle('opacity-50', !files.length);
        }

        function loadImage(file) {
            return new Promise((resolve, reject) => {
                const image = new Image(), url = URL.createObjectURL(file);
                image.onload = () => { URL.revokeObjectURL(url); resolve(image); };
                image.onerror = () => { URL.revokeObjectURL(url); reject(new Error('unreadable image')); };
                image.src = url;
            });
        }

        async function toJpeg(file) {
            const image = await loadImage(file), scale = Math.min(MAX_SIDE / Math.max(image.naturalWidth, image.naturalHeight), 1);
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(image.naturalWidth * scale);
            canvas.height = Math.round(image.naturalHeight * scale);
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', QUALITY));
            return { width: canvas.width, height: canvas.height, bytes: new Uint8Array(await blob.arrayBuffer()) };
        }

        function layout(image, mode) {
            if (mode === 'fit') {
                const width = image.width * POINTS_PER_PIXEL, height = image.height * POINTS_PER_PIXEL;
                return { pageWidth: width, pageHeight: height, width, height, x: 0, y: 0 };
            }
            const [pageWidth, pageHeight] = image.width > image.height ? [A4[1], A4[0]] : A4;
            const scale = Math.min((pageWidth - MARGIN * 2) / image.width, (pageHeight - MARGIN * 2) / image.height);
            const width = image.width * scale, height = image.height * scale;
            return { pageWidth, pageHeight, width, height, x: (pageWidth - width) / 2, y: (pageHeight - height) / 2 };
        }

        function pageObjects(image, box, number) {
            const draw = `q ${box.width.toFixed(2)} 0 0 ${box.height.toFixed(2)} ${box.x.toFixed(2)} ${box.y.toFixed(2)} cm /Im0 Do Q`;
            return [
                [number, `<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${box.pageWidth.toFixed(2)} ${box.pageHeight.toFixed(2)}] /Resources << /XObject << /Im0 ${number + 1} 0 R >> >> /Contents ${number + 2} 0 R >>`],
                [number + 1, `<< /Type /XObject /Subtype /Image /Width ${image.width} /Height ${image.height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${image.bytes.length} >>`, image.bytes],
                [number + 2, `<< /Length ${draw.length} >>`, new TextEncoder().encode(draw)],
            ];
        }

        function writePdf(objects) {
            const encoder = new TextEncoder(), chunks = [], offsets = [];
            let size = 0;
            const push = chunk => { const bytes = typeof chunk === 'string' ? encoder.encode(chunk) : chunk; chunks.push(bytes); size += bytes.length; };
            push('%PDF-1.4\n');
            objects.forEach(([number, body, stream]) => {
                offsets[number] = size;
                push(`${number} 0 obj\n${body}\n`);
                if (stream) { push('stream\n'); push(stream); push('\nendstream\n'); }
                push('endobj\n');
            });
            const table = size, rows = offsets.slice(1).map(offset => String(offset).padStart(10, '0') + ' 00000 n \n').join('');
            push(`xref\n0 ${objects.length + 1}\n0000000000 65535 f \n${rows}trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${table}\n%%EOF\n`);
            return new Blob(chunks, { type: 'application/pdf' });
        }

        function buildPdf(images, mode) {
            const kids = images.map((_, index) => `${3 + index * 3} 0 R`).join(' ');
            const pages = images.flatMap((image, index) => pageObjects(image, layout(image, mode), 3 + index * 3));
            return writePdf([[1, '<< /Type /Catalog /Pages 2 0 R >>'], [2, `<< /Type /Pages /Kids [${kids}] /Count ${images.length} >>`], ...pages]);
        }

        async function make() {
            $('ip-error').classList.add('hidden');
            $('ip-make').disabled = true;
            $('ip-label').textContent = TEXT.working;
            try {
                const images = await Promise.all(files.map(toJpeg));
                FT.download(TEXT.file_name, buildPdf(images, $('ip-page').value));
            } catch (error) {
                console.error('images to pdf failed', error);
                $('ip-error').classList.remove('hidden');
            }
            $('ip-make').disabled = false;
            $('ip-label').textContent = TEXT.make;
        }

        $('ip-files').addEventListener('change', e => { files = files.concat([...e.target.files]); e.target.value = ''; render(); });
        $('ip-make').addEventListener('click', make);
        render();
    });
</script>
