<?php

use App\MessagePipeline\Consumer\ConsumerRegistry;

beforeEach(function () {
    ConsumerRegistry::clear();
});

afterEach(function () {
    ConsumerRegistry::clear();
});

// patternToRegex()
it('converts exact patterns to exact-match regex', function () {
    $regex = ConsumerRegistry::patternToRegex('task.created');

    expect('task.created')->toMatch($regex)
        ->and('task.updated')->not->toMatch($regex);
});

it('converts * wildcard to single segment regex', function () {
    $regex = ConsumerRegistry::patternToRegex('task.*');

    expect('task.created')->toMatch($regex)
        ->and('task.updated')->toMatch($regex)
        ->and('task.a.b')->not->toMatch($regex);
});

it('converts # wildcard to multi segment regex', function () {
    $regex = ConsumerRegistry::patternToRegex('task.#');

    expect('task.created')->toMatch($regex)
        ->and('task.a.b')->toMatch($regex)
        ->and('task')->not->toMatch($regex);
});

it('supports wildcard at beginning of routing key', function () {
    $regex = ConsumerRegistry::patternToRegex('*.created');

    expect('task.created')->toMatch($regex)
        ->and('order.created')->toMatch($regex)
        ->and('task.a.created')->not->toMatch($regex);
});

// resolve()
it('resolves exact match before any wildcard', function () {
    ConsumerRegistry::register('task.*', 'WildcardHandler');
    ConsumerRegistry::register('task.created', 'ExactHandler');

    $handler = ConsumerRegistry::resolve('task.created');

    expect($handler)->toBe('ExactHandler');
});

it('resolves * wildcard before # wildcard', function () {
    ConsumerRegistry::register('task.#', 'MultiWildcardHandler');
    ConsumerRegistry::register('task.*', 'SingleWildcardHandler');

    $handler = ConsumerRegistry::resolve('task.updated');

    expect($handler)->toBe('SingleWildcardHandler');
});

it('resolves # wildcard when no exact or * match exists', function () {
    ConsumerRegistry::register('task.#', 'MultiWildcardHandler');

    $handler = ConsumerRegistry::resolve('task.any.event');

    expect($handler)->toBe('MultiWildcardHandler');
});

it('returns null when no matching pattern exists', function () {
    ConsumerRegistry::register('order.created', 'OrderHandler');

    $handler = ConsumerRegistry::resolve('task.created');

    expect($handler)->toBeNull();
});

// register() and all()
it('register() stores handlers and all() returns them', function () {
    ConsumerRegistry::register('task.created', 'CreatedHandler');
    ConsumerRegistry::register('task.updated', 'UpdatedHandler');

    $all = ConsumerRegistry::all();

    expect($all)->toBe([
        'task.created' => 'CreatedHandler',
        'task.updated' => 'UpdatedHandler',
    ]);
});

// clear()
it('clear() resets registry', function () {
    ConsumerRegistry::register('task.created', 'CreatedHandler');

    ConsumerRegistry::clear();

    expect(ConsumerRegistry::all())->toBe([]);
});
