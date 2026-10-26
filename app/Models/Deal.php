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
        return $this->items->where('type', DealItem::TYPE_BUY)->first()?->count ?? 0;
    }

    public function getInitialTotalAttribute()
    {
        return $this->items->where('type', DealItem::TYPE_BUY)->first()?->total ?? 0;
    }

    public function getCurrentTotalAttribute()
    {
        $buyTotal = $this->items->where('type', DealItem::TYPE_BUY)->sum('total');
        $sellTotal = $this->items->where('type', DealItem::TYPE_SELL)->sum('total');
        return $buyTotal - $sellTotal;
    }

    public function getTotalCommissionAttribute()
    {
        return $this->items->sum('commission');
    }

    public function getNetResultAttribute()
    {
        return $this->getCurrentTotalAttribute() - $this->getTotalCommissionAttribute();
    }
}
