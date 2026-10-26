<?php

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;

test('admin can log in', function () {
    $admin = User::factory()->create([
        'email' => 'admin@admijn.ru',
        'password' => bcrypt('qaz2wsx'),
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@admijn.ru',
        'password' => 'qaz2wsx',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/deals');
});

test('user can open a deal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/deals', [
        'ticker' => 'AAPL',
        'count' => 10,
        'price' => 150.00,
        'comment' => 'Test buy',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Сделка открыта');
    
    $this->assertDatabaseHas('deals', [
        'user_id' => $user->id,
        'ticker' => 'AAPL',
    ]);

    $this->assertDatabaseHas('deal_items', [
        'ticker' => 'AAPL',
        'type' => DealItem::TYPE_BUY,
        'price' => 150,
        'count' => 10,
        'total' => 1500,
        'commission' => 1500 * 0.0001,
    ]);
});

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
    $response->assertSee('5'); // count
    $response->assertSee('1,000.00'); // total
});

test('user can close a deal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'BTC']);

    $response = $this->post("/deals/{$deal->id}/close");
    
    $response->assertRedirect('/deals');
    $this->assertNotNull($deal->fresh()->closed_at);
});

test('user can add item to deal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'ETH']);

    $response = $this->post("/deals/{$deal->id}/items", [
        'type' => DealItem::TYPE_SELL,
        'count' => 5,
        'price' => 2000,
        'total' => 10000,
    ]);

    $response->assertRedirect("/deals/{$deal->id}");
    $this->assertDatabaseHas('deal_items', [
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'count' => 5,
        'total' => 10000,
    ]);
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
    $response->assertSee('600.000000');
});
