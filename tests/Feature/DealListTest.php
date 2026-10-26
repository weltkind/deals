<?php

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;

test('deals list shows correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'TSLA']);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'TSLA',
        'price' => 200,
        'count' => 5,
        'total' => 1000,
        'commission' => 0.1,
    ]);

    $response = $this->get('/deals');
    $response->assertStatus(200);
    $response->assertSee('TSLA');
    $response->assertSee('Long');
    $response->assertSee('5'); // initialCount and currentCount in this case
    $response->assertSee('Текущее кол-во');
    $response->assertSee('1 000.00'); // initialTotal
    $response->assertDontSee('Итог сделки (Buy-Sell)');
    $response->assertSee('Сумма комиссий');
    $response->assertDontSee('Чистый итог');
});

test('deal card shows correct totals and commissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'META']);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'META',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 1.0,
    ]);
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'ticker' => 'META',
        'price' => 120,
        'count' => 5,
        'total' => 600,
        'commission' => 0.6,
    ]);

    $response = $this->get("/deals/{$deal->id}");
    $response->assertSee('-400.00'); // current_total (600 - 1000 = -400)
    $response->assertSee('1.60'); // total_commission
    $response->assertSee('-401.60'); // net_result (-400 - 1.6 = -401.6)
});

test('deal card shows details', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'MSFT', 'comment' => 'Major position']);
    $item = DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'MSFT',
        'price' => 300,
        'count' => 2,
        'total' => 600,
        'commission' => 0.06,
        'comment' => 'Initial buy'
    ]);

    $response = $this->get("/deals/{$deal->id}");
    $response->assertStatus(200);
    $response->assertSee('MSFT');
    $response->assertSee('Major position');
    $response->assertSee('Initial buy');
    $response->assertSee('600.00');
});
