<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\LedgerEntryDirection;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $user_id
 * @property AccountType $type
 * @property AccountStatus $status
 * @property string $currency
 * @property string|null $account_number
 * @property string|null $slug
 * @property string|null $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'type', 'status', 'currency', 'account_number', 'slug', 'name'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasUuids;

    public const string MONEY_IN_SLUG = 'money_in';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'status' => AccountStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeSystemInbound(Builder $query): Builder
    {
        return $query->where('type', AccountType::SystemInbound);
    }

    public static function moneyIn(): self
    {
        return static::query()
            ->where('slug', self::MONEY_IN_SLUG)
            ->firstOrFail();
    }

    /**
     * Balance in minor units (kobo).
     *
     * User wallets (liability): credits increase balance.
     * System inbound (asset boundary): debits increase balance.
     */
    public function balanceInMinorUnits(): int
    {
        return $this->calculateBalanceInMinorUnits(forUpdate: false);
    }

    /**
     * Current balance under row locks — use inside DB::transaction after locking the account.
     *
     * Locking reads avoid REPEATABLE READ snapshots that can miss just-committed ledger rows
     * from a concurrent transfer or withdrawal.
     */
    public function balanceInMinorUnitsForUpdate(): int
    {
        return $this->calculateBalanceInMinorUnits(forUpdate: true);
    }

    private function calculateBalanceInMinorUnits(bool $forUpdate): int
    {
        $creditsQuery = $this->ledgerEntries()
            ->where('direction', LedgerEntryDirection::Credit);

        $debitsQuery = $this->ledgerEntries()
            ->where('direction', LedgerEntryDirection::Debit);

        if ($forUpdate) {
            $creditsQuery->lockForUpdate();
            $debitsQuery->lockForUpdate();
        }

        $credits = (int) $creditsQuery->sum('amount');
        $debits = (int) $debitsQuery->sum('amount');

        return match ($this->type) {
            AccountType::User => $credits - $debits,
            AccountType::SystemInbound => $debits - $credits,
        };
    }
}
