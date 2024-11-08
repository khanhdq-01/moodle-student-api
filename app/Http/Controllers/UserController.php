<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Models\MoodleUser;

class UserController extends Controller
{
    protected $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function createUsers(Request $request)
    {

        $request->validate([
        'users' => 'required|array',
        'users.*.username' => 'required|string',
        'users.*.firstname' => 'required|string',
        'users.*.lastname' => 'required|string',
        'users.*.email' => 'required|email',
        'users.*.auth' => 'nullable|string',
        'users.*.password' => 'nullable|string',
        'users.*.maildisplay' => 'nullable|integer',
        'users.*.city' => 'nullable|string',
        'users.*.country' => 'nullable|string',
        'users.*.timezone' => 'nullable|string',
        'users.*.description' => 'nullable|string',
        'users.*.phone1' => 'nullable|string',
        'users.*.role' => 'required|in:student,teacher',
        ]);
   
        $users = $request->input('users');
        
        $userIds = $this->userService->createUsers($users);
        
        return response()->json([
            'code' => 200,
            'message' => 'Đăng ký thành công',
        ], 200);
    }

    public function index()
    {
        return response()->json([
            'data' => MoodleUser::all(),
        ]);
    }
}
