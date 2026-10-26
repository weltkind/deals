<?php

use App\Models\User;

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
