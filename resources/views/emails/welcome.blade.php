<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to Tijaar</title>
  <style>
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f5; }
    .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
    .card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
    .header { background: linear-gradient(135deg, #1790d7 0%, #4db3e8 100%); padding: 32px 40px; text-align: center; }
    .logo { max-height: 56px; display: inline-block; }
    .body { padding: 40px; color: #374151; line-height: 1.6; }
    .btn { display: inline-block; background: linear-gradient(135deg, #1790d7, #4db3e8); color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 600; margin: 24px 0; }
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
        <img src="{{ rtrim(config('app.url'), '/') }}/images/tijaar-logo.png" alt="Tijaar" class="logo" width="120" height="auto">
      </div>
      <div class="body">
        <h1>Welcome to Tijaar, {{ $name }}!</h1>
        @if($role === 'seller')
          <p>Your seller account has been successfully created. You're now ready to start your selling journey on Tijaar.</p>
          <p>Complete these next steps to get started:</p>
          <ul style="margin: 16px 0; padding-left: 24px;">
            <li><strong>Create your store</strong> – Set up your store profile, logo, and policies</li>
            <li><strong>Complete KYC</strong> – Verify your identity for secure payouts</li>
            <li><strong>Add products</strong> – List your first products and start selling</li>
          </ul>
          <p style="text-align: center;">
            <a href="{{ $dashboardUrl }}" class="btn">Go to Seller Dashboard</a>
          </p>
        @else
          <p>Your account has been successfully created. Welcome to Tijaar – your trusted marketplace for buying and selling across Pakistan and UAE.</p>
          <p>You can now browse products, add items to your wishlist, connect with sellers, and manage your orders.</p>
          <p style="text-align: center;">
            <a href="{{ $dashboardUrl }}" class="btn">Get Started</a>
          </p>
        @endif
        <p class="muted">Need help? Visit our help center or contact support. We're here for you.</p>
      </div>
      <div class="footer">
        <p>&copy; {{ date('Y') }} Tijaar. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
