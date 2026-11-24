<?php

use App\Models\Deal;
use App\Models\DealItem;
use App\Models\User;

test('user can change the type of a deal item', function () {
    $user = User::factory()->create();
    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
    $item = DealItem::create([
        'user_id' => $user->id, 'deal_id' => $deal->id, 'type' => DealItem::TYPE_BUY,
        'ticker' => 'AAPL', 'price' => 100, 'count' => 10, 'total' => 1000, 'commission' => 0.1,
    ]);

    $this->actingAs($user)->get("/deal-items/{$item->id}/edit")
        ->assertOk()->assertSee('name="type"', false)->assertSee('value="buy" selected', false);

    $this->patch("/deal-items/{$item->id}", ['type' => 'sell', 'price' => 100, 'count' => 10, 'total' => 1000])
        ->assertRedirect("/deals/{$deal->id}")->assertSessionHasNoErrors();
    $this->assertDatabaseHas('deal_items', ['id' => $item->id, 'type' => 'sell']);

    $this->get("/deal-items/{$item->id}/edit")->assertSee('value="sell" selected', false);
    $this->patch("/deal-items/{$item->id}", ['type' => 'buy', 'price' => 100, 'count' => 10, 'total' => 1000])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('deal_items', ['id' => $item->id, 'type' => 'buy']);
});

test('user cannot set an invalid deal item type', function () {
    $user = User::factory()->create();
    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
    $item = DealItem::create([
        'user_id' => $user->id, 'deal_id' => $deal->id, 'type' => DealItem::TYPE_BUY,
        'ticker' => 'AAPL', 'price' => 100, 'count' => 10, 'total' => 1000, 'commission' => 0.1,
    ]);

    $this->actingAs($user)->patch("/deal-items/{$item->id}", ['type' => 'invalid', 'price' => 100, 'count' => 10])
        ->assertSessionHasErrors('type');
    $this->assertDatabaseHas('deal_items', ['id' => $item->id, 'type' => 'buy']);
});

test('user can edit a deal item', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
    $item = DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'AAPL',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 0.1,
    ]);

    $response = $this->get("/deal-items/{$item->id}/edit");
    $response->assertStatus(200);
    $response->assertSee('Редактировать операцию');

    $response = $this->patch("/deal-items/{$item->id}", [
        'price' => 110,
        'count' => 10,
        'total' => 1100,
        'comment' => 'Updated comment',
    ]);

    $response->assertRedirect("/deals/{$deal->id}");
    $this->assertDatabaseHas('deal_items', [
        'id' => $item->id,
        'price' => 110,
        'total' => 1100,
        'comment' => 'Updated comment',
    ]);
});

test('user can delete a deal item', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
    $item = DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'AAPL',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 0.1,
    ]);

    $response = $this->delete("/deal-items/{$item->id}");

    $response->assertRedirect("/deals/{$deal->id}");
    $this->assertDatabaseMissing('deal_items', ['id' => $item->id]);
});

test('deal status is recalculated after item edit/delete', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
    $item1 = DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_BUY,
        'ticker' => 'AAPL',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 0.1,
    ]);
    $item2 = DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'ticker' => 'AAPL',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 0.1,
    ]);

    // Deal should be closed now
    $deal->update(['closed_at' => now()]); // In real logic it would be closed on item2 creation if through controller

    // Check it's closed
    expect($deal->fresh()->closed_at)->not->toBeNull();

    // Edit item2 count so it's no longer zero balance
    $this->patch("/deal-items/{$item2->id}", [
        'count' => 5,
        'price' => 100,
        'total' => 500,
    ]);

    expect($deal->fresh()->closed_at)->toBeNull();

    // Delete item2 so it's not zero balance (only item1 left)
    $this->delete("/deal-items/{$item2->id}");
    expect($deal->fresh()->closed_at)->toBeNull();

    // Add item back to close it
    $item2 = DealItem::create([
        'user_id' => $user->id,
        'deal_id' => $deal->id,
        'type' => DealItem::TYPE_SELL,
        'ticker' => 'AAPL',
        'price' => 100,
        'count' => 10,
        'total' => 1000,
        'commission' => 0.1,
    ]);
    // Manually trigger recalculation via dummy update or just call the logic
    // Actually our updateItem/destroyItem calls it.

    $this->patch("/deal-items/{$item2->id}", [
        'count' => 10,
        'price' => 100,
        'total' => 1000,
    ]);
    expect($deal->fresh()->closed_at)->not->toBeNull();
});
