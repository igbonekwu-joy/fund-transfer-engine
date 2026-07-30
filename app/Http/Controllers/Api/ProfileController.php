<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function store (ProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'name' => $request->fullName,
            'phone' => $request->mobile,
            'gender' => $request->gender ?? 'Male',
            'address' => $request->address,
            'dob' => $request->dob
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->toApiArray(),
        ]);
    }
}
