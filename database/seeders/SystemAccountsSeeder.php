<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class SystemAccountsSeeder extends Seeder
{
    /**
     * Seed system ledger accounts that represent money boundaries.
     */
    public function run(): void
    {
        Account::query()->updateOrCreate(
            ['slug' => Account::MONEY_IN_SLUG],
            [
                'user_id' => null,
                'type' => AccountType::SystemInbound,
                'status' => AccountStatus::Active,
                'currency' => 'NGN',
                'account_number' => null,
                'name' => 'Money in',
            ],
        );
    }
}
