<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>created_at</th>
            <th>ticker</th>
            <th>Тип</th>
            <th>Число акций</th>
            <th>Начальная позиция</th>
            <th>Итоговая сумма</th>
            <th>Сумма комиссий</th>
            <th>Чистый итог</th>
            <th>Оценка</th>
            <th>Управление</th>
        </tr>
    </thead>
    <tbody>
        @foreach($deals as $deal)
            <tr>
                <td>{{ $deal->created_at->format('Y.m.d H:i:s') }}</td>
                <td>{{ $deal->ticker }}</td>
                <td>
                    <span class="badge {{ $deal->type_label === 'Long' ? 'bg-success' : ($deal->type_label === 'Short' ? 'bg-danger' : 'bg-secondary') }}">
                        {{ $deal->type_label }}
                    </span>
                </td>
                <td>{{ $deal->initial_count }}</td>
                <td>{{ number_format($deal->initial_total, 2, '.', ' ') }}</td>
                <td>
                    <span class="{{ $deal->current_total >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($deal->current_total, 2, '.', ' ') }}
                        ({{ number_format($deal->profit_percentage, 2) }}%)
                    </span>
                </td>
                <td>{{ number_format($deal->total_commission, 2, '.', ' ') }}</td>
                <td>
                    <span class="{{ $deal->net_result >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($deal->net_result, 2, '.', ' ') }}
                    </span>
                </td>
                <td>
                    @if($deal->rating == 5)
                        <span class="badge bg-success">5</span>
                    @elseif(in_array($deal->rating, [1, 2]))
                        <span class="badge bg-danger">{{ $deal->rating }}</span>
                    @else
                        {{ $deal->rating ?: '-' }}
                    @endif
                </td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('deals.show', $deal) }}" class="btn btn-sm btn-info text-white">Карточка</a>
                        @if(!$deal->closed_at)
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#buyItemModal{{ $deal->id }}">
                                Купить
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#sellItemModal{{ $deal->id }}">
                                Продать
                            </button>
                        @endif
                        @php
                            $lastItem = $deal->items->sortByDesc('created_at')->first();
                        @endphp
                        @if($lastItem && $lastItem->image)
                            <a href="{{ asset('uploads/' . $lastItem->image) }}" target="_blank" class="btn btn-sm p-0 border-0 me-1">
                                <img src="{{ asset('uploads/' . $lastItem->image) }}" alt="screenshot" style="height: 31px; width: auto; object-fit: cover;" class="rounded border">
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
            @if(!$deal->closed_at)
                @include('deals.partials.add_item_modal', ['deal' => $deal, 'modalId' => 'buyItemModal' . $deal->id, 'type' => \App\Models\DealItem::TYPE_BUY, 'title' => 'Купить: ' . $deal->ticker])
                @include('deals.partials.add_item_modal', ['deal' => $deal, 'modalId' => 'sellItemModal' . $deal->id, 'type' => \App\Models\DealItem::TYPE_SELL, 'title' => 'Продать: ' . $deal->ticker])
            @endif
        @endforeach
    </tbody>
</table>
