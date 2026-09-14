<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('users')
            ->whereNotNull('account_number')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($now): void {
                foreach ($users as $user) {
                    DB::table('accounts')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $user->id,
                        'type' => 'user',
                        'status' => 'active',
                        'currency' => 'NGN',
                        'account_number' => $user->account_number,
                        'slug' => null,
                        'name' => 'Primary wallet',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $accounts = DB::table('accounts')
            ->where('type', 'user')
            ->whereNotNull('user_id')
            ->whereNotNull('account_number')
            ->get(['user_id', 'account_number']);

        foreach ($accounts as $account) {
            DB::table('users')
                ->where('id', $account->user_id)
                ->whereNull('account_number')
                ->update(['account_number' => $account->account_number]);
        }

        DB::table('accounts')->where('type', 'user')->delete();
    }
};
