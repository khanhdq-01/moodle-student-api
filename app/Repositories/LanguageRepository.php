<?php

namespace App\Repositories;

use App\Models\User;
use App\Interfaces\LanguageRepositoryInterface;

class LanguageRepository implements LanguageRepositoryInterface
{
    public function updateUserLanguage(User $user, string $lang): bool
    {
        $user->lang = $lang;
        return $user->save();
    }
}
