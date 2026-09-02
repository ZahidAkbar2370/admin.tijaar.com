<?php

namespace App\Services;

use App\Http\Controllers\Api\PrivateListingController;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;

class ConversationService
{
    public const SAFETY_NOTICE =
        'For your safety, do not share personal contact details, payment links, or off-platform payment info. '
        . 'Complete all payments through Tijaar only.';

    /** Conversations where the user is buyer or listing seller. */
    public static function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('seller_id', $user->id);
        });
    }

    public static function isParticipant(User $user, Conversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $user->id
            || (int) $conversation->seller_id === (int) $user->id;
    }

    /** Resolve listing owner user id for business or private products. */
    public static function sellerUserIdForProduct(Product $product): ?int
    {
        if ($product->seller_type === 'private' && $product->seller_id) {
            return (int) $product->seller_id;
        }
        if ($product->store_id && $product->relationLoaded('store') && $product->store?->seller) {
            return (int) $product->store->seller->user_id;
        }
        if ($product->store_id) {
            $product->loadMissing('store.seller');

            return $product->store?->seller ? (int) $product->store->seller->user_id : null;
        }

        return null;
    }

    public static function validateSellerForProduct(int $sellerId, ?Product $product = null): ?User
    {
        $seller = User::find($sellerId);
        if (! $seller) {
            return null;
        }

        if ($product) {
            $ownerId = self::sellerUserIdForProduct($product);
            if ($ownerId !== (int) $sellerId) {
                return null;
            }
            if ($product->seller_type === 'private') {
                return $seller->role === 'customer' ? $seller : null;
            }

            return $seller->role === 'seller' ? $seller : null;
        }

        return $seller->role === 'seller' || ($seller->role === 'customer' && ($seller->is_private_seller ?? false))
            ? $seller
            : null;
    }

    public static function productPreviewPayload(Product $product): array
    {
        $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $slug = $product->slug ?: $product->id;

        return [
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $slug,
            'price' => $product->price !== null ? (float) $product->price : null,
            'image_url' => $product->getMainImageUrl(),
            'url' => "{$frontend}/product/{$slug}",
        ];
    }

    /**
     * Hide from casual listing owner when linked product is no longer active.
     * Approved private sellers always keep their chats visible.
     */
    public static function shouldHideForListingOwner(User $listingOwner, Conversation $conversation): bool
    {
        // Business store sellers always keep chats visible.
        if (($listingOwner->role ?? '') === 'seller') {
            return false;
        }

        // Approved private sellers keep chats even if product is sold/removed.
        if ((bool) ($listingOwner->is_private_seller ?? false)) {
            return false;
        }

        if ((int) $conversation->seller_id !== (int) $listingOwner->id) {
            return false;
        }

        if (! $conversation->product_id) {
            return false;
        }

        $product = Product::withTrashed()->find($conversation->product_id);
        if (! $product) {
            return true;
        }

        if ($product->trashed() || $product->status === 'removed') {
            return true;
        }

        $display = PrivateListingController::displayStatusForListing($product);
        if (in_array($display, ['sold', 'expired', 'removed', 'draft'], true)) {
            return true;
        }

        if ($product->status !== 'published') {
            return true;
        }

        return false;
    }

    public static function formatProductForList(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) ($product->slug ?: $product->id),
            'price' => $product->price !== null ? (float) $product->price : null,
            'image_url' => $product->getMainImageUrl(),
        ];
    }

    public static function formatMessage($message, int $viewerId): array
    {
        $attachments = $message->relationLoaded('attachments')
            ? $message->attachments
            : $message->attachments()->get();

        $payload = [
            'id' => $message->id,
            'type' => $message->type ?? 'text',
            'body' => $message->body,
            'is_mine' => (int) $message->user_id === $viewerId,
            'user' => $message->user?->name,
            'created_at' => $message->created_at?->toIso8601String(),
            'attachments' => $attachments->map(fn ($a) => [
                'id' => $a->id,
                'url' => \App\Support\UploadHelper::url($a->path),
                'name' => $a->name,
                'mime_type' => $a->mime_type,
            ])->values()->all(),
        ];

        if (($message->type ?? 'text') === 'product_preview') {
            $decoded = json_decode($message->body ?? '', true);
            $payload['product_preview'] = is_array($decoded) ? $decoded : null;
        }

        return $payload;
    }
}
