<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $org = Organization::query()->create([
            'name' => 'Demo Org',
            'slug' => 'demo-org',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'organization_id' => $org->id,
            'password' => bcrypt('password-password'),
        ]);
    }
}
