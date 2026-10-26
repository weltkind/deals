<div class="modal fade" id="addItemModal{{ $deal->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('deals.items.store', $deal) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить операцию: {{ $deal->ticker }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Тип</label>
                        <select name="type" class="form-select">
                            <option value="{{ \App\Models\DealItem::TYPE_BUY }}">Покупка (Buy)</option>
                            <option value="{{ \App\Models\DealItem::TYPE_SELL }}">Продажа (Sell)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Кол-во акций</label>
                        <input type="number" name="count" id="count_{{ $deal->id }}" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Цена за акцию</label>
                        <input type="number" step="0.000001" name="price" id="price_{{ $deal->id }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Итого</label>
                        <input type="number" step="0.000001" name="total" id="total_{{ $deal->id }}" class="form-control">
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
    const dealId = '{{ $deal->id }}';
    const priceInput = document.getElementById('price_' + dealId);
    const countInput = document.getElementById('count_' + dealId);
    const totalInput = document.getElementById('total_' + dealId);

    function calculate(e) {
        const p = parseFloat(priceInput.value);
        const c = parseFloat(countInput.value);
        const t = parseFloat(totalInput.value);

        if (e.target === priceInput || e.target === countInput) {
            if (!isNaN(p) && !isNaN(c)) {
                totalInput.value = (p * c).toFixed(6);
            }
        } else if (e.target === totalInput) {
            if (!isNaN(t) && !isNaN(c) && c !== 0) {
                priceInput.value = (t / c).toFixed(6);
            }
        }
    }

    priceInput.addEventListener('input', calculate);
    countInput.addEventListener('input', calculate);
    totalInput.addEventListener('input', calculate);
})();
</script>
