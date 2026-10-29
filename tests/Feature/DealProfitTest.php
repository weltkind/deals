<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DealProfitTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_percentage_calculation_for_long()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Buy 10 AAPL at 150 = 1500
        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
        DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => DealItem::TYPE_BUY,
            'ticker' => 'AAPL',
            'price' => 150,
            'count' => 10,
            'total' => 1500,
            'commission' => 0.15,
        ]);

        // Sell 10 AAPL at 165 = 1650
        DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => DealItem::TYPE_SELL,
            'ticker' => 'AAPL',
            'price' => 165,
            'count' => 10,
            'total' => 1650,
            'commission' => 0.16,
        ]);

        $deal = $deal->fresh();
        
        // Initial Total: 1500
        // Current Total: 1650 - 1500 = 150
        // Profit %: (150 / 1500) * 100 = 10%
        $this->assertEquals(10.0, $deal->profit_percentage);
    }

    public function test_profit_percentage_calculation_for_short()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Sell 5 TSLA at 200 = 1000 (Open Short)
        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'TSLA']);
        DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => DealItem::TYPE_SELL,
            'ticker' => 'TSLA',
            'price' => 200,
            'count' => 5,
            'total' => 1000,
            'commission' => 0.1,
        ]);

        // Buy back 5 TSLA at 180 = 900
        DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => DealItem::TYPE_BUY,
            'ticker' => 'TSLA',
            'price' => 180,
            'count' => 5,
            'total' => 900,
            'commission' => 0.09,
        ]);

        $deal = $deal->fresh();
        
        // Initial Total: 1000 (from first SELL)
        // Current Total: 1000 (sell) - 900 (buy) = 100
        // Profit %: (100 / 1000) * 100 = 10%
        $this->assertEquals(10.0, $deal->profit_percentage);
    }
}
