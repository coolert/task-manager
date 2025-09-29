<?php

use Illuminate\Support\Facades\Gate;

it('project view: denies outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();

    expect(Gate::forUser($outsider)->denies('view', $project))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $project))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $project))->toBeTrue()
        ->and(Gate::forUser($member)->allows('view', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('view', $project))->toBeTrue();

});

it('project update: allows owner/admin, denies member/viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();

    expect(Gate::forUser($owner)->allows('update', $project))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $project))->toBeTrue()
        ->and(Gate::forUser($member)->denies('update', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('update', $project))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('update', $project))->toBeTrue();
});

it('project delete: only owner', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();

    expect(Gate::forUser($owner)->allows('delete', $project))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('delete', $project))->toBeTrue()
        ->and(Gate::forUser($member)->denies('delete', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('delete', $project))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('delete', $project))->toBeTrue();
});

it('project manageMembers: allows owner/admin, denies member/viewer/outsider', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();

    expect(Gate::forUser($owner)->allows('manageMembers', $project))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageMembers', $project))->toBeTrue()
        ->and(Gate::forUser($member)->denies('manageMembers', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('manageMembers', $project))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('manageMembers', $project))->toBeTrue();
});

it('project transferOwnership: only owner', function () {
    ['project' => $project, 'owner' => $owner, 'admin' => $admin, 'member' => $member, 'viewer' => $viewer, 'outsider' => $outsider] = setupProjectWithRoles();

    expect(Gate::forUser($owner)->allows('transferOwnership', $project))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('transferOwnership', $project))->toBeTrue()
        ->and(Gate::forUser($member)->denies('transferOwnership', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->denies('transferOwnership', $project))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('transferOwnership', $project))->toBeTrue();
});
