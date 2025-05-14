<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->middleware('auth:sanctum');
        $this->profileService = $profileService;
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $result = $this->profileService->updateProfile($user, $request->only([
            'firstname', 'lastname', 'email', 'phone1', 'city', 'country',
        ]));

        return response()->json([
            'code' => 200,
            'message' => $result['message'],
        ]);
    }
}