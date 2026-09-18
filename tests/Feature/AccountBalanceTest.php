<?php

use App\Models\Account;
use App\Models\User;
use App\Services\Ledger\FundingService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

it('requires authentication to read a balance', function () {
    $wallet = Account::factory()->create();

    $this->getJson("/api/v1/accounts/{$wallet->id}/balance")
        ->assertUnauthorized();
});

it('returns the balance for an owned user wallet', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 3_250_00);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/accounts/{$wallet->id}/balance");

    $response->assertOk()
        ->assertExactJson([
            'account_id' => $wallet->id,
            'currency' => 'NGN',
            'balance' => 3_250_00,
        ]);
});

it('returns zero balance for an owned wallet with no ledger activity', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/accounts/{$wallet->id}/balance")
        ->assertOk()
        ->assertJsonPath('balance', 0);
});

it('returns 404 when reading another users wallet balance', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $wallet = Account::factory()->for($owner)->create();

    app(FundingService::class)->deposit($wallet, 1_000_00);

    $this->actingAs($other, 'sanctum')
        ->getJson("/api/v1/accounts/{$wallet->id}/balance")
        ->assertNotFound()
        ->assertJsonPath('message', 'Account not found.');
});

it('returns 404 when reading the money_in system account balance', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();
    $moneyIn = Account::moneyIn();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/accounts/{$moneyIn->id}/balance")
        ->assertNotFound()
        ->assertJsonPath('message', 'Account not found.');
});

it('returns 404 for an unknown account id', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/accounts/00000000-0000-0000-0000-000000000000/balance')
        ->assertNotFound()
        ->assertJsonPath('message', 'Account not found.');
});
