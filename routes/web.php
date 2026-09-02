<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return redirect('/admin');
});

// Serve storage files (fallback when symlink missing or for consistent serving)
Route::get('storage/{path}', function (string $path) {
    $fullPath = Storage::disk('public')->path($path);
    if (!Storage::disk('public')->exists($path) || !is_file($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath, [
        'Content-Type' => File::mimeType($fullPath),
    ]);
})->where('path', '.*')->name('storage.serve');

// Admin panel (Blade, session-based)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('forgot-password', [\App\Http\Controllers\Admin\ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Admin\ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

    Route::middleware(['admin.web', 'admin.permission'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        Route::get('users/export', [\App\Http\Controllers\Admin\UserController::class, 'export'])->name('users.export');
        Route::get('users/{user}', [\App\Http\Controllers\Admin\CustomerPageController::class, 'show'])->name('users.show');
        Route::get('users/{user}/profile', [\App\Http\Controllers\Admin\CustomerPageController::class, 'profile'])->name('users.profile');
        Route::get('users/{user}/addresses', [\App\Http\Controllers\Admin\CustomerPageController::class, 'addresses'])->name('users.addresses');
        Route::get('users/{user}/alerts', [\App\Http\Controllers\Admin\CustomerPageController::class, 'alerts'])->name('users.alerts');
        Route::get('users/{user}/wallet', [\App\Http\Controllers\Admin\CustomerPageController::class, 'wallet'])->name('users.wallet');
        Route::get('users/{user}/promotions', [\App\Http\Controllers\Admin\CustomerPageController::class, 'promotions'])->name('users.promotions');
        Route::get('users/{user}/free-listing', [\App\Http\Controllers\Admin\CustomerPageController::class, 'freeListing'])->name('users.free-listing');
        Route::get('users/{user}/transactions', [\App\Http\Controllers\Admin\CustomerPageController::class, 'transactions'])->name('users.transactions');
        Route::get('users/{user}/orders', [\App\Http\Controllers\Admin\CustomerPageController::class, 'orders'])->name('users.orders');
        Route::get('users/{user}/account-actions', [\App\Http\Controllers\Admin\CustomerPageController::class, 'accountActions'])->name('users.account-actions');
        Route::get('users/{user}/listings', [\App\Http\Controllers\Admin\CustomerListingController::class, 'index'])->name('users.listings.index');
        Route::get('users/{user}/listings/create', [\App\Http\Controllers\Admin\CustomerListingController::class, 'create'])->name('users.listings.create');
        Route::post('users/{user}/listings', [\App\Http\Controllers\Admin\CustomerListingController::class, 'store'])->name('users.listings.store');
        Route::get('users/{user}/listings/{listing}/edit', [\App\Http\Controllers\Admin\CustomerListingController::class, 'edit'])->name('users.listings.edit');
        Route::put('users/{user}/listings/{listing}', [\App\Http\Controllers\Admin\CustomerListingController::class, 'update'])->name('users.listings.update');
        Route::delete('users/{user}/listings/{listing}', [\App\Http\Controllers\Admin\CustomerListingController::class, 'destroy'])->name('users.listings.destroy');
        Route::post('users/{user}/listings/{listingId}/restore', [\App\Http\Controllers\Admin\CustomerListingController::class, 'restore'])->name('users.listings.restore');
        Route::post('users/{user}/listings/{listing}/status', [\App\Http\Controllers\Admin\CustomerListingController::class, 'updateStatus'])->name('users.listings.status');
        Route::put('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
        Route::put('users/{user}/addresses/{address}', [\App\Http\Controllers\Admin\UserController::class, 'updateAddress'])->name('users.addresses.update');
        Route::post('users/{user}/addresses', [\App\Http\Controllers\Admin\UserController::class, 'storeAddress'])->name('users.addresses.store');
        Route::post('users/{user}/notifications', [\App\Http\Controllers\Admin\UserController::class, 'updateNotifications'])->name('users.notifications.update');
        Route::post('users/{user}/listing-limit', [\App\Http\Controllers\Admin\UserController::class, 'updateListingLimit'])->name('users.listing-limit');
        Route::post('users/{user}/suspend', [\App\Http\Controllers\Admin\UserController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/unsuspend', [\App\Http\Controllers\Admin\UserController::class, 'unsuspend'])->name('users.unsuspend');
        Route::post('users/{user}/ban', [\App\Http\Controllers\Admin\UserController::class, 'ban'])->name('users.ban');
        Route::post('users/{user}/unban', [\App\Http\Controllers\Admin\UserController::class, 'unban'])->name('users.unban');
        Route::post('users/{user}/wallet-adjust', [\App\Http\Controllers\Admin\AdminUserActionController::class, 'adjustWallet'])->name('users.wallet-adjust');
        Route::post('users/{user}/assign-promotion', [\App\Http\Controllers\Admin\AdminUserActionController::class, 'assignPromotion'])->name('users.assign-promotion');

        Route::get('customer-sellers', [\App\Http\Controllers\Admin\CustomerSellerController::class, 'index'])->name('customer-sellers.index');

        Route::get('people-settings', [\App\Http\Controllers\Admin\PeopleSettingsController::class, 'index'])->name('people-settings.index');
        Route::post('people-settings/customer', [\App\Http\Controllers\Admin\PeopleSettingsController::class, 'updateCustomer'])->name('people-settings.customer.update');
        Route::post('people-settings/seller', [\App\Http\Controllers\Admin\PeopleSettingsController::class, 'updateSeller'])->name('people-settings.seller.update');
        Route::post('people-settings/private-seller', [\App\Http\Controllers\Admin\PeopleSettingsController::class, 'updatePrivateSeller'])->name('people-settings.private-seller.update');

        Route::get('sellers', [\App\Http\Controllers\Admin\SellerController::class, 'index'])->name('sellers.index');
        Route::post('sellers', [\App\Http\Controllers\Admin\SellerController::class, 'store'])->name('sellers.store');
        Route::get('sellers/export', [\App\Http\Controllers\Admin\SellerController::class, 'export'])->name('sellers.export');
        Route::get('sellers/{user}', [\App\Http\Controllers\Admin\SellerPageController::class, 'show'])->name('sellers.show');
        Route::get('sellers/{user}/profile', [\App\Http\Controllers\Admin\SellerPageController::class, 'profile'])->name('sellers.profile');
        Route::get('sellers/{user}/kyc', [\App\Http\Controllers\Admin\SellerPageController::class, 'kyc'])->name('sellers.kyc');
        Route::get('sellers/{user}/storefront', [\App\Http\Controllers\Admin\SellerPageController::class, 'storePage'])->name('sellers.storefront');
        Route::get('sellers/{user}/addresses', [\App\Http\Controllers\Admin\SellerPageController::class, 'addresses'])->name('sellers.addresses');
        Route::post('sellers/{user}/addresses', [\App\Http\Controllers\Admin\SellerController::class, 'storeAddress'])->name('sellers.addresses.store');
        Route::put('sellers/{user}/addresses/{address}', [\App\Http\Controllers\Admin\SellerController::class, 'updateAddress'])->name('sellers.addresses.update');
        Route::get('sellers/{user}/alerts', [\App\Http\Controllers\Admin\SellerPageController::class, 'alerts'])->name('sellers.alerts');
        Route::post('sellers/{user}/notifications', [\App\Http\Controllers\Admin\SellerController::class, 'updateNotifications'])->name('sellers.notifications.update');
        Route::get('sellers/{user}/wallet', [\App\Http\Controllers\Admin\SellerPageController::class, 'wallet'])->name('sellers.wallet');
        Route::get('sellers/{user}/promotions', [\App\Http\Controllers\Admin\SellerPageController::class, 'promotions'])->name('sellers.promotions');
        Route::get('sellers/{user}/transactions', [\App\Http\Controllers\Admin\SellerPageController::class, 'transactions'])->name('sellers.transactions');
        Route::get('sellers/{user}/orders', [\App\Http\Controllers\Admin\SellerPageController::class, 'orders'])->name('sellers.orders');
        Route::get('sellers/{user}/account-actions', [\App\Http\Controllers\Admin\SellerPageController::class, 'accountActions'])->name('sellers.account-actions');
        Route::get('sellers/{user}/products', [\App\Http\Controllers\Admin\SellerProductController::class, 'index'])->name('sellers.products.index');
        Route::get('sellers/{user}/products/create', [\App\Http\Controllers\Admin\SellerProductController::class, 'create'])->name('sellers.products.create');
        Route::post('sellers/{user}/products', [\App\Http\Controllers\Admin\SellerProductController::class, 'store'])->name('sellers.products.store');
        Route::get('sellers/{user}/products/{product}/edit', [\App\Http\Controllers\Admin\SellerProductController::class, 'edit'])->name('sellers.products.edit');
        Route::put('sellers/{user}/products/{product}', [\App\Http\Controllers\Admin\SellerProductController::class, 'update'])->name('sellers.products.update');
        Route::delete('sellers/{user}/products/{product}', [\App\Http\Controllers\Admin\SellerProductController::class, 'destroy'])->name('sellers.products.destroy');
        Route::post('sellers/{user}/products/{product}/status', [\App\Http\Controllers\Admin\SellerProductController::class, 'updateStatus'])->name('sellers.products.status');
        Route::post('sellers/{user}/wallet-adjust', [\App\Http\Controllers\Admin\AdminUserActionController::class, 'adjustWallet'])->name('sellers.wallet-adjust');
        Route::post('sellers/{user}/assign-promotion', [\App\Http\Controllers\Admin\AdminUserActionController::class, 'assignPromotion'])->name('sellers.assign-promotion');
        Route::put('sellers/{user}', [\App\Http\Controllers\Admin\SellerController::class, 'update'])->name('sellers.update');
        Route::post('sellers/{user}/approve', [\App\Http\Controllers\Admin\SellerController::class, 'approve'])->name('sellers.approve');
        Route::post('sellers/{user}/reject', [\App\Http\Controllers\Admin\SellerController::class, 'reject'])->name('sellers.reject');
        Route::post('sellers/{user}/verify-kyc', [\App\Http\Controllers\Admin\SellerController::class, 'verifyKyc'])->name('sellers.verify-kyc');
        Route::post('sellers/{user}/reject-kyc', [\App\Http\Controllers\Admin\SellerController::class, 'rejectKyc'])->name('sellers.reject-kyc');
        Route::post('sellers/{user}/kyc-status', [\App\Http\Controllers\Admin\SellerController::class, 'updateKycStatus'])->name('sellers.kyc-status');
        Route::post('sellers/{user}/suspend', [\App\Http\Controllers\Admin\SellerController::class, 'suspend'])->name('sellers.suspend');
        Route::post('sellers/{user}/unsuspend', [\App\Http\Controllers\Admin\SellerController::class, 'unsuspend'])->name('sellers.unsuspend');
        Route::post('sellers/{user}/ban', [\App\Http\Controllers\Admin\SellerController::class, 'ban'])->name('sellers.ban');
        Route::post('sellers/{user}/unban', [\App\Http\Controllers\Admin\SellerController::class, 'unban'])->name('sellers.unban');

        // Categories
        Route::get('categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/export', [\App\Http\Controllers\Admin\CategoryController::class, 'export'])->name('categories.export');
        Route::get('categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('categories.edit');
        Route::get('categories/{category}/json', [\App\Http\Controllers\Admin\CategoryController::class, 'json'])->name('categories.json');
        Route::put('categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::post('categories/{category}/toggle-featured', [\App\Http\Controllers\Admin\CategoryController::class, 'toggleFeatured'])->name('categories.toggle-featured');
        Route::delete('categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Brands
        Route::get('brands', [\App\Http\Controllers\Admin\BrandController::class, 'index'])->name('brands.index');
        Route::get('brands/export', [\App\Http\Controllers\Admin\BrandController::class, 'export'])->name('brands.export');
        Route::get('brands/create', [\App\Http\Controllers\Admin\BrandController::class, 'create'])->name('brands.create');
        Route::post('brands', [\App\Http\Controllers\Admin\BrandController::class, 'store'])->name('brands.store');
        Route::get('brands/{brand}/edit', [\App\Http\Controllers\Admin\BrandController::class, 'edit'])->name('brands.edit');
        Route::get('brands/{brand}/json', [\App\Http\Controllers\Admin\BrandController::class, 'json'])->name('brands.json');
        Route::put('brands/{brand}', [\App\Http\Controllers\Admin\BrandController::class, 'update'])->name('brands.update');
        Route::delete('brands/{brand}', [\App\Http\Controllers\Admin\BrandController::class, 'destroy'])->name('brands.destroy');

        // Stores
        Route::get('stores', [\App\Http\Controllers\Admin\StoreController::class, 'index'])->name('stores.index');
        Route::get('stores/export', [\App\Http\Controllers\Admin\StoreController::class, 'export'])->name('stores.export');
        Route::get('stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'show'])->name('stores.show');
        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('email-settings', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'index'])->name('email-settings.index');
        Route::get('email-settings/smtp', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'smtp'])->name('email-settings.smtp');
        Route::post('email-settings/smtp', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'updateSmtp'])->name('email-settings.smtp.update');
        Route::get('email-settings/events', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'events'])->name('email-settings.events');
        Route::post('email-settings/events', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'updateEvents'])->name('email-settings.events.update');
        Route::post('email-settings/test-email', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'testEmail'])->name('email-settings.test-email');
        Route::get('email-templates', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('email-templates/{email_template}/edit', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('email-templates/{email_template}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('countries', [\App\Http\Controllers\Admin\LocationController::class, 'countries'])->name('countries.index');
            Route::post('countries', [\App\Http\Controllers\Admin\LocationController::class, 'storeCountry'])->name('countries.store');
            Route::put('countries/{country}', [\App\Http\Controllers\Admin\LocationController::class, 'updateCountry'])->name('countries.update');
            Route::delete('countries/{country}', [\App\Http\Controllers\Admin\LocationController::class, 'destroyCountry'])->name('countries.destroy');
            Route::get('provinces', [\App\Http\Controllers\Admin\LocationController::class, 'provinces'])->name('provinces.index');
            Route::post('provinces', [\App\Http\Controllers\Admin\LocationController::class, 'storeProvince'])->name('provinces.store');
            Route::put('provinces/{province}', [\App\Http\Controllers\Admin\LocationController::class, 'updateProvince'])->name('provinces.update');
            Route::delete('provinces/{province}', [\App\Http\Controllers\Admin\LocationController::class, 'destroyProvince'])->name('provinces.destroy');
            Route::get('cities', [\App\Http\Controllers\Admin\LocationController::class, 'cities'])->name('cities.index');
            Route::post('cities', [\App\Http\Controllers\Admin\LocationController::class, 'storeCity'])->name('cities.store');
            Route::put('cities/{city}', [\App\Http\Controllers\Admin\LocationController::class, 'updateCity'])->name('cities.update');
            Route::delete('cities/{city}', [\App\Http\Controllers\Admin\LocationController::class, 'destroyCity'])->name('cities.destroy');
            Route::post('cities/import-leopards', [\App\Http\Controllers\Admin\LocationController::class, 'importLeopardsCities'])->name('cities.import-leopards');
            Route::post('cities/sync-leopards-ids', [\App\Http\Controllers\Admin\LocationController::class, 'syncLeopardsIds'])->name('cities.sync-leopards-ids');
        });
        Route::get('sitemap', [\App\Http\Controllers\Admin\SitemapController::class, 'index'])->name('sitemap.index');
        Route::put('sitemap/config', [\App\Http\Controllers\Admin\SitemapController::class, 'updateConfig'])->name('sitemap.update-config');
        Route::put('sitemap/overrides', [\App\Http\Controllers\Admin\SitemapController::class, 'updateOverride'])->name('sitemap.update-override');
        Route::post('sitemap/static', [\App\Http\Controllers\Admin\SitemapController::class, 'storeStatic'])->name('sitemap.static.store');
        Route::put('sitemap/static/{sitemapStaticUrl}', [\App\Http\Controllers\Admin\SitemapController::class, 'updateStatic'])->name('sitemap.static.update');
        Route::delete('sitemap/static/{sitemapStaticUrl}', [\App\Http\Controllers\Admin\SitemapController::class, 'destroyStatic'])->name('sitemap.static.destroy');
        Route::get('settings/private-sellers', [\App\Http\Controllers\Admin\PrivateSellerSettingsController::class, 'index'])->name('private-seller-settings.index');
        Route::post('settings/private-sellers', [\App\Http\Controllers\Admin\PrivateSellerSettingsController::class, 'update'])->name('private-seller-settings.update');

        // Customer (customer-as-seller) listing and payout defaults
        Route::get('customer-settings', [\App\Http\Controllers\Admin\CustomerSettingsController::class, 'index'])->name('customer-settings.index');
        Route::post('customer-settings', [\App\Http\Controllers\Admin\CustomerSettingsController::class, 'update'])->name('customer-settings.update');

        // Business seller settings
        Route::get('seller-settings', [\App\Http\Controllers\Admin\SellerSettingsController::class, 'index'])->name('seller-settings.index');
        Route::post('seller-settings', [\App\Http\Controllers\Admin\SellerSettingsController::class, 'update'])->name('seller-settings.update');

        Route::get('recaptcha-settings', [\App\Http\Controllers\Admin\RecaptchaSettingsController::class, 'index'])->name('recaptcha-settings.index');
        Route::post('recaptcha-settings', [\App\Http\Controllers\Admin\RecaptchaSettingsController::class, 'update'])->name('recaptcha-settings.update');

        Route::get('wachat-settings', [\App\Http\Controllers\Admin\WachatSettingsController::class, 'index'])->name('wachat-settings.index');
        Route::post('wachat-settings', [\App\Http\Controllers\Admin\WachatSettingsController::class, 'update'])->name('wachat-settings.update');
        Route::get('wachat-settings/events', [\App\Http\Controllers\Admin\WachatSettingsController::class, 'events'])->name('wachat-settings.events');
        Route::post('wachat-settings/events', [\App\Http\Controllers\Admin\WachatSettingsController::class, 'updateEvents'])->name('wachat-settings.events.update');
        Route::post('wachat-settings/test', [\App\Http\Controllers\Admin\WachatSettingsController::class, 'testSend'])->name('wachat-settings.test');
        Route::get('whatsapp-templates', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'index'])->name('whatsapp-templates.index');
        Route::get('whatsapp-templates/{whatsapp_template}/edit', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'edit'])->name('whatsapp-templates.edit');
        Route::put('whatsapp-templates/{whatsapp_template}', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'update'])->name('whatsapp-templates.update');

        Route::get('activities', [\App\Http\Controllers\Admin\ActivityController::class, 'index'])->name('activities.index');

        Route::get('expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('expenses/create', [\App\Http\Controllers\Admin\ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('expenses', [\App\Http\Controllers\Admin\ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'show'])->name('expenses.show');
        Route::get('expenses/{expense}/edit', [\App\Http\Controllers\Admin\ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // Courier — credentials and tracking connection tests
        Route::get('courier', [\App\Http\Controllers\Admin\CourierSettingsController::class, 'index'])->name('courier.index');
        Route::patch('courier/{provider}/enabled', [\App\Http\Controllers\Admin\CourierSettingsController::class, 'updateEnabled'])->name('courier.enabled');
        Route::post('courier/{provider}/logo', [\App\Http\Controllers\Admin\CourierSettingsController::class, 'uploadLogo'])->name('courier.logo');
        Route::delete('courier/{provider}/logo', [\App\Http\Controllers\Admin\CourierSettingsController::class, 'removeLogo'])->name('courier.logo.remove');

        // Payment methods — one page per gateway plus COD
        Route::get('payment-methods', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'index'])->name('payment-methods.index');
        Route::get('payment-methods/cod', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'cod'])->name('payment-methods.cod');
        Route::post('payment-methods/cod', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'updateCod'])->name('payment-methods.cod.update');
        Route::get('payment-methods/jazzcash', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'jazzcash'])->name('payment-methods.jazzcash');
        Route::post('payment-methods/jazzcash', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'updateJazzcash'])->name('payment-methods.jazzcash.update');
        Route::post('payment-methods/jazzcash/test', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'testJazzcash'])->name('payment-methods.jazzcash.test');
        Route::get('payment-methods/stripe', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'stripe'])->name('payment-methods.stripe');
        Route::post('payment-methods/stripe', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'updateStripe'])->name('payment-methods.stripe.update');
        Route::get('payment-methods/paypal', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'paypal'])->name('payment-methods.paypal');
        Route::post('payment-methods/paypal', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'updatePaypal'])->name('payment-methods.paypal.update');
        Route::get('payment-methods/easypaisa', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'easypaisa'])->name('payment-methods.easypaisa');
        Route::post('payment-methods/easypaisa', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'updateEasypaisa'])->name('payment-methods.easypaisa.update');
        Route::post('payment-methods/{method}/logo', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'uploadLogo'])->name('payment-methods.logo');
        Route::delete('payment-methods/{method}/logo', [\App\Http\Controllers\Admin\PaymentMethodSettingsController::class, 'removeLogo'])->name('payment-methods.logo.remove');

        // Commission — legacy URLs redirect; advanced rules at commissions.*
        Route::get('commission', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'index'])->name('commission-settings.index');
        Route::get('commission/customer-order', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'customerOrder'])->name('commission-settings.customer-order');
        Route::post('commission/customer-order', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'updateCustomerOrder'])->name('commission-settings.customer-order.update');
        Route::get('commission/private-seller', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'privateSeller'])->name('commission-settings.private-seller');
        Route::post('commission/private-seller', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'updatePrivateSeller'])->name('commission-settings.private-seller.update');
        Route::get('commission/seller', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'seller'])->name('commission-settings.seller');
        Route::post('commission/seller', [\App\Http\Controllers\Admin\CommissionSettingsController::class, 'updateSeller'])->name('commission-settings.seller.update');

        // Legacy marketplace fees URL — marketplace fee is on Private Seller Setting
        Route::get('marketplace-fees', fn () => redirect()->route('admin.people-settings.index', ['tab' => 'customer']))->name('marketplace-fees.index');

        Route::get('commissions', [\App\Http\Controllers\Admin\CommissionController::class, 'index'])->name('commissions.index');
        Route::get('commissions/create', [\App\Http\Controllers\Admin\CommissionController::class, 'create'])->name('commissions.create');
        Route::post('commissions', [\App\Http\Controllers\Admin\CommissionController::class, 'store'])->name('commissions.store');
        Route::delete('commissions/{commission}', [\App\Http\Controllers\Admin\CommissionController::class, 'destroy'])->name('commissions.destroy');

        Route::get('orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/mark-payment-paid', [\App\Http\Controllers\Admin\OrderController::class, 'markPaymentPaid'])->name('orders.mark-payment-paid');
        Route::get('transactions', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/{transaction}', [\App\Http\Controllers\Admin\TransactionController::class, 'show'])->name('transactions.show');
        Route::get('track-orders', [\App\Http\Controllers\Admin\TrackOrderController::class, 'index'])->name('track-orders.index');
        Route::get('payouts', [\App\Http\Controllers\Admin\PayoutController::class, 'index'])->name('payouts.index');
        Route::post('payouts/config', [\App\Http\Controllers\Admin\PayoutController::class, 'updateConfig'])->name('payouts.update-config');
        Route::get('payouts/{payout}', [\App\Http\Controllers\Admin\PayoutController::class, 'show'])->name('payouts.show');
        Route::post('payouts/{payout}/approve', [\App\Http\Controllers\Admin\PayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('payouts/{payout}/reject', [\App\Http\Controllers\Admin\PayoutController::class, 'reject'])->name('payouts.reject');
        Route::post('payouts/{payout}/mark-paid', [\App\Http\Controllers\Admin\PayoutController::class, 'markPaid'])->name('payouts.mark-paid');
        Route::get('products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
        Route::get('products/export', [\App\Http\Controllers\Admin\ProductController::class, 'export'])->name('products.export');
        Route::get('products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'show'])->name('products.show');
        Route::put('products/{product}/seo', [\App\Http\Controllers\Admin\ProductController::class, 'updateSeo'])->name('products.update-seo');
        Route::put('products/{product}/status', [\App\Http\Controllers\Admin\ProductController::class, 'updateStatus'])->name('products.update-status');
        Route::post('products/{product}/approve', [\App\Http\Controllers\Admin\ProductController::class, 'approve'])->name('products.approve');
        Route::post('products/{product}/reject', [\App\Http\Controllers\Admin\ProductController::class, 'reject'])->name('products.reject');

        Route::get('home-featured', [\App\Http\Controllers\Admin\HomeFeaturedController::class, 'index'])->name('home-featured.index');
        Route::post('home-featured', [\App\Http\Controllers\Admin\HomeFeaturedController::class, 'update'])->name('home-featured.update');

        Route::get('coupons', [\App\Http\Controllers\Admin\CouponController::class, 'index'])->name('coupons.index');
        Route::get('coupons/create', [\App\Http\Controllers\Admin\CouponController::class, 'create'])->name('coupons.create');
        Route::post('coupons', [\App\Http\Controllers\Admin\CouponController::class, 'store'])->name('coupons.store');
        Route::get('coupons/{coupon}/edit', [\App\Http\Controllers\Admin\CouponController::class, 'edit'])->name('coupons.edit');
        Route::put('coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'update'])->name('coupons.update');
        Route::delete('coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'destroy'])->name('coupons.destroy');
        Route::get('promotion-packages', [\App\Http\Controllers\Admin\PromotionPackageController::class, 'index'])->name('promotion-packages.index');
        Route::get('promotion-packages/create', [\App\Http\Controllers\Admin\PromotionPackageController::class, 'create'])->name('promotion-packages.create');
        Route::post('promotion-packages', [\App\Http\Controllers\Admin\PromotionPackageController::class, 'store'])->name('promotion-packages.store');
        Route::get('promotion-packages/{promotionPackage}/edit', [\App\Http\Controllers\Admin\PromotionPackageController::class, 'edit'])->name('promotion-packages.edit');
        Route::put('promotion-packages/{promotionPackage}', [\App\Http\Controllers\Admin\PromotionPackageController::class, 'update'])->name('promotion-packages.update');
        Route::get('reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::post('reviews/{review}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('reviews/{review}/reject', [\App\Http\Controllers\Admin\ReviewController::class, 'reject'])->name('reviews.reject');
        Route::get('conversations', [\App\Http\Controllers\Admin\ConversationController::class, 'index'])->name('conversations.index');
        Route::get('conversations/reported', [\App\Http\Controllers\Admin\ConversationController::class, 'reported'])->name('conversations.reported');
        Route::get('conversations/{conversation}', [\App\Http\Controllers\Admin\ConversationController::class, 'show'])->name('conversations.show');
        Route::get('disputes', [\App\Http\Controllers\Admin\DisputeController::class, 'index'])->name('disputes.index');
        Route::get('disputes/{dispute}', [\App\Http\Controllers\Admin\DisputeController::class, 'show'])->name('disputes.show');
        Route::post('disputes/{dispute}/arbitrate', [\App\Http\Controllers\Admin\DisputeController::class, 'arbitrate'])->name('disputes.arbitrate');
        Route::post('disputes/{dispute}/message', [\App\Http\Controllers\Admin\DisputeController::class, 'addMessage'])->name('disputes.add-message');
        Route::post('disputes/{dispute}/refund', [\App\Http\Controllers\Admin\DisputeController::class, 'processRefund'])->name('disputes.process-refund');
        Route::get('refunds', [\App\Http\Controllers\Admin\RefundController::class, 'index'])->name('refunds.index');
        Route::get('refunds/{refund}', [\App\Http\Controllers\Admin\RefundController::class, 'show'])->name('refunds.show');
        Route::post('refunds/{refund}/process', [\App\Http\Controllers\Admin\RefundController::class, 'process'])->name('refunds.process');
        Route::get('roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [\App\Http\Controllers\Admin\RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');
        Route::get('roles/permissions-matrix', [\App\Http\Controllers\Admin\RoleController::class, 'permissionsMatrix'])->name('roles.permissions-matrix');
        Route::get('sub-admins', [\App\Http\Controllers\Admin\SubAdminController::class, 'index'])->name('sub-admins.index');
        Route::get('sub-admins/create', [\App\Http\Controllers\Admin\SubAdminController::class, 'create'])->name('sub-admins.create');
        Route::post('sub-admins', [\App\Http\Controllers\Admin\SubAdminController::class, 'store'])->name('sub-admins.store');
        Route::get('sub-admins/{user}/edit', [\App\Http\Controllers\Admin\SubAdminController::class, 'edit'])->name('sub-admins.edit');
        Route::put('sub-admins/{user}', [\App\Http\Controllers\Admin\SubAdminController::class, 'update'])->name('sub-admins.update');
        Route::get('private-sellers', [\App\Http\Controllers\Admin\PrivateSellerController::class, 'index'])->name('private-sellers.index');
        Route::post('private-sellers/{user}/approve-kyc', [\App\Http\Controllers\Admin\PrivateSellerController::class, 'approveKyc'])->name('private-sellers.approve-kyc');
        Route::post('private-sellers/{user}/reject-kyc', [\App\Http\Controllers\Admin\PrivateSellerController::class, 'rejectKyc'])->name('private-sellers.reject-kyc');
        Route::get('abuse-safety', [\App\Http\Controllers\Admin\AbuseSafetyController::class, 'index'])->name('abuse-safety.index');
        Route::post('abuse-safety', [\App\Http\Controllers\Admin\AbuseSafetyController::class, 'update'])->name('abuse-safety.update');
        Route::get('flagged-items', [\App\Http\Controllers\Admin\AbuseSafetyController::class, 'flaggedItems'])->name('abuse-safety.flagged');
        Route::get('pages', [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('pages.index');
        Route::post('pages/upload-image', [\App\Http\Controllers\Admin\PageController::class, 'uploadImage'])->name('pages.upload-image');
        Route::get('pages/{page}/edit', [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('pages.edit');
        Route::put('pages/{page}', [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('pages.update');
        Route::post('pages/{page}/sections', [\App\Http\Controllers\Admin\PageController::class, 'saveSection'])->name('pages.sections.save');
        Route::post('pages/{page}/sections/{index}/delete', [\App\Http\Controllers\Admin\PageController::class, 'deleteSection'])->whereNumber('index')->name('pages.sections.delete');
        Route::post('pages/{page}/sections/{index}/move/{direction}', [\App\Http\Controllers\Admin\PageController::class, 'moveSection'])->whereNumber('index')->whereIn('direction', ['up', 'down'])->name('pages.sections.move');
        Route::resource('testimonials', \App\Http\Controllers\Admin\TestimonialController::class)->except(['show']);
        Route::resource('faqs', \App\Http\Controllers\Admin\FaqController::class)->except(['show']);
        Route::resource('blogs', \App\Http\Controllers\Admin\BlogController::class)->except(['show']);
        Route::get('contact-submissions', [\App\Http\Controllers\Admin\ContactSubmissionController::class, 'index'])->name('contact-submissions.index');
        Route::get('contact-submissions/{contactSubmission}', [\App\Http\Controllers\Admin\ContactSubmissionController::class, 'show'])->name('contact-submissions.show');
        Route::get('newsletter-subscribers', [\App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('newsletter.index');
        Route::get('analytics/sales', [\App\Http\Controllers\Admin\AnalyticsController::class, 'salesReport'])->name('analytics.sales');
        Route::get('analytics/seller-earning', [\App\Http\Controllers\Admin\AnalyticsController::class, 'sellerEarningReport'])->name('analytics.seller-earning');
        Route::get('analytics/commission', [\App\Http\Controllers\Admin\AnalyticsController::class, 'commissionReport'])->name('analytics.commission');
        Route::get('analytics/payout', [\App\Http\Controllers\Admin\AnalyticsController::class, 'payoutReport'])->name('analytics.payout');
        Route::get('analytics/refund', [\App\Http\Controllers\Admin\AnalyticsController::class, 'refundReport'])->name('analytics.refund');
        Route::get('inventory/low-stock', [\App\Http\Controllers\Admin\InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('inventory/out-of-stock', [\App\Http\Controllers\Admin\InventoryController::class, 'outOfStock'])->name('inventory.out-of-stock');
        Route::get('shipping-zones', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'index'])->name('shipping-zones.index');
        Route::get('shipping-zones/create', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'create'])->name('shipping-zones.create');
        Route::post('shipping-zones', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'store'])->name('shipping-zones.store');
        Route::get('shipping-zones/{shippingZone}/edit', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'edit'])->name('shipping-zones.edit');
        Route::put('shipping-zones/{shippingZone}', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'update'])->name('shipping-zones.update');
        Route::post('shipping-zones/{shippingZone}/rules', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'storeRule'])->name('shipping-zones.rules.store');
        Route::delete('shipping-zones/{shippingZone}/rules/{rule}', [\App\Http\Controllers\Admin\ShippingZoneController::class, 'destroyRule'])->name('shipping-zones.rules.destroy');
        Route::get('notifications/counts', [\App\Http\Controllers\Admin\NotificationController::class, 'counts'])->name('notifications.counts');
        Route::post('notifications/mark-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('notifications.mark-read');
    });
});

// Social auth redirect (OAuth flow - Google redirects here)
Route::get('/auth/{provider}/redirect', function (string $provider) {
    if (!in_array($provider, ['google', 'facebook'])) {
        abort(400, 'Invalid provider');
    }
    return Socialite::driver($provider)->stateless()->redirect();
})->name('auth.social.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callbackWeb'])->name('auth.social.callback');
