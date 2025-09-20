<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        fake()->seed(20250920);

        $profile = config('seeding.profile', 'demo');

        match ($profile) {
            'mini'  => $this->call(MiniSeeder::class),
            default => $this->call(DemoSeeder::class),
        };
    }
}
