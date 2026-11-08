@if ($options !== [])
    <fieldset class="field">
        <legend>{{ $legend }}</legend>
        <div class="opts">
            @foreach ($options as $option)
                <label class="opt">
                    <input type="radio" name="{{ $field }}" value="{{ $option }}" required @checked(old($field) === $option)>
                    <span>{{ $option }}</span>
                </label>
            @endforeach
        </div>
        @error($field) <p class="err">{{ $message }}</p> @enderror
    </fieldset>
@endif
