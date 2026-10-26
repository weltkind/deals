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
    public function index()
    {
        $activeDeals = Deal::where('user_id', Auth::id())
            ->whereNull('closed_at')
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $closedDeals = Deal::where('user_id', Auth::id())
            ->whereNotNull('closed_at')
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('deals.index', compact('activeDeals', 'closedDeals'));
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
                'type' => DealItem::TYPE_BUY,
                'ticker' => $deal->ticker,
                'price' => $price,
                'count' => $count,
                'total' => $total,
                'commission' => $commission,
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

        if ($type === DealItem::TYPE_SELL) {
            $totalBuy = $deal->items()->where('type', DealItem::TYPE_BUY)->sum('count');
            if ($count > $totalBuy) {
                return back()->withErrors(['count' => 'нельзя продать больше чем сумма сделки'])->withInput();
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
