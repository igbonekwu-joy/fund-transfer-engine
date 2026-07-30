<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ProfileRequest;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile) {}

    public function store (ProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->profile->update($user, $request);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->toApiArray(),
        ]);
    }
}
