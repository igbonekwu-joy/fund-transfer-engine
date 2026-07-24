<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('debug refresh', function () {
    $this->disableCookieEncryption();

    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Joy',
        'email' => 'joy@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $token = null;
    foreach ($register->headers->getCookies() as $cookie) {
        if ($cookie->getName() === 'refresh_token') {
            $token = $cookie->getValue();
        }
    }

    expect($token)->not->toBeNull();

    $refresh = $this->call(
        'POST',
        '/api/v1/auth/refresh',
        cookies: ['refresh_token' => $token],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{}',
    );

    expect($refresh->getStatusCode())->toBe(200);
    expect($refresh->json('message'))->toBe('Token refreshed.');
});
