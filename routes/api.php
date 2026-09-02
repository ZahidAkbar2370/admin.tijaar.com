<?php

use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('v1/webhooks/stripe', [\App\Http\Controllers\Api\PaymentController::class, 'stripeWebhook']);
Route::post('v1/webhooks/jazzcash/callback', [\App\Http\Controllers\Api\PaymentController::class, 'jazzcashCallback']);
Route::post('v1/webhooks/jazzcash/ipn', [\App\Http\Controllers\Api\PaymentController::class, 'jazzcashIpn']);
Route::post('v1/webhooks/easypaisa/callback', [\App\Http\Controllers\Api\PaymentController::class, 'easypaisaCallback']);
Route::options('{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');
Route::prefix('v1')->group(function () {
    // Auth routes without throttle to avoid "Too many attempts" on login (Issue 11)
    Route::middleware('throttle:60,1')->group(function () {
    // API info
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'message' => 'Tijaar API v1',
            'docs' => [
                'POST /api/v1/register' => 'Register (customer/seller)',
                'POST /api/v1/login' => 'Login',
                'POST /api/v1/forgot-password' => 'Password reset request',
                'POST /api/v1/reset-password' => 'Password reset (token)',
                'GET /api/v1/markets' => 'List markets',
            ],
        ]);
    });
    Route::get('/smtp-test', function () {
        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from Tijaar /smtp-test endpoint.', function ($message) {
                $message->to('happybro6516@gmail.com')
                        ->subject('SMTP Test Email');
            });
            return response()->json(['success' => true, 'message' => 'Test email sent to happybro6516@gmail.com']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email', 'error' => $e->getMessage()], 500);
        }
    });

    // Public
    Route::get('/markets', [MarketController::class, 'index']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/featured', [\App\Http\Controllers\Api\CategoryController::class, 'featured']);
    Route::get('/categories/{slug}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);

    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/promoted-ads', [\App\Http\Controllers\Api\ProductController::class, 'promotedAds']);
    Route::get('/products/{slug}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::post('/products/{productId}/analytics', [\App\Http\Controllers\Api\ProductAnalyticsController::class, 'track'])->whereNumber('productId');
    Route::get('/search/suggest', [\App\Http\Controllers\Api\SearchController::class, 'suggest']);
    Route::get('/search/featured', [\App\Http\Controllers\Api\SearchController::class, 'featured']);
    Route::get('/search/trending', [\App\Http\Controllers\Api\SearchController::class, 'trending']);
    Route::get('/search/deals', [\App\Http\Controllers\Api\SearchController::class, 'deals']);
    Route::get('/flash-deals', [\App\Http\Controllers\Api\FlashDealController::class, 'index']);
    Route::get('/flash-deals/{idOrSlug}', [\App\Http\Controllers\Api\FlashDealController::class, 'show']);
    Route::get('/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'index']);
    Route::get('/stores', [\App\Http\Controllers\Api\StoreController::class, 'index']);
    Route::get('/stores/{slug}', [\App\Http\Controllers\Api\StoreController::class, 'show']);
    Route::get('/brands', [\App\Http\Controllers\Api\BrandController::class, 'index']);
    Route::get('/brands/featured', [\App\Http\Controllers\Api\BrandController::class, 'featured']);
    Route::get('/brands/{slug}', [\App\Http\Controllers\Api\BrandController::class, 'show']);
    Route::get('/market/current', [MarketController::class, 'current']);
    Route::get('/site-settings', [\App\Http\Controllers\Api\SiteSettingsController::class, 'index']);
    Route::get('/media/delivery', [\App\Http\Controllers\Api\ImageDeliveryController::class, 'show']);
    Route::get('/locations', [\App\Http\Controllers\Api\LocationController::class, 'index']);
    Route::get('/locations/countries', [\App\Http\Controllers\Api\LocationController::class, 'countries']);
    Route::get('/locations/provinces', [\App\Http\Controllers\Api\LocationController::class, 'provinces']);
    Route::get('/locations/cities', [\App\Http\Controllers\Api\LocationController::class, 'cities']);
    Route::get('/sitemap', [\App\Http\Controllers\Api\SitemapController::class, 'indexJson']);
    Route::get('/sitemap/static', [\App\Http\Controllers\Api\SitemapController::class, 'staticJson']);
    Route::get('/sitemap/categories', [\App\Http\Controllers\Api\SitemapController::class, 'categoriesJson']);
    Route::get('/sitemap/products/{page}', [\App\Http\Controllers\Api\SitemapController::class, 'productsJson'])->whereNumber('page');
    Route::post('/payment/preview', [\App\Http\Controllers\Api\PaymentOptionsController::class, 'preview']);
    Route::post('/shipping/estimate', [\App\Http\Controllers\Api\ShippingController::class, 'estimate']);

    // Guest cart (session-based, no auth)
    Route::get('/cart/guest', [\App\Http\Controllers\Api\CartController::class, 'guestIndex']);
    Route::post('/cart/guest/add', [\App\Http\Controllers\Api\CartController::class, 'guestAdd']);
    Route::put('/cart/guest/update', [\App\Http\Controllers\Api\CartController::class, 'guestUpdate']);
    Route::delete('/cart/guest/remove/{productId}', [\App\Http\Controllers\Api\CartController::class, 'guestRemove']);

    // CMS (public)
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\CmsController::class, 'page']);
    Route::get('/banners', [\App\Http\Controllers\Api\CmsController::class, 'banners']);
    Route::get('/faqs', [\App\Http\Controllers\Api\CmsController::class, 'faqs']);
    Route::get('/blogs', [\App\Http\Controllers\Api\CmsController::class, 'blogs']);
    Route::get('/blog/{slug}', [\App\Http\Controllers\Api\CmsController::class, 'blog']);
    Route::post('/newsletter', [\App\Http\Controllers\Api\CmsController::class, 'newsletter']);
    Route::post('/contact', [\App\Http\Controllers\Api\CmsController::class, 'contact']);
    Route::get('/testimonials', [\App\Http\Controllers\Api\CmsController::class, 'testimonials']);
    Route::get('/home-sections', [\App\Http\Controllers\Api\CmsController::class, 'homeSections']);
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);
    });

    // Auth (no throttle – avoid "Too many attempts" on login)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/seller/register', [\App\Http\Controllers\Api\SellerRegisterController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/auth/{provider}/url', [SocialAuthController::class, 'redirect']);
    Route::post('/auth/{provider}/token', [SocialAuthController::class, 'token']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Remaining authenticated routes require email verification when enabled in admin.
        Route::middleware('email.verified')->group(function () {

        // Profile
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/profile/password', [UserController::class, 'changePassword']);
        Route::post('/profile/avatar', [UserController::class, 'uploadAvatar']);

        Route::get('/whatsapp/status', [\App\Http\Controllers\Api\WhatsappVerificationController::class, 'status']);
        Route::post('/whatsapp/send-otp', [\App\Http\Controllers\Api\WhatsappVerificationController::class, 'sendOtp']);
        Route::post('/whatsapp/verify-otp', [\App\Http\Controllers\Api\WhatsappVerificationController::class, 'verifyOtp']);

        Route::get('/phone/status', [\App\Http\Controllers\Api\PhoneVerificationController::class, 'status']);
        Route::post('/phone/send-otp', [\App\Http\Controllers\Api\PhoneVerificationController::class, 'sendOtp']);
        Route::post('/phone/verify-otp', [\App\Http\Controllers\Api\PhoneVerificationController::class, 'verifyOtp']);

        // 2FA
        Route::post('/two-factor/enable', [TwoFactorController::class, 'enable']);
        Route::post('/two-factor/verify', [TwoFactorController::class, 'verify']);
        Route::post('/two-factor/disable', [TwoFactorController::class, 'disable']);

        // Addresses
        Route::get('/addresses', [\App\Http\Controllers\Api\AddressController::class, 'index']);
        Route::post('/addresses', [\App\Http\Controllers\Api\AddressController::class, 'store']);
        Route::get('/addresses/{address}', [\App\Http\Controllers\Api\AddressController::class, 'show']);
        Route::put('/addresses/{address}', [\App\Http\Controllers\Api\AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [\App\Http\Controllers\Api\AddressController::class, 'destroy']);
        Route::post('/addresses/{address}/default', [\App\Http\Controllers\Api\AddressController::class, 'setDefault']);

        // Saved cards
        Route::get('/saved-cards', [\App\Http\Controllers\Api\SavedCardController::class, 'index']);
        Route::post('/saved-cards', [\App\Http\Controllers\Api\SavedCardController::class, 'store']);
        Route::delete('/saved-cards/{savedCard}', [\App\Http\Controllers\Api\SavedCardController::class, 'destroy']);
        Route::post('/saved-cards/{savedCard}/default', [\App\Http\Controllers\Api\SavedCardController::class, 'setDefault']);

        // Notifications (push notification log + FCM token)
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/fcm-token', [\App\Http\Controllers\Api\FcmTokenController::class, 'store']);

        // Notification preferences
        Route::get('/notification-preferences', [\App\Http\Controllers\Api\NotificationPreferenceController::class, 'index']);
        Route::put('/notification-preferences', [\App\Http\Controllers\Api\NotificationPreferenceController::class, 'update']);

        // Sessions/devices
        Route::get('/sessions', [\App\Http\Controllers\Api\SessionController::class, 'index']);
        Route::delete('/sessions/{token}', [\App\Http\Controllers\Api\SessionController::class, 'destroy']);

        // Market preference
        Route::post('/market/preference', [MarketController::class, 'setPreference']);

        // Wishlist
        Route::get('/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'index']);
        Route::post('/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'store']);
        Route::delete('/wishlist/{productId}', [\App\Http\Controllers\Api\WishlistController::class, 'destroy']);
        Route::post('/wishlist/{productId}/move-to-cart', [\App\Http\Controllers\Api\WishlistController::class, 'moveToCart']);
        Route::post('/wishlist/{productId}/alert', [\App\Http\Controllers\Api\WishlistController::class, 'toggleAlert']);

        // Cart (requires auth)
        Route::get('/cart', [\App\Http\Controllers\Api\CartController::class, 'index']);
        Route::post('/cart/add', [\App\Http\Controllers\Api\CartController::class, 'add']);
        Route::post('/cart/add-deal', [\App\Http\Controllers\Api\CartController::class, 'addDeal']);
        Route::put('/cart/update', [\App\Http\Controllers\Api\CartController::class, 'update']);
        Route::delete('/cart/remove/{productId}', [\App\Http\Controllers\Api\CartController::class, 'remove']);
        Route::delete('/cart', [\App\Http\Controllers\Api\CartController::class, 'clear']);
        Route::post('/cart/merge', [\App\Http\Controllers\Api\CartController::class, 'merge']);

        // Wallet
        Route::get('/wallet/balance', [\App\Http\Controllers\Api\WalletController::class, 'balance']);
        Route::post('/wallet/deposit', [\App\Http\Controllers\Api\WalletController::class, 'createDeposit']);
        Route::get('/wallet/transactions', [\App\Http\Controllers\Api\WalletController::class, 'transactions']);
        Route::get('/wallet/export', [\App\Http\Controllers\Api\WalletController::class, 'export']);

        // Orders
        Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
        Route::get('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
        Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
        Route::post('/orders/{order}/request-cancellation', [\App\Http\Controllers\Api\OrderController::class, 'requestCancellation']);
        Route::post('/orders/{order}/retry-payment', [\App\Http\Controllers\Api\OrderController::class, 'retryPayment']);
        Route::patch('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'update']);
        Route::post('/coupons/validate', [\App\Http\Controllers\Api\CouponController::class, 'validate']);
        Route::post('/shipping/calculate', [\App\Http\Controllers\Api\ShippingController::class, 'calculate']);
        Route::post('/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
        Route::post('/reviews/{review}/helpful', [\App\Http\Controllers\Api\ReviewController::class, 'helpful']);
        Route::post('/reviews/{review}/report', [\App\Http\Controllers\Api\ReviewController::class, 'report']);
        Route::post('/reviews/{review}/reply', [\App\Http\Controllers\Api\ReviewController::class, 'reply']);
        Route::get('/promotions/packages', [\App\Http\Controllers\Api\PromotionController::class, 'packages']);
        Route::get('/promotions/my-subscriptions', [\App\Http\Controllers\Api\PromotionController::class, 'mySubscriptions']);
        Route::get('/promotions/history', [\App\Http\Controllers\Api\PromotionController::class, 'history']);
        Route::get('/promotions/eligibility', [\App\Http\Controllers\Api\PromotionController::class, 'eligibility']);
        Route::post('/promotions/purchase', [\App\Http\Controllers\Api\PromotionController::class, 'purchase']);
        Route::post('/refunds/request', [\App\Http\Controllers\Api\RefundController::class, 'request']);

        // Conversations (chat)
        Route::get('/conversations/unread-count', [\App\Http\Controllers\Api\ConversationController::class, 'unreadCount']);
        Route::get('/conversations', [\App\Http\Controllers\Api\ConversationController::class, 'index']);
        Route::post('/conversations/start', [\App\Http\Controllers\Api\ConversationController::class, 'start']);
        Route::post('/conversations', [\App\Http\Controllers\Api\ConversationController::class, 'store']);
        Route::get('/conversations/{id}', [\App\Http\Controllers\Api\ConversationController::class, 'show']);
        Route::post('/conversations/{id}/messages', [\App\Http\Controllers\Api\ConversationController::class, 'sendMessage']);
        Route::post('/conversations/{id}/report', [\App\Http\Controllers\Api\ConversationController::class, 'report']);

        // Disputes
        Route::get('/disputes', [\App\Http\Controllers\Api\DisputeController::class, 'index']);
        Route::post('/disputes', [\App\Http\Controllers\Api\DisputeController::class, 'store']);
        Route::get('/disputes/{id}', [\App\Http\Controllers\Api\DisputeController::class, 'show']);
        Route::post('/disputes/{id}/messages', [\App\Http\Controllers\Api\DisputeController::class, 'addMessage']);
        Route::post('/disputes/{id}/respond', [\App\Http\Controllers\Api\DisputeController::class, 'sellerRespond']);

        // Seller store & products
        Route::get('/seller/store', [\App\Http\Controllers\Api\SellerStoreController::class, 'show']);
        Route::post('/seller/store', [\App\Http\Controllers\Api\SellerStoreController::class, 'store']);
        Route::put('/seller/store', [\App\Http\Controllers\Api\SellerStoreController::class, 'update']);
        // POST accepted for update so FormData is parsed (PHP does not parse multipart body on PUT)
        Route::post('/seller/store/update', [\App\Http\Controllers\Api\SellerStoreController::class, 'update']);
        Route::post('/seller/kyc', [\App\Http\Controllers\Api\SellerStoreController::class, 'uploadKyc']);
        Route::post('/seller/vacation-mode', [\App\Http\Controllers\Api\SellerStoreController::class, 'vacationMode']);
        Route::get('/seller/products', [\App\Http\Controllers\Api\SellerProductController::class, 'index']);
        Route::get('/seller/products/export', [\App\Http\Controllers\Api\SellerProductController::class, 'export']);
        Route::post('/seller/products/import', [\App\Http\Controllers\Api\SellerProductController::class, 'import']);
        Route::get('/seller/products/promotion-eligibility', [\App\Http\Controllers\Api\SellerProductController::class, 'promotionEligibility']);
        Route::post('/seller/products', [\App\Http\Controllers\Api\SellerProductController::class, 'createProduct']);
        Route::post('/seller/products/{id}/duplicate', [\App\Http\Controllers\Api\SellerProductController::class, 'duplicate']);
        Route::post('/seller/products/{id}/publish', [\App\Http\Controllers\Api\SellerProductController::class, 'publish']);
        Route::get('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'show']);
        Route::put('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'update']);
        // POST accepted for update so multipart/form-data is parsed (PHP does not parse multipart body on PUT)
        Route::post('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'update']);
        Route::delete('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'destroy']);
        Route::post('/seller/products/{id}/restore', [\App\Http\Controllers\Api\SellerProductController::class, 'restore']);
        Route::get('/seller/products/{productId}/variants', [\App\Http\Controllers\Api\ProductVariantController::class, 'index']);
        Route::post('/seller/products/{productId}/variants', [\App\Http\Controllers\Api\ProductVariantController::class, 'store']);
        Route::post('/seller/products/{productId}/variants/bulk', [\App\Http\Controllers\Api\ProductVariantController::class, 'storeBulk']);
        Route::put('/seller/products/{productId}/variants/{variantId}', [\App\Http\Controllers\Api\ProductVariantController::class, 'update']);
        Route::post('/seller/products/{productId}/variants/{variantId}', [\App\Http\Controllers\Api\ProductVariantController::class, 'update']);
        Route::delete('/seller/products/{productId}/variants/{variantId}', [\App\Http\Controllers\Api\ProductVariantController::class, 'destroy']);
        Route::get('/seller/orders', [\App\Http\Controllers\Api\SellerOrderController::class, 'index']);
        Route::get('/seller/orders/{order}', [\App\Http\Controllers\Api\SellerOrderController::class, 'show']);
        Route::post('/seller/orders/{order}/retry-courier', [\App\Http\Controllers\Api\SellerOrderController::class, 'retryCourier']);
        Route::post('/seller/orders/{order}/approve', [\App\Http\Controllers\Api\SellerOrderController::class, 'approve']);
        Route::post('/seller/orders/{order}/reject', [\App\Http\Controllers\Api\SellerOrderController::class, 'reject']);
        Route::post('/seller/orders/{order}/approve-cancellation', [\App\Http\Controllers\Api\SellerOrderController::class, 'approveCancellation']);
        Route::post('/seller/orders/{order}/reject-cancellation', [\App\Http\Controllers\Api\SellerOrderController::class, 'rejectCancellation']);
        Route::get('/seller/shipments', [\App\Http\Controllers\Api\ShipmentController::class, 'index']);
        Route::post('/seller/orders/{orderId}/tracking', [\App\Http\Controllers\Api\ShipmentController::class, 'addTracking']);
        Route::put('/seller/shipments/{shipmentId}/status', [\App\Http\Controllers\Api\ShipmentController::class, 'updateStatus']);
        Route::put('/seller/products/{productId}/stock', [\App\Http\Controllers\Api\InventoryController::class, 'updateStock']);
        Route::put('/seller/products/{productId}/low-stock-threshold', [\App\Http\Controllers\Api\InventoryController::class, 'updateLowStockThreshold']);
        Route::get('/seller/products/{productId}/stock-history', [\App\Http\Controllers\Api\InventoryController::class, 'history']);
        Route::get('/seller/inventory/low-stock', [\App\Http\Controllers\Api\InventoryController::class, 'lowStock']);
        Route::get('/seller/inventory/out-of-stock', [\App\Http\Controllers\Api\InventoryController::class, 'outOfStock']);
        Route::get('/seller/flash-deals', [\App\Http\Controllers\Api\SellerFlashDealController::class, 'index']);
        Route::post('/seller/flash-deals', [\App\Http\Controllers\Api\SellerFlashDealController::class, 'store']);
        Route::get('/seller/flash-deals/{id}', [\App\Http\Controllers\Api\SellerFlashDealController::class, 'show']);
        Route::put('/seller/flash-deals/{id}', [\App\Http\Controllers\Api\SellerFlashDealController::class, 'update']);
        Route::delete('/seller/flash-deals/{id}', [\App\Http\Controllers\Api\SellerFlashDealController::class, 'destroy']);
        Route::post('/seller/flash-deals-bulk', [\App\Http\Controllers\Api\SellerProductController::class, 'addFlashDeals']);
        Route::post('/seller/new-arrivals', [\App\Http\Controllers\Api\SellerProductController::class, 'addNewArrivals']);
        Route::get('/payouts/earnings', [\App\Http\Controllers\Api\PayoutController::class, 'earnings']);
        Route::post('/payouts/request', [\App\Http\Controllers\Api\PayoutController::class, 'request']);
        Route::get('/payouts/history', [\App\Http\Controllers\Api\PayoutController::class, 'history']);

        // Private seller KYC
        Route::get('/private-seller/status', [\App\Http\Controllers\Api\PrivateSellerController::class, 'status']);
        Route::post('/private-seller/apply', [\App\Http\Controllers\Api\PrivateSellerController::class, 'apply']);

        // Private listings (customer-as-seller)
        Route::get('/private-listings/config', [\App\Http\Controllers\Api\PrivateListingController::class, 'config']);
        Route::get('/private-listings/orders', [\App\Http\Controllers\Api\PrivateListingController::class, 'orders']);
        Route::get('/private-listings/orders/{order}', [\App\Http\Controllers\Api\PrivateListingController::class, 'showOrder']);
        Route::post('/private-listings/orders/{order}/approve', [\App\Http\Controllers\Api\SellerOrderController::class, 'approve']);
        Route::post('/private-listings/orders/{order}/reject', [\App\Http\Controllers\Api\SellerOrderController::class, 'reject']);
        Route::post('/private-listings/orders/{order}/approve-cancellation', [\App\Http\Controllers\Api\SellerOrderController::class, 'approveCancellation']);
        Route::post('/private-listings/orders/{order}/reject-cancellation', [\App\Http\Controllers\Api\SellerOrderController::class, 'rejectCancellation']);
        Route::get('/private-listings', [\App\Http\Controllers\Api\PrivateListingController::class, 'index']);
        Route::post('/private-listings', [\App\Http\Controllers\Api\PrivateListingController::class, 'store']);
        Route::post('/private-listings/{product}/activate', [\App\Http\Controllers\Api\PrivateListingController::class, 'activate']);
        Route::post('/private-listings/{product}/pay-activate', [\App\Http\Controllers\Api\PrivateListingController::class, 'payToActivate']);
        Route::put('/private-listings/{product}', [\App\Http\Controllers\Api\PrivateListingController::class, 'update']);
        Route::delete('/private-listings/{product}', [\App\Http\Controllers\Api\PrivateListingController::class, 'destroy']);
        Route::post('/private-listings/{id}/restore', [\App\Http\Controllers\Api\PrivateListingController::class, 'restore']);

        // Admin
        Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->prefix('admin')->group(function () {
            Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend']);
            Route::post('/users/{user}/unsuspend', [UserManagementController::class, 'unsuspend']);
            Route::post('/users/{user}/ban', [UserManagementController::class, 'ban']);
            Route::post('/users/{user}/unban', [UserManagementController::class, 'unban']);
        });
        }); // email.verified
    });
});
