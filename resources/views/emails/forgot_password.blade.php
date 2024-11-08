<!-- resources/views/emails/forgot_password.blade.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu đặt lại mật khẩu</title>
</head>
<body>
    <h2>Xin chào,</h2>
    <p>Đây là mã OTP để bạn có thể đặt lại mật khẩu của mình:</p>
    <h3>{{ $otp }}</h3>
    <p>Vui lòng sử dụng mã OTP này để hoàn tất quá trình đặt lại mật khẩu.</p>
    <p>Chúc bạn thành công!</p>
</body>
</html>
