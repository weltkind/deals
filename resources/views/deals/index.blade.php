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
        <div class="tab-pane fade show active" id="active">
            @include('deals.partials.active_table', ['deals' => $activeDeals])
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
                    <div class="mb-3">
                        <label class="form-label">Скриншот (вставьте из буфера обмена)</label>
                        <div id="image_preview" class="border rounded p-2 text-center bg-light" style="min-height: 100px; cursor: pointer;">
                            <span class="text-muted">Нажмите сюда и вставьте скриншот (Ctrl+V)</span>
                        </div>
                        <input type="hidden" name="image_data" id="image_data">
                        <button type="button" id="clear_image" class="btn btn-sm btn-outline-danger mt-2 d-none">Удалить скриншот</button>
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

    priceInput.addEventListener('input', calculate);
    countInput.addEventListener('input', calculate);
    totalInput.addEventListener('input', calculate);

    const imagePreview = document.getElementById('image_preview');
    const imageDataInput = document.getElementById('image_data');
    const clearImageBtn = document.getElementById('clear_image');

    imagePreview.addEventListener('paste', function(e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let index in items) {
            const item = items[index];
            if (item.kind === 'file' && item.type.indexOf('image') !== -1) {
                const blob = item.getAsFile();
                const reader = new FileReader();
                reader.onload = function(event) {
                    const base64data = event.target.result;
                    imagePreview.innerHTML = `<img src="${base64data}" class="img-fluid" style="max-height: 300px;">`;
                    imageDataInput.value = base64data;
                    clearImageBtn.classList.remove('d-none');
                };
                reader.readAsDataURL(blob);
            }
        }
    });

    imagePreview.tabIndex = 0;

    clearImageBtn.addEventListener('click', function() {
        imagePreview.innerHTML = '<span class="text-muted">Нажмите сюда и вставьте скриншот (Ctrl+V)</span>';
        imageDataInput.value = '';
        clearImageBtn.classList.add('d-none');
    });
});
</script>
@endsection
