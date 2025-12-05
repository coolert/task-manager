<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Factory::create()->seed(20250920);

        $profile = config('seeding.profile', 'demo');

        match ($profile) {
            'mini'  => $this->call(MiniSeeder::class),
            default => $this->call(DemoSeeder::class),
        };
    }
}
