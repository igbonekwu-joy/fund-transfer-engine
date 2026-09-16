<?php

use App\Models\User;
use Illuminate\Support\Str;

/**
 * @return array<string, string>
 */
function walletRequestServer(string $csrf): array
{
    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_X_XSRF_TOKEN' => $csrf,
    ];
}

/**
 * @param  array<string, mixed>  $data
 */
function walletJsonBody(array $data): string
{
    return json_encode($data, JSON_THROW_ON_ERROR);
}

/**
 * @param  array<string, mixed>  $payload
 */
function postWallet(mixed $testCase, User $user, string $uri, array $payload): mixed
{
    $csrf = Str::random(40);

    return $testCase->actingAs($user, 'sanctum')
        ->call(
            'POST',
            $uri,
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            walletRequestServer($csrf),
            walletJsonBody($payload),
        );
}
