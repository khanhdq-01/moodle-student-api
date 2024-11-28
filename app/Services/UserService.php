<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUsers(array $users)
    {
        $userIds = [];
        $availableauths = [];
        
        unset($availableauths['mnet']);
        foreach ($users as $user) {
            $userExists = DB::table('mdl_user')
                ->where([
                    ['username', '=', $user['username']],
                    ['mnethostid', '=', config('moodle.mnet_localhost_id')] // Cấu hình mnethostid từ Moodle
                ])
                ->exists();
    
            if ($userExists) {
                throw new \InvalidArgumentException('Username already exists: ' . $user['username']);
            }
            $password = $this->hashInternalUserPassword($user['password']);
    
            $userId = DB::table('mdl_user')->insertGetId([
                'username' => $user['username'],
                'email' => $user['email'],
                'password' => $password,
                'phone1' => $user['phone1'] ?? '',
            ]);

            $userIds[] = [
                'id' => $userId,
                'username' => $user['username'],
            ];
        }
    
        return $userIds;
    }    
    private function hashInternalUserPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
