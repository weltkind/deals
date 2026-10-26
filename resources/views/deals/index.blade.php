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

    <ul class="nav nav-tabs mb-3" id="dealsTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button">Активные сделки</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">История сделок</button>
        </li>
    </ul>
    
    <div class="tab-content" id="dealsTabsContent">
        <div class="tab-pane fade show active" id="active">
            @include('deals.partials.table', ['deals' => $activeDeals])
        </div>
        <div class="tab-pane fade" id="history">
            @include('deals.partials.table', ['deals' => $closedDeals])
        </div>
    </div>
</div>

<!-- Modal -->
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
                        <input type="number" step="0.000001" name="price" id="price" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Итого</label>
                        <input type="number" step="0.000001" name="total" id="total" class="form-control">
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
                totalInput.value = (p * c).toFixed(6);
            }
        } else if (this === totalInput) {
            if (!isNaN(t) && !isNaN(c) && c !== 0) {
                priceInput.value = (t / c).toFixed(6);
            }
        }
    }

    priceInput.addEventListener('input', calculate);
    countInput.addEventListener('input', calculate);
    totalInput.addEventListener('input', calculate);
});
</script>
@endsection
