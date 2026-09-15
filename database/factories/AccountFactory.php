<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AccountType::User,
            'status' => AccountStatus::Active,
            'currency' => 'NGN',
            'account_number' => fake()->unique()->numerify('##########'),
            'slug' => null,
            'name' => 'Primary wallet',
        ];
    }

    public function systemInbound(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'type' => AccountType::SystemInbound,
            'account_number' => null,
            'slug' => Account::MONEY_IN_SLUG,
            'name' => 'Money in',
        ]);
    }

    public function withoutAccountNumber(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_number' => null,
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AccountStatus::Frozen,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AccountStatus::Closed,
        ]);
    }

    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency' => $currency,
        ]);
    }
}
