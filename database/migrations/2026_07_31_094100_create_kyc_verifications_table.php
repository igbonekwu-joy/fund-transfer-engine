<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tier')->default(1);
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected'])->default('pending');

            // Tier 1
            $table->string('bvn_encrypted')->nullable();
            $table->string('nin_encrypted')->nullable();
            $table->boolean('bvn_verified')->default(false);
            $table->boolean('nin_verified')->default(false);

            $table->string('provider')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
