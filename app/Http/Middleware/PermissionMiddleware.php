<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Route name / prefix to permission slug mapping.
     */
    protected static array $routeToPermission = [
        'admin.users' => 'users.manage',
        'admin.sellers' => 'sellers.manage',
        'admin.products' => 'products.manage',
        'admin.orders' => 'orders.manage',
        'admin.transactions' => 'orders.manage',
        'admin.track-orders' => 'orders.manage',
        'admin.pages' => 'cms.manage',
        'admin.banners' => 'cms.manage',
        'admin.faqs' => 'cms.manage',
        'admin.blogs' => 'cms.manage',
        'admin.settings' => 'settings.manage',
        'admin.sitemap' => 'settings.manage',
        'admin.refunds' => 'orders.manage',
        'admin.payouts' => 'orders.manage',
        'admin.roles' => 'settings.manage',
        'admin.sub-admins' => 'settings.manage',
        'admin.private-sellers' => 'sellers.manage',
        'admin.abuse-safety' => 'settings.manage',
        'admin.commissions' => 'settings.manage',
        'admin.commission-settings' => 'settings.manage',
        'admin.marketplace-fees' => 'settings.manage',
        'admin.courier' => 'settings.manage',
        'admin.payment-methods' => 'settings.manage',
        'admin.customer-settings' => 'settings.manage',
        'admin.customer-sellers' => 'users.manage',
        'admin.people-settings' => 'settings.manage',
        'admin.private-seller-settings' => 'settings.manage',
        'admin.seller-settings' => 'settings.manage',
        'admin.recaptcha-settings' => 'settings.manage',
        'admin.wachat-settings' => 'settings.manage',
        'admin.activities' => 'settings.manage',
        'admin.expenses' => 'settings.manage',
        'admin.coupons' => 'products.manage',
        'admin.promotion-packages' => 'products.manage',
        'admin.reviews' => 'products.manage',
        'admin.conversations' => 'sellers.manage',
        'admin.disputes' => 'orders.manage',
        'admin.categories' => 'products.manage',
        'admin.brands' => 'products.manage',
        'admin.shipping-zones' => 'settings.manage',
        'admin.inventory' => 'products.manage',
        'admin.analytics' => 'settings.manage',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('admin.login');
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        if ($user->role !== 'sub_admin') {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        $user->load('roles.permissions');
        $allowedSlugs = $user->roles->flatMap->permissions->pluck('slug')->unique()->values();
        foreach (self::$routeToPermission as $prefix => $permissionSlug) {
            if (str_starts_with($routeName, $prefix)) {
                if (!$allowedSlugs->contains($permissionSlug)) {
                    abort(403, 'You do not have permission to access this section.');
                }
                break;
            }
        }

        return $next($request);
    }
}
