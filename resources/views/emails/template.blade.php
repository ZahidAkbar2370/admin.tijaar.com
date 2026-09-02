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
  <title>{{ $siteName }}</title>
  <style>
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f5; }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
    .card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
    .header { background: linear-gradient(135deg, #1790d7 0%, #4db3e8 100%); padding: 32px 40px; text-align: center; }
    .header--banner { padding: 0; background: none; }
    .header--banner img { display: block; width: 100%; max-height: 120px; object-fit: cover; }
    .logo { max-height: 56px; display: inline-block; }
    .body { padding: 40px; color: #374151; line-height: 1.6; }
    .body a { color: #1790d7; }
    .footer { padding: 24px 40px; background: #f9fafb; text-align: center; font-size: 12px; color: #6b7280; }
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
        {!! $bodyHtml !!}
      </div>
      <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
