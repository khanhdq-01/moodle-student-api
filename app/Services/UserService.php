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
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
                'auth' => $user['auth'] ?? 'manual',
                'password' => $password,
                'confirmed' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
                'maildisplay' => $user['maildisplay'] ?? 1,
                'city' => $user['city'] ?? '',
                'country' => $user['country'] ?? '',
                'timezone' => $user['timezone'] ?? '',
                'description' => $user['description'] ?? '',
                'phone1' => $user['phone1'] ?? '',
            ]);

            $roleId = $user['role'] === 'student' ? 5 : 3;
            $contextId = DB::table('mdl_context')
                ->where('contextlevel', '=', 50) // 50 là contextlevel cho user
                ->first()
                ->id;

            DB::table('mdl_role_assignments')->insert([
                'roleid' => $roleId,
                'contextid' => $contextId,
                'userid' => $userId,
                'timemodified' => time(),
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
