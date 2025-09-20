<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\BuildProject;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    use BuildProject;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::factory()
            ->create([
                'name'       => 'Admin Owner',
                'avatar_url' => 'https://api.dicebear.com/6.x/micah/jpg?seed=Admin',
            ]);

        for ($i = 0; $i < 3; $i++) {
            $this->buildProject([
                'owner'           => $owner,
                'member_count'    => fake()->numberBetween(3, 5),
                'labels_count'    => fake()->numberBetween(5, 7),
                'tasks_count'     => 20,
                'task_order_step' => 10,
                'assignee_ratio'  => 60,
            ]);
        }
    }
}
