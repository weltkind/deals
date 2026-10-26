@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('deals.index') }}" class="btn btn-secondary">Назад к списку</a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Сделка #{{ $deal->id }} ({{ $deal->ticker }})</h3>
        </div>
        <div class="card-body">
            <p><strong>Статус:</strong> {{ $deal->closed_at ? 'Закрыта (' . $deal->closed_at->format('Y.m.d H:i:s') . ')' : 'Активна' }}</p>
            <p><strong>Дата открытия:</strong> {{ $deal->created_at->format('Y.m.d H:i:s') }}</p>
            <p><strong>Комментарий:</strong> {{ $deal->comment ?: 'Нет' }}</p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Составляющие сделки (Deal Items)</h4>
        @if(!$deal->closed_at)
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal{{ $deal->id }}">
                    + Добавить операцию
                </button>
                <form action="{{ route('deals.close', $deal) }}" method="POST" class="d-inline" onsubmit="return confirm('Закрыть сделку?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">Закрыть сделку</button>
                </form>
            </div>
        @endif
    </div>

    @if(!$deal->closed_at)
        @include('deals.partials.add_item_modal', ['deal' => $deal])
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
            </tr>
        </thead>
        <tbody>
            @foreach($deal->items as $item)
                <tr>
                    <td>{{ $item->created_at->format('Y.m.d H:i:s') }}</td>
                    <td><span class="badge {{ $item->type === \App\Models\DealItem::TYPE_BUY ? 'bg-success' : 'bg-danger' }}">{{ $item->type }}</span></td>
                    <td>{{ $item->ticker }}</td>
                    <td>{{ number_format($item->price, 6) }}</td>
                    <td>{{ $item->count }}</td>
                    <td>{{ number_format($item->total, 6) }}</td>
                    <td>{{ number_format($item->commission, 2) }}</td>
                    <td>{{ $item->comment }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
