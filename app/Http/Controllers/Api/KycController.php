<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kyc\Tier1Request;
use App\Services\Kyc\Fakes\FakeBvnVerifier;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    public function __construct(private readonly FakeBvnVerifier $verifier) {}

    public function tier1(Tier1Request $request): JsonResponse
    {
        // bvn verification
        $data = $this->verifier->verify($request->bvn, $request->user());

        return response()->json($data);
    }
}
