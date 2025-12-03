<?php

use Illuminate\Support\Facades\Route;

if (app()->environment(['local', 'development', 'testing'])) {
    Route::get('/openapi.yaml', function () {
        return response()->file(base_path('docs/openapi/openapi.yaml'), [
            'Content-Type' => 'application/yaml',
        ]);
    });
}
