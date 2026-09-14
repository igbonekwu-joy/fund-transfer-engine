<?php

namespace Database\Factories;

use App\Enums\LedgerEntryDirection;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'account_id' => Account::factory(),
            'direction' => LedgerEntryDirection::Debit,
            'amount' => 100_00,
            'currency' => 'NGN',
        ];
    }
}
