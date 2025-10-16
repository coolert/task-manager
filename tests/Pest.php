<?php

require_once __DIR__ . '/Support/Api.php';

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class)->in('Feature', 'Unit');
uses(RefreshDatabase::class)->in('Feature');

/**
 * @return array{
 *   project: Project,
 *   owner: User,
 *   admin: User,
 *   member: User,
 *   viewer: User,
 *   outsider: User }
 */
function setupProjectWithRoles(): array
{
    $owner    = User::factory()->create();
    $admin    = User::factory()->create();
    $member   = User::factory()->create();
    $viewer   = User::factory()->create();
    $outsider = User::factory()->create();
    $project  = Project::factory()->for($owner, 'owner')->create();

    ProjectMember::factory()->for($project)->for($admin)->create(['role' => ProjectRole::Admin]);
    ProjectMember::factory()->for($project)->for($member)->create(['role' => ProjectRole::Member]);
    ProjectMember::factory()->for($project)->for($viewer)->create(['role' => ProjectRole::Viewer]);

    return compact('project', 'owner', 'admin', 'member', 'viewer', 'outsider');
}
