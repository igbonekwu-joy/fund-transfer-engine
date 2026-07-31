<?php

namespace App\Services\Kyc\Contracts;

use App\Models\User;
use App\Services\Kyc\VerificationResult;

interface BvnVerifierInterface
{
    public function verify(string $bvn, User $user): VerificationResult;
}
