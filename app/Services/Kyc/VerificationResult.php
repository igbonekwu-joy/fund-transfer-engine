<?php

namespace App\Services\Kyc;

class VerificationResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public readonly bool $matched,
        public readonly ?string $reason = null,
        public readonly array $raw = [],
    ) {}
}
