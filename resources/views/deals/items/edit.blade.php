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

                <div class="mb-3">
                    <label class="form-label">Скриншот</label>
                    <div id="image_container" class="mb-2">
                        @if($item->image)
                            <div class="mb-2" id="current_image_wrapper">
                                <p class="text-muted small">Текущий скриншот:</p>
                                <img src="{{ asset('uploads/' . $item->image) }}" alt="screenshot" class="img-fluid border rounded" style="max-height: 200px;">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                                    <label class="form-check-label text-danger" for="remove_image">
                                        Удалить текущий скриншот
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div id="image_preview" class="border rounded p-2 text-center bg-light" style="min-height: 100px; cursor: pointer;">
                        <span class="text-muted">Нажмите сюда и вставьте новый скриншот (Ctrl+V), чтобы заменить текущий</span>
                    </div>
                    <input type="hidden" name="image_data" id="image_data">
                    <button type="button" id="clear_image" class="btn btn-sm btn-outline-danger mt-2 d-none">Отменить новый скриншот</button>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            </form>
        </div>
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

    const imagePreview = document.getElementById('image_preview');
    const imageDataInput = document.getElementById('image_data');
    const clearImageBtn = document.getElementById('clear_image');
    const removeImageCheckbox = document.getElementById('remove_image');

    if (imagePreview) {
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
                        
                        // If we paste a new image, we probably don't need the "remove old" checkbox checked
                        if (removeImageCheckbox) {
                            removeImageCheckbox.checked = false;
                        }
                    };
                    reader.readAsDataURL(blob);
                }
            }
        });

        imagePreview.tabIndex = 0;
    }

    if (clearImageBtn) {
        clearImageBtn.addEventListener('click', function() {
            imagePreview.innerHTML = '<span class="text-muted">Нажмите сюда и вставьте новый скриншот (Ctrl+V), чтобы заменить текущий</span>';
            imageDataInput.value = '';
            clearImageBtn.classList.add('d-none');
        });
    }
});
</script>
@endsection
