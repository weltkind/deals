<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $fillable = ['user_id', 'ticker', 'comment', 'closed_at'];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(DealItem::class);
    }

    public function getCurrentCountAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->type === DealItem::TYPE_BUY ? $item->count : -$item->count;
        });
    }

    public function getInitialCountAttribute()
    {
        $firstItem = $this->items()->orderBy('created_at', 'asc')->first();
        if (!$firstItem) return 0;
        
        return $this->items->where('type', $firstItem->type)->sum('count');
    }

    public function getInitialTotalAttribute()
    {
        $firstItem = $this->items()->orderBy('created_at', 'asc')->first();
        if (!$firstItem) return 0;
        
        return $this->items->where('type', $firstItem->type)->sum('total');
    }

    public function getProfitPercentageAttribute()
    {
        $initialTotal = $this->getInitialTotalAttribute();
        if ($initialTotal == 0) {
            return 0;
        }

        $currentTotal = $this->getCurrentTotalAttribute();
        
        // Для Long: (Sell - Buy) / Buy
        // Для Short: (Sell - Buy) / Sell (но так как в Short Buy < Sell для прибыли, а currentTotal = Sell - Buy > 0, то всё верно)
        // В обоих случаях currentTotal это профит (или убыток), а initialTotal это стоимость открытия.
        return ($currentTotal / $initialTotal) * 100;
    }

    public function getCurrentTotalAttribute()
    {
        $buyTotal = $this->items->where('type', DealItem::TYPE_BUY)->sum('total');
        $sellTotal = $this->items->where('type', DealItem::TYPE_SELL)->sum('total');
        return $sellTotal - $buyTotal;
    }

    public function getTotalCommissionAttribute()
    {
        return $this->items->sum('commission');
    }

    public function getNetResultAttribute()
    {
        return $this->getCurrentTotalAttribute() - $this->getTotalCommissionAttribute();
    }

    public function getTypeLabelAttribute()
    {
        $firstItem = $this->items()->orderBy('created_at', 'asc')->first();
        if (!$firstItem) {
            return '-';
        }
        return $firstItem->type === DealItem::TYPE_BUY ? 'Long' : 'Short';
    }
}
