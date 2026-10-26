<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('deals.items.store', $deal) }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Тип: <strong>{{ $type === \App\Models\DealItem::TYPE_BUY ? 'Покупка (Buy)' : 'Продажа (Sell)' }}</strong></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Кол-во акций</label>
                        <input type="number" name="count" id="count_{{ $modalId }}" class="form-control" required min="1" 
                            value="{{ $type === \App\Models\DealItem::TYPE_SELL ? $deal->initial_count : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Цена за акцию</label>
                        <input type="number" step="0.01" name="price" id="price_{{ $modalId }}" class="form-control"
                            value="{{ $type === \App\Models\DealItem::TYPE_SELL ? $deal->items->where('type', \App\Models\DealItem::TYPE_BUY)->first()?->price : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Итого</label>
                        <input type="number" step="0.01" name="total" id="total_{{ $modalId }}" class="form-control"
                            value="{{ $type === \App\Models\DealItem::TYPE_SELL ? $deal->initial_total : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Комментарий</label>
                        <textarea name="comment" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const modalId = '{{ $modalId }}';
    const priceInput = document.getElementById('price_' + modalId);
    const countInput = document.getElementById('count_' + modalId);
    const totalInput = document.getElementById('total_' + modalId);

    function calculate(e) {
        const p = parseFloat(priceInput.value);
        const c = parseFloat(countInput.value);
        const t = parseFloat(totalInput.value);

        if (e.target === priceInput || e.target === countInput) {
            if (!isNaN(p) && !isNaN(c)) {
                totalInput.value = (p * c).toFixed(2);
            }
        } else if (e.target === totalInput) {
            if (!isNaN(t) && !isNaN(c) && c !== 0) {
                priceInput.value = (t / c).toFixed(2);
            }
        }
    }

    priceInput.addEventListener('input', calculate);
    countInput.addEventListener('input', calculate);
    totalInput.addEventListener('input', calculate);
})();
</script>
