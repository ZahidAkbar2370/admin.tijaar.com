<?php

namespace App\Services;

use App\Models\User;

class StoreProfileSync
{
    /**
     * Copy seller account contact + location from user profile onto the store record.
     */
    public static function syncFromUser(User $user): void
    {
        $store = $user->seller?->store;
        if (! $store) {
            return;
        }

        $store->update([
            'phone' => $user->phone,
            'email' => $user->email,
            'city' => $user->city,
            'state' => $user->state,
            'country' => LocationService::defaultCountryName(),
        ]);
    }
}
