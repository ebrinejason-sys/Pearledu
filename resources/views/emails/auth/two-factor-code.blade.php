<p>Hi {{ $user->full_name }},</p>
<p>Your {{ config('app.name') }} sign-in code is:</p>
<p style="font-size:28px;font-weight:700;letter-spacing:4px;">{{ $code }}</p>
<p>This code expires in {{ $expiresMinutes }} minutes. If you didn't request this, you can ignore this email.</p>
