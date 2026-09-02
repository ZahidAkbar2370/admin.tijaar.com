<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $whatsappChannelOn = (string) \App\Models\Setting::get('notification_whatsapp_enabled', '1') === '1';

        NotificationPreference::seedDefaultsForUser((int) $user->id, $whatsappChannelOn);

        $prefs = $user->notificationPreferences()->get();
        if (! $whatsappChannelOn) {
            $prefs = $prefs->where('channel', '!=', 'whatsapp')->values();
        }

        return response()->json([
            'success' => true,
            'preferences' => $prefs,
            'whatsapp_channel_enabled' => $whatsappChannelOn,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required|string|in:email,push,push_web,push_app,sms,whatsapp',
            'type' => 'required|string|in:order,listing,message,promotion',
            'enabled' => 'required|boolean',
        ]);

        if ($request->channel === 'whatsapp'
            && (string) \App\Models\Setting::get('notification_whatsapp_enabled', '1') !== '1') {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp notifications are disabled by admin.',
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $channel = $request->channel === 'push' ? 'push_web' : $request->channel;

        $pref = NotificationPreference::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'channel' => $channel,
                'type' => $request->type,
            ],
            ['enabled' => true]
        );
        $pref->update(['enabled' => $request->enabled]);

        return response()->json(['success' => true, 'preference' => $pref->fresh()]);
    }
}
