<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealItem extends Model
{
    const TYPE_BUY = 'buy';
    const TYPE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'deal_id',
        'type',
        'ticker',
        'comment',
        'price',
        'count',
        'total',
        'commission',
        'closed_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'commission' => 'decimal:2',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
