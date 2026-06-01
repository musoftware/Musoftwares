@props([
    'headers' => [],
    'items' => [],
    'actions' => [],
    'pagination' => null,
    'emptyMessage' => 'No data available',
    'searchable' => false,
    'sortable' => true,
    'hover' => true,
    'striped' => false,
    'bordered' => false,
    'responsive' => true,
    'class' => ''
])

<div class="data-table-container {{ $class }}">
    @if($searchable)
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
                <input type="text" class="form-control" placeholder="{{ __('general.search') }}" wire:model.debounce.300ms="search">
            </div>
        </div>
    @endif

    <div class="{{ $responsive ? 'table-responsive' : '' }}">
        <table class="table at-table {{ $hover ? 'table-hover' : '' }} {{ $striped ? 'table-striped' : '' }} {{ $bordered ? 'table-bordered' : '' }}">
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th class="{{ $header['class'] ?? '' }}" @if($sortable && ($header['sortable'] ?? false)) wire:click="sortBy('{{ $header['key'] }}')" style="cursor: pointer;" @endif>
                            {{ $header['label'] }}
                            @if($sortable && ($header['sortable'] ?? false))
                                <i class="ti ti-arrows-sort"></i>
                            @endif
                        </th>
                    @endforeach
                    @if(!empty($actions))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                    <tr class="data-table-row">
                        @foreach($headers as $header)
                            <td class="{{ $header['cell-class'] ?? '' }}" data-label="{{ $header['label'] }}">
                                @if(isset($header['render']) && is_callable($header['render']))
                                    {!! call_user_func($header['render'], $item, $index) !!}
                                @else
                                    {{ data_get($item, $header['key']) }}
                                @endif
                            </td>
                        @endforeach
                        
                        @if(!empty($actions))
                            <td class="table-actions" data-label="Actions">
                                <div class="d-flex gap-2">
                                    @foreach($actions as $action)
                                        @php
                                            $visible = true;
                                            if (isset($action['visible'])) {
                                                $visible = is_callable($action['visible']) ? call_user_func($action['visible'], $item) : $action['visible'];
                                            }
                                        @endphp
                                        
                                        @if($visible)
                                            @if(($action['type'] ?? 'button') === 'link')
                                                <a href="{{ is_callable($action['href'] ?? '') ? call_user_func($action['href'], $item) : ($action['href'] ?? '#') }}" 
                                                   class="btn btn-{{ $action['size'] ?? 'sm' }} btn-{{ $action['variant'] ?? 'primary' }} {{ $action['class'] ?? '' }}"
                                                   target="{{ $action['target'] ?? '_self' }}"
                                                   @foreach(($action['data'] ?? []) as $key => $val)
                                                       data-{{ $key }}="{{ is_callable($val) ? call_user_func($val, $item) : $val }}"
                                                   @endforeach
                                                >
                                                    @if(isset($action['icon'])) <i class="{{ $action['icon'] }}"></i> @endif
                                                    {{ $action['label'] ?? '' }}
                                                </a>
                                            @elseif(($action['type'] ?? 'button') === 'button')
                                                <button type="button" 
                                                        class="btn btn-{{ $action['size'] ?? 'sm' }} btn-{{ $action['variant'] ?? 'primary' }} {{ $action['class'] ?? '' }}"
                                                        @if(isset($action['onclick'])) onclick="{{ is_callable($action['onclick']) ? call_user_func($action['onclick'], $item) : $action['onclick'] }}" @endif
                                                        @foreach(($action['data'] ?? []) as $key => $val)
                                                            data-{{ $key }}="{{ is_callable($val) ? call_user_func($val, $item) : $val }}"
                                                        @endforeach
                                                >
                                                    @if(isset($action['icon'])) <i class="{{ $action['icon'] }}"></i> @endif
                                                    {{ $action['label'] ?? '' }}
                                                </button>
                                            @elseif(($action['type'] ?? 'button') === 'dropdown')
                                                <div class="dropdown">
                                                    <button class="btn btn-{{ $action['size'] ?? 'sm' }} btn-{{ $action['variant'] ?? 'secondary' }} dropdown-toggle {{ $action['class'] ?? '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        @if(isset($action['icon'])) <i class="{{ $action['icon'] }}"></i> @endif
                                                        {{ $action['label'] ?? 'Actions' }}
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @foreach(($action['items'] ?? []) as $dropdownItem)
                                                            @php
                                                                $itemVisible = true;
                                                                if (isset($dropdownItem['visible'])) {
                                                                    $itemVisible = is_callable($dropdownItem['visible']) ? call_user_func($dropdownItem['visible'], $item) : $dropdownItem['visible'];
                                                                }
                                                            @endphp
                                                            @if($itemVisible)
                                                                @if(($dropdownItem['type'] ?? 'link') === 'divider')
                                                                    <li><hr class="dropdown-divider"></li>
                                                                @elseif(($dropdownItem['type'] ?? 'link') === 'link')
                                                                    <li>
                                                                        <a class="dropdown-item {{ $dropdownItem['class'] ?? '' }}" 
                                                                           href="{{ is_callable($dropdownItem['href'] ?? '') ? call_user_func($dropdownItem['href'], $item) : ($dropdownItem['href'] ?? '#') }}">
                                                                           @if(isset($dropdownItem['icon'])) <i class="{{ $dropdownItem['icon'] }}"></i> @endif
                                                                           {{ $dropdownItem['label'] ?? '' }}
                                                                        </a>
                                                                    </li>
                                                                @elseif(($dropdownItem['type'] ?? 'link') === 'button')
                                                                    <li>
                                                                        <button class="dropdown-item {{ $dropdownItem['class'] ?? '' }}" 
                                                                                @if(isset($dropdownItem['onclick'])) onclick="{{ is_callable($dropdownItem['onclick']) ? call_user_func($dropdownItem['onclick'], $item) : $dropdownItem['onclick'] }}" @endif>
                                                                            @if(isset($dropdownItem['icon'])) <i class="{{ $dropdownItem['icon'] }}"></i> @endif
                                                                            {{ $dropdownItem['label'] ?? '' }}
                                                                        </button>
                                                                    </li>
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) + (empty($actions) ? 0 : 1) }}" class="text-center py-4 data-table-empty-state">
                            <div class="text-muted">
                                <i class="ti ti-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">{{ $emptyMessage }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pagination)
        <div class="data-table-pagination mt-3">
            {{ $pagination->links() }}
        </div>
    @endif
</div>
