<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\KycRequest;
use App\Services\User\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function __construct(private readonly KycService $kyc) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'kyc' => $this->kyc->getVerification($request->user()),
        ]);
    }

    public function store(KycRequest $request): JsonResponse
    {
        $result = $this->kyc->submit($request->user(), $request);

        return response()->json([
            'message' => 'KYC verification submitted successfully.',
            'kyc' => $result['kyc'],
        ]);
    }
}
