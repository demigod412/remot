<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
    <style>
        body { margin: 0; padding: 0; background: #0f0f14; font-family: 'Segoe UI', Arial, sans-serif; color: #e2e8f0; }
        .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px 40px; }
        .header { background: #1a1a2e; border-radius: 12px 12px 0 0; padding: 28px 36px; text-align: center; border-bottom: 2px solid #6c47ff; }
        .header img { height: 36px; }
        .header-name { font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px; }
        .body { background: #16161f; padding: 36px; border-radius: 0 0 12px 12px; }
        .content { font-size: 15px; line-height: 1.75; color: #cbd5e1; }
        .content p { margin: 0 0 16px; }
        .content a { color: #6c47ff; text-decoration: none; }
        .content a:hover { text-decoration: underline; }
        .content strong { color: #ffffff; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.07); margin: 28px 0; }
        .footer { text-align: center; margin-top: 32px; font-size: 12px; color: rgba(255,255,255,0.25); line-height: 1.8; }
        .footer a { color: rgba(255,255,255,0.35); text-decoration: none; }
        .badge { display: inline-block; background: rgba(108,71,255,0.15); color: #a78bfa; border: 1px solid rgba(108,71,255,0.3); border-radius: 6px; padding: 2px 10px; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <div class="header-name">{{ config('app.name') }}</div>
    </div>

    <div class="body">
        <div class="content">
            {!! nl2br(e($body)) !!}
        </div>
        <hr class="divider">
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            You received this email because you have an account with us.<br>
            <a href="{{ config('app.url') }}">Visit Website</a>
        </div>
    </div>

</div>
</body>
</html>
