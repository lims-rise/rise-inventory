<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reset your password</title></head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Arial,sans-serif;color:#14213d;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;">
            <tr><td style="background:#0b1e3a;padding:22px 28px;color:#ffffff;font-weight:800;letter-spacing:.16em;font-size:14px;">
                RISE <span style="color:#8fc1ff;font-weight:600;letter-spacing:0;">&nbsp;Inventory Asset Database</span>
            </td></tr>
            <tr><td style="padding:30px 28px 8px;">
                <h1 style="margin:0 0 12px;font-size:21px;">Reset your password</h1>
                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Hi {{ $user->full_name }},</p>
                <p style="margin:0 0 22px;font-size:15px;line-height:1.6;">We received a request to reset the password for <b>{{ $user->email }}</b>. Click the button below to choose a new one.</p>
                <table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="border-radius:10px;background:#1f6feb;">
                    <a href="{{ $url }}" style="display:inline-block;padding:13px 26px;color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;">Set a new password</a>
                </td></tr></table>
                <p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#6b7a90;">This link expires in {{ $minutes }} minutes and can be used once. If you didn't ask for this, you can ignore this email; your password stays the same.</p>
            </td></tr>
            <tr><td style="padding:18px 28px 26px;">
                <p style="margin:0;font-size:12px;line-height:1.6;color:#9aa6b8;word-break:break-all;">If the button doesn't work, copy this link into your browser:<br><a href="{{ $url }}" style="color:#1f6feb;">{{ $url }}</a></p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
