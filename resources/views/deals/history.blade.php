@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Дневник сделок</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDealModal">
            Добавить сделку
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <ul class="nav nav-tabs mb-3" id="dealsTabs">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('deals.index') ? 'active' : '' }}" href="{{ route('deals.index') }}">Активные сделки</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('deals.history') ? 'active' : '' }}" href="{{ route('deals.history') }}">История сделок</a>
        </li>
    </ul>
    
    <div class="tab-content" id="dealsTabsContent">
        <div class="tab-pane fade show active" id="history">
            <div class="card mb-3">
                <div class="card-body">
                    <form action="{{ route('deals.history') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Дата закрытия с</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">по</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-secondary">Фильтровать</button>
                            <a href="{{ route('deals.history') }}" class="btn btn-outline-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            @include('deals.partials.history_table', ['deals' => $closedDeals])

            @php
                $totalBuySell = $closedDeals->sum('current_total');
                $totalCommissions = $closedDeals->sum('total_commission');
                $totalNetResult = $closedDeals->sum('net_result');
            @endphp

            <div class="card mt-3">
                <div class="card-body">
                    <h5>Итого по закрытым сделкам:</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Итого (Buy-Sell):</strong>
                            <span class="{{ $totalBuySell >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($totalBuySell, 2) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <strong>Сумма комиссий:</strong>
                            {{ number_format($totalCommissions, 2) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Чистый итог:</strong>
                            <span class="{{ $totalNetResult >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($totalNetResult, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal - copied from index to allow adding deals from history page if needed -->
<div class="modal fade" id="addDealModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('deals.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Открыть сделку</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Тип</label>
                        <select name="type" class="form-select">
                            <option value="buy">Купить (Long)</option>
                            <option value="sell">Продать (Short)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тикер</label>
                        <input type="text" name="ticker" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Кол-во акций</label>
                        <input type="number" name="count" id="count" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Цена за акцию</label>
                        <input type="number" step="0.01" name="price" id="price" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Итого</label>
                        <input type="number" step="0.01" name="total" id="total" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Комментарий</label>
                        <textarea name="comment" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Открыть</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceInput = document.getElementById('price');
    const countInput = document.getElementById('count');
    const totalInput = document.getElementById('total');

    function calculate() {
        const p = parseFloat(priceInput.value);
        const c = parseFloat(countInput.value);
        const t = parseFloat(totalInput.value);

        if (this === priceInput || this === countInput) {
            if (!isNaN(p) && !isNaN(c)) {
                totalInput.value = (p * c).toFixed(2);
            }
        } else if (this === totalInput) {
            if (!isNaN(t) && !isNaN(c) && c !== 0) {
                priceInput.value = (t / c).toFixed(2);
            }
        }
    }

    if (priceInput) priceInput.addEventListener('input', calculate);
    if (countInput) countInput.addEventListener('input', calculate);
    if (totalInput) totalInput.addEventListener('input', calculate);
});
</script>
@endsection
