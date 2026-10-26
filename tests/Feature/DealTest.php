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

    $commissionRate = config('app.broker_commission', 0.0001);
    $total = 1500;
    $commission = $total * $commissionRate;

    $this->assertDatabaseHas('deal_items', [
        'ticker' => 'AAPL',
        'type' => DealItem::TYPE_BUY,
        'price' => 150,
        'count' => 10,
        'total' => 1500,
        'commission' => $commission,
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
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'ETH',
        'price' => 1000,
        'count' => 10,
        'total' => 10000,
        'commission' => 1,
    ]);

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

test('user cannot sell more than bought', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

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

    $response = $this->post("/deals/{$deal->id}/items", [
        'type' => DealItem::TYPE_SELL,
        'count' => 11,
        'price' => 160,
        'total' => 1760,
    ]);

    $response->assertSessionHasErrors(['count' => 'нельзя продать больше чем сумма сделки']);
    $this->assertDatabaseMissing('deal_items', [
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'count' => 11,
    ]);
});

test('user can sell exact amount bought', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

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

    $response = $this->post("/deals/{$deal->id}/items", [
        'type' => DealItem::TYPE_SELL,
        'count' => 10,
        'price' => 160,
        'total' => 1600,
    ]);

    $response->assertRedirect("/deals/{$deal->id}");
    $this->assertDatabaseHas('deal_items', [
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'count' => 10,
    ]);
});

test('sell validation considers multiple buy items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
    // First buy 10
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
    // Second buy 5
    DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'AAPL',
        'price' => 155,
        'count' => 5,
        'total' => 775,
        'commission' => 0.08,
    ]);

    // Total bought: 15. Try to sell 16.
    $response = $this->post("/deals/{$deal->id}/items", [
        'type' => DealItem::TYPE_SELL,
        'count' => 16,
        'price' => 160,
        'total' => 2560,
    ]);

    $response->assertSessionHasErrors(['count' => 'нельзя продать больше чем сумма сделки']);

    // Try to sell 15.
    $response = $this->post("/deals/{$deal->id}/items", [
        'type' => DealItem::TYPE_SELL,
        'count' => 15,
        'price' => 160,
        'total' => 2400,
    ]);

    $response->assertRedirect("/deals/{$deal->id}");
    $this->assertDatabaseHas('deal_items', [
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'count' => 15,
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
