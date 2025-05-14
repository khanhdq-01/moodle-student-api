<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\LanguageService;
use App\Http\Requests\LanguageRequest;

class LanguageController extends Controller
{
    protected $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function updateLanguage(LanguageRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => 'Bạn chưa đăng nhập hoặc token không hợp lệ.',
            ], 401);
        }

        $result = $this->languageService->updateLanguage($user, $request->input('lang'));

        return response()->json([
            'code' => $result['code'],
            'message' => $result['message'],
        ], $result['code']);
    }
}
