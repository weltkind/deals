<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserUpdate extends Command
{
    protected $signature = 'user:update {id} {--email=} {--password=} {--name=}';
    protected $description = 'Update user login (email), password or name by ID';

    public function handle()
    {
        $id = $this->argument('id');
        $user = User::find($id);

        if (!$user) {
            $this->error("User with ID {$id} not found!");
            return 1;
        }

        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        if (!$email && !$password && !$name) {
            $this->warn("No updates provided. Use --email, --password or --name options.");
            return 0;
        }

        if ($email) {
            if (User::where('email', $email)->where('id', '!=', $id)->exists()) {
                $this->error("Email {$email} is already taken by another user!");
                return 1;
            }
            $user->email = $email;
        }

        if ($password) {
            $user->password = Hash::make($password);
        }

        if ($name) {
            $user->name = $name;
        }

        $user->save();

        $this->info("User ID {$id} updated successfully!");
        return 0;
    }
}
