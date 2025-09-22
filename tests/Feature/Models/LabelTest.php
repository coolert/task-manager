<?php

use App\Models\Label;
use App\Models\Project;
use Illuminate\Database\QueryException;

it('label relations work', function () {
    $project = Project::factory()->create();

    $label = Label::factory()->for($project)->create(['name' => 'Bug']);
    expect($label->project->is($project))->toBeTrue();

    $fn = fn () => Label::factory()->for($project)->create(['name' => 'Bug']);
    expect($fn)->toThrow(QueryException::class);
});
