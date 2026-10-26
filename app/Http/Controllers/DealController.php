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
                $total = $price * $count;
            } elseif ($request->filled(['total', 'count']) && !$request->filled('price')) {
                $price = $total / $count;
            }

            $commissionRate = config('app.broker_commission', 0.0001);
            $commission = $total * $commissionRate;

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
                $total = $price * $count;
            } elseif ($request->filled(['total', 'count']) && !$request->filled('price')) {
                $price = $total / $count;
            }

            $commissionRate = config('app.broker_commission', 0.0001);
            $commission = $total * $commissionRate;

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
}
