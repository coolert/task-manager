<?php

/** @var TestCase $this */

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

use Tests\TestCase;

beforeEach(function () {
    Route::middleware(['web', 'project.member'])
        ->get('/_t/project/{project}/ping', fn (Project $project) => response()->json(['ok' => true]));
});

it('non-bind project (404)', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/_t/project/999999/ping')
        ->assertNotFound();
});

it('blocks non-member (403)', function () {
    $owner    = User::factory()->create();
    $outsider = User::factory()->create();
    $project  = Project::factory()->for($owner, 'owner')->create();

    actingAs($outsider)
        ->getJson("/_t/project/{$project->id}/ping")
        ->assertForbidden();
});

it('allow member (200)', function () {
    $owner   = User::factory()->create();
    $member  = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    ProjectMember::factory()
        ->for($project)
        ->for($member)
        ->create([
            'role' => ProjectRole::Member,
        ]);

    actingAs($member)
        ->getJson("/_t/project/{$project->id}/ping")
        ->assertOk()
        ->assertJson(['ok' => true]);
});
