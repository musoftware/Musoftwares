<div class="field">
    <label for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" type="{{ $type ?? 'text' }}" name="{{ $name }}" value="{{ old($name) }}" autocomplete="{{ $autocomplete }}"
           @if (! empty($dir)) dir="{{ $dir }}" @endif @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror required>
    @error($name) <p class="err" id="{{ $name }}-error">{{ $message }}</p> @enderror
</div>
