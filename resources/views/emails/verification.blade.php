<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #10b981;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #10b981;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #e4e4e4;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Grapadi Strategix</div>
            <h2>Verifikasi Email Anda</h2>
        </div>

        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>

            <p>Terima kasih telah mendaftar di Grapadi Strategix. Untuk mengaktifkan akun Anda, silakan verifikasi alamat email Anda dengan mengklik tombol di bawah ini:</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verifikasi Email Saya</a>
            </div>

            <p>Jika tombol di atas tidak bekerja, Anda dapat menyalin dan menempelkan URL berikut di browser Anda:</p>
            <p style="word-break: break-all; color: #666; background: #f4f4f4; padding: 10px; border-radius: 5px;">
                {{ $verificationUrl }}
            </p>

            <p>Link verifikasi ini akan kedaluwarsa dalam 24 jam.</p>

            <p>Jika Anda tidak merasa mendaftar di Grapadi Strategix, Anda dapat mengabaikan email ini.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Grapadi Strategix. All rights reserved.</p>
            <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
