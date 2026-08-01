<?php

use App\Models\User;
use Illuminate\Support\Str;

function kycRequestServer(string $csrf): array
{
    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_X_XSRF_TOKEN' => $csrf,
    ];
}

function kycRequestBody(array $data): string
{
    return json_encode($data);
}

it('allows a user to submit a tier1 nin verification successfully', function () {
    $user = User::factory()->create();

    $csrf = Str::random(40);

    $response = $this->actingAs($user, 'sanctum')
        ->call(
            'POST',
            '/api/v1/user/kyc',
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            kycRequestServer($csrf),
            kycRequestBody([
                'tier' => 'tier1',
                'provider' => 'nin',
                'id_number' => '12345678901',
            ]),
        );

    $response->assertOk();
    $response->assertJsonPath('kyc.status', 'approved');
    $response->assertJsonPath('kyc.provider', 'nin');
    $response->assertJsonPath('kyc.nin_verified', true);
    $response->assertJsonPath('kyc.bvn_verified', false);
});

it('rejects tier1 submission when the opposite tier1 provider is already verified', function () {
    $user = User::factory()->create();
    $user->kycVerification()->create([
        'tier' => 1,
        'status' => 'approved',
        'provider' => 'nin',
        'provider_response' => ['provider' => 'nin', 'valid' => true],
        'nin_verified' => true,
        'nin_encrypted' => encrypt('12345678901'),
        'verified_at' => now(),
    ]);

    $csrf = Str::random(40);

    $response = $this->actingAs($user, 'sanctum')
        ->call(
            'POST',
            '/api/v1/user/kyc',
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            kycRequestServer($csrf),
            kycRequestBody([
                'tier' => 'tier1',
                'provider' => 'bvn',
                'id_number' => '12345678901',
            ]),
        );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('provider');
});

it('requires tier1 approval before submitting tier2', function () {
    $user = User::factory()->create();

    $csrf = Str::random(40);

    $response = $this->actingAs($user, 'sanctum')
        ->call(
            'POST',
            '/api/v1/user/kyc',
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            kycRequestServer($csrf),
            kycRequestBody([
                'tier' => 'tier2',
                'supporting_document_type' => 'passport',
                'supporting_document_value' => 'A1234567',
            ]),
        );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('tier');
});

it('requires tier2 approval before submitting tier3', function () {
    $user = User::factory()->create();
    $user->kycVerification()->create([
        'tier' => 1,
        'status' => 'approved',
        'provider' => 'nin',
        'provider_response' => ['provider' => 'nin', 'valid' => true],
        'nin_verified' => true,
        'nin_encrypted' => encrypt('12345678901'),
        'verified_at' => now(),
    ]);

    $csrf = Str::random(40);

    $response = $this->actingAs($user, 'sanctum')
        ->call(
            'POST',
            '/api/v1/user/kyc',
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            kycRequestServer($csrf),
            kycRequestBody([
                'tier' => 'tier3',
                'tier3_consent' => true,
            ]),
        );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('tier');
});

it('allows a user with tier2 approval to submit tier3 consent', function () {
    $user = User::factory()->create();
    $user->kycVerification()->create([
        'tier' => 2,
        'status' => 'approved',
        'provider' => 'nin',
        'provider_response' => ['tier2' => true, 'document' => 'utility_bill'],
        'nin_verified' => true,
        'nin_encrypted' => encrypt('12345678901'),
        'verified_at' => now(),
    ]);

    $csrf = Str::random(40);

    $response = $this->actingAs($user, 'sanctum')
        ->call(
            'POST',
            '/api/v1/user/kyc',
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            kycRequestServer($csrf),
            kycRequestBody([
                'tier' => 'tier3',
                'tier3_consent' => true,
            ]),
        );

    $response->assertOk();
    $response->assertJsonPath('kyc.status', 'approved');
    $response->assertJsonPath('kyc.tier', 'tier3');
});
