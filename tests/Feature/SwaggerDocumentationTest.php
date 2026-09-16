<?php

it('serves the swagger ui when documentation is enabled', function () {
    config(['l5-swagger.enabled' => true]);

    $this->get('/api/documentation')->assertSuccessful();
});

it('serves the generated openapi json when documentation is enabled', function () {
    config([
        'l5-swagger.enabled' => true,
        'l5-swagger.defaults.generate_always' => true,
    ]);

    $response = $this->get('/docs');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/json');

    $payload = $response->json();

    expect($payload)->toHaveKeys(['openapi', 'info', 'paths'])
        ->and($payload['info']['title'])->toContain('API')
        ->and($payload['paths'])->toHaveKeys([
            '/api/v1/auth/register',
            '/api/v1/auth/login',
            '/api/v1/auth/refresh',
            '/api/v1/auth/logout',
            '/api/v1/auth/user',
            '/api/v1/user/profile',
            '/api/v1/user/kyc',
            '/api/v1/wallet/deposit',
            '/api/v1/wallet/withdraw',
            '/api/v1/wallet/transfer',
        ]);
});

it('hides swagger routes when documentation is disabled', function () {
    config(['l5-swagger.enabled' => false]);

    $this->get('/api/documentation')->assertNotFound();
    $this->get('/docs')->assertNotFound();
});
