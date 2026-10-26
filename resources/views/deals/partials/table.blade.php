<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>created_at</th>
            <th>ticker</th>
            <th>Начальный размер</th>
            <th>Начальная сумма</th>
            <th>Текущая сумма</th>
            <th>Итог сделки (Buy-Sell)</th>
            <th>Сумма комиссий</th>
            <th>Чистый итог</th>
            <th>Управление</th>
        </tr>
    </thead>
    <tbody>
        @foreach($deals as $deal)
            @php
                $buyItems = $deal->items->where('type', \App\Models\DealItem::TYPE_BUY);
                $sellItems = $deal->items->where('type', \App\Models\DealItem::TYPE_SELL);
                
                $initialCount = $buyItems->sum('count');
                $initialTotal = $buyItems->sum('total');
                $currentTotal = $initialTotal - $sellItems->sum('total');
            @endphp
            <tr>
                <td>{{ $deal->created_at->format('Y.m.d H:i:s') }}</td>
                <td>{{ $deal->ticker }}</td>
                <td>{{ $initialCount }}</td>
                <td>{{ number_format($initialTotal, 2) }}</td>
                <td>{{ number_format($currentTotal, 2) }}</td>
                <td>
                    <span class="{{ $deal->current_total >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($deal->current_total, 2) }}
                    </span>
                </td>
                <td>{{ number_format($deal->total_commission, 2) }}</td>
                <td>
                    <span class="{{ $deal->net_result >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($deal->net_result, 2) }}
                    </span>
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
