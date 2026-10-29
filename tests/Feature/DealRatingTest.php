<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_deal_comment_and_rating()
    {
        $user = User::factory()->create();
        $deal = Deal::create([
            'user_id' => $user->id,
            'ticker' => 'AAPL',
            'comment' => 'Initial comment'
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('deals.update', $deal), [
            'comment' => 'Updated comment',
            'rating' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'comment' => 'Updated comment',
            'rating' => 5,
        ]);
    }

    public function test_rating_must_be_between_1_and_5()
    {
        $user = User::factory()->create();
        $deal = Deal::create([
            'user_id' => $user->id,
            'ticker' => 'AAPL',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('deals.update', $deal), [
            'rating' => 6,
        ]);

        $response->assertSessionHasErrors('rating');
        
        $response = $this->patch(route('deals.update', $deal), [
            'rating' => 0,
        ]);

        $response->assertSessionHasErrors('rating');
    }
}
