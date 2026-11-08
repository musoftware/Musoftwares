<form method="post" action="{{ route('shop.cart.add', $product->id) }}">
    @csrf
    @include('shop::partials.option-group', ['field' => 'size', 'legend' => 'المقاس', 'options' => $product->sizes ?? []])
    @include('shop::partials.option-group', ['field' => 'color', 'legend' => 'اللون', 'options' => $product->colors ?? []])

    <div class="field">
        <label for="quantity">الكمية</label>
        <input id="quantity" class="qty" type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="{{ config('shop.max_quantity') }}" inputmode="numeric" required>
        @error('quantity') <p class="err">{{ $message }}</p> @enderror
    </div>

    <div class="actions">
        <button class="btn" type="submit" name="buy_now" value="1">اطلب الآن</button>
        <button class="btn alt" type="submit">أضف للسلة</button>
    </div>
</form>
