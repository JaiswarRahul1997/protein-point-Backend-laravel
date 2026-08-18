<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;line-height:1.5;color:#111;">
    <p>Hi {{ $subscriber->name ?: 'there' }},</p>
    <p>Please confirm your email subscription to Protein Point updates.</p>
    <p><a href="{{ $confirmUrl }}">Confirm subscription</a></p>
    <p>If you did not request this, you can ignore this email.</p>
</body>
</html>
