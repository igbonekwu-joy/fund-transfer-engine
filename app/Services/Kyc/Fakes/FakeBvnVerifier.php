<?php

namespace App\Services\Kyc\Fakes;

use App\Models\User;
use App\Services\Kyc\Contracts\BvnVerifierInterface;
use App\Services\Kyc\VerificationResult;

class FakeBvnVerifier implements BvnVerifierInterface
{
    public function verify(string $bvn, User $user): VerificationResult
    {
        // Simulate real-world latency
        usleep(random_int(500_000, 1_500_000));

        // Deterministic test hooks: triggers specific outcomes
        // on demand instead of only getting random results.
        if ($bvn === '00000000000') {
            return new VerificationResult(false, 'BVN not found.');
        }

        if ($bvn === '11111111111') {
            return new VerificationResult(false, 'Name mismatch.');
        }

        // Otherwise, any 11-digit BVN passes, and matches a loose real-world shape.
        return new VerificationResult(
            matched: (bool) preg_match('/^\d{11}$/', $bvn),
            raw: ['provider' => 'fake', 'checked_at' => now()->toISOString()],
        );
    }
}
