<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', // Set the specific UUID
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
