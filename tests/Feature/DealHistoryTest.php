<?php

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;

test('deals history shows totals and filter works', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a closed deal
    $deal1 = Deal::create([
        'user_id' => $user->id, 
        'ticker' => 'MSFT', 
        'closed_at' => now()->subDays(5)
    ]);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal1->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'MSFT',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 10,
    ]);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal1->id,
        'type' => DealItem::TYPE_SELL,
        'ticker' => 'MSFT',
        'price' => 120,
        'count' => 10,
        'total' => 1200,
        'commission' => 12,
    ]);

    // Create another closed deal
    $deal2 = Deal::create([
        'user_id' => $user->id, 
        'ticker' => 'GOOG', 
        'closed_at' => now()
    ]);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal2->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'GOOG',
        'price' => 200,
        'count' => 5,
        'total' => 1000,
        'commission' => 5,
    ]);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal2->id,
        'type' => DealItem::TYPE_SELL,
        'ticker' => 'GOOG',
        'price' => 180,
        'count' => 5,
        'total' => 900,
        'commission' => 4,
    ]);

    $response = $this->get('/deals/history');
    $response->assertSee('MSFT');
    $response->assertSee('GOOG');
    $response->assertSee('Итог сделки (Buy-Sell)');
    $response->assertSee('Сумма комиссий');
    $response->assertSee('Чистый итог');
    $response->assertSee('Итого по закрытым сделкам:');
    $response->assertSee('100.00'); // MSFT (1200-1000=200) + GOOG (900-1000=-100) = 100
    $response->assertSee('31.00'); // Commissions: 10+12+5+4 = 31
    $response->assertSee('69.00'); // Net result: 100 - 31 = 69

    // Test filter
    $response = $this->get('/deals/history?date_from=' . now()->subDays(1)->format('Y-m-d'));
    $response->assertDontSee('MSFT');
    $response->assertSee('GOOG');
});

test('deals filter works with boundary dates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Deal 1: closed 3 days ago
    Deal::create([
        'user_id' => $user->id,
        'ticker' => 'D1',
        'closed_at' => now()->subDays(3)->startOfDay()
    ]);

    // Deal 2: closed today
    Deal::create([
        'user_id' => $user->id,
        'ticker' => 'D2',
        'closed_at' => now()->startOfDay()
    ]);

    // Filter from 2 days ago to today
    $response = $this->get('/deals/history?date_from=' . now()->subDays(2)->format('Y-m-d') . '&date_to=' . now()->format('Y-m-d'));
    $response->assertDontSee('D1');
    $response->assertSee('D2');

    // Filter from 4 days ago to 2 days ago
    $response = $this->get('/deals/history?date_from=' . now()->subDays(4)->format('Y-m-d') . '&date_to=' . now()->subDays(2)->format('Y-m-d'));
    $response->assertSee('D1');
    $response->assertDontSee('D2');
});
