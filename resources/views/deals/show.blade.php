@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4 d-flex justify-content-between">
        <a href="{{ route('deals.index') }}" class="btn btn-secondary">Назад к списку</a>
        
        <form action="{{ route('deals.destroy', $deal) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить всю сделку и все её операции?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Удалить сделку</button>
        </form>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Сделка #{{ $deal->id }} ({{ $deal->ticker }})</h3>
        </div>
        <div class="card-body">
            <p><strong>Статус:</strong> {{ $deal->closed_at ? 'Закрыта (' . $deal->closed_at->format('Y.m.d H:i:s') . ')' : 'Активна' }}</p>
            <p><strong>Дата открытия:</strong> {{ $deal->created_at->format('Y.m.d H:i:s') }}</p>
            <p><strong>Комментарий:</strong> {{ $deal->comment ?: 'Нет' }}</p>
            <hr>
            <p><strong>Итог сделки (Buy-Sell):</strong> 
                <span class="{{ $deal->current_total >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($deal->current_total, 2, '.', ' ') }}
                    ({{ number_format($deal->profit_percentage, 2) }}%)
                </span>
            </p>
            <p><strong>Сумма комиссий:</strong> {{ number_format($deal->total_commission, 2, '.', ' ') }}</p>
            <p><strong>Чистый итог:</strong> 
                <span class="{{ $deal->net_result >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($deal->net_result, 2, '.', ' ') }}
                </span>
            </p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Составляющие сделки (Deal Items)</h4>
        @if(!$deal->closed_at)
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#buyItemModal{{ $deal->id }}">
                    Купить
                </button>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#sellItemModal{{ $deal->id }}">
                    Продать
                </button>
            </div>
        @endif
    </div>

    @if(!$deal->closed_at)
        @include('deals.partials.add_item_modal', ['deal' => $deal, 'modalId' => 'buyItemModal' . $deal->id, 'type' => \App\Models\DealItem::TYPE_BUY, 'title' => 'Купить: ' . $deal->ticker])
        @include('deals.partials.add_item_modal', ['deal' => $deal, 'modalId' => 'sellItemModal' . $deal->id, 'type' => \App\Models\DealItem::TYPE_SELL, 'title' => 'Продать: ' . $deal->ticker])
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Тип</th>
                <th>Тикер</th>
                <th>Цена</th>
                <th>Кол-во</th>
                <th>Итого</th>
                <th>Комиссия</th>
                <th>Комментарий</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deal->items as $item)
                <tr>
                    <td>{{ $item->created_at->format('Y.m.d H:i:s') }}</td>
                    <td><span class="badge {{ $item->type === \App\Models\DealItem::TYPE_BUY ? 'bg-success' : 'bg-danger' }}">{{ $item->type }}</span></td>
                    <td>{{ $item->ticker }}</td>
                    <td>{{ number_format($item->price, 2, '.', ' ') }}</td>
                    <td>{{ $item->count }}</td>
                    <td>{{ number_format($item->total, 2, '.', ' ') }}</td>
                    <td>{{ number_format($item->commission, 2, '.', ' ') }}</td>
                    <td>{{ $item->comment }}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('deals.items.edit', $item) }}" class="btn btn-sm btn-outline-primary">Редактировать</a>
                            <form action="{{ route('deals.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить эту операцию?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
