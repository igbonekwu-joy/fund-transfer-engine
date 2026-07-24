<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rotates the refresh token on refresh and rejects replay of the consumed token', function () {
    $this->disableCookieEncryption();

    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Joy',
        'email' => 'joy@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $originalToken = null;
    foreach ($register->headers->getCookies() as $cookie) {
        if ($cookie->getName() === 'refresh_token') {
            $originalToken = $cookie->getValue();
        }
    }

    expect($originalToken)->not->toBeNull();

    $refresh = $this->call(
        'POST',
        '/api/v1/auth/refresh',
        cookies: ['refresh_token' => $originalToken],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{}',
    );

    expect($refresh->getStatusCode())->toBe(200);
    expect($refresh->json('message'))->toBe('Token refreshed.');

    $newToken = null;
    foreach ($refresh->headers->getCookies() as $cookie) {
        if ($cookie->getName() === 'refresh_token') {
            $newToken = $cookie->getValue();
        }
    }

    expect($newToken)->not->toBeNull();
    expect($newToken)->not->toBe($originalToken);

    $replay = $this->call(
        'POST',
        '/api/v1/auth/refresh',
        cookies: ['refresh_token' => $originalToken],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{}',
    );

    expect($replay->getStatusCode())->toBe(401);
    expect($replay->json('message'))->toBe('Invalid refresh token.');
});
