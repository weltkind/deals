@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <a href="{{ route('deals.show', $item->deal_id) }}" class="btn btn-secondary">Назад к сделке</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Редактировать операцию: {{ $item->type === 'buy' ? 'Покупка' : 'Продажа' }} {{ $item->ticker }}</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('deals.items.update', $item) }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="price" class="form-label">Цена</label>
                    <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $item->price) }}">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="count" class="form-label">Количество</label>
                    <input type="number" class="form-control @error('count') is-invalid @enderror" id="count" name="count" value="{{ old('count', $item->count) }}" required>
                    @error('count')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="total" class="form-label">Итого (если не заполнено, рассчитается как Цена * Кол-во)</label>
                    <input type="number" step="0.01" class="form-control @error('total') is-invalid @enderror" id="total" name="total" value="{{ old('total', $item->total) }}">
                    @error('total')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="comment" class="form-label">Комментарий</label>
                    <textarea class="form-control @error('comment') is-invalid @enderror" id="comment" name="comment">{{ old('comment', $item->comment) }}</textarea>
                    @error('comment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </form>
        </div>
    </div>
</div>
@endsection
