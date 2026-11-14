@php
    use Modules\Shop\Support\Money;
    $product = $line['product'];
    $panel = $panel ?? false;
    $choices = ['size' => ['المقاس', $product->sizes ?? []], 'color' => ['اللون', $product->colors ?? []]];
@endphp
<div class="line">
    <img src="{{ $product->imageUrlFor($line['color']) }}" alt="" width="72" height="72" loading="lazy">
    <div>
        <a class="line-name" href="{{ $product->url() }}">{{ $product->name }}</a>
        <p class="line-price">{{ Money::format($line['line_total']) }}</p>

        @if (($product->sizes ?? []) !== [] || ($product->colors ?? []) !== [])
            <form class="line-opts" method="post" action="{{ route('shop.cart.options', $line['key']) }}" data-auto-submit>
                @csrf @method('patch')
                @if ($panel) <input type="hidden" name="panel" value="1"> @endif
                @foreach ($choices as $field => [$label, $options])
                    @if ($options !== [])
                        <label>
                            <span>{{ $label }}</span>
                            <select name="{{ $field }}" aria-label="{{ $label }} {{ $product->name }}">
                                @foreach ($options as $option)
                                    <option value="{{ $option }}" @selected($line[$field] === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                @endforeach
                <noscript><button class="small-btn" type="submit">تحديث</button></noscript>
            </form>
        @endif

        <div class="line-tools">
            <form class="stepper" method="post" action="{{ route('shop.cart.update', $line['key']) }}">
                @csrf @method('patch')
                @if ($panel) <input type="hidden" name="panel" value="1"> @endif
                <button type="submit" name="quantity" value="{{ $line['quantity'] - 1 }}" aria-label="قلل كمية {{ $product->name }}" @disabled($line['quantity'] <= 1)>−</button>
                <span aria-label="الكمية">{{ $line['quantity'] }}</span>
                <button type="submit" name="quantity" value="{{ $line['quantity'] + 1 }}" aria-label="زود كمية {{ $product->name }}" @disabled($line['quantity'] >= config('shop.max_quantity'))>+</button>
            </form>
            <form method="post" action="{{ route('shop.cart.remove', $line['key']) }}">
                @csrf @method('delete')
                @if ($panel) <input type="hidden" name="panel" value="1"> @endif
                <button class="link-btn" type="submit" aria-label="احذف {{ $product->name }} من السلة">حذف</button>
            </form>
        </div>
    </div>
</div>
