<?php

use App\Exceptions\Auth\UnauthenticatedException;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

const TEST_ORIGIN = 'http://localhost';

beforeEach(function () {
    // login/register are CSRF-exempt but Origin-checked; keep this in sync
    // with whatever VerifyXsrfToken reads from config('cors.allowed_origins').
    config(['cors.allowed_origins' => [TEST_ORIGIN]]);
});

function validRegisterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Joy Adeyemi',
        'email' => 'joy@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

function withOrigin(array $headers = []): array
{
    return array_merge(['Origin' => TEST_ORIGIN], $headers);
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
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

    $response->assertCreated();
    $response->assertJsonStructure([
        'message',
        'user' => ['name', 'email'],
    ]);
    $response->assertJson([
        'message' => 'User registered successfully.',
    ]);
});

it('persists the user in the database with a hashed password', function () {
    $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

    $this->assertDatabaseHas('users', [
        'email' => 'joy@example.com',
        'name' => 'Joy Adeyemi',
    ]);

    $user = User::firstWhere('email', 'joy@example.com');

    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('creates an access token and a refresh token for the new user', function () {
    $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

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
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

    $response->assertJsonMissingPath('access_token');
    $response->assertJsonMissingPath('refresh_token');
});

it('sets the access token as a secure httpOnly cookie', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

    $response->assertCookie('access_token');

    $cookie = findResponseCookie($response, 'access_token');

    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBe(true);
    expect($cookie->getSameSite())->toBe('none');
    expect($cookie->getPath())->toBe('/');
});

it('sets the refresh token as a secure httpOnly cookie', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

    $response->assertCookie('refresh_token');

    $cookie = findResponseCookie($response, 'refresh_token');

    expect($cookie)->not->toBeNull();
    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBe(true);
    expect($cookie->getSameSite())->toBe('none');
    expect($cookie->getPath())->toBe('/api/v1/auth/refresh');
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'joy@example.com']);

    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

it('rejects registration when passwords do not match', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload([
        'password_confirmation' => 'mismatch',
    ]), withOrigin());

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
});

it('rejects registration when the Origin header is missing or not allowed', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegisterPayload(), [
        'Origin' => 'https://attacker.example',
    ]);

    $response->assertStatus(419);
});

it('rejects a refresh token that has already been consumed', function () {
    $user = User::factory()->create();
    $refreshToken = $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken;

    $authService = app(AuthService::class);

    $result = $authService->refresh($refreshToken);

    expect($result)->toHaveKeys(['user', 'access_token', 'refresh_token']);

    // Second attempt with the SAME original token fails
    // it was already deleted or rotated by the first call.
    expect(fn () => $authService->refresh($refreshToken))
        ->toThrow(UnauthenticatedException::class, 'Invalid refresh token.');
});

it('rotates tokens on refresh and rejects replay of the consumed refresh token', function () {
    $this->disableCookieEncryption();

    $register = $this->postJson('/api/v1/auth/register', validRegisterPayload(), withOrigin());
    $refreshToken = findResponseCookie($register, 'refresh_token')?->getValue();
    $csrfToken = findResponseCookie($register, 'XSRF-TOKEN')?->getValue();

    expect($refreshToken)->not->toBeNull()->not->toBeEmpty();
    expect($csrfToken)->not->toBeNull()->not->toBeEmpty();

    $refresh = $this->call(
        'POST',
        '/api/v1/auth/refresh',
        cookies: [
            'refresh_token' => $refreshToken,
            'XSRF-TOKEN' => $csrfToken,
        ],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_XSRF_TOKEN' => $csrfToken,
        ],
        content: '{}',
    );

    $refresh->assertOk();
    $newCsrfToken = findResponseCookie($refresh, 'XSRF-TOKEN')?->getValue();

    $replay = $this->call(
        'POST',
        '/api/v1/auth/refresh',
        cookies: [
            'refresh_token' => $refreshToken,
            'XSRF-TOKEN' => $csrfToken,
        ],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_XSRF_TOKEN' => $csrfToken,
        ],
        content: '{}',
    );

    $replay->assertUnauthorized();
    $replay->assertJson(['message' => 'Invalid refresh token.']);
});

it('never allows more than maxAttempts concurrent logins through the sliding window', function () {
    $ipMax = 10;
    $emailMax = 3;
    $processCount = 10;
    $uniqueKey = 'race-test-'.bin2hex(random_bytes(6)).'-'.now()->timestamp;

    $processes = [];

    for ($i = 0; $i < $processCount; $i++) {
        $process = new Process([
            'php', base_path('artisan'), 'probe:rate-limit', $uniqueKey, (string) $ipMax, (string) $emailMax, '60',
        ]);
        $process->start();
        $processes[] = $process;
    }

    foreach ($processes as $process) {
        $process->wait();
    }

    foreach ($processes as $index => $process) {
        expect($process->isSuccessful())->toBeTrue(
            "Probe process #{$index} failed with exit code {$process->getExitCode()}.\n"
            ."STDOUT: {$process->getOutput()}\n"
            ."STDERR: {$process->getErrorOutput()}"
        );
    }

    $allowedCount = collect($processes)
        ->filter(fn (Process $p) => trim($p->getOutput()) === 'ALLOWED')
        ->count();

    $blockedCount = collect($processes)
        ->filter(fn (Process $p) => trim($p->getOutput()) === 'BLOCKED')
        ->count();

    expect($allowedCount)->toBe($emailMax);
    expect($blockedCount)->toBe($processCount - $emailMax);
});

it('does not throw a TypeError when email is submitted as an array', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => ['a@example.com', 'b@example.com'],
        'password' => 'password123',
    ], withOrigin());

    $response->assertStatus(422);
    $response->assertJsonMissing(['message' => 'Server Error']);
});
