<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>created_at</th>
            <th>ticker</th>
            <th>Кол-во акций (остаток)</th>
            <th>Начальный размер</th>
            <th>Текущий размер</th>
            <th>Начальная сумма</th>
            <th>Текущая сумма</th>
            <th>Управление</th>
        </tr>
    </thead>
    <tbody>
        @foreach($deals as $deal)
            @php
                $buyItems = $deal->items->where('type', \App\Models\DealItem::TYPE_BUY);
                $sellItems = $deal->items->where('type', \App\Models\DealItem::TYPE_SELL);
                
                $initialCount = $buyItems->sum('count');
                $currentCount = $initialCount - $sellItems->sum('count');
                
                $initialTotal = $buyItems->sum('total');
                $currentTotal = $initialTotal - $sellItems->sum('total');
            @endphp
            <tr>
                <td>{{ $deal->created_at->format('Y.m.d H:i:s') }}</td>
                <td>{{ $deal->ticker }}</td>
                <td>{{ $currentCount }}</td>
                <td>{{ $initialCount }}</td>
                <td>{{ $currentCount }}</td>
                <td>{{ number_format($initialTotal, 2) }}</td>
                <td>{{ number_format($currentTotal, 2) }}</td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('deals.show', $deal) }}" class="btn btn-sm btn-info text-white">Карточка</a>
                        @if(!$deal->closed_at)
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal{{ $deal->id }}">
                                + Добавить
                            </button>
                            <form action="{{ route('deals.close', $deal) }}" method="POST" onsubmit="return confirm('Закрыть сделку?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">Закрыть</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @if(!$deal->closed_at)
                @include('deals.partials.add_item_modal', ['deal' => $deal])
            @endif
        @endforeach
    </tbody>
</table>
