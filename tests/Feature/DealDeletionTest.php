<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_deal_with_items()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deal = Deal::create([
            'user_id' => $user->id,
            'ticker' => 'AAPL',
        ]);

        $item1 = DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => DealItem::TYPE_BUY,
            'ticker' => 'AAPL',
            'price' => 150,
            'count' => 10,
            'total' => 1500,
            'commission' => 1,
        ]);

        $item2 = DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => DealItem::TYPE_SELL,
            'ticker' => 'AAPL',
            'price' => 160,
            'count' => 5,
            'total' => 800,
            'commission' => 1,
        ]);

        $response = $this->delete(route('deals.destroy', $deal));

        $response->assertRedirect(route('deals.index'));
        $response->assertSessionHas('success', 'Сделка и все её составляющие удалены');

        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
        $this->assertDatabaseMissing('deal_items', ['id' => $item1->id]);
        $this->assertDatabaseMissing('deal_items', ['id' => $item2->id]);
    }

    public function test_user_cannot_delete_other_user_deal()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $deal = Deal::create([
            'user_id' => $user1->id,
            'ticker' => 'AAPL',
        ]);

        $this->actingAs($user2);
        $response = $this->delete(route('deals.destroy', $deal));

        $response->assertStatus(403);
        $this->assertDatabaseHas('deals', ['id' => $deal->id]);
    }
}
