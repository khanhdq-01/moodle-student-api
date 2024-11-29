<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function updateProfile(User $user, array $data): array
    {
        // Lọc các trường hợp null hoặc không thay đổi
        $updateData = array_filter($data, function ($value) {
            return !is_null($value);
        });

        // Thực hiện cập nhật nếu có thay đổi
        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return [
            'success' => true,
            'message' => 'Cập nhật thông tin thành công.',
        ];
    }
}
