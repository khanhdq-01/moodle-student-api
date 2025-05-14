<?php

namespace App\Interfaces;

use App\Models\User;

interface LanguageRepositoryInterface
{
    public function updateUserLanguage(User $user, string $lang): bool;
}
