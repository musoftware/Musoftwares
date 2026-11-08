@if ($line['size'] || $line['color'])
    <p class="line-meta">
        @if ($line['size']) المقاس: {{ $line['size'] }} @endif
        @if ($line['color']) اللون: {{ $line['color'] }} @endif
    </p>
@endif
