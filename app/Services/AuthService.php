<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPasswordMail;
use App\Services\SMS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function login($username, $password)
    {
        $user = User::authenticate($username, $password);
        
        if (!$user) {
            return [
                'code' => 404,
                'message' => 'Tài khoản không tồn tại hoặc đã bị khóa.',
            ];
        }
        
        $role = DB::table('mdl_role')
              ->join('mdl_role_assignments', 'mdl_role.id', '=', 'mdl_role_assignments.roleid')
              ->join('mdl_user', 'mdl_role_assignments.userid', '=', 'mdl_user.id')
              ->where('mdl_user.username', $username)
              ->value('mdl_role.id');

        $token = $user->createToken('login-token')->plainTextToken;

        return [
            'code' => 200,
            'message' => 'Đăng nhập thành công.',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'role' => $role,
                'token' => $token
            ]
        ];
    }

    public function logout($user)
    {
        if (!$user) {
            return [
                'code' => 401,
                'message' => 'Bạn chưa đăng nhập.',
            ];
        }

        $user->tokens->each(function ($token) {
            $token->delete();
        });

        return [
            'code' => 200,
            'message' => 'Đăng xuất thành công.',
        ];
    }

    public function changePassword($user, $currentPassword, $newPassword)
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'code' => 401,
                'message' => 'Mật khẩu cũ không đúng.',
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return [
            'code' => 200,
            'message' => 'Đổi mật khẩu thành công.',
        ];
    }

    public function forgotPassword($input)
    {
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $input)->first();
            $method = 'email';
        } elseif (preg_match('/^\d+$/', $input)) {
            $user = User::where('phone1', $input)->first();
            $method = 'phone1';
        } else {
            return [
                'code' => 11,
                'message' => 'Định dạng không hợp lệ. Vui lòng nhập email hoặc số điện thoại hợp lệ.',
            ];
        }

        if (!$user) {
            return [
                'code' => 11,
                'message' => 'Người dùng không tồn tại.',
            ];
        }

        $otp = random_int(100000, 999999);
        $token = $otp . Str::random(26);

        UserPasswordReset::create([
            'userid' => $user->id,
            'token' => $token,
            'timerequested' => time(),
        ]);

        $message = '';
        $otp_channel = '';

        if ($method === 'email') {
            Mail::to($user->email)->send(new ForgotPasswordMail($otp));
            $message = 'OTP đã được gửi đến email của bạn.';
            $otp_channel = 'email';
        } elseif ($method === 'phone1') {
            $this->sendOtpToPhone($user->phone1, $otp);
            $message = 'OTP đã được gửi đến số điện thoại của bạn.';
            $otp_channel = 'phone1';
        }

        return [
            'code' => 200,
            'message' => 'Thành công',
            'otp_message' => $message,
            'otp_channel' => $otp_channel,
        ];
    }

    public function resetPassword($otp, $password)
    {
        $resetRecord = UserPasswordReset::whereRaw("LEFT(token, 6) = ?", [$otp])->first();

        if (!$resetRecord) {
            return [
                'code' => 11,
                'message' => 'OTP không hợp lệ',
            ];
        }

        if (time() - $resetRecord->timerequested > 120) {
            $resetRecord->delete();
            return [
                'code' => 11,
                'message' => 'OTP đã hết hạn',
            ];
        }

        $user = User::where('id', $resetRecord->userid)->first();

        if (!$user) {
            return [
                'code' => 11,
                'message' => 'Người dùng không tồn tại',
            ];
        }

        $user->password = Hash::make($password);
        $user->save();

        $resetRecord->delete();

        return [
            'code' => 200,
            'message' => 'Đặt lại mật khẩu thành công',
        ];
    }

    protected function sendOtpToPhone($phone1, $otp)
    {
        $message = "Test message 123 $otp";
        $smsService = new SMS();
        $response = $smsService->send($phone1, $message);
        Log::info('Infobip Response: ' . json_encode($response));

        if ($response['success']) {
            Log::info('SMS gửi thành công!');
        } else {
            Log::error('SMS gửi không thành công: ' . $response['message']);
        }
    }
}
