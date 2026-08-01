<?php

namespace App\Services\User;

use App\Http\Requests\User\KycRequest;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KycService
{
    public function submit(User $user, KycRequest $request): array
    {
        $tier = $this->parseTier($request->tier);

        return DB::transaction(function () use ($user, $request, $tier) {
            $verification = $user->kycVerification()->lockForUpdate()->first();

            if (! $verification) {
                $verification = new KycVerification(['user_id' => $user->id]);
            }

            if ($tier === 1) {
                return ['kyc' => $this->submitTier1($verification, $request)];
            }

            if ($tier === 2) {
                return ['kyc' => $this->submitTier2($verification, $request)];
            }

            return ['kyc' => $this->submitTier3($verification, $request)];
        });
    }

    public function getVerification(User $user): array
    {
        $verification = $user->kycVerification;

        if (! $verification) {
            return [
                'tier' => null,
                'status' => 'pending',
                'provider' => null,
                'bvn_verified' => false,
                'nin_verified' => false,
                'verified_at' => null,
                'rejection_reason' => null,
                'provider_response' => null,
            ];
        }

        return $verification->toApiArray();
    }

    private function submitTier1(KycVerification $verification, KycRequest $request): array
    {
        $provider = $request->string('provider')->toString();
        $idNumber = $request->string('id_number')->toString();

        if ($verification->exists && $verification->status === 'approved' && $verification->tier >= 1) {
            if ($verification->provider === $provider) {
                return $verification->toApiArray();
            }

            throw ValidationException::withMessages([
                'provider' => 'Only one Tier 1 verification provider may be approved for a user.',
            ]);
        }

        if ($provider === 'nin' && $verification->bvn_verified) {
            throw ValidationException::withMessages([
                'provider' => 'A BVN verification is already approved. Only one Tier 1 identifier may be verified.',
            ]);
        }

        if ($provider === 'bvn' && $verification->nin_verified) {
            throw ValidationException::withMessages([
                'provider' => 'A NIN verification is already approved. Only one Tier 1 identifier may be verified.',
            ]);
        }

        if (! $this->verifyTier1Id($provider, $idNumber)) {
            $verification->fill([
                'tier' => 1,
                'status' => 'rejected',
                'provider' => $provider,
                'provider_response' => ['provider' => $provider, 'valid' => false],
                'rejection_reason' => 'The provided '.$provider.' number is invalid.',
                'verified_at' => null,
            ]);

            $this->clearTier1Fields($verification, $provider);
            $verification->save();

            throw ValidationException::withMessages([
                'id_number' => 'The provided '.$provider.' number could not be verified.',
            ]);
        }

        $verification->fill([
            'tier' => 1,
            'status' => 'approved',
            'provider' => $provider,
            'provider_response' => ['provider' => $provider, 'valid' => true],
            'rejection_reason' => null,
            'verified_at' => Carbon::now(),
            'bvn_verified' => $provider === 'bvn',
            'nin_verified' => $provider === 'nin',
            'bvn_encrypted' => $provider === 'bvn' ? Crypt::encryptString($idNumber) : null,
            'nin_encrypted' => $provider === 'nin' ? Crypt::encryptString($idNumber) : null,
        ]);

        if ($provider === 'bvn') {
            $verification->nin_encrypted = null;
        } else {
            $verification->bvn_encrypted = null;
        }

        $verification->save();

        return $verification->toApiArray();
    }

    private function submitTier2(KycVerification $verification, KycRequest $request): array
    {
        if (! $verification->exists || $verification->status !== 'approved' || $verification->tier < 1) {
            throw ValidationException::withMessages([
                'tier' => 'Tier 2 verification requires a completed Tier 1 approval first.',
            ]);
        }

        if ($verification->tier >= 2 && $verification->status === 'approved') {
            return $verification->toApiArray();
        }

        $documentType = $request->string('supporting_document_type')->toString();
        $documentValue = $request->string('supporting_document_value')->toString();

        if (! $this->verifyTier2Document($documentType, $documentValue)) {
            $verification->fill([
                'tier' => 2,
                'status' => 'rejected',
                'provider_response' => ['tier2' => false, 'document' => $documentType],
                'rejection_reason' => 'The supporting document could not be verified.',
                'verified_at' => null,
            ]);

            $verification->save();

            throw ValidationException::withMessages([
                'supporting_document_value' => 'The supporting document details could not be verified.',
            ]);
        }

        $verification->fill([
            'tier' => 2,
            'status' => 'approved',
            'provider_response' => ['tier2' => true, 'document' => $documentType],
            'rejection_reason' => null,
            'verified_at' => Carbon::now(),
        ]);

        $verification->save();

        return $verification->toApiArray();
    }

    private function submitTier3(KycVerification $verification, KycRequest $request): array
    {
        if (! $verification->exists || $verification->status !== 'approved' || $verification->tier < 2) {
            throw ValidationException::withMessages([
                'tier' => 'Tier 3 verification requires an approved Tier 2 verification first.',
            ]);
        }

        if ($verification->tier >= 3 && $verification->status === 'approved') {
            return $verification->toApiArray();
        }

        if (! $request->boolean('tier3_consent')) {
            throw ValidationException::withMessages([
                'tier3_consent' => 'Tier 3 consent must be accepted to complete the verification.',
            ]);
        }

        $verification->fill([
            'tier' => 3,
            'status' => 'approved',
            'provider_response' => ['tier3' => true],
            'rejection_reason' => null,
            'verified_at' => Carbon::now(),
        ]);

        $verification->save();

        return $verification->toApiArray();
    }

    private function parseTier(string $tier): int
    {
        return (int) str_replace('tier', '', $tier);
    }

    private function verifyTier1Id(string $provider, string $idNumber): bool
    {
        if (! preg_match('/^\d{11}$/', $idNumber)) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $idNumber)) {
            return false;
        }

        if ($provider === 'nin') {
            return $this->validateTier1Checksum($idNumber);
        }

        return $this->validateTier1Checksum($idNumber, 7);
    }

    private function validateTier1Checksum(string $idNumber, int $seed = 10): bool
    {
        $digits = array_map('intval', str_split($idNumber));
        $checksum = array_sum($digits) % $seed;

        return $checksum !== 0;
    }

    private function verifyTier2Document(string $documentType, string $documentValue): bool
    {
        return strlen(trim($documentValue)) >= 6 && in_array($documentType, ['passport', 'utility_bill', 'bank_statement'], true);
    }

    private function clearTier1Fields(KycVerification $verification, string $provider): void
    {
        if ($provider === 'bvn') {
            $verification->nin_verified = false;
            $verification->nin_encrypted = null;
        } else {
            $verification->bvn_verified = false;
            $verification->bvn_encrypted = null;
        }
    }
}
