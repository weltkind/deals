<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealItem;
use App\Http\Requests\StoreDealRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $activeDeals = Deal::where('user_id', Auth::id())
            ->whereNull('closed_at')
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('deals.index', compact('activeDeals'));
    }

    public function history(Request $request)
    {
        $query = Deal::where('user_id', Auth::id())
            ->whereNotNull('closed_at');

        if ($request->filled('date_from')) {
            $query->whereDate('closed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('closed_at', '<=', $request->date_to);
        }

        $closedDeals = $query->with('items')
            ->orderBy('closed_at', 'desc')
            ->get();

        return view('deals.history', compact('closedDeals'));
    }

    public function show(Deal $deal)
    {
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }
        $deal->load('items');
        return view('deals.show', compact('deal'));
    }

    public function update(Deal $deal, Request $request)
    {
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $deal->update($validated);

        return redirect()->back()->with('success', 'Сделка обновлена');
    }

    public function destroy(Deal $deal)
    {
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }

        DB::transaction(function () use ($deal) {
            foreach ($deal->items as $item) {
                if ($item->image) {
                    $path = public_path('uploads/' . $item->image);
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }
            $deal->items()->delete();
            $deal->delete();
        });

        return redirect()->route('deals.index')->with('success', 'Сделка и все её составляющие удалены');
    }

    public function store(StoreDealRequest $request)
    {
        $deal = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            
            $type = $request->input('type', DealItem::TYPE_BUY);

            $deal = Deal::create([
                'user_id' => Auth::id(),
                'ticker' => $validated['ticker'],
                'comment' => $validated['comment'] ?? null,
            ]);

            $price = $validated['price'] ?? null;
            $count = $validated['count'];
            $total = $validated['total'] ?? null;
            
            if ($request->filled(['price', 'count']) && !$request->filled('total')) {
                $total = round($price * $count, 2);
            } elseif ($request->filled(['total', 'count']) && !$request->filled('price')) {
                $price = round($total / $count, 2);
            }

            $commissionRate = config('app.broker_commission', 0.0001);
            $commission = round($total * $commissionRate, 2);

            $imageName = null;
            if ($request->filled('image_data')) {
                $imageData = $request->input('image_data');
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type_match)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $imageData = base64_decode($imageData);
                    if ($imageData !== false) {
                        $imageName = md5($imageData) . '.jpg';
                        $path = public_path('uploads/' . $imageName);
                        file_put_contents($path, $imageData);
                    }
                }
            }

            DealItem::create([
                'user_id' => Auth::id(),
                'deal_id' => $deal->id,
                'type' => $type,
                'ticker' => $deal->ticker,
                'price' => $price,
                'count' => $count,
                'total' => $total,
                'commission' => $commission,
                'comment' => $validated['comment'] ?? null,
                'image' => $imageName,
            ]);

            return $deal;
        });

        return redirect()->route('deals.show', $deal)->with('success', 'Сделка открыта');
    }

    public function storeItem(Deal $deal, StoreDealRequest $request)
    {
        if ($deal->user_id !== Auth::id() || $deal->closed_at) {
            abort(403);
        }

        $validated = $request->validated();
        $type = $request->input('type', DealItem::TYPE_BUY);
        $count = $validated['count'];

        // Расчет текущего баланса акций
        $currentShares = $deal->items()->sum(DB::raw("CASE WHEN type = 'buy' THEN count ELSE -count END"));

        if ($type === DealItem::TYPE_SELL) {
            // Если мы продаем, то либо мы закрываем лонг, либо открываем шорт.
            // Но в задании сказано: "нельзя продать больше чем сумма сделки" (для buy)
            // И "число акций всех прошлых buy-sell deal_items включая новые из формы = 0, т.е. сделка закрыта"
            // Это намекает на то, что баланс должен стремиться к нулю.
            
            // Если сделка была открыта как BUY (long), то сумма buy >= сумма sell
            // Если сделка была открыта как SELL (short), то сумма sell >= сумма buy (в абсолютных значениях)
            
            $firstItem = $deal->items()->orderBy('created_at', 'asc')->first();
            
            if ($firstItem->type === DealItem::TYPE_BUY) {
                $totalBuy = $deal->items()->where('type', DealItem::TYPE_BUY)->sum('count');
                $totalSell = $deal->items()->where('type', DealItem::TYPE_SELL)->sum('count');
                if ($totalSell + $count > $totalBuy) {
                    return back()->withErrors(['count' => 'нельзя продать больше чем сумма сделки'])->withInput();
                }
            } else {
                // Short trading logic: first item is SELL.
                // В шорте мы сначала продаем, потом покупаем.
                // Для простоты пока оставим проверку только для лонга, если не указано иное, 
                // но задание просит "проверь аналогичную логику при торговле в шорт".
                // В шорте нельзя КУПИТЬ больше чем продали.
            }
        }

        if ($type === DealItem::TYPE_BUY) {
            $firstItem = $deal->items()->orderBy('created_at', 'asc')->first();
            if ($firstItem && $firstItem->type === DealItem::TYPE_SELL) {
                $totalSell = $deal->items()->where('type', DealItem::TYPE_SELL)->sum('count');
                $totalBuy = $deal->items()->where('type', DealItem::TYPE_BUY)->sum('count');
                if ($totalBuy + $count > $totalSell) {
                    return back()->withErrors(['count' => 'нельзя купить больше чем сумма сделки (short)'])->withInput();
                }
            }
        }

        DB::transaction(function () use ($deal, $request, $validated, $type, $count) {
            $price = $validated['price'] ?? null;
            $total = $validated['total'] ?? null;
            
            if ($request->filled(['price', 'count']) && !$request->filled('total')) {
                $total = round($price * $count, 2);
            } elseif ($request->filled(['total', 'count']) && !$request->filled('price')) {
                $price = round($total / $count, 2);
            }

            $commissionRate = config('app.broker_commission', 0.0001);
            $commission = round($total * $commissionRate, 2);

            $imageName = null;
            if ($request->filled('image_data')) {
                $imageData = $request->input('image_data');
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type_match)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $imageData = base64_decode($imageData);
                    if ($imageData !== false) {
                        $imageName = md5($imageData) . '.jpg';
                        $path = public_path('uploads/' . $imageName);
                        file_put_contents($path, $imageData);
                    }
                }
            }

            $dealItem = DealItem::create([
                'user_id' => Auth::id(),
                'deal_id' => $deal->id,
                'type' => $type,
                'ticker' => $deal->ticker,
                'price' => $price,
                'count' => $count,
                'total' => $total,
                'commission' => $commission,
                'comment' => $validated['comment'] ?? null,
                'image' => $imageName,
            ]);

            // Проверка закрытия сделки
            $remainingShares = $deal->items()->sum(DB::raw("CASE WHEN type = 'buy' THEN count ELSE -count END"));
            if ($remainingShares == 0) {
                $deal->update(['closed_at' => now()]);
            }
        });

        return redirect()->route('deals.show', $deal)->with('success', 'Операция добавлена');
    }

    public function close(Deal $deal)
    {
        if ($deal->user_id !== Auth::id()) {
            abort(403);
        }

        $deal->update([
            'closed_at' => now()
        ]);

        return redirect()->route('deals.index')->with('success', 'Сделка закрыта');
    }

    public function editItem(DealItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        return view('deals.items.edit', compact('item'));
    }

    public function updateItem(DealItem $item, StoreDealRequest $request)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $deal = $item->deal;
        $validated = $request->validated();
        
        DB::transaction(function () use ($item, $deal, $request, $validated) {
            $price = $validated['price'] ?? null;
            $count = $validated['count'];
            $total = $validated['total'] ?? null;
            
            if ($request->filled(['price', 'count']) && !$request->filled('total')) {
                $total = round($price * $count, 2);
            } elseif ($request->filled(['total', 'count']) && !$request->filled('price')) {
                $price = round($total / $count, 2);
            }

            $commissionRate = config('app.broker_commission', 0.0001);
            $commission = round($total * $commissionRate, 2);

            $imageName = $item->image;
            if ($request->filled('image_data')) {
                $imageData = $request->input('image_data');
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type_match)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $imageData = base64_decode($imageData);
                    if ($imageData !== false) {
                        // Удаляем старый файл, если он есть
                        if ($item->image) {
                            $oldPath = public_path('uploads/' . $item->image);
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        $imageName = md5($imageData) . '.jpg';
                        $path = public_path('uploads/' . $imageName);
                        file_put_contents($path, $imageData);
                    }
                }
            } elseif ($request->boolean('remove_image')) {
                if ($item->image) {
                    $oldPath = public_path('uploads/' . $item->image);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                    $imageName = null;
                }
            }

            $item->update([
                'price' => $price,
                'count' => $count,
                'total' => $total,
                'commission' => $commission,
                'comment' => $validated['comment'] ?? null,
                'image' => $imageName,
            ]);

            $this->recalculateDealStatus($deal);
        });

        return redirect()->route('deals.show', $deal)->with('success', 'Операция обновлена');
    }

    public function destroyItem(DealItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $deal = $item->deal;

        DB::transaction(function () use ($item, $deal) {
            if ($item->image) {
                $path = public_path('uploads/' . $item->image);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            $item->delete();
            $this->recalculateDealStatus($deal);
        });

        return redirect()->route('deals.show', $deal)->with('success', 'Операция удалена');
    }

    private function recalculateDealStatus(Deal $deal)
    {
        $remainingShares = $deal->items()->sum(DB::raw("CASE WHEN type = 'buy' THEN count ELSE -count END"));
        if ($remainingShares == 0) {
            if (!$deal->closed_at) {
                $deal->update(['closed_at' => now()]);
            }
        } else {
            if ($deal->closed_at) {
                $deal->update(['closed_at' => null]);
            }
        }
    }
}
