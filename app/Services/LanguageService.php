<?php

namespace App\Services;

use App\Models\User;

class LanguageService
{
    public function updateLanguage(User $user, string $language): array
    {
        // Kiểm tra ngôn ngữ có hợp lệ không
        if (!User::isValidLanguage($language)) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Ngôn ngữ không được hỗ trợ.',
            ];
        }

        // Cập nhật ngôn ngữ của người dùng
        $user->update(['lang' => $language]);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Cập nhật ngôn ngữ thành công.',
        ];
    }
}
