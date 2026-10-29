<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use App\Models\DealItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class DealItemImageEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!file_exists(public_path('uploads'))) {
            mkdir(public_path('uploads'), 0777, true);
        }
    }

    public function test_can_update_deal_item_image()
    {
        $user = User::factory()->create();
        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
        
        // 1. Создаем айтем с картинкой
        $imageData1 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $imageName1 = md5(base64_decode(substr($imageData1, strpos($imageData1, ',') + 1))) . '.jpg';
        
        $item = DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => 'buy',
            'ticker' => 'AAPL',
            'count' => 10,
            'price' => 150,
            'total' => 1500,
            'commission' => 0.15,
            'image' => $imageName1
        ]);
        
        file_put_contents(public_path('uploads/' . $imageName1), 'fake content');

        $this->actingAs($user);

        // 2. Обновляем айтем новой картинкой
        $imageData2 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $imageName2 = md5(base64_decode(substr($imageData2, strpos($imageData2, ',') + 1))) . '.jpg';

        $response = $this->patch(route('deals.items.update', $item), [
            'count' => 10,
            'price' => 150,
            'image_data' => $imageData2
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deal_items', [
            'id' => $item->id,
            'image' => $imageName2
        ]);

        $this->assertFileExists(public_path('uploads/' . $imageName2));
        $this->assertFileDoesNotExist(public_path('uploads/' . $imageName1));

        // Чистка
        if (file_exists(public_path('uploads/' . $imageName2))) unlink(public_path('uploads/' . $imageName2));
    }

    public function test_can_remove_deal_item_image()
    {
        $user = User::factory()->create();
        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
        
        $imageName = 'test_image.jpg';
        $item = DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => 'buy',
            'ticker' => 'AAPL',
            'count' => 10,
            'price' => 150,
            'total' => 1500,
            'commission' => 0.15,
            'image' => $imageName
        ]);
        
        file_put_contents(public_path('uploads/' . $imageName), 'fake content');

        $this->actingAs($user);

        $response = $this->patch(route('deals.items.update', $item), [
            'count' => 10,
            'price' => 150,
            'remove_image' => '1'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deal_items', [
            'id' => $item->id,
            'image' => null
        ]);

        $this->assertFileDoesNotExist(public_path('uploads/' . $imageName));
    }

    public function test_deleting_item_removes_image_file()
    {
        $user = User::factory()->create();
        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
        
        $imageName = 'delete_test.jpg';
        $item = DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => 'buy',
            'ticker' => 'AAPL',
            'count' => 10,
            'price' => 150,
            'total' => 1500,
            'commission' => 0.15,
            'image' => $imageName
        ]);
        
        file_put_contents(public_path('uploads/' . $imageName), 'fake content');

        $this->actingAs($user);

        $response = $this->delete(route('deals.items.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('deal_items', ['id' => $item->id]);
        $this->assertFileDoesNotExist(public_path('uploads/' . $imageName));
    }

    public function test_deleting_deal_removes_all_item_image_files()
    {
        $user = User::factory()->create();
        $deal = Deal::create(['user_id' => $user->id, 'ticker' => 'AAPL']);
        
        $imageName1 = 'deal_delete_test1.jpg';
        $imageName2 = 'deal_delete_test2.jpg';
        
        DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => 'buy',
            'ticker' => 'AAPL',
            'count' => 10,
            'price' => 150,
            'total' => 1500,
            'commission' => 0.15,
            'image' => $imageName1
        ]);
        
        DealItem::create([
            'user_id' => $user->id,
            'deal_id' => $deal->id,
            'type' => 'sell',
            'ticker' => 'AAPL',
            'count' => 5,
            'price' => 160,
            'total' => 800,
            'commission' => 0.08,
            'image' => $imageName2
        ]);
        
        file_put_contents(public_path('uploads/' . $imageName1), 'fake content');
        file_put_contents(public_path('uploads/' . $imageName2), 'fake content');

        $this->actingAs($user);

        $response = $this->delete(route('deals.destroy', $deal));

        $response->assertRedirect();
        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
        $this->assertDatabaseMissing('deal_items', ['deal_id' => $deal->id]);
        
        $this->assertFileDoesNotExist(public_path('uploads/' . $imageName1));
        $this->assertFileDoesNotExist(public_path('uploads/' . $imageName2));
    }
}
