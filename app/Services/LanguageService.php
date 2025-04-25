<?php

namespace App\Services;

use App\Models\User;
use App\Interfaces\LanguageRepositoryInterface;

class LanguageService
{
    protected $languageRepo;

    public function __construct(LanguageRepositoryInterface $languageRepo)
    {
        $this->languageRepo = $languageRepo;
    }

    public function updateLanguage(User $user, string $lang): array
    {
        $success = $this->languageRepo->updateUserLanguage($user, $lang);

        if ($success) {
            return [
                'code' => 200,
                'message' => 'Cập nhật ngôn ngữ thành công.',
            ];
        }

        return [
            'code' => 500,
            'message' => 'Cập nhật ngôn ngữ thất bại.',
        ];
    }
}
