<?php

use Illuminate\Support\Facades\Schema;

it('has all core tables', function () {
    $tables = [
        'users',
        'projects',
        'project_members',
        'labels',
        'tasks',
        'task_labels',
        'task_comments',
    ];
    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});
