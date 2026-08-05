<?php

namespace Database\Seeders;

use App\Support\Hosting\HostingStockService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(HostingStockService::class)->syncFromConfig();
    }
}
