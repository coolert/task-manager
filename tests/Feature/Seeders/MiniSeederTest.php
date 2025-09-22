<?php

use Database\Seeders\MiniSeeder;
use Tests\TestCase;

it('mini seeder runs', function () {
    /** @var TestCase $this */
    $this->seed(MiniSeeder::class);

    expect(\App\Models\Project::count())->toBeGreaterThanOrEqual(1)
        ->and(\App\Models\Task::count())->toBeGreaterThanOrEqual(3)
        ->and(\App\Models\Label::count())->toBeGreaterThanOrEqual(2);
});
