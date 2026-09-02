<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Product;
use App\Models\User;
use App\Services\ConversationService;
use App\Support\UploadHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversationIds = ConversationService::scopeForUser(Conversation::query(), $user)
            ->pluck('id');

        $visibleIds = Conversation::whereIn('id', $conversationIds)
            ->with(['product' => fn ($q) => $q->withTrashed()])
            ->get()
            ->filter(fn ($c) => ! ConversationService::shouldHideForListingOwner($user, $c))
            ->pluck('id');

        $count = Message::whereIn('conversation_id', $visibleIds)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['success' => true, 'unread_count' => $count]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = ConversationService::scopeForUser(
            Conversation::with(['user:id,name', 'seller:id,name', 'product' => fn ($q) => $q->withTrashed()])
                ->withCount('messages'),
            $user
        )
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(30);

        $items = $conversations->getCollection()
            ->filter(fn ($c) => ! ConversationService::shouldHideForListingOwner($user, $c))
            ->map(fn ($c) => $this->serializeConversationListItem($c, $user))
            ->values();

        $conversations->setCollection($items);

        return response()->json([
            'success' => true,
            'conversations' => $conversations->items(),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
            ],
            'safety_notice' => ConversationService::SAFETY_NOTICE,
        ]);
    }

    /**
     * Start or open a product conversation. Auto-sends product preview on first create.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'message' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();
        $product = Product::with(['store.seller'])->findOrFail((int) $request->product_id);
        $sellerId = ConversationService::sellerUserIdForProduct($product);
        if (! $sellerId) {
            return response()->json(['success' => false, 'message' => 'Seller not found for this product.'], 422);
        }
        if ($sellerId === (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot message your own listing.'], 422);
        }

        $seller = ConversationService::validateSellerForProduct($sellerId, $product);
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Invalid seller for this product.'], 422);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $user->id,
                'seller_id' => $seller->id,
                'product_id' => $product->id,
            ],
            ['last_message_at' => now()]
        );

        $created = $conversation->wasRecentlyCreated;
        if ($created) {
            $this->createProductPreviewMessage($conversation, $user, $product);
        }

        if ($request->filled('message')) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'type' => 'text',
                'body' => trim((string) $request->message),
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'conversation' => ['id' => $conversation->id],
        ], $created ? 201 : 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'seller_id' => 'required|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'message' => 'nullable|string|max:5000',
        ]);

        if ($request->filled('product_id')) {
            $request->merge(['message' => $request->input('message')]);

            return $this->start($request);
        }

        $user = $request->user();
        $seller = ConversationService::validateSellerForProduct((int) $request->seller_id);
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Invalid seller'], 422);
        }
        if ((int) $seller->id === (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot message yourself.'], 422);
        }

        if (! $request->filled('message')) {
            return response()->json(['success' => false, 'message' => 'Message is required.'], 422);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $user->id,
                'seller_id' => $seller->id,
                'product_id' => null,
            ],
            ['last_message_at' => now()]
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => 'text',
            'body' => trim((string) $request->message),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'conversation' => ['id' => $conversation->id],
            'message' => ConversationService::formatMessage($message->load('user'), $user->id),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::with([
            'user:id,name',
            'seller:id,name',
            'product' => fn ($q) => $q->withTrashed(),
        ])->findOrFail($id);

        if (! ConversationService::isParticipant($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if (ConversationService::shouldHideForListingOwner($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Conversation not available.'], 404);
        }

        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with(['user:id,name', 'attachments'])
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        $other = (int) $conversation->user_id === (int) $user->id
            ? $conversation->seller
            : $conversation->user;

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'other_user' => $other ? ['id' => $other->id, 'name' => $other->name] : null,
                'product' => ConversationService::formatProductForList($conversation->product),
            ],
            'messages' => $messages->map(fn ($m) => ConversationService::formatMessage($m, $user->id))->values(),
            'safety_notice' => ConversationService::SAFETY_NOTICE,
        ]);
    }

    public function report(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $conversation = Conversation::findOrFail($id);
        $user = $request->user();
        if (! ConversationService::isParticipant($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        \App\Models\ConversationReport::updateOrCreate(
            ['conversation_id' => $id, 'user_id' => $user->id],
            ['reason' => $request->reason, 'notes' => $request->notes ?? null, 'status' => 'pending']
        );

        return response()->json(['success' => true, 'message' => 'Conversation reported']);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if (! $request->filled('body') && ! $request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Send a text message or an image.',
            ], 422);
        }

        $user = $request->user();
        $conversation = Conversation::with(['product' => fn ($q) => $q->withTrashed()])->findOrFail($id);

        if (! ConversationService::isParticipant($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if (ConversationService::shouldHideForListingOwner($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Conversation not available.'], 404);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => $request->hasFile('image') ? 'image' : 'text',
            'body' => $request->filled('body') ? trim((string) $request->body) : '',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = UploadHelper::storePublic($file, 'messages/'.$conversation->id);
            MessageAttachment::create([
                'message_id' => $message->id,
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $conversation->update(['last_message_at' => now()]);

        $message->load(['user:id,name', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => ConversationService::formatMessage($message, $user->id),
        ], 201);
    }

    private function serializeConversationListItem(Conversation $c, User $viewer): array
    {
        $other = (int) $c->user_id === (int) $viewer->id ? $c->seller : $c->user;
        $last = $c->messages()->with('attachments')->first();
        $lastPreview = null;
        if ($last) {
            if ($last->type === 'product_preview') {
                $lastPreview = 'Product inquiry';
            } elseif ($last->type === 'image' || $last->attachments->isNotEmpty()) {
                $lastPreview = 'Photo';
            } else {
                $lastPreview = \Str::limit((string) $last->body, 50);
            }
        }

        return [
            'id' => $c->id,
            'other_user' => $other ? ['id' => $other->id, 'name' => $other->name] : null,
            'product' => ConversationService::formatProductForList($c->product),
            'last_message' => $last ? [
                'body' => $lastPreview,
                'type' => $last->type ?? 'text',
                'created_at' => $last->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => $c->messages()->where('user_id', '!=', $viewer->id)->whereNull('read_at')->count(),
        ];
    }

    private function createProductPreviewMessage(Conversation $conversation, User $buyer, Product $product): void
    {
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $buyer->id,
            'type' => 'product_preview',
            'body' => json_encode(ConversationService::productPreviewPayload($product)),
        ]);
        $conversation->update(['last_message_at' => now()]);
    }
}
