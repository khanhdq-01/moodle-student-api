<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\MoodleUser;
use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Str;
use App\Models\UserPasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Services\SMS;
use Illuminate\Support\Facades\Log;


class AuthService
{
    public function login($credentials)
    {
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $token = $user->createToken('MyAppToken')->plainTextToken;

            return [
                'code' => 200,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    '_id' => $user->id,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                    'email' => $user->email,
                    'phone1' => $user->phone1,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'role' => $user->role,
                    'type' => $user->type,
                    'permissions' => $user->permissions ?? [],
                    'access_token' => $token,
                    'token_expired' => now()->addMinutes(60)->toDateTimeString(),
                ]
            ];
        }

        return [
            'code' => 11,
            'message' => 'Tài khoản hoặc mật khẩu không đúng'
        ];
    }
    public function logout()
    {
        $user = Auth::user();

        if ($user) {
        $user->currentAccessToken()->delete();
        return [
            'code' =>200,
            'message'=> 'logout thanh cong'
            ];
        }
        return [
            'code' => 11,
            'message' => 'Người dùng chưa đăng nhập'
        ];

    }

    public function forgotPassword($username, $method)
    {
        $user = MoodleUser::where('username', $username)->first();

        if (!$user) {
            return [
                'code' => 11,
                'message' => 'Username không tồn tại'
            ];
        }

        $otp = random_int(100000, 999999);
        $token = $otp . Str::random(26);

        UserPasswordReset::create([
            'userid' => $user->id,
            'token' => $token,
            'timerequested' => time(),
        ]);

        if ($method === 'email' && $user->email) {
            Mail::to($user->email)->send(new ForgotPasswordMail($otp));
            $message = 'OTP đã được gửi đến email của bạn';
            $otp_channel = 'email';
        } elseif ($method === 'phone1' && $user->phone1) {
            $this->sendOtpToPhone($user->phone1, $otp);
            $message = 'OTP đã được gửi đến số điện thoại của bạn';
            $otp_channel = 'phone1';
        } else {
            return [
                'code' => 11,
                'message' => 'Không có thông tin email hoặc số điện thoại để gửi OTP'
            ];
        }

        return [
            'code' => 200,
            'message' => 'Thành công',
            'otp_message' => $message,
            'otp_channel' => $otp_channel,
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

    public function resetPassword($otp, $password)
    {

        $resetRecord = UserPasswordReset::whereRaw("LEFT(token, 6) = ?", [$otp])->first();

        if (!$resetRecord) {
            return [
                'code' => 11,
                'message' => 'OTP không hợp lệ'
            ];
        }

        if (time() - $resetRecord->timerequested > 120) {
            $resetRecord->delete();
            return [
                'code' => 11,
                'message' => 'OTP đã hết hạn'
            ];
        }

        $user = MoodleUser::where('id', $resetRecord->userid)->first();

        if (!$user) {
            return [
                'code' => 11,
                'message' => 'Người dùng không tồn tại'
            ];
        }

        $user->password = Hash::make($password);
        $user->save();

        $resetRecord->delete();

        return [
            'code' => 200,
            'message' => 'Đặt lại mật khẩu thành công'
        ];
    }
}
