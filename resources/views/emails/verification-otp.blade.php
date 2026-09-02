<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Your Email - Tijaar</title>
  <style>
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f5; }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
    .card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
    .header { background: linear-gradient(135deg, #1790d7 0%, #4db3e8 100%); padding: 32px 40px; text-align: center; }
    .logo { max-height: 56px; display: inline-block; }
    .body { padding: 40px; color: #374151; line-height: 1.6; }
    .otp-box { background: #f0f9ff; border: 2px dashed #1790d7; border-radius: 12px; padding: 24px; text-align: center; margin: 24px 0; }
    .otp-code { font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #1790d7; font-family: 'Courier New', monospace; }
    .footer { padding: 24px 40px; background: #f9fafb; text-align: center; font-size: 12px; color: #6b7280; }
    h1 { font-size: 22px; color: #111827; margin: 0 0 16px 0; }
    p { margin: 0 0 12px 0; font-size: 15px; }
    .muted { color: #6b7280; font-size: 13px; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header">
        <img src="{{ rtrim(config('app.url', ''), '/') }}/images/tijaar-logo.png" alt="Tijaar" class="logo" width="120" height="auto">
      </div>
      <div class="body">
        <h1>Verify Your Email Address</h1>
        <p>Thank you for registering with Tijaar. To complete your account setup, please use the verification code below:</p>
        <div class="otp-box">
          <div class="otp-code">{{ $otpCode }}</div>
        </div>
        <p class="muted">This code expires in <strong>15 minutes</strong>. Do not share this code with anyone.</p>
        <p class="muted">If you did not request this verification, please ignore this email or contact our support team if you have concerns.</p>
      </div>
      <div class="footer">
        <p>&copy; {{ date('Y') }} Tijaar. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
