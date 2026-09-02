<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationRead;
use App\Models\User;
use App\Services\Admin\UserSegmentService;
use App\Support\PhoneHelper;
use App\Support\RegistrationSource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        AdminNotificationRead::markRead(auth()->id(), 'new_customers');

        $query = UserSegmentService::customersQuery()
            ->withCount([
                'orders as orders_count',
                'products as private_listings_count' => fn ($q) => $q->where('seller_type', 'private'),
            ])
            ->orderBy('created_at', 'desc');

        UserSegmentService::applySearch($query, $request->input('search'));
        UserSegmentService::applyStatus($query, $request->input('status'));

        if ($request->filled('source') && in_array($request->source, RegistrationSource::VALUES, true)) {
            $query->where('registration_source', $request->source);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'email_verified' => 'nullable|boolean',
        ]);

        $phone = null;
        if ($request->filled('phone')) {
            $phone = PhoneHelper::normalize($request->phone);
            if ($phone === null) {
                return back()->withInput()->with('error', 'Phone must be a valid Pakistani mobile (03XXXXXXXXX).');
            }
            if (User::where('phone', $phone)->exists()) {
                return back()->withInput()->with('error', 'This mobile number is already registered.');
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $phone,
            'role' => 'customer',
            'registration_source' => RegistrationSource::WEB,
            'email_verified_at' => $request->boolean('email_verified') ? now() : null,
        ]);

        return redirect()->route('admin.users.show', $user)->with('success', 'Customer created successfully.');
    }

    public function export(Request $request)
    {
        $query = UserSegmentService::customersQuery()
            ->withCount([
                'orders as orders_count',
                'products as private_listings_count' => fn ($q) => $q->where('seller_type', 'private'),
            ])
            ->orderBy('created_at', 'desc');

        UserSegmentService::applySearch($query, $request->input('search'));
        UserSegmentService::applyStatus($query, $request->input('status'));

        if ($request->filled('source') && in_array($request->source, RegistrationSource::VALUES, true)) {
            $query->where('registration_source', $request->source);
        }

        $users = $query->get();

        $headers = ['ID', 'Name', 'Email', 'Phone', 'Registered Via', 'Orders (Buyer)', 'Listings (Seller)', 'Status', 'Joined'];
        $rows = [implode(',', $headers)];

        foreach ($users as $u) {
            $status = $u->is_banned ? 'Banned' : ($u->is_suspended ? 'Suspended' : 'Active');
            $joined = $u->created_at ? $u->created_at->copy()->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d g:i A') : '';
            $rows[] = implode(',', [
                $u->id,
                '"' . str_replace('"', '""', $u->name) . '"',
                '"' . str_replace('"', '""', $u->email) . '"',
                '"' . str_replace('"', '""', $u->phone ?? '') . '"',
                RegistrationSource::label($u->registration_source),
                $u->orders_count ?? 0,
                $u->private_listings_count ?? 0,
                $status,
                '"' . str_replace('"', '""', $joined) . '"',
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customers-' . date('Y-m-d') . '.csv"',
        ]);
    }

    public function suspend(User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Cannot suspend admin.');
        }
        $user->update(['is_suspended' => true]);
        return redirect()->route('admin.users.account-actions', $user)->with('success', 'User suspended.');
    }

    public function unsuspend(User $user)
    {
        $user->update(['is_suspended' => false]);
        return redirect()->route('admin.users.account-actions', $user)->with('success', 'User unsuspended.');
    }

    public function ban(User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Cannot ban admin.');
        }
        $user->update(['is_banned' => true]);
        $user->tokens()->delete();
        return redirect()->route('admin.users.account-actions', $user)->with('success', 'User banned.');
    }

    public function unban(User $user)
    {
        $user->update(['is_banned' => false]);
        return redirect()->route('admin.users.account-actions', $user)->with('success', 'User unbanned.');
    }

    public function storeAddress(Request $request, User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Not a customer.');
        }

        $request->validate([
            'type' => 'required|in:billing,shipping',
            'label' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'nullable|string|max:120',
            'country' => 'required|string|max:120',
            'zip_code' => 'nullable|string|max:30',
            'is_default' => 'nullable|boolean',
        ]);

        $phone = PhoneHelper::normalize($request->phone);
        if ($phone === null) {
            $phone = preg_replace('/\D+/', '', (string) $request->phone) ?: null;
        }

        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create(\App\Services\LocationService::ensureCountry([
            'type' => $request->type,
            'label' => $request->input('label'),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->city,
            'state' => $request->input('state'),
            'country' => $request->country,
            'zip_code' => $request->input('zip_code'),
            'is_default' => $request->boolean('is_default'),
        ]));

        return redirect()->route('admin.users.addresses', $user)->with('success', 'Address added.');
    }

    public function updateAddress(Request $request, User $user, \App\Models\Address $address)
    {
        if ($user->role !== 'customer' || (int) $address->user_id !== (int) $user->id) {
            return back()->with('error', 'Address not found for this customer.');
        }

        $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'nullable|string|max:120',
            'country' => 'nullable|string|max:120',
            'zip_code' => 'nullable|string|max:30',
        ]);

        $phone = $request->filled('phone') ? PhoneHelper::normalize($request->phone) : $address->phone;
        if ($request->filled('phone') && $phone === null) {
            // Allow non-PK address phones as stored digits for delivery contact
            $phone = preg_replace('/\D+/', '', (string) $request->phone) ?: null;
        }

        $address->update(\App\Services\LocationService::withDefaultCountry([
            'first_name' => $request->input('first_name', $address->first_name),
            'last_name' => $request->input('last_name', $address->last_name),
            'phone' => $phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->city,
            'state' => $request->input('state'),
            'country' => $request->input('country', $address->country),
            'zip_code' => $request->input('zip_code'),
        ]));

        return redirect()->route('admin.users.addresses', $user)->with('success', 'Address updated.');
    }

    public function updateNotifications(Request $request, User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Not a customer.');
        }

        $prefs = $request->input('prefs', []);
        if (! is_array($prefs)) {
            return back()->with('error', 'Invalid preferences.');
        }

        $whatsappChannelOn = (string) \App\Models\Setting::get('notification_whatsapp_enabled', '1') === '1';

        foreach ($prefs as $key => $enabled) {
            [$channel, $type] = array_pad(explode('|', (string) $key, 2), 2, null);
            if (! in_array($channel, ['email', 'whatsapp', 'push', 'push_web', 'push_app', 'sms'], true)) {
                continue;
            }
            if ($channel === 'push') {
                $channel = 'push_web';
            }
            if (! in_array($type, ['order', 'listing', 'message', 'promotion'], true)) {
                continue;
            }
            if ($channel === 'whatsapp' && ! $whatsappChannelOn) {
                continue;
            }
            \App\Models\NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'channel' => $channel, 'type' => $type],
                ['enabled' => (string) $enabled === '1' || $enabled === 1 || $enabled === true]
            );
        }

        return redirect()->route('admin.users.alerts', $user)->with('success', 'Notification preferences updated.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Not a customer.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'whatsapp_number' => 'nullable|string|max:30',
            'email_verified' => 'nullable|boolean',
            'whatsapp_verified' => 'nullable|boolean',
        ]);

        $phone = null;
        if ($request->filled('phone')) {
            $phone = PhoneHelper::normalize($request->phone);
            if ($phone === null) {
                return back()->withInput()->with('error', 'Phone must be a valid Pakistani mobile (03XXXXXXXXX).');
            }
            if (User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
                return back()->withInput()->with('error', 'This mobile number is already used by another account.');
            }
        }

        $whatsapp = null;
        if ($request->filled('whatsapp_number')) {
            $whatsapp = PhoneHelper::normalize($request->whatsapp_number);
            if ($whatsapp === null) {
                return back()->withInput()->with('error', 'WhatsApp number must be a valid Pakistani mobile (03XXXXXXXXX).');
            }
            if (User::where(function ($q) use ($whatsapp) {
                $q->where('phone', $whatsapp)->orWhere('whatsapp_number', $whatsapp);
            })->where('id', '!=', $user->id)->exists()) {
                return back()->withInput()->with('error', 'This WhatsApp number is already used by another account.');
            }
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $phone,
            'whatsapp_number' => $whatsapp,
        ];

        if ($request->boolean('email_verified')) {
            $data['email_verified_at'] = $user->email_verified_at ?? now();
        } else {
            $data['email_verified_at'] = null;
        }

        $whatsappChanged = $user->whatsapp_number !== $whatsapp;
        if ($whatsapp && $request->boolean('whatsapp_verified')) {
            $data['whatsapp_verified_at'] = $whatsappChanged ? now() : ($user->whatsapp_verified_at ?? now());
        } else {
            $data['whatsapp_verified_at'] = null;
        }

        $user->update($data);

        return redirect()->route('admin.users.profile', $user)->with('success', 'Customer details updated.');
    }

    public function updateListingLimit(Request $request, User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Not a customer.');
        }
        $request->validate([
            'private_listing_limit' => 'nullable|integer|min:1|max:100',
            'use_global_limit' => 'nullable|boolean',
            'payout_hold_days' => 'nullable|integer|min:0|max:90',
            'clear_payout_hold_days' => 'nullable|boolean',
        ]);

        if ($request->boolean('clear_payout_hold_days')) {
            $user->payout_hold_days = null;
        } elseif ($request->filled('payout_hold_days')) {
            $user->payout_hold_days = (int) $request->payout_hold_days;
        }

        if ($request->boolean('use_global_limit') || !$request->filled('private_listing_limit')) {
            $user->private_listing_limit = null;
            $user->save();
            return redirect()->route('admin.users.free-listing', $user)->with('success', 'Customer now uses the free listing limit only (no plan). Payout hold updated if provided.');
        }

        $user->private_listing_limit = (int) $request->private_listing_limit;
        $user->save();
        return redirect()->route('admin.users.free-listing', $user)->with('success', 'Plan / custom listing limit updated (allows more than the free tier).');
    }
}
