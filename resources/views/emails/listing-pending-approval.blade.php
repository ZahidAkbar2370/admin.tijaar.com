@php
  $appUrl = rtrim(config('app.url'), '/');
  $emailLogo = \App\Models\Setting::get('email_logo');
  $emailBanner = \App\Models\Setting::get('email_banner');
  $emailLogoUrl = $emailLogo ? \App\Support\UploadHelper::url($emailLogo) : $appUrl . '/images/tijaar-logo.png';
  $emailBannerUrl = $emailBanner ? \App\Support\UploadHelper::url($emailBanner) : null;
  $siteName = \App\Models\Setting::get('site_name', config('app.name', 'Tijaar'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listing Pending Approval – {{ $siteName }}</title>
  <style>
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f5; }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
    .card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
    .header { background: linear-gradient(135deg, #1790d7 0%, #4db3e8 100%); padding: 32px 40px; text-align: center; }
    .header--banner { padding: 0; background: none; }
    .header--banner img { display: block; width: 100%; max-height: 120px; object-fit: cover; }
    .logo { max-height: 56px; display: inline-block; }
    .body { padding: 40px; color: #374151; line-height: 1.7; font-size: 15px; }
    .body a { color: #1790d7; text-decoration: none; }
    .body a:hover { text-decoration: underline; }
    .footer { padding: 24px 40px; background: #f9fafb; text-align: center; font-size: 12px; color: #6b7280; }
    h1 { font-size: 20px; color: #111827; margin: 0 0 20px 0; font-weight: 600; }
    .product-name { font-weight: 600; color: #111827; }
    .btn { display: inline-block; background: linear-gradient(135deg, #1790d7, #4db3e8); color: #fff !important; text-decoration: none; padding: 14px 28px; border-radius: 10px; font-weight: 600; margin: 24px 0 8px 0; font-size: 15px; }
    .muted { color: #6b7280; font-size: 14px; margin-top: 24px; }
    .divider { height: 1px; background: #e5e7eb; margin: 24px 0; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="header {{ $emailBannerUrl ? 'header--banner' : '' }}">
        @if($emailBannerUrl)
          <img src="{{ $emailBannerUrl }}" alt="{{ $siteName }}" width="600" height="120">
        @else
          <img src="{{ $emailLogoUrl }}" alt="{{ $siteName }}" class="logo" width="120" height="auto">
        @endif
      </div>
      <div class="body">
        <h1>Your listing is pending approval</h1>
        <p>Dear {{ $customerName }},</p>
        <p>Thank you for submitting your listing to {{ $siteName }}. We have received your product <span class="product-name">«{{ $productName }}»</span> and it is currently <strong>pending review</strong> by the Tijaar team.</p>
        <p>Our team will review your listing to ensure it meets our marketplace guidelines. Once approved, your product will go live and you will receive a separate email notification so you can start receiving orders.</p>
        <p>You do not need to take any action at this time. If we need any additional information, we will reach out to you via email.</p>
        <div class="divider"></div>
        <p style="margin-bottom: 8px;">You can track the status of your listings at any time from your seller dashboard:</p>
        <p>
          <a href="{{ $dashboardUrl }}" class="btn">View seller dashboard</a>
        </p>
        <p class="muted">Thank you for selling with {{ $siteName }}. We appreciate your patience and look forward to having your product on our marketplace.</p>
      </div>
      <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
