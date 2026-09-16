<?php

use App\Enums\LedgerEntryDirection;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Support\Ledger\BalancedLedgerWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ConcurrencyTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(ConcurrencyTestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Concurrency');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

require __DIR__.'/Support/concurrency.php';
require __DIR__.'/Support/wallet_http.php';

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Credit a user wallet via the money_in system boundary.
 */
function fundAccount(Account $wallet, int $amount): void
{
    $moneyIn = Account::moneyIn();

    app(BalancedLedgerWriter::class)->post(
        TransactionType::Deposit,
        [
            [
                'account_id' => $moneyIn->id,
                'direction' => LedgerEntryDirection::Debit,
                'amount' => $amount,
                'currency' => $wallet->currency,
            ],
            [
                'account_id' => $wallet->id,
                'direction' => LedgerEntryDirection::Credit,
                'amount' => $amount,
                'currency' => $wallet->currency,
            ],
        ],
    );
}
