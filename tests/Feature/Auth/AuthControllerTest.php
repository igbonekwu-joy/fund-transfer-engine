<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function validRegisterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Joy Adeyemi',
        'email' => 'joy@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

function findResponseCookie($response, string $name)
{
    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->getName() === $name) {
            return $cookie;
        }
    }

    return null;
}

it('registers a user and returns a 201 with the expected json shape', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $response->assertCreated();
    $response->assertJsonStructure([
        'message',
        'user' => ['id', 'name', 'email'],
    ]);
    $response->assertJson([
        'message' => 'User registered successfully.',
    ]);
});

it('persists the user in the database with a hashed password', function () {
    $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $this->assertDatabaseHas('users', [
        'email' => 'joy@example.com',
        'name' => 'Joy Adeyemi',
    ]);

    $user = User::firstWhere('email', 'joy@example.com');

    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('creates an access token and a refresh token for the new user', function () {
    $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $user = User::firstWhere('email', 'joy@example.com');

    expect($user->tokens)->toHaveCount(2);

    $accessToken = $user->tokens->firstWhere('name', 'fundTransferAuthToken');
    $refreshToken = $user->tokens->firstWhere('name', 'fundTransferRefreshToken');

    expect($accessToken)->not->toBeNull();
    expect($accessToken->abilities)->toBe(['*']);

    expect($refreshToken)->not->toBeNull();
    expect($refreshToken->abilities)->toBe(['refresh']);
});

it('does not expose the access or refresh token in the response body', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $response->assertJsonMissingPath('access_token');
    $response->assertJsonMissingPath('refresh_token');
});

it('sets the access token as a secure httpOnly cookie', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $response->assertCookie('access_token');

    $cookie = findResponseCookie($response, 'access_token');

    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBe(app()->isProduction());
    expect($cookie->getSameSite())->toBe('strict');
    expect($cookie->getPath())->toBe('/');
});

it('sets the refresh token as a secure httpOnly cookie', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $response->assertCookie('refresh_token');

    $cookie = findResponseCookie($response, 'refresh_token');

    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBe(app()->isProduction());
    expect($cookie->getSameSite())->toBe('strict');
    expect($cookie->getPath())->toBe('/api/v1/auth/refresh');
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'joy@example.com']);

    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload());

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

it('rejects registration when passwords do not match', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload([
        'password_confirmation' => 'mismatch',
    ]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
});

// it('rotates tokens on refresh and rejects replay of the consumed refresh token', function () {
//     $this->disableCookieEncryption();

//     $register = $this->postJson('/api/v1/auth/register', validRegisterPayload());
//     $refreshToken = findResponseCookie($register, 'refresh_token')?->getValue();

//     expect($refreshToken)->not->toBeNull()->not->toBeEmpty();

//     $refresh = $this->withHeader('Cookie', 'refresh_token='.rawurlencode($refreshToken))
//         ->postJson('/api/v1/auth/refresh');

//     $refresh->assertOk();
//     $refresh->assertJson(['message' => 'Token refreshed.']);
//     $refresh->assertCookie('access_token');
//     $refresh->assertCookie('refresh_token');

//     $replacement = findResponseCookie($refresh, 'refresh_token')?->getValue();
//     expect($replacement)->not->toBeNull()->not->toBe($refreshToken);

//     $user = User::firstWhere('email', 'joy@example.com');
//     expect($user->tokens)->toHaveCount(2);
//     expect($user->tokens->firstWhere('name', 'fundTransferRefreshToken'))->not->toBeNull();

//     $replay = $this->withHeader('Cookie', 'refresh_token='.rawurlencode($refreshToken))
//         ->postJson('/api/v1/auth/refresh');

//     $replay->assertUnauthorized();
//     $replay->assertJson(['message' => 'Invalid refresh token.']);
// });
