<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

test('register returns a json response', function () {
    $controller = new AuthController;

    $response = $controller->register(new Request);

    expect($response)->toBeInstanceOf(JsonResponse::class);
});
