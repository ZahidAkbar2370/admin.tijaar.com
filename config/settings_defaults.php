<?php

return [
    'site_name' => env('APP_NAME', 'Tijaar'),
    'site_tagline' => '',
    'support_email' => env('MAIL_FROM_ADDRESS', ''),
    'support_phone' => '',
    'currency_default' => 'PKR',
    'timezone' => 'UTC',

    'meta_title' => 'Tijaar | Online Shopping Marketplace in Pakistan',
    'meta_description' => 'Tijaar is Pakistan\'s multi-seller marketplace. Shop from verified sellers with secure payments, buyer protection, and fast courier delivery or become a verified seller and reach buyers nationwide.',
    'meta_keywords' => 'Tijaar, online shopping Pakistan, marketplace Pakistan, multi seller marketplace, buy online Pakistan, sell online Pakistan, verified sellers, ecommerce Pakistan, online marketplace, secure payments, buyer protection, courier delivery, become a seller, multi vendor marketplace, shop online Pakistan, trusted sellers, cash on delivery Pakistan, online store Pakistan',
    'meta_author' => 'tijaar.com',

    'seo_h1_home' => 'Online Shopping Marketplace in Pakistan',
    'seo_h1_category' => '{name} Online in Pakistan',
    'seo_h1_subcategory' => '{name} Online in Pakistan',
    'seo_h1_product' => '{name}',
    'seo_h1_blog' => '{title}',
    'seo_h1_blog_list' => 'Blog',
    'seo_h1_policy' => '{title}',
    'seo_h1_cms' => '{title}',
    'seo_h1_shop' => 'Shop All Products',
    'seo_h1_brand' => '{name}',
    'seo_h1_seller_store' => '{name}',
    'seo_h1_search' => 'Search results for "{query}"',
    'seo_h1_search_empty' => 'Search',
    'seo_h1_sellers' => 'Our Verified Sellers',
    'seo_h1_all_categories' => 'All Categories',
    'seo_h1_best_sellers' => 'Best Sellers',
    'seo_h1_flash_deals' => 'Flash Deals',
    'seo_h1_flash_deal' => '{title}',
    'seo_h1_cart' => 'Shopping Cart',
    'seo_h1_checkout' => 'Checkout',

    // Rich text / CMS typography (admin-configurable font sizes)
    'font_size_h1' => '1.875rem',
    'font_size_h2' => '1.5rem',
    'font_size_h3' => '1.25rem',
    'font_size_h4' => '1.125rem',
    'font_size_h5' => '1rem',
    'font_size_h6' => '0.875rem',
    'font_size_p' => '1rem',
    'font_size_body' => '1.125rem',

    'robots_txt' => '',
    'llm_txt' => '',

    'stripe_key' => env('STRIPE_KEY', ''),
    'stripe_secret' => env('STRIPE_SECRET', ''),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'stripe_pkr_to_usd' => env('STRIPE_PKR_TO_USD', '280'),
    'stripe_enabled' => '1',

    'paypal_client_id' => env('PAYPAL_CLIENT_ID', ''),
    'paypal_client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
    'paypal_mode' => env('PAYPAL_MODE', 'sandbox'),
    'paypal_enabled' => '1',

    'jazzcash_merchant_id' => env('JAZZCASH_MERCHANT_ID', ''),
    'jazzcash_password' => env('JAZZCASH_PASSWORD', ''),
    'jazzcash_integrity_salt' => env('JAZZCASH_INTEGRITY_SALT', ''),
    'jazzcash_checkout_url' => env('JAZZCASH_CHECKOUT_URL', ''),
    'jazzcash_mwallet_url' => env('JAZZCASH_MWALLET_URL', ''),
    'jazzcash_mwallet_v2_url' => env('JAZZCASH_MWALLET_V2_URL', ''),
    'jazzcash_checkout_mode' => env('JAZZCASH_CHECKOUT_MODE', 'mwallet_v2'),
    'jazzcash_status_inquiry_url' => env('JAZZCASH_STATUS_INQUIRY_URL', ''),
    'jazzcash_return_url' => env('JAZZCASH_RETURN_URL', ''),
    'jazzcash_enabled' => '0',

    'payment_cod_enabled' => '1',
    'partial_payment_enabled' => '1',
    'partial_payment_online_percent' => '50',

    'leopards_enabled' => '0',
    'postex_enabled' => '0',
    'dex_enabled' => '0',
    'daewoo_fastex_enabled' => '0',
    'mnp_enabled' => '0',
    'baloch_cargo_enabled' => '0',
    'tcs_enabled' => '0',

    'easypaisa_merchant_id' => env('EASYPAISA_MERCHANT_ID', ''),
    'easypaisa_store_id' => '',
    'easypaisa_enabled' => '0',

    'mail_mailer' => env('MAIL_MAILER', 'smtp'),
    'mail_host' => env('MAIL_HOST', ''),
    'mail_port' => env('MAIL_PORT', '465'),
    'mail_username' => env('MAIL_USERNAME', ''),
    'mail_password' => env('MAIL_PASSWORD', ''),
    'mail_encryption' => env('MAIL_ENCRYPTION', 'ssl'),
    'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
    'mail_from_name' => env('MAIL_FROM_NAME', 'Tijaar'),

    'email_welcome_enabled' => '1',
    'email_password_reset_enabled' => '1',
    'email_order_placed_enabled' => '1',
    'email_order_shipped_enabled' => '1',
    // When enabled, new registrations must verify email (OTP) before login.
    'email_verification_required' => '1',

    // Product Settings
    'product_approval_required' => '0',
    'private_listing_approval' => '0',
    'private_listing_free_limit' => '3',
    'private_listing_limit' => '15',
    'private_listing_fee' => '50',
    'private_listing_expiry_days' => '30',
    'private_listing_max_images' => '6',
    'private_listing_max_image_updates' => '0', // 0 = unlimited
    'private_listing_video_enabled' => '0',
    'private_sellers_enabled' => '1',
    'private_seller_must_verify_email' => '0',
    'private_seller_must_verify_phone' => '0',
    'private_seller_must_verify_whatsapp' => '0',

    // Payout holding period (days after delivery before earnings are released)
    'payout_hold_days' => '0',
    'private_payout_hold_days' => '',

    // Buyer checkout fees (added to customer total at checkout)
    'buyer_marketplace_fee_type' => 'fixed',
    'buyer_marketplace_fee_value' => '0',
    'buyer_online_transaction_fee_type' => 'fixed',
    'buyer_online_transaction_fee_value' => '0',
    // Legacy keys (fallback until migrated in DB)
    'marketplace_fee_type' => 'fixed',
    'marketplace_fee_value' => '0',
    'online_transaction_fee_type' => 'fixed',
    'online_transaction_fee_value' => '0',
    // Customer-seller order deductions (from product price, not shipping)
    'private_seller_marketplace_fee_type' => 'fixed',
    'private_seller_marketplace_fee_value' => '0',
    'private_seller_online_transaction_fee_type' => 'fixed',
    'private_seller_online_transaction_fee_value' => '0',
    'seller_commission_type' => 'percentage',
    'seller_commission_value' => '2',
    'private_seller_commission_type' => 'percentage',
    'private_seller_commission_value' => '2',

    // Seller order-reject penalties (PKR, debited from seller wallet; negative balance allowed)
    'order_reject_penalty_customer_seller' => '500',
    'order_reject_penalty_private_seller' => '1000',

    // Google reCAPTCHA v2 (login / register)
    'recaptcha_enabled' => '0',
    'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY', ''),
    'recaptcha_secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    'recaptcha_on_login' => '1',
    'recaptcha_on_register' => '1',

    // WaChat WhatsApp (Waghl API)
    'wachat_enabled' => '0',
    'wachat_api_key' => env('WACHAT_API_KEY', ''),
    'wachat_sender' => env('WACHAT_SENDER', ''),
    'wachat_api_endpoint' => env('WACHAT_API_ENDPOINT', 'https://custom2.waghl.com/send-message'),
    'wachat_msg_order_placed_customer' => '1',
    'wachat_msg_order_placed_seller' => '1',
    'wachat_msg_payment_success' => '1',
    'wachat_msg_order_approved' => '1',
    'wachat_msg_order_shipped' => '1',
    'wachat_msg_order_delivered_seller' => '1',
    // User-facing WhatsApp notification channel (profile prefs). Off hides WhatsApp toggles.
    'notification_whatsapp_enabled' => '1',
];
