<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\BuildProject;
use Illuminate\Database\Seeder;

class MiniSeeder extends Seeder
{
    use BuildProject;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::factory()
            ->create([
                'name'       => 'Mini Owner',
                'email'      => 'owner@example.com',
                'avatar_url' => 'https://api.dicebear.com/6.x/micah/jpg?seed=Admin',
            ]);

        $this->buildProject([
            'owner'           => $owner,
            'member_count'    => 1,
            'labels_count'    => 2,
            'tasks_count'     => 3,
            'task_order_step' => 10,
            'assignee_ratio'  => 60,
        ]);
    }
}
