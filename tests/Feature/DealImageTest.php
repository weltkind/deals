<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\File;

class DealImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!File::exists(public_path('uploads'))) {
            File::makeDirectory(public_path('uploads'), 0777, true);
        }
    }

    public function test_can_create_deal_with_image()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Dummy red dot 1x1 image in base64
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $response = $this->post('/deals', [
            'type' => 'buy',
            'ticker' => 'AAPL',
            'count' => 10,
            'price' => 150,
            'image_data' => $base64Image,
            'comment' => 'Test with image'
        ]);

        $deal = Deal::first();
        $item = DealItem::first();

        $response->assertRedirect("/deals/{$deal->id}");
        $this->assertNotNull($item->image);
        $this->assertTrue(File::exists(public_path('uploads/' . $item->image)));
        
        // Cleanup
        File::delete(public_path('uploads/' . $item->image));
    }

    public function test_can_add_deal_item_with_image()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
        
        // Dummy blue dot 1x1 image in base64
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADegHC44y6+AAAAABJRU5ErkJggg==';

        $response = $this->post("/deals/{$deal->id}/items", [
            'type' => 'buy',
            'count' => 5,
            'price' => 155,
            'image_data' => $base64Image,
        ]);

        $item = DealItem::orderBy('id', 'desc')->first();

        $response->assertRedirect("/deals/{$deal->id}");
        $this->assertNotNull($item->image);
        $this->assertTrue(File::exists(public_path('uploads/' . $item->image)));

        // Cleanup
        File::delete(public_path('uploads/' . $item->image));
    }
}
