<?php

use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

it('returns ok for health check', function () {
    $response = getJson('/api/health');

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json->where('status', 'ok')
            ->has('timestamp')
        );
});
