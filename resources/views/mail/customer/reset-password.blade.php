<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إعادة تعيين كلمة المرور</title>
</head>
<body style="margin:0;background:#f4f1e8;color:#123329;font-family:Tahoma,Arial,sans-serif;line-height:1.8;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f1e8;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;overflow:hidden;border:1px solid #dce6df;border-radius:18px;background:#ffffff;box-shadow:0 12px 36px rgba(18,51,41,.08);">
                    <tr>
                        <td align="center" style="background:#123329;padding:28px 24px;">
                            <img src="{{ asset('logo-dark.png') }}" alt="بيرحاء" width="150" style="display:block;max-width:150px;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 36px;text-align:right;">
                            <p style="margin:0 0 8px;color:#b07c00;font-size:13px;font-weight:700;letter-spacing:.04em;">حماية حسابك في بيرحاء</p>
                            <h1 style="margin:0 0 22px;color:#123329;font-size:26px;line-height:1.45;">إعادة تعيين كلمة المرور</h1>
                            <p style="margin:0 0 16px;color:#315e52;font-size:16px;">مرحبًا {{ $customerName }}،</p>
                            <p style="margin:0 0 26px;color:#315e52;font-size:16px;">وصلنا طلب لإعادة تعيين كلمة مرور حسابك. استخدم الزر التالي لاختيار كلمة مرور جديدة.</p>
                            <p style="margin:0 0 28px;text-align:center;">
                                <a href="{{ $resetUrl }}" style="display:inline-block;border-radius:10px;background:#007f5f;color:#ffffff;padding:13px 28px;font-size:16px;font-weight:700;text-decoration:none;">تعيين كلمة مرور جديدة</a>
                            </p>
                            <div style="border-right:4px solid #d6a928;border-radius:8px;background:#fbf8ee;padding:14px 16px;color:#315e52;font-size:14px;">
                                ينتهي هذا الرابط خلال {{ $expiresInMinutes }} دقيقة، ويمكن استخدامه مرة واحدة فقط.
                            </div>
                            <p style="margin:26px 0 0;color:#5d746d;font-size:14px;">إذا لم تطلب إعادة تعيين كلمة المرور، فتجاهل هذه الرسالة ولن يتغير حسابك.</p>
                            <p style="margin:24px 0 0;color:#123329;font-size:14px;font-weight:700;">فريق بيرحاء</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:18px 0 0;color:#71827c;font-size:12px;">هذه رسالة آلية لحماية حسابك، فلا تشارك رابط إعادة التعيين مع أي شخص.</p>
            </td>
        </tr>
    </table>
</body>
</html>
