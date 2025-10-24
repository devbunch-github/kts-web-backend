@php
$businessName = $accountant->account?->business_name ?? 'Your Business';
$loginUrl = config('app.frontend_url') . '/admin/login';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Reset - {{ $businessName }}</title>
    <style>
    body {
        background-color: #faf6f3;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        margin: 0;
        padding: 0;
        color: #333;
    }

    .container {
        width: 100%;
        padding: 60px 15px;
        display: flex;
        justify-content: center;
    }

    .card {
        background-color: #fff;
        max-width: 600px;
        width: 100%;
        border-radius: 8px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .brand {
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 4px;
        margin-bottom: 40px;
        color: #000;
    }

    h1 {
        font-size: 18px;
        color: #111;
        margin-bottom: 8px;
    }

    p {
        font-size: 14px;
        color: #555;
        margin: 8px 0;
        line-height: 1.6;
    }

    .credentials {
        text-align: left;
        display: inline-block;
        margin-top: 20px;
        font-size: 14px;
        color: #444;
    }

    .credentials strong {
        display: inline-block;
        width: 100px;
    }

    .login {
        margin-top: 25px;
        font-weight: 600;
    }

    .login a {
        color: #b77272;
        text-decoration: none;
    }

    .footer {
        margin-top: 40px;
        font-size: 12px;
        color: #999;
        text-align: center;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="brand">{{ strtoupper($businessName) }}</div>

            <h1>Your Password Has Been Reset!</h1>
            <p>The administrator has updated your login credentials for your Accountant Dashboard.</p>

            <div class="credentials">
                <p><strong>Username:</strong> {{ $accountant->name }}</p>
                <p><strong>New Password:</strong> {{ $newPassword }}</p>
            </div>

            <p class="login">
                Click on <a href="{{ $loginUrl }}" target="_blank">Login</a> to continue
            </p>

            <div class="footer">
                Copyright © {{ now()->year }} {{ $businessName }}
            </div>
        </div>
    </div>
</body>

</html>
