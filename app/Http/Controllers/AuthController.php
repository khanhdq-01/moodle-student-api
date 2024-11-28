<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use GuzzleHttp\Psr7\Message;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        $response = $this->authService->login($credentials);

        if ($response['code'] === 200) {
            return response()->json($response);
        }

        return response()->json($response, 422);
    }

    public function logout( Request $request){
        $response = $this->authService->logout();
        if ($response['code'] === 200) {
            return response()->json($response);
        }

        return response()->json($response, 422);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'method' => 'required|in:email,phone1'
        ]);

        $response = $this->authService->forgotPassword($request->username, $request->method);

        return response()->json($response, $response['code']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);
        $response = $this->authService->resetPassword($request->otp, $request->password);

        return response()->json($response, $response['code']);
    }
}

